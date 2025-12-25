<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "art";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle remove from wishlist
if (isset($_POST['remove_from_wishlist']) && isset($_SESSION['user_id'])) {
    $wishlist_id = intval($_POST['wishlist_id']);
    $user_id = $_SESSION['user_id'];
    
    // Verify ownership before deleting
    $check_sql = "SELECT * FROM wishlist WHERE wishlist_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $wishlist_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $delete_sql = "DELETE FROM wishlist WHERE wishlist_id = ?";
        $stmt2 = $conn->prepare($delete_sql);
        $stmt2->bind_param("i", $wishlist_id);
        if ($stmt2->execute()) {
            $success_message = "Item removed from wishlist successfully!";
        } else {
            $error_message = "Error removing item from wishlist.";
        }
        $stmt2->close();
    } else {
        $error_message = "Item not found in your wishlist.";
    }
    $stmt->close();
}

// Handle add to cart from wishlist
if (isset($_POST['add_to_cart_from_wishlist']) && isset($_SESSION['user_id'])) {
    $artwork_id = intval($_POST['artwork_id']);
    $wishlist_id = intval($_POST['wishlist_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if already in cart
    $check_sql = "SELECT * FROM cart WHERE user_id = ? AND artwork_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $artwork_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Add to cart
        $insert_sql = "INSERT INTO cart (user_id, artwork_id, quantity) VALUES (?, ?, 1)";
        $stmt2 = $conn->prepare($insert_sql);
        $stmt2->bind_param("ii", $user_id, $artwork_id);
        if ($stmt2->execute()) {
            $success_message = "Artwork added to cart successfully!";
        } else {
            $error_message = "Error adding to cart. Please try again.";
        }
        $stmt2->close();
    } else {
        $error_message = "Artwork is already in your cart!";
    }
    $stmt->close();
}

// Fetch wishlist items for the logged-in user
$wishlist_items = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT w.wishlist_id, a.artwork_id, a.title, a.price, a.image_url, ar.artist_name 
            FROM wishlist w 
            JOIN artworks a ON w.artwork_id = a.artwork_id 
            JOIN artists ar ON a.artist_id = ar.artist_id 
            WHERE w.user_id = ? 
            ORDER BY w.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while($item = $result->fetch_assoc()) {
            $wishlist_items[] = $item;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | Art Gallery</title>
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

        .wishlist-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
        }

        .page-title {
            font-size: 3rem;
            font-weight: 800;
            color: var(--dark);
            position: relative;
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
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }

        .wishlist-item {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.15);
            transition: all 0.3s ease;
            position: relative;
        }

        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(44, 44, 84, 0.2);
        }

        .item-image {
            width: 100%;
            height: 250px;
            position: relative;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .wishlist-item:hover .item-image img {
            transform: scale(1.05);
        }

        .image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--bluegreen), var(--green));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            text-align: center;
            padding: 20px;
            font-weight: 500;
        }

        .item-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .wishlist-item:hover .item-actions {
            opacity: 1;
        }

        .action-btn {
            background: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }

        .remove-btn:hover {
            background: var(--orange1);
            color: white;
        }

        .cart-btn:hover {
            background: var(--green);
            color: white;
        }

        .item-info {
            padding: 20px;
        }

        .item-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            cursor: pointer;
            line-height: 1.4;
        }

        .item-title:hover {
            color: var(--orange1);
        }

        .item-artist {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .item-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--orange1);
            margin-bottom: 15px;
        }

        .add-to-cart-btn {
            background: linear-gradient(135deg, var(--orange1), var(--orange2));
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, var(--dark), #3a3a70);
            transform: translateY(-2px);
        }

        .empty-wishlist {
            text-align: center;
            padding: 80px 20px;
            grid-column: 1 / -1;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.1);
        }

        .empty-wishlist-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ccc;
        }

        .empty-wishlist h2 {
            color: var(--dark);
            margin-bottom: 15px;
        }

        .continue-shopping {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background: var(--dark);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .continue-shopping:hover {
            background: var(--orange1);
            transform: translateY(-2px);
        }

        .alert-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: var(--green);
        }

        .alert-error {
            background: var(--orange1);
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
            
            .item-actions {
                opacity: 1;
            }
        }

        @media (max-width: 480px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="wishlist-container">
        <div class="page-header">
            <h1 class="page-title">My Wishlist</h1>
        </div>

        <?php if (isset($success_message)): ?>
            <script>showAlert('<?php echo $success_message; ?>', 'success');</script>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <script>showAlert('<?php echo $error_message; ?>', 'error');</script>
        <?php endif; ?>

        <?php if (empty($wishlist_items)): ?>
            <div class="empty-wishlist">
                <div class="empty-wishlist-icon">❤️</div>
                <h2>Your wishlist is empty</h2>
                <p>Start adding your favorite artworks to your wishlist!</p>
                <a href="artgallary.php" class="continue-shopping">
                    Browse Artworks
                </a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-item">
                        <div class="item-image">
                            <?php
                            if (!empty($item['image_url'])) {
                                $possible_paths = [
                                    $item['image_url'],
                                    "images/" . $item['image_url'],
                                    "./" . $item['image_url'],
                                    "./images/" . basename($item['image_url']),
                                    basename($item['image_url'])
                                ];
                                
                                $found_path = null;
                                foreach ($possible_paths as $test_path) {
                                    $clean_path = str_replace('\\', '/', $test_path);
                                    if (file_exists($clean_path)) {
                                        $found_path = $clean_path;
                                        break;
                                    }
                                }
                                
                                if ($found_path) {
                                    echo "<img src='{$found_path}' alt='{$item['title']}'>";
                                } else {
                                    echo "<div class='image-placeholder'>Image Not Found</div>";
                                }
                            } else {
                                echo "<div class='image-placeholder'>{$item['title']}<br><small>by {$item['artist_name']}</small></div>";
                            }
                            ?>
                            
                            <div class="item-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="wishlist_id" value="<?php echo $item['wishlist_id']; ?>">
                                    <button type="submit" name="remove_from_wishlist" class="action-btn remove-btn" title="Remove from Wishlist">
                                        ×
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="item-info">
                            <div class="item-title" onclick="viewArtworkDetails(<?php echo $item['artwork_id']; ?>)">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </div>
                            <div class="item-artist">by <?php echo htmlspecialchars($item['artist_name']); ?></div>
                            <div class="item-price">Rs.<?php echo number_format($item['price'], 2); ?></div>
                            
                            <form method="POST">
                                <input type="hidden" name="artwork_id" value="<?php echo $item['artwork_id']; ?>">
                                <input type="hidden" name="wishlist_id" value="<?php echo $item['wishlist_id']; ?>">
                                <button type="submit" name="add_to_cart_from_wishlist" class="add-to-cart-btn">
                                    Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert-message alert-${type}`;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        function viewArtworkDetails(artworkId) {
            window.location.href = `artwork-details.php?id=${artworkId}`;
        }
    </script>
    
    <?php include('footer.html'); ?>
</body>
</html>
<?php $conn->close(); ?>