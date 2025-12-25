<?php 
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artwork Details | Art Gallery</title>
    <style>
        /* Your existing CSS styles remain exactly the same */
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
            background-color: var(--light);
            color: var(--dark);
            padding: 20px;
            min-height: 100vh;
        }

        .details-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            padding: 10px 20px;
            background: var(--dark);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-btn:hover {
            background: var(--orange1);
            transform: translateX(-5px);
        }

        .artwork-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
        }

        .artwork-image {
            width: 100%;
            height: 500px;
            position: relative;
            overflow: hidden;
        }

        .artwork-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: #f8f9fa; 
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

        .artwork-info {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .artwork-title {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .artwork-artist {
            font-size: 1.1rem;
            color: var(--dark);
            opacity: 0.7;
            margin-bottom: 20px;
        }

        .artwork-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--orange1);
            margin-bottom: 25px;
        }

        /* Tabs Styling */
        .tabs-container {
            margin-bottom: 25px;
        }

        .tabs-header {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-btn.active {
            color: var(--orange1);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--orange1);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Details Tab */
        .artwork-details-list {
            padding: 15px;
            background-color: rgba(194, 237, 218, 0.2);
            border-radius: 8px;
        }

        .detail-item {
            display: flex;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(44, 44, 84, 0.1);
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            width: 140px;
            color: var(--dark);
        }

        .detail-value {
            flex: 1;
            color: #555;
        }

        /* Description Tab */
        .description-content {
            line-height: 1.6;
            color: #555;
        }

        .artwork-description {
            margin-bottom: 20px;
        }

        .artist-details {
            padding: 15px;
            background-color: rgba(194, 237, 218, 0.2);
            border-radius: 8px;
            margin-top: 20px;
        }

        .artist-name {
            font-weight: bold;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .artist-bio {
            font-size: 0.9rem;
            line-height: 1.5;
            color: #555;
        }

        .stock-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .in-stock {
            background-color: rgba(104, 211, 136, 0.2);
            color: var(--green);
        }

        .low-stock {
            background-color: rgba(255, 183, 102, 0.2);
            color: var(--orange1);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .buy-now-btn, .add-to-cart-btn, .wishlist-btn {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .buy-now-btn {
            background: var(--orange1);
            color: white;
        }

        .buy-now-btn:hover {
            background: var(--dark);
            transform: translateY(-2px);
        }

        .add-to-cart-btn {
            background: var(--dark);
            color: white;
        }

        .add-to-cart-btn:hover {
            background: var(--orange1);
            transform: translateY(-2px);
        }

        .wishlist-btn {
            width: 50px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .wishlist-btn:hover, .wishlist-btn.active {
            border-color: var(--orange1);
            color: var(--orange1);
        }

        .error-message {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
        }

        .error-message h2 {
            color: var(--orange1);
            margin-bottom: 20px;
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
            .artwork-details {
                grid-template-columns: 1fr;
            }
            
            .artwork-image {
                height: 400px;
            }
            
            .artwork-info {
                padding: 25px;
            }
            
            .artwork-title {
                font-size: 1.6rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .tabs-header {
                flex-direction: column;
            }
            
            .tab-btn {
                text-align: left;
                border-bottom: 1px solid #eee;
            }
            
            .tab-btn.active::after {
                display: none;
            }
            
            .detail-item {
                flex-direction: column;
            }
            
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <?php 
   
    include('header.php');
    
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

    // Handle add to cart - FIXED VERSION
    if (isset($_POST['add_to_cart']) && isset($_SESSION['user_id'])) {
        $artwork_id = intval($_POST['artwork_id']);
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
                echo "<script>showAlert('Artwork added to cart successfully!', 'success');</script>";
            } else {
                echo "<script>showAlert('Error adding to cart. Please try again.', 'error');</script>";
            }
            $stmt2->close();
        } else {
            echo "<script>showAlert('Artwork is already in your cart!', 'error');</script>";
        }
        $stmt->close();
    }

    // Handle add to wishlist - FIXED VERSION
    if (isset($_POST['add_to_wishlist']) && isset($_SESSION['user_id'])) {
        $artwork_id = intval($_POST['artwork_id']);
        $user_id = $_SESSION['user_id'];
        
        // Check if already in wishlist
        $check_sql = "SELECT * FROM wishlist WHERE user_id = ? AND artwork_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $user_id, $artwork_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Add to wishlist
            $insert_sql = "INSERT INTO wishlist (user_id, artwork_id) VALUES (?, ?)";
            $stmt2 = $conn->prepare($insert_sql);
            $stmt2->bind_param("ii", $user_id, $artwork_id);
            if ($stmt2->execute()) {
                echo "<script>showAlert('Artwork added to wishlist successfully!', 'success');</script>";
            } else {
                echo "<script>showAlert('Error adding to wishlist. Please try again.', 'error');</script>";
            }
            $stmt2->close();
        } else {
            echo "<script>showAlert('Artwork is already in your wishlist!', 'error');</script>";
        }
        $stmt->close();
    }

    // Get artwork ID from URL parameter
    $artwork_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($artwork_id > 0) {
        // Fetch artwork details from database with category and subcategory names
        $sql = "SELECT 
                    a.*, 
                    ar.artist_name, 
                    ar.artist_bio,
                    c.name as category_name,
                    s.name as subcategory_name
                FROM artworks a 
                JOIN artists ar ON a.artist_id = ar.artist_id 
                JOIN categories c ON a.category_id = c.category_id
                LEFT JOIN subcategories s ON a.subcategory_id = s.subcategory_id
                WHERE a.artwork_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $artwork_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $artwork = $result->fetch_assoc();
            
            $title = htmlspecialchars($artwork['title']);
            $price = number_format($artwork['price'], 2);
            $artist = htmlspecialchars($artwork['artist_name']);
            $artist_bio = htmlspecialchars($artwork['artist_bio']);
            $description = htmlspecialchars($artwork['description']);
            $category = htmlspecialchars($artwork['category_name']);
            $subcategory = htmlspecialchars($artwork['subcategory_name']);
            $stock_quantity = $artwork['stock_quantity'];
            $created_date = date('M d, Y', strtotime($artwork['created_at']));
            $image_url = $artwork['image_url'];
            
            // Handle image display
            $imageDisplay = "";
            if (!empty($image_url)) {
                // Try multiple possible path combinations
                $possible_paths = [
                    $image_url,
                    "images/" . $image_url,
                    "./" . $image_url,
                    "./images/" . basename($image_url),
                    basename($image_url)
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
                    $imageDisplay = "<img src='{$found_path}' alt='{$title}' />";
                } else {
                    $imageDisplay = "
                        <div class='image-placeholder'>
                            Image not found<br>
                            <small>DB Path: {$image_url}</small>
                        </div>
                    ";
                }
            } else {
                $imageDisplay = "
                    <div class='image-placeholder'>
                        {$title}<br>
                        <small>by {$artist}</small>
                    </div>
                ";
            }
            
            // Determine stock status
            $stock_class = $stock_quantity > 0 ? "in-stock" : "low-stock";
            $stock_text = $stock_quantity > 0 ? "In Stock" : "Limited Stock";
            
            // Output artwork details
            echo "
            <div class='details-container'>
                <a href='artgallary.php' class='back-btn'>
                    ← Back to Gallery
                </a>
                <div class='artwork-details'>
                    <div class='artwork-image'>
                        {$imageDisplay}
                    </div>
                    <div class='artwork-info'>
                        <div>
                            <h1 class='artwork-title'>{$title}</h1>
                            <div class='artwork-artist'>by {$artist}</div>
                            <div class='artwork-price'>Rs.{$price}</div>
                            
                            <div class='tabs-container'>
                                <div class='tabs-header'>
                                    <button class='tab-btn active' data-tab='details'>Details</button>
                                    <button class='tab-btn' data-tab='description'>Description</button>
                                </div>
                                
                                <div class='tab-content active' id='details-tab'>
                                    <div class='artwork-details-list'>
                                        <div class='detail-item'>
                                            <div class='detail-label'>Category:</div>
                                            <div class='detail-value'>{$category}</div>
                                        </div>";
                                        
                                        if (!empty($subcategory)) {
                                            echo "<div class='detail-item'>
                                                <div class='detail-label'>Style:</div>
                                                <div class='detail-value'>{$subcategory}</div>
                                            </div>";
                                        }
                                        
                                        echo "<div class='detail-item'>
                                            <div class='detail-label'>Created:</div>
                                            <div class='detail-value'>{$created_date}</div>
                                        </div>
                                        <div class='detail-item'>
                                            <div class='detail-label'>Availability:</div>
                                            <div class='detail-value'>
                                                <span class='stock-status {$stock_class}'>{$stock_text}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class='tab-content' id='description-tab'>
                                    <div class='description-content'>
                                        <div class='artwork-description'>
                                            <p>{$description}</p>
                                        </div>
                                        
                                        <div class='artist-details'>
                                            <div class='artist-name'>About the Artist: {$artist}</div>
                                            <div class='artist-bio'>{$artist_bio}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class='action-buttons'>
                            <form method='GET' action='checkout.php' style='display:inline; flex:1;'>
                                <input type='hidden' name='artwork_id' value='{$artwork_id}'>
                                <button type='submit' class='buy-now-btn'>
                                    🛒 Buy Now
                                </button>
                            </form>
                            <form method='POST' style='display:inline; flex:1;'>
                                <input type='hidden' name='artwork_id' value='{$artwork_id}'>
                                <button type='submit' name='add_to_cart' class='add-to-cart-btn'>
                                    ＋ Add to Cart
                                </button>
                            </form>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='artwork_id' value='{$artwork_id}'>
                                <button type='submit' name='add_to_wishlist' class='wishlist-btn' title='Add to Wishlist'>
                                    ♡
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>";
        } else {
            echo "
            <div class='details-container'>
                <div class='error-message'>
                    <h2>Artwork Not Found</h2>
                    <p>The artwork you're looking for doesn't exist or has been removed.</p>
                    <p><a href='artgallary.php' class='back-btn'>Return to Gallery</a></p>
                </div>
            </div>";
        }
        
        $stmt->close();
    } else {
        echo "
        <div class='details-container'>
            <div class='error-message'>
                <h2>Invalid Artwork</h2>
                <p>Please select a valid artwork from the gallery.</p>
                <p><a href='artgallary.php' class='back-btn'>Return to Gallery</a></p>
            </div>
        </div>";
    }

    // Close connection
    $conn->close();
    ?>
    
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

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            // Set default active tab (Details tab)
            if (tabBtns.length > 0) {
                tabBtns[0].click();
            }
        });
    </script>
    
    <?php include('footer.html'); ?>
</body>
</html>