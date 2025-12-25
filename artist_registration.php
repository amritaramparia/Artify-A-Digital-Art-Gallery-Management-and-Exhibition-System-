<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: registration.php");
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'art';
$username = 'root';
$password = '';

// Initialize variables
$error = '';
$success = '';
$is_artist = false;
$artist_data = [];

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is already an artist
$user_id = $_SESSION['user_id'];
$artist_check = $conn->query("SELECT * FROM artists WHERE user_id = $user_id");
if ($artist_check && $artist_check->num_rows > 0) {
    $is_artist = true;
    $artist_data = $artist_check->fetch_assoc();
}

// Get user data including email
$user_result = $conn->query("SELECT * FROM register_data WHERE id = $user_id");
$user_data = $user_result->fetch_assoc();
$user_email = $user_data['email'];

// Handle artist registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['become_artist'])) {
    $artist_name = $conn->real_escape_string($_POST['artist_name']);
    $artist_bio = $conn->real_escape_string($_POST['artist_bio']);
    $art_style = $conn->real_escape_string($_POST['art_style']);
    $experience = $conn->real_escape_string($_POST['experience']);
    $portfolio_link = $conn->real_escape_string($_POST['portfolio_link']);
    
    // Check if already an artist
    if ($is_artist) {
        $error = "You are already registered as an artist!";
    } else {
        // Insert into artists table
        $sql = "INSERT INTO artists (user_id, artist_name, artist_bio, art_style, experience_level, portfolio_link, email, approval_status) 
                VALUES ($user_id, '$artist_name', '$artist_bio', '$art_style', '$experience', '$portfolio_link', '$user_email', 'pending')";
        
        if ($conn->query($sql) === TRUE) {
            $success = "Artist application submitted successfully! It will be reviewed by our team.";
            
            // **IMPORTANT: Set $is_artist to true and fetch the new artist data**
            $is_artist = true;
            
            // Fetch the newly inserted artist data
            $artist_check = $conn->query("SELECT * FROM artists WHERE user_id = $user_id");
            if ($artist_check && $artist_check->num_rows > 0) {
                $artist_data = $artist_check->fetch_assoc();
            }
            
            // Send confirmation email (pseudo-code)
            // $to = $user_email;
            // $subject = "Artist Application Received - Artify";
            // $message = "Dear $artist_name,\n\nThank you for applying to become an artist on Artify. Your application is under review.\n\nWe'll notify you once it's approved.";
            // $headers = "From: no-reply@artify.com";
            // mail($to, $subject, $message, $headers);
            
            // **REDIRECT TO PREVENT FORM RESUBMISSION**
            header("Location: artist_registration.php");
            exit();
        } else {
            $error = "Error submitting artist application: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Registration | Artify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your CSS styles remain unchanged */
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
            display: flex;
            justify-content: center;
            align-items: center;
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
            max-width: 800px;
            width: 100%;
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
        
        .content {
            padding: 40px;
        }
        
        .welcome-message {
            text-align: center;
            margin-bottom: 30px;
            color: var(--orange1);
            font-size: 20px;
        }
        
        .artist-form {
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
        .form-group textarea,
        .form-group select {
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
        textarea:focus,
        select:focus {
            border-color: var(--orange2);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 183, 102, 0.3);
        }
        
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
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-artist:hover {
            background: #ffa94d;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 183, 102, 0.5);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
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
        
        .artist-status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
            background: #e6f7ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        .requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--orange1);
        }
        
        .requirements h3 {
            color: var(--orange1);
            margin-bottom: 10px;
        }
        
        .requirements ul {
            margin-left: 20px;
            color: #555;
        }
        
        .requirements li {
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .header, .content {
                padding: 25px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Artist Registration</h1>
            <p>Apply to become a verified artist on Artify</p>
        </div>
        
        <div class="content">
            <div class="welcome-message">
                Complete your artist profile
            </div>
            
            <div class="requirements">
                <h3>Artist Account Benefits</h3>
                <ul>
                    <li>Sell your artwork on our platform</li>
                    <li>Get a dedicated artist profile page</li>
                    <li>Receive direct inquiries from potential buyers</li>
                    <li>Join our artist community events</li>
                </ul>
            </div>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($is_artist && !empty($artist_data)): ?>
                <div class="artist-status">
                    <h3>Your Artist Application Status</h3>
                    <p><strong>Artist Name:</strong> 
                        <?php echo isset($artist_data['artist_name']) ? htmlspecialchars($artist_data['artist_name']) : 'Not Set'; ?>
                    </p>
                    <p><strong>Art Style:</strong> 
                        <?php echo isset($artist_data['art_style']) ? htmlspecialchars($artist_data['art_style']) : 'Not Set'; ?>
                    </p>
                    <p><strong>Experience Level:</strong> 
                        <?php echo isset($artist_data['experience_level']) ? ucfirst($artist_data['experience_level']) : 'Not Set'; ?>
                    </p>
                    <?php if (!empty($artist_data['portfolio_link'])): ?>
                        <p><strong>Portfolio:</strong> 
                            <a href="<?php echo htmlspecialchars($artist_data['portfolio_link']); ?>" target="_blank">
                                <?php echo htmlspecialchars($artist_data['portfolio_link']); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <p><strong>Status:</strong> 
                        <?php echo isset($artist_data['approval_status']) ? ucfirst($artist_data['approval_status']) : 'Pending'; ?>
                    </p>
                    
                    <div style="margin-top: 20px;">
                        <p>Once your application is approved, you'll be able to:</p>
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>Upload and sell your artwork</li>
                            <li>Get a dedicated artist profile page</li>
                            <li>Receive direct inquiries from potential buyers</li>
                        </ul>
                    </div>
                </div>
            <?php elseif ($is_artist): ?>
                <!-- Show a loading state or generic message -->
                <div class="artist-status">
                    <h3>Your Artist Application Status</h3>
                    <p>Your application has been submitted successfully!</p>
                    <p><strong>Status:</strong> Pending</p>
                    <div style="margin-top: 20px;">
                        <p>Once your application is approved, you'll be able to:</p>
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>Upload and sell your artwork</li>
                            <li>Get a dedicated artist profile page</li>
                            <li>Receive direct inquiries from potential buyers</li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="" class="artist-form">
                    <input type="hidden" name="become_artist" value="1">
                    
                    <div class="form-group">
                        <label for="artist_name">Artist Name *</label>
                        <input type="text" id="artist_name" name="artist_name" value="<?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>" required>
                        <small style="color: #777;">This will be your public display name as an artist</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled>
                        <small style="color: #777;">Email is taken from your registration</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="artist_bio">Artist Biography *</label>
                        <textarea id="artist_bio" name="artist_bio" placeholder="Tell us about your artistic journey, inspirations, and style" required><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="art_style">Art Style *</label>
                        <input type="text" id="art_style" name="art_style" placeholder="e.g., Abstract, Realism, Digital Art, etc." required>
                    </div>
                    
                    <div class="form-group">
                        <label for="experience">Experience Level *</label>
                        <select id="experience" name="experience" required>
                            <option value="">Select your experience level</option>
                            <option value="beginner">Beginner (1-2 years)</option>
                            <option value="intermediate">Intermediate (3-5 years)</option>
                            <option value="professional">Professional (5+ years)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="portfolio_link">Portfolio Link</label>
                        <input type="url" id="portfolio_link" name="portfolio_link" placeholder="https://yourportfolio.com">
                    </div>
                    
                    <button type="submit" class="btn btn-artist">
                        <i class="fas fa-paint-brush"></i> Submit Artist Application
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="action-buttons">
                <a href="profile.php" class="btn btn-primary">
                    <i class="fas fa-user"></i> Back to Profile
                </a>
                
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <?php
    $conn->close();
    ?>
</body>
</html>