<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: registration.php");
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'art';
$username = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';
$is_artist = false;
$artist_data = null;
$user_data = null;
$can_upload_profile = true; // Flag to check if artist can upload profile picture

$user_id = $_SESSION['user_id'];
$user_result = $conn->query("SELECT * FROM register_data WHERE id = $user_id");
if ($user_result && $user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
} else {
    // User not found, redirect to login
    header("Location: registration.php");
    exit();
}

$artist_check = $conn->query("SELECT * FROM artists WHERE user_id = $user_id");
if ($artist_check && $artist_check->num_rows > 0) {
    $artist_data = $artist_check->fetch_assoc();
    if ($artist_data['approval_status'] === 'approved') {
        $is_artist = true;

        $artist_id = $artist_data['artist_id'];
        
        // Check if artist already has a profile picture
        if (!empty($artist_data['profile_pic'])) {
            $can_upload_profile = false;
        }
        
        // Total artworks count
        $artworks_count_query = "SELECT COUNT(*) as total_artworks FROM artworks WHERE artist_id = $artist_id";
        $artworks_result = $conn->query($artworks_count_query);
        $artworks_count = $artworks_result->fetch_assoc()['total_artworks'];
        
        // Sold artworks count
        $sold_count_query = "SELECT COUNT(*) as sold_artworks FROM artworks WHERE artist_id = $artist_id AND is_sold = 1";
        /* $sold_result = $conn->query($sold_count_query);
        $sold_count = $sold_result->fetch_assoc()['sold_artworks'];*/
    }
}

// Handle profile update (for both users and artists)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a profile update (not image upload)
    if (isset($_POST['first_name'])) {
        $first_name = isset($_POST['first_name']) ? $conn->real_escape_string($_POST['first_name']) : $user_data['first_name'];
        $last_name = isset($_POST['last_name']) ? $conn->real_escape_string($_POST['last_name']) : $user_data['last_name'];
        
        // Only update if names are provided and different from current
        if ($first_name !== $user_data['first_name'] || $last_name !== $user_data['last_name']) {
            $update_sql = "UPDATE register_data SET 
                          first_name = '$first_name', 
                          last_name = '$last_name'
                          WHERE id = $user_id";
            
            if ($conn->query($update_sql) === TRUE) {
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $success = "Profile updated successfully!";
                
                // Refresh user data
                $user_result = $conn->query("SELECT * FROM register_data WHERE id = $user_id");
                $user_data = $user_result->fetch_assoc();
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
        }
    }
}

// Handle profile image upload for artists (only if they don't have one already)
if ($is_artist && $can_upload_profile && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    // FIXED: Correct upload directory path
    $upload_dir = "C:/xampp/htdocs/phppro/artify/images/artist_profiles/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    $file_type = $_FILES['profile_image']['type'];
    $file_size = $_FILES['profile_image']['size'];
    $file_name = $_FILES['profile_image']['name'];

    error_log("File upload attempt: " . $file_name . ", type: " . $file_type . " Size: " . $file_size);

    if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        
        // Use correct column name - artist_id instead of id
        $filename = 'artist_' . $artist_data['artist_id'] . '_' . time() . '.' . $file_extension;
        $destination = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
            // Use relative path for web access
            $web_path = 'images/artist_profiles/' . $filename;
            
            // Update artist record with profile image path
            $image_path = $conn->real_escape_string($web_path);
            
            // Use correct column name - artist_id
            $artist_id = $artist_data['artist_id'];
            $update_image_sql = "UPDATE artists SET profile_pic = '$image_path' WHERE artist_id = $artist_id";
            
            if ($conn->query($update_image_sql) === TRUE) {
                $success = "Profile image uploaded successfully! This is your permanent profile picture and cannot be changed.";
                $can_upload_profile = false; // Disable further uploads
               
                // Refresh artist data
                $artist_check = $conn->query("SELECT * FROM artists WHERE user_id = $user_id");
                $artist_data = $artist_check->fetch_assoc();
                
                // Debug: Check what was saved
                error_log("Profile image saved to: " . $image_path);
                
                // Force page refresh to show new image
                echo '<script>setTimeout(function(){ window.location.reload(); }, 1000);</script>';
            } else {
                $error = "Error updating profile image: " . $conn->error;
            }
        } else {
            $error = "Error uploading profile image. Check directory permissions.";
            error_log("Upload failed. Directory: " . $upload_dir);
        }
    } else {
        $error = "Please upload a valid JPG, JPEG, or PNG image (max 2MB).";
    }
} elseif ($is_artist && !$can_upload_profile && isset($_FILES['profile_image'])) {
    $error = "You have already uploaded your profile picture. Profile pictures cannot be changed once uploaded.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_artist ? 'Artist Profile' : 'User Profile'; ?> | Artify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --orange1: #f43a09;
            --orange2: #ffb766;
            --bluegreen: #c2edda;
            --green: #68d388;
            --dark: #2c2c54;
            --light: #f5f5f5;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://wallpaperset.com/w/full/8/6/9/51764.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 20%, rgba(0, 0, 0, 0.8) 100%);
            z-index: -1;
        }
        
        .container {
            max-width: <?php echo $is_artist ? '1200px' : '800px'; ?>;
            width: 100%;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            background: linear-gradient(120deg, var(--orange1), var(--orange2));
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 18px;
        }
        
        .profile-content {
            padding: 40px;
            <?php if ($is_artist): ?>
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            <?php endif; ?>
        }
        
        /* Artist-specific styles */
        .profile-sidebar {
            background: #f9f9f9;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            overflow: hidden;
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--orange2);
            background-color: #f8f9fa;
            display: block;
        }
        
        .profile-image-upload {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            background: var(--orange1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 3px solid white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .profile-image-upload.disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .profile-image-upload:hover:not(.disabled) {
            background: var(--orange2);
            transform: scale(1.1);
        }
        
        .profile-image-upload i {
            color: white;
            font-size: 18px;
        }

        .profile-image-upload.disabled i {
            color: #666;
        }
        
        .image-upload-form {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            z-index: 100;
            width: 250px;
            margin-top: 10px;
        }
        
        .image-upload-form.active {
            display: block;
        }
        
        .image-upload-form input[type="file"] {
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .image-upload-form .btn {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
        }

        .upload-disabled-message {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .artist-name {
            text-align: center;
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .artist-style {
            text-align: center;
            color: var(--orange1);
            margin-bottom: 20px;
            font-style: italic;
        }
        
        .artist-stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            text-align: center;
        }
        
        .stat {
            padding: 10px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--orange1);
        }
        
        .stat-label {
            font-size: 14px;
            color: #777;
        }
        
        .artist-details {
            margin-top: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #555;
        }
        
        .profile-main {
            padding: 0 10px;
        }
        
        .section-title {
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--orange2);
        }
        
        .artist-bio {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .feature-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 40px;
            color: var(--orange1);
            margin-bottom: 15px;
        }
        
        .feature-title {
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .feature-description {
            color: #777;
            font-size: 14px;
        }
        
        /* User-specific styles */
        .welcome-message {
            text-align: center;
            margin-bottom: 30px;
            color: var(--orange1);
            font-size: 20px;
        }
        
        .profile-form {
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        input:focus, 
        textarea:focus {
            border-color: var(--orange2);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 183, 102, 0.3);
        }
        
        /* Common styles */
        .btn {
            padding: 14px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--green);
            color: white;
            box-shadow: 0 4px 10px rgba(104, 211, 136, 0.4);
        }
        
        .btn-primary:hover {
            background: #5abf78;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(104, 211, 136, 0.5);
        }
        
        .btn-secondary {
            background: var(--orange1);
            color: white;
            box-shadow: 0 4px 10px rgba(244, 58, 9, 0.4);
        }
        
        .btn-secondary:hover {
            background: #e03507;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(244, 58, 9, 0.5);
        }
        
        .btn-artist {
            background: var(--orange2);
            color: var(--dark);
            box-shadow: 0 4px 10px rgba(255, 183, 102, 0.4);
        }
        
        .btn-artist:hover {
            background: #ffa94d;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 183, 102, 0.5);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.4);
        }
        
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(220, 53, 69, 0.5);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: <?php echo $is_artist ? 'repeat(2, 1fr)' : 'repeat(auto-fit, minmax(200px, 1fr))'; ?>;
            gap: 15px;
            margin-top: 30px;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
        }
        
        .success {
            background: var(--bluegreen);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #ffcccc;
            color: #d8000c;
            border: 1px solid #f5c6cb;
        }
        
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--orange1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 20px;
            box-shadow: 0 2px 10px rgba(244, 58, 9, 0.3);
        }

        .close-btn:hover {
            background: var(--orange2);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(244, 58, 9, 0.4);
        }
        
        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .header, .profile-content {
                padding: 25px;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .artist-stats {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="index.php" class="close-btn" title="Go to homepage">
                <i class="fas fa-times"></i>
            </a>
            <h1><?php echo $is_artist ? 'Artist Profile' : 'User Profile'; ?></h1>
            <p><?php echo $is_artist ? 'Welcome to your dedicated artist space' : 'Manage your account information'; ?></p>
        </div>
        
        <div class="profile-content">
            <?php if ($is_artist): ?>
                <!-- ARTIST PROFILE CONTENT -->
                <div class="profile-sidebar">
                     <?php if ($error): ?>
                        <div class="message error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="message success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <div class="profile-image-container">
                        <?php 
                        // FIXED: Correct image path handling
                        $profile_image_src = 'https://via.placeholder.com/150';
                        if (!empty($artist_data['profile_pic'])) {
                            // Check if it's a full URL
                            if (filter_var($artist_data['profile_pic'], FILTER_VALIDATE_URL)) {
                                $profile_image_src = $artist_data['profile_pic'];
                            } else {
                                // It's a relative path - use the correct path structure
                                $profile_image_src = $artist_data['profile_pic'];
                                
                                // Check if the file exists using the absolute server path
                                $base_path = "C:/xampp/htdocs/phppro/artify/";
                                $full_path = $base_path . ltrim($artist_data['profile_pic'], '/');
                                
                                if (file_exists($full_path)) {
                                    // File exists, use the relative path for web access
                                    $profile_image_src = $artist_data['profile_pic'];
                                } else {
                                    // If file doesn't exist, use placeholder
                                    $profile_image_src = 'https://via.placeholder.com/150?text=Image+Not+Found';
                                }
                            }
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($profile_image_src); ?>" 
                             alt="Artist Profile" class="profile-image" id="profileImage"
                             onerror="this.src='https://via.placeholder.com/150?text=Image+Error'">
                        
                        <?php if ($can_upload_profile): ?>
                            <!-- Show upload button only if no profile picture exists -->
                            <div class="profile-image-upload" id="uploadTrigger" title="Upload Profile Picture">
                                <i class="fas fa-camera"></i>
                            </div>
                            
                            <form method="POST" action="" enctype="multipart/form-data" class="image-upload-form" id="imageUploadForm">
                                <input type="file" name="profile_image" id="profileImageInput" accept="image/jpeg,image/jpg,image/png" required>
                                <button type="submit" class="btn btn-primary btn-small">
                                    <i class="fas fa-upload"></i> Upload
                                </button>
                                <button type="button" class="btn btn-secondary btn-small" id="cancelUpload">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <small style="display: block; margin-top: 5px; color: #777; text-align: center;">
                                    <i class="fas fa-exclamation-triangle"></i> This can only be set once
                                </small>
                            </form>
                        <?php else: ?>
                            <!-- Show disabled camera icon when profile picture is already uploaded -->
                            <div class="profile-image-upload disabled" title="Profile picture already set - Cannot be changed">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="upload-disabled-message">
                                <i class="fas fa-check-circle"></i> Profile picture set<br>
                                <small>Cannot be changed</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h2 class="artist-name"><?php echo htmlspecialchars($artist_data['artist_name']); ?></h2>
                    <p class="artist-style"><?php echo htmlspecialchars($artist_data['art_style']); ?> Artist</p>
                    
                    <div class="artist-stats">
                        <div class="stat">
                            <div class="stat-number"><?php echo $artworks_count; ?></div>
                            <div class="stat-label">Artworks</div>
                        </div>
                    </div>
                    
                    <div class="artist-details">
                        <div class="detail-item">
                            <div class="detail-label">Experience Level</div>
                            <div class="detail-value"><?php echo ucfirst($artist_data['experience_level']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Joined</div>
                            <div class="detail-value"><?php echo date('F Y', strtotime($artist_data['created_at'])); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($artist_data['email']); ?></div>
                        </div>
                        
                        <?php if (!empty($artist_data['portfolio_link'])): ?>
                        <div class="detail-item">
                            <div class="detail-label">Portfolio</div>
                            <div class="detail-value">
                                <a href="<?php echo htmlspecialchars($artist_data['portfolio_link']); ?>" target="_blank">
                                    View Portfolio
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-main">
                    <h2 class="section-title">About Me</h2>
                    <div class="artist-bio">
                        <p><?php echo nl2br(htmlspecialchars($artist_data['artist_bio'])); ?></p>
                    </div>
                    
                    <h2 class="section-title">Artist Features</h2>
                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-palette"></i>
                            </div>
                            <h3 class="feature-title">Sell Your Artwork</h3>
                            <p class="feature-description">List your creations for sale and reach art enthusiasts worldwide</p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <h3 class="feature-title">Live Auctions</h3>
                            <p class="feature-description">Participate in exclusive auctions and get the best value for your art</p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <h3 class="feature-title">Dedicated Profile</h3>
                            <p class="feature-description">Showcase your portfolio with a personalized artist page</p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="feature-title">Community Events</h3>
                            <p class="feature-description">Join exclusive artist gatherings and networking events</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- REGULAR USER PROFILE CONTENT -->
                <div class="welcome-message">
                    Welcome, <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>!
                </div>
                
                <?php if ($error): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="message success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="profile-form">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                        <small style="color: #777; display: block; margin-top: 5px;">Email cannot be changed</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            <?php endif; ?>
            
            <!-- ACTION BUTTONS -->
            <div class="action-buttons">
                <?php if ($is_artist): ?>
                    <!-- ARTIST ACTION BUTTONS -->
                    <a href="upload_artwork.php" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Artwork
                    </a>
                    
                <?php else: ?>
                    <!-- REGULAR USER ACTION BUTTONS -->
                    <a href="artist_registration.php" class="btn btn-secondary">
                        <i class="fas fa-paint-brush"></i> Become an Artist
                    </a>
                    
                    <a href="my_orders.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> My Orders
                    </a>
                    
                    <a href="wishlist.php" class="btn btn-artist">
                        <i class="fas fa-heart"></i> Wishlist
                    </a>
                <?php endif; ?>
                
                <a href="logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                
                <?php if (!$is_artist): ?>
                    <a href="logout.php?action=delete" class="btn btn-danger" onclick="return confirmDeleteAccount()">
                        <i class="fas fa-trash-alt"></i> Delete Account
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Profile image upload functionality - only if upload is allowed
        const uploadTrigger = document.getElementById('uploadTrigger');
        const imageUploadForm = document.getElementById('imageUploadForm');
        const cancelUpload = document.getElementById('cancelUpload');
        const profileImageInput = document.getElementById('profileImageInput');
        const profileImage = document.getElementById('profileImage');

        // Toggle upload form visibility only if upload is allowed
        if (uploadTrigger && !uploadTrigger.classList.contains('disabled')) {
            uploadTrigger.addEventListener('click', function() {
                imageUploadForm.classList.toggle('active');
            });
        }

        // Cancel upload
        if (cancelUpload) {
            cancelUpload.addEventListener('click', function() {
                imageUploadForm.classList.remove('active');
                profileImageInput.value = '';
            });
        }

        // Preview image before upload
        if (profileImageInput) {
            profileImageInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        profileImage.src = e.target.result;
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }

        // Close upload form when clicking outside
        document.addEventListener('click', function(e) {
            if (uploadTrigger && !uploadTrigger.classList.contains('disabled') && 
                !uploadTrigger.contains(e.target) && imageUploadForm && !imageUploadForm.contains(e.target)) {
                imageUploadForm.classList.remove('active');
                if (profileImageInput) profileImageInput.value = '';
            }
        });

        // Confirmation for account deletion
        const deleteBtn = document.querySelector('a[href="logout.php?action=delete"]');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function(e) {
                if (!confirm('Are you absolutely sure you want to delete your account? All your data will be permanently lost.')) {
                    e.preventDefault();
                }
            });
        }
        
        function confirmDeleteAccount() {
            return confirm('Are you absolutely sure you want to delete your account? All your data will be permanently lost and this action cannot be undone.');
        }

        // Show warning when trying to upload profile picture
        if (profileImageInput) {
            profileImageInput.addEventListener('click', function() {
                if (!this.hasAttribute('data-warning-shown')) {
                    alert('Important: You can only upload your profile picture once. This action cannot be undone.');
                    this.setAttribute('data-warning-shown', 'true');
                }
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>