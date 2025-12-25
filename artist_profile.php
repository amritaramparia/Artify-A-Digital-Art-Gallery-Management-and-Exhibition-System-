<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_result = $conn->query("SELECT * FROM register_data WHERE id = $user_id");
$user_data = $user_result->fetch_assoc();

// Check if user is an approved artist
$is_approved_artist = false;
$artist_data = null;

$artist_check = $conn->query("SELECT * FROM artists WHERE user_id = $user_id");
if ($artist_check && $artist_check->num_rows > 0) {
    $artist_data = $artist_check->fetch_assoc();
    if ($artist_data['approval_status'] === 'approved') {
        $is_approved_artist = true;
    }
}

// Redirect to artist registration if not an approved artist
if (!$is_approved_artist) {
    header("Location: artist_registration.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Profile | Artify</title>
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
            max-width: 1200px;
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
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .profile-sidebar {
            background: #f9f9f9;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 5px solid var(--orange2);
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
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 30px;
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
        }
        
        .btn-artist:hover {
            background: #ffa94d;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 183, 102, 0.5);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        @media (max-width: 992px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .header {
                padding: 25px;
            }
            
            .profile-content {
                padding: 25px;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Artist Profile</h1>
            <p>Welcome to your dedicated artist space</p>
        </div>
        
        <div class="profile-content">
            <div class="profile-sidebar">
                <img src="images/artist_profiles" alt="Artist Profile" class="profile-image">
                <h2 class="artist-name"><?php echo htmlspecialchars($artist_data['artist_name']); ?></h2>
                <p class="artist-style"><?php echo htmlspecialchars($artist_data['art_style']); ?> Artist</p>
                
                <div class="artist-stats">
                    <div class="stat">
                        <div class="stat-number">12</div>
                        <div class="stat-label">Artworks</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">5</div>
                        <div class="stat-label">Sales</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">4.8</div>
                        <div class="stat-label">Rating</div>
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
                
                <div class="action-buttons">
                    <a href="upload_artwork.php" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Artwork
                    </a>
                    
                    <a href="my_artworks.php" class="btn btn-artist">
                        <i class="fas fa-paint-brush"></i> View My Artworks
                    </a>
                    
                    <a href="auctions.php" class="btn btn-secondary">
                        <i class="fas fa-gavel"></i> Join Auctions
                    </a>
                    
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php
    $conn->close();
    ?>
</body>
</html>