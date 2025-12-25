<?php
// artist-artworks.php
session_start();

// Check if user is logged in as an artist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "art";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get artist ID from session (user_id)
$user_id = $_SESSION['user_id'];

// Fetch artist details
$artist_sql = "SELECT artist_id, artist_name FROM artists WHERE user_id = ?";
$stmt = $conn->prepare($artist_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$artist_result = $stmt->get_result();

if ($artist_result->num_rows == 0) {
    echo "<script>alert('Artist profile not found.'); window.location.href='index.php';</script>";
    exit();
}

$artist = $artist_result->fetch_assoc();
$artist_id = $artist['artist_id'];
$artist_name = $artist['artist_name'];
$stmt->close();

// Fetch artist's artworks
$artworks_sql = "SELECT a.*, c.name as category_name, s.name as subcategory_name 
                 FROM artworks a 
                 LEFT JOIN categories c ON a.category_id = c.category_id 
                 LEFT JOIN subcategories s ON a.subcategory_id = s.subcategory_id 
                 WHERE a.artist_id = ? 
                 ORDER BY a.created_at DESC";
$artworks_stmt = $conn->prepare($artworks_sql);
$artworks_stmt->bind_param("i", $artist_id);
$artworks_stmt->execute();
$artworks_result = $artworks_stmt->get_result();

$artworks = [];
$total_artworks = 0;
$total_value = 0;

if ($artworks_result->num_rows > 0) {
    while ($row = $artworks_result->fetch_assoc()) {
        $artworks[] = $row;
        $total_artworks++;
        $total_value += $row['price'];
    }
}
$artworks_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Artworks | Art Gallery</title>
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
            background-color: var(--light);
            color: var(--dark);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
            margin-bottom: 30px;
        }

        .artist-info {
            text-align: center;
            margin-bottom: 30px;
        }

        .artist-name {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 20px;
        }

        .stat-box {
            background: var(--bluegreen);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            min-width: 150px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--orange1);
        }

        .stat-label {
            font-size: 1rem;
            color: var(--dark);
            margin-top: 5px;
        }

        .artworks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .artwork-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .artwork-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(44, 44, 84, 0.2);
        }

        .artwork-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .artwork-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .artwork-card:hover .artwork-image img {
            transform: scale(1.05);
        }

        .artwork-details {
            padding: 20px;
        }

        .artwork-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .artwork-category {
            font-size: 0.9rem;
            color: var(--dark);
            background: var(--bluegreen);
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .artwork-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--orange1);
            margin: 10px 0;
        }

        .artwork-stock {
            font-size: 0.9rem;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .artwork-id {
            font-size: 0.8rem;
            color: #666;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            margin-top: 30px;
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--orange2);
            margin-bottom: 20px;
        }

        .empty-message {
            font-size: 1.2rem;
            color: var(--dark);
            margin-bottom: 20px;
        }

        .add-artwork-btn {
            background: var(--orange1);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .add-artwork-btn:hover {
            background: var(--dark);
            transform: translateY(-2px);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .edit-btn, .delete-btn {
            flex: 1;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
        }

        .edit-btn {
            background: var(--bluegreen);
            color: var(--dark);
        }

        .edit-btn:hover {
            background: var(--green);
        }

        .delete-btn {
            background: #ffeaea;
            color: #d32f2f;
        }

        .delete-btn:hover {
            background: #ffcdcd;
        }

        @media (max-width: 768px) {
            .stats {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
            
            .stat-box {
                width: 100%;
                max-width: 250px;
            }
            
            .artworks-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .artworks-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 20px;
            }
            
            .artist-name {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="container">
        <div class="header">
            <div class="artist-info">
                <h1 class="artist-name"><?php echo htmlspecialchars($artist_name); ?></h1>
                <p>Your Art Collection</p>
            </div>
        </div>
        
        <?php if ($total_artworks > 0): ?>
            <div class="artworks-grid">
                <?php foreach ($artworks as $artwork): ?>
                    <div class="artwork-card">
                        <div class="artwork-image">
                            <?php
                            $image_url = $artwork['image_url'];
                            if (!empty($image_url) && file_exists($image_url)) {
                                echo "<img src='{$image_url}' alt='" . htmlspecialchars($artwork['title']) . "'>";
                            } else {
                                // Try alternative paths
                                $alt_paths = [
                                    "images/" . $image_url,
                                    "./" . $image_url,
                                    "./images/" . basename($image_url)
                                ];
                                $found = false;
                                
                                foreach ($alt_paths as $alt_path) {
                                    if (!empty($alt_path) && file_exists($alt_path)) {
                                        echo "<img src='{$alt_path}' alt='" . htmlspecialchars($artwork['title']) . "'>";
                                        $found = true;
                                        break;
                                    }
                                }
                                
                                if (!$found) {
                                    echo "<div style='width:100%;height:200px;background:var(--bluegreen);display:flex;align-items:center;justify-content:center;color:var(--dark);font-weight:bold;'>Artwork Image</div>";
                                }
                            }
                            ?>
                        </div>
                        <div class="artwork-details">
                            <h3 class="artwork-title"><?php echo htmlspecialchars($artwork['title']); ?></h3>
                            
                            <div class="artwork-category">
                                <?php 
                                echo htmlspecialchars($artwork['category_name'] ?? 'Uncategorized');
                                if (!empty($artwork['subcategory_name'])) {
                                    echo ' • ' . htmlspecialchars($artwork['subcategory_name']);
                                }
                                ?>
                            </div>
                            
                            <div class="artwork-price">₹<?php echo number_format($artwork['price'], 2); ?></div>
                            
                            <div class="artwork-stock">
                                Stock: <?php echo $artwork['stock_quantity']; ?> available
                            </div>
                            
                            <div class="artwork-id">
                                ID: #<?php echo $artwork['artwork_id']; ?> • Created: <?php echo date('M d, Y', strtotime($artwork['created_at'])); ?>
                            </div>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🎨</div>
                <h2 class="empty-message">No Artworks Found</h2>
                <p>You haven't added any artworks yet.</p>
                <a href="add-artwork.php" class="add-artwork-btn">Add Your First Artwork</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include('footer.html'); ?>
    
    <script>
        function confirmDelete(artworkId, title) {
            if (confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
                window.location.href = `delete-artwork.php?id=${artworkId}`;
            }
        }
    </script>
</body>
</html>