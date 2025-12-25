<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'art';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get all approved artists with their artwork count
$query = "SELECT a.*, COUNT(aw.artwork_id) as artwork_count 
          FROM artists a 
          LEFT JOIN artworks aw ON a.artist_id = aw.artist_id 
          WHERE a.approval_status = 'approved' 
          GROUP BY a.artist_id 
          ORDER BY a.created_at DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artists Gallery | Art Gallery</title>
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

        .gallery-container {
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

        .current-filter {
            background: linear-gradient(135deg, var(--orange1), var(--orange2));
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: bold;
            box-shadow: 0 6px 15px rgba(244, 58, 9, 0.3);
            transform: translateY(-5px);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(-5px); }
            50% { transform: translateY(5px); }
        }

        .artists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 35px;
            padding: 20px 0;
        }

        .artist-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.15);
            transition: all 0.4s ease;
            position: relative;
            transform-style: preserve-3d;
            transform: perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1);
        }

        .artist-card:hover {
            transform: perspective(1000px) rotateX(5deg) rotateY(-5deg) scale(1.03);
            box-shadow: 0 20px 40px rgba(44, 44, 84, 0.25);
        }

        .artist-image {
            width: 100%;
            height: 280px;
            position: relative;
            overflow: hidden;
            transform: translateZ(20px);
        }

        .artist-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .artist-card:hover .artist-image img {
            transform: scale(1.1);
        }

        .artist-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(244, 58, 9, 0.1), rgba(104, 211, 136, 0.1));
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 1;
        }

        .artist-card:hover .artist-image::before {
            opacity: 1;
        }

        .artist-info {
            padding: 25px;
            position: relative;
            transform: translateZ(10px);
        }

        .artist-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
            line-height: 1.4;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .artist-name:hover {
            color: var(--orange1);
            text-decoration: underline;
        }

        .artist-style {
            font-size: 1rem;
            color: var(--dark);
            opacity: 0.8;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .artist-bio {
            font-size: 0.95rem;
            color: var(--dark);
            opacity: 0.7;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .artist-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(44, 44, 84, 0.1);
        }

        .artwork-count {
            background: linear-gradient(135deg, var(--green), var(--bluegreen));
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(104, 211, 136, 0.3);
        }

        .experience-level {
            color: var(--dark);
            opacity: 0.7;
            font-size: 0.9rem;
            text-transform: capitalize;
            font-weight: 600;
        }

        .view-profile-btn {
            background: linear-gradient(135deg, var(--orange1), var(--orange2));
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.4s ease;
            opacity: 0;
            transform: translateY(15px) translateZ(10px);
            box-shadow: 0 6px 15px rgba(244, 58, 9, 0.3);
            letter-spacing: 0.5px;
        }

        .artist-card:hover .view-profile-btn {
            opacity: 1;
            transform: translateY(0) translateZ(10px);
        }

        .view-profile-btn:hover {
            background: linear-gradient(135deg, var(--dark), #3a3a70);
            transform: translateY(-3px) translateZ(10px);
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.3);
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

        .no-artists {
            text-align: center;
            grid-column: 1 / -1;
            padding: 60px;
            color: var(--dark);
            font-size: 1.4rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.1);
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
            
            .artists-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 480px) {
            .artists-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="gallery-container">
       <div class="page-header">
            <h1 class="page-title">Our Artists</h1>
        </div>
        <div class="current-filter">Showing All Artists</div>

        <div class="artists-grid" id="artists-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($artist = mysqli_fetch_assoc($result)): ?>
                    <div class="artist-card" data-artist-id="<?php echo $artist['artist_id']; ?>">
                        <div class="artist-image">
                            <?php if ($artist['profile_pic']): ?>
                                <img src="<?php echo $artist['profile_pic']; ?>" 
                                     alt="<?php echo htmlspecialchars($artist['artist_name']); ?>" 
                                     onerror="this.src='images/default-artist.jpg'">
                            <?php else: ?>
                                <div class="image-placeholder">
                                    <?php echo htmlspecialchars($artist['artist_name']); ?>
                                    <small>Artist Profile</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="artist-info">
                            <div class="artist-name" onclick="viewArtist(<?php echo $artist['artist_id']; ?>)">
                                <?php echo htmlspecialchars($artist['artist_name']); ?>
                            </div>
                            <div class="artist-style">
                                <?php echo htmlspecialchars($artist['art_style'] ?: 'Various Styles'); ?>
                            </div>
                            <div class="artist-bio">
                                <?php echo htmlspecialchars($artist['artist_bio'] ?: 'No biography available.'); ?>
                            </div>
                            
                            <div class="artist-stats">
                                <span class="artwork-count"><?php echo $artist['artwork_count']; ?> Artworks</span>
                                <span class="experience-level"><?php echo $artist['experience_level']; ?></span>
                            </div>
                            
                            <button class="view-profile-btn" onclick="viewArtist(<?php echo $artist['artist_id']; ?>)">
                                VIEW PROFILE
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-artists">
                    <h3>No Artists Available</h3>
                    <p>We're currently updating our artist gallery. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php 
    // Remove the mysqli_close() call - connection closes automatically
    include('footer.html'); 
    ?>
    
    <script>
        // Function to view artist details
        function viewArtist(artistId) {
            window.location.href = 'artist-details.php?id=' + artistId;
        }
    </script>
</body>
</html>