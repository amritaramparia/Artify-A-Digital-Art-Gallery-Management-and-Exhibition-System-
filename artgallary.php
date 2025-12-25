<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Art Gallery | Products</title>
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

        .sort-container {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(44, 44, 84, 0.15);
            transform: perspective(500px) rotateY(-5deg);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .sort-container:hover {
            transform: perspective(500px) rotateY(0deg);
            box-shadow: 0 12px 25px rgba(44, 44, 84, 0.2);
        }

        .sort-label {
            font-weight: 700;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .sort-select {
            padding: 12px 20px;
            border-radius: 8px;
            border: 2px solid var(--bluegreen);
            background: white;
            color: var(--dark);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(194, 237, 218, 0.3);
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--orange1);
            box-shadow: 0 0 0 3px rgba(244, 58, 9, 0.2);
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

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 35px;
            padding: 20px 0;
        }

        .artwork-card {
            background: white;
            border-radius: 16px;
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

        .artwork-image {
            width: 100%;
            height: 280px;
            position: relative;
            overflow: hidden;
            transform: translateZ(20px);
        }

        .artwork-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
            background-color: #f8f9fa; 
        }

        .artwork-card:hover .artwork-image img {
            transform: scale(1.1);
        }

        .artwork-image::before {
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

        .artwork-card:hover .artwork-image::before {
            opacity: 1;
        }

        .artwork-actions {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            opacity: 0;
            transform: translateX(15px) translateZ(30px);
            transition: all 0.4s ease;
            z-index: 2;
        }

        .artwork-card:hover .artwork-actions {
            opacity: 1;
            transform: translateX(0) translateZ(30px);
        }

        .action-btn {
            background: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            color: var(--dark);
            font-size: 1.3rem;
            transform: translateZ(30px);
        }

        .action-btn:hover {
            background: var(--orange1);
            color: white;
            transform: translateZ(30px) scale(1.15);
            box-shadow: 0 6px 15px rgba(244, 58, 9, 0.4);
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
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .artwork-title:hover {
            color: var(--orange1);
            text-decoration: underline;
        }

        .artwork-artist {
            font-size: 1rem;
            color: var(--dark);
            opacity: 0.8;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .artwork-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--orange1);
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px rgba(244, 58, 9, 0.2);
        }

        .add-to-cart-btn {
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

        .artwork-card:hover .add-to-cart-btn {
            opacity: 1;
            transform: translateY(0) translateZ(10px);
        }

        .add-to-cart-btn:hover {
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

        .no-artworks {
            text-align: center;
            grid-column: 1 / -1;
            padding: 60px;
            color: var(--dark);
            font-size: 1.4rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.1);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 50px;
            gap: 20px;
        }

        .page-info {
            font-weight: 600;
            color: var(--dark);
            background: white;
            padding: 12px 25px;
            border-radius: 30px;
            box-shadow: 0 6px 15px rgba(44, 44, 84, 0.1);
        }

        .page-btn {
            background: linear-gradient(135deg, var(--orange1), var(--orange2));
            color: white;
            border: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.5rem;
            font-weight: bold;
            transition: all 0.4s ease;
            box-shadow: 0 8px 20px rgba(244, 58, 9, 0.3);
            transform: perspective(500px) rotateY(0deg);
        }

        .page-btn:hover {
            transform: perspective(500px) rotateY(15deg) scale(1.1);
            box-shadow: 0 12px 25px rgba(244, 58, 9, 0.4);
        }

        .page-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        @media (max-width: 1100px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 25px;
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
            
            .sort-container {
                transform: none;
            }
            
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 20px;
            }
            
            .artwork-card:hover {
                transform: perspective(1000px) rotateX(2deg) rotateY(-2deg) scale(1.02);
            }
        }

        @media (max-width: 480px) {
            .gallery-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 2rem;
            }
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
    ?>
    
    <div class="gallery-container">
        <div class="page-header">
            <h1 class="page-title">Products</h1>
            <div class="sort-container">
                <span class="sort-label">Sort by:</span>
                <select class="sort-select" id="sort-select" onchange="handleSortChange()">
                    <option value="latest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'latest') ? 'selected' : ''; ?>>Latest Arrivals</option>
                    <option value="price-high" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-high') ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="price-low" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price-low') ? 'selected' : ''; ?>>Price: Low to High</option>
                </select>
            </div>
        </div>

        <?php
        // Display current filter
        if (isset($_GET['subcategory']) || isset($_GET['category'])) {
            $filter_text = "Showing: ";
            
            // Subcategory names mapping
            $subcategory_names = [
                1 => "Abstract Painting", 2 => "Fresco", 3 => "Modern Art Paintings",
                4 => "Ink Wash Painting", 5 => "Acrylic Painting", 6 => "Nature | Scenery",
                7 => "Cityscape Painting", 8 => "Figurative Painting", 9 => "Flower Painting",
                10 => "Hindu God-Goddess", 11 => "Free-standing Sculpture", 12 => "Relief Sculpture",
                13 => "Carving", 14 => "Kinetic Sculpture", 15 => "Pottery & Ceramics",
                16 => "Glass Art", 17 => "Calligraphy", 18 => "Mosaic Art"
            ];
            
            if (isset($_GET['subcategory'])) {
                $subcategory_id = intval($_GET['subcategory']);
                $filter_text .= isset($subcategory_names[$subcategory_id]) ? $subcategory_names[$subcategory_id] : "Unknown Category";
            } elseif (isset($_GET['category'])) {
                $category_id = intval($_GET['category']);
                $category_names = [1 => "All Paintings", 2 => "All Sculpture", 3 => "All Showpiece & Decorative"];
                $filter_text .= isset($category_names[$category_id]) ? $category_names[$category_id] : "Unknown Category";
            }
            
            echo '<div class="current-filter">' . $filter_text . '</div>';
        } else {
            echo '<div class="current-filter">Showing: All Products</div>';
        }
        ?>

        <div class="gallery-grid" id="artworks-grid">
            <?php
            // Build SQL query based on filter
            $sql = "SELECT a.*, ar.artist_name FROM artworks a JOIN artists ar ON a.artist_id = ar.artist_id WHERE 1=1";
            $params = [];
            $types = "";
            
            // Handle subcategory filter
            if (isset($_GET['subcategory']) && $_GET['subcategory'] != '') {
                $subcategory_id = intval($_GET['subcategory']);
                $sql .= " AND a.subcategory_id = ?";
                $params[] = $subcategory_id;
                $types .= "i";
            }
            // Handle category filter
            elseif (isset($_GET['category']) && $_GET['category'] != '') {
                $category_id = intval($_GET['category']);
                $sql .= " AND a.category_id = ?";
                $params[] = $category_id;
                $types .= "i";
            }
            
            // Handle sorting
            $sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
            switch($sort_option) {
                case 'price-high': $sql .= " ORDER BY a.price DESC"; break;
                case 'price-low': $sql .= " ORDER BY a.price ASC"; break;
                case 'latest':
                default: $sql .= " ORDER BY a.created_at DESC"; break;
            }
            
            // Pagination
            $itemsPerPage = 24;
            $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $offset = ($currentPage - 1) * $itemsPerPage;
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM artworks a WHERE 1=1";
            if (isset($_GET['subcategory']) && $_GET['subcategory'] != '') {
                $countSql .= " AND a.subcategory_id = " . intval($_GET['subcategory']);
            } elseif (isset($_GET['category']) && $_GET['category'] != '') {
                $countSql .= " AND a.category_id = " . intval($_GET['category']);
            }
            
            $countResult = $conn->query($countSql);
            $totalItems = $countResult->fetch_assoc()['total'];
            $totalPages = ceil($totalItems / $itemsPerPage);
            
            // Add pagination to main query
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $itemsPerPage;
            $params[] = $offset;
            $types .= "ii";
            
            // Prepare and execute query
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while($artwork = $result->fetch_assoc()) {
                    $title = htmlspecialchars($artwork['title']);
                    $price = number_format($artwork['price'], 2);
                    $artist = htmlspecialchars($artwork['artist_name']);
                    $artwork_id = $artwork['artwork_id'];
                    $image_url = $artwork['image_url'];
                    
                    // Handle image display
                    $imageDisplay = "";
                    if (!empty($image_url)) {
                        $possible_paths = [$image_url, "images/" . $image_url, "./" . $image_url, "./images/" . basename($image_url), basename($image_url)];
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
                            $imageDisplay = "<div class='image-placeholder'>{$title}<small>Image not found</small></div>";
                        }
                    } else {
                        $imageDisplay = "<div class='image-placeholder'>{$title}<small>by {$artist}</small></div>";
                    }
                    
                    echo "
                    <div class='artwork-card'>
                        <div class='artwork-image'>
                            {$imageDisplay}
                            <div class='artwork-actions'>
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='artwork_id' value='{$artwork_id}'>
                                    <button type='submit' name='add_to_wishlist' class='action-btn wishlist-btn' title='Add to Wishlist'>♡</button>
                                </form>
                                <button class='action-btn view-btn' title='Quick View' onclick='viewArtworkDetails({$artwork_id})'>👁</button>
                            </div>
                        </div>
                        <div class='artwork-info'>
                            <div class='artwork-title' onclick='viewArtworkDetails({$artwork_id})'>{$title}</div>
                            <div class='artwork-artist'>by {$artist}</div>
                            <div class='artwork-price'>Rs.{$price}</div>
                            <form method='POST' style='width:100%;'>
                                <input type='hidden' name='artwork_id' value='{$artwork_id}'>
                                <button type='submit' name='add_to_cart' class='add-to-cart-btn'>ADD TO CART</button>
                            </form>
                        </div>
                    </div>";
                }
            } else {
                echo "<div class='no-artworks'>No artworks found in the selected category.</div>";
            }

            $stmt->close();
            $conn->close();
            ?>
        </div>

        <?php if ($totalItems > $itemsPerPage): ?>
        <div class="pagination">
            <button class="page-btn" id="prev-page" <?php echo $currentPage <= 1 ? 'disabled' : ''; ?> onclick="changePage(<?php echo $currentPage - 1; ?>)">←</button>
            <div class="page-info">Page <span id="current-page"><?php echo $currentPage; ?></span> of <span id="total-pages"><?php echo $totalPages; ?></span></div>
            <button class="page-btn" id="next-page" <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?> onclick="changePage(<?php echo $currentPage + 1; ?>)">→</button>
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

        function handleSortChange() {
            const sortSelect = document.getElementById('sort-select');
            const selectedSort = sortSelect.value;
            const currentUrl = new URL(window.location);
            
            if (selectedSort === 'latest') {
                currentUrl.searchParams.delete('sort');
            } else {
                currentUrl.searchParams.set('sort', selectedSort);
            }
            
            currentUrl.searchParams.delete('page');
            window.location.href = currentUrl.toString();
        }

        function changePage(page) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('page', page);
            window.location.href = currentUrl.toString();
        }

        function viewArtworkDetails(artworkId) {
            window.location.href = `artwork-details.php?id=${artworkId}`;
        }
    </script>
    
    <?php include('footer.html'); ?>
</body>
</html>