<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'art';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get artist ID from URL
$artist_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get artist details
$artist_query = "SELECT * FROM artists WHERE artist_id = ? AND approval_status = 'approved'";
$stmt = mysqli_prepare($conn, $artist_query);
mysqli_stmt_bind_param($stmt, 'i', $artist_id);
mysqli_stmt_execute($stmt);
$artist_result = mysqli_stmt_get_result($stmt);
$artist = mysqli_fetch_assoc($artist_result);

if (!$artist) {
    header('Location: artists.php');
    exit();
}

// Get artist's artworks
$artworks_query = "SELECT * FROM artworks WHERE artist_id = ? ORDER BY created_at DESC";
$stmt2 = mysqli_prepare($conn, $artworks_query);
mysqli_stmt_bind_param($stmt2, 'i', $artist_id);
mysqli_stmt_execute($stmt2);
$artworks_result = mysqli_stmt_get_result($stmt2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($artist['artist_name']); ?> | Art Gallery</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--light) 0%, #e8f4f1 100%);
            color: var(--dark);
            padding: 20px;
            min-height: 100vh;
        }

        .artist-details-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            position: relative;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            color: var(--dark);
            position: relative;
            text-shadow: 2px 2px 4px rgba(44, 44, 84, 0.1);
            letter-spacing: -1px;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 120px;
            height: 6px;
            background: linear-gradient(90deg, var(--orange1), var(--orange2));
            border-radius: 3px;
            box-shadow: 0 4px 8px rgba(244, 58, 9, 0.3);
        }

        .back-btn {
            background: linear-gradient(135deg, var(--orange1), var(--orange2));
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.4s ease;
            box-shadow: 0 6px 15px rgba(244, 58, 9, 0.3);
            transform: perspective(500px) rotateY(-5deg);
        }

        .back-btn:hover {
            transform: perspective(500px) rotateY(0deg) translateY(-3px);
            box-shadow: 0 10px 20px rgba(244, 58, 9, 0.4);
        }

        .artist-profile {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 15px 35px rgba(44, 44, 84, 0.1);
            display: flex;
            gap: 40px;
            align-items: flex-start;
            transform-style: preserve-3d;
            transform: perspective(1000px) rotateX(0deg) rotateY(0deg);
        }

        .artist-image-container {
            flex-shrink: 0;
            transform: translateZ(20px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .artist-profile-image {
            max-width: 350px;
            max-height: 350px;
            width: 100%;
            height: auto;
            border-radius: 20px;
            object-fit: contain;
            background: linear-gradient(135deg, var(--bluegreen), var(--green));
            box-shadow: 0 15px 30px rgba(44, 44, 84, 0.2);
            transition: all 0.4s ease;
            padding: 10px;
        }

        .artist-profile-image:hover {
            transform: scale(1.02) translateZ(30px);
            box-shadow: 0 20px 40px rgba(44, 44, 84, 0.3);
        }

        .artist-details {
            flex: 1;
            transform: translateZ(10px);
            min-width: 0; /* Prevents flex item from overflowing */
        }

        .artist-name {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 15px;
            line-height: 1.2;
            word-wrap: break-word;
        }

        .artist-style {
            font-size: 1.4rem;
            color: var(--orange1);
            margin-bottom: 25px;
            font-weight: 600;
            font-style: italic;
        }

        .artist-bio {
            font-size: 1.1rem;
            line-height: 1.7;
            color: var(--dark);
            margin-bottom: 30px;
            opacity: 0.8;
        }

        .artist-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--light), white);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(44, 44, 84, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.8);
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(44, 44, 84, 0.15);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--dark);
            opacity: 0.7;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            word-wrap: break-word;
            word-break: break-all;
            overflow-wrap: break-word;
            line-height: 1.4;
        }

        .email-value {
            font-size: 1rem;
            word-break: break-word;
            overflow-wrap: anywhere;
            hyphens: auto;
        }

        .experience-level {
            background: linear-gradient(135deg, var(--green), var(--bluegreen));
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(104, 211, 136, 0.3);
        }

        .portfolio-link {
            background: linear-gradient(135deg, var(--dark), #3a3a70);
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.4s ease;
            box-shadow: 0 8px 20px rgba(44, 44, 84, 0.2);
            margin-top: 20px;
        }

        .portfolio-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(44, 44, 84, 0.3);
        }

        /* Artworks Section */
        .artworks-section {
            margin-top: 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100px;
            height: 6px;
            background: linear-gradient(90deg, var(--orange1), var(--orange2));
            border-radius: 3px;
        }

        .artworks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 35px;
            padding: 20px 0;
        }

        .artwork-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.15);
            transition: all 0.4s ease;
            position: relative;
            transform-style: preserve-3d;
            transform: perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1);
        }

        .artwork-card:hover {
            transform: perspective(1000px) rotateX(5deg) rotateY(-5deg) scale(1.03);
            box-shadow: 0 20px 40px rgba(44, 44, 84, 0.25);
        }

        .artwork-image-container {
            width: 100%;
            height: 250px;
            overflow: hidden;
            position: relative;
        }

        .artwork-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: linear-gradient(135deg, var(--bluegreen), var(--green));
            transition: transform 0.5s ease;
            padding: 10px;
        }

        .artwork-card:hover .artwork-image {
            transform: scale(1.05);
        }

        .artwork-info {
            padding: 25px;
            position: relative;
            transform: translateZ(10px);
        }

        .artwork-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .artwork-price {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--orange1);
            margin-bottom: 15px;
        }

        .artwork-description {
            font-size: 0.95rem;
            color: var(--dark);
            opacity: 0.7;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .view-artwork-btn {
            background: linear-gradient(135deg, var(--orange1), var(--orange2));
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.4s ease;
            box-shadow: 0 6px 15px rgba(244, 58, 9, 0.3);
            letter-spacing: 0.5px;
        }

        .view-artwork-btn:hover {
            background: linear-gradient(135deg, var(--dark), #3a3a70);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.3);
        }

        .no-artworks {
            text-align: center;
            grid-column: 1 / -1;
            padding: 60px;
            color: var(--dark);
            font-size: 1.4rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.1);
        }

        .image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--bluegreen), var(--green));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            text-align: center;
            padding: 20px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .image-placeholder small {
            font-size: 0.8rem;
            margin-top: 10px;
            opacity: 0.7;
        }

        @media (max-width: 1100px) {
            .artworks-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 25px;
            }
            
            .artist-profile-image {
                max-width: 300px;
                max-height: 300px;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 25px;
                text-align: center;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
            
            .page-title::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .artist-profile {
                flex-direction: column;
                text-align: center;
                padding: 30px 20px;
                gap: 30px;
            }
            
            .artist-profile-image {
                max-width: 250px;
                max-height: 250px;
                margin: 0 auto;
            }
            
            .artist-stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .stat-value {
                font-size: 1rem;
            }
            
            .email-value {
                font-size: 0.9rem;
            }
            
            .artworks-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 20px;
            }
            
            .artist-details-container {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .artworks-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .artist-name {
                font-size: 2.2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .artist-stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                min-height: 80px;
                padding: 15px;
            }
            
            .artist-profile-image {
                max-width: 200px;
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="artist-details-container">
        <div class="page-header">
            <h1 class="page-title">Artist Profile</h1>
        </div>

        <a href="artists.php" class="back-btn">← Back to Artists</a>
        
        <div class="artist-profile">
            <div class="artist-image-container">
                <?php if ($artist['profile_pic']): ?>
                    <img src="<?php echo $artist['profile_pic']; ?>" 
                         alt="<?php echo htmlspecialchars($artist['artist_name']); ?>" 
                         class="artist-profile-image"
                         onerror="this.src='images/default-artist.jpg'">
                <?php else: ?>
                    <div class="artist-profile-image image-placeholder">
                        <?php echo htmlspecialchars($artist['artist_name']); ?>
                        <small>Artist Profile</small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="artist-details">
                <h1 class="artist-name"><?php echo htmlspecialchars($artist['artist_name']); ?></h1>
                <p class="artist-style"><?php echo htmlspecialchars($artist['art_style'] ?: 'Various Styles'); ?></p>
                
                <div class="artist-bio">
                    <?php echo nl2br(htmlspecialchars($artist['artist_bio'] ?: 'No biography available.')); ?>
                </div>
                
                <div class="experience-level">
                    <?php echo ucfirst($artist['experience_level']); ?> Level
                </div>
                
                <div class="artist-stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Email</div>
                        <div class="stat-value email-value"><?php echo htmlspecialchars($artist['email']); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Joined</div>
                        <div class="stat-value"><?php echo date('F j, Y', strtotime($artist['created_at'])); ?></div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-label">Status</div>
                        <div class="stat-value" style="color: var(--green);">Verified</div>
                    </div>
                </div>
                
                <?php if ($artist['portfolio_link']): ?>
                    <a href="<?php echo htmlspecialchars($artist['portfolio_link']); ?>" 
                       target="_blank" 
                       class="portfolio-link">
                       🌐 View Portfolio
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="artworks-section">
            <div class="section-header">
                <h2 class="section-title">Artworks Collection</h2>
            </div>
            
            <?php if (mysqli_num_rows($artworks_result) > 0): ?>
                <div class="artworks-grid">
                    <?php while ($artwork = mysqli_fetch_assoc($artworks_result)): ?>
                        <div class="artwork-card">
                            <div class="artwork-image-container">
                                <?php if ($artwork['image_url']): ?>
                                    <img src="<?php echo $artwork['image_url']; ?>" 
                                         alt="<?php echo htmlspecialchars($artwork['title']); ?>" 
                                         class="artwork-image"
                                         onerror="this.src='images/default-artwork.jpg'">
                                <?php else: ?>
                                    <div class="artwork-image image-placeholder">
                                        <?php echo htmlspecialchars($artwork['title']); ?>
                                        <small>Artwork Image</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="artwork-info">
                                <h3 class="artwork-title"><?php echo htmlspecialchars($artwork['title']); ?></h3>
                                <div class="artwork-price">$<?php echo number_format($artwork['price'], 2); ?></div>
                                <p class="artwork-description">
                                    <?php echo htmlspecialchars($artwork['description'] ?: 'No description available.'); ?>
                                </p>
                                <button class="view-artwork-btn" onclick="viewArtwork(<?php echo $artwork['artwork_id']; ?>)">
                                    VIEW ARTWORK
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-artworks">
                    <h3>No Artworks Available</h3>
                    <p>This artist hasn't uploaded any artworks yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include('footer.html'); ?>
    
    <script>
        function viewArtwork(artworkId) {
            window.location.href = 'artwork-details.php?id=' + artworkId;
        }
    </script>
</body>
</html>

<?php 
// Close statements and connection
mysqli_stmt_close($stmt);
mysqli_stmt_close($stmt2);
mysqli_close($conn); 
?>