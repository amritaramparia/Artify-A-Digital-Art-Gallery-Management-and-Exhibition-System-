<?php
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

// Handle delete artwork
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_sql = "DELETE FROM artworks WHERE artwork_id = $delete_id";
    
    if ($conn->query($delete_sql)) {
        $delete_message = "Artwork deleted successfully!";
        header("location:".basename($_SERVER['PHP_SELF']));
        exit();
    } else {
        $delete_error = "Error deleting artwork: " . $conn->error;
    }
}

// Handle status update
if (isset($_POST['update_status'])) {
    $artwork_id = intval($_POST['artwork_id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $update_sql = "UPDATE artworks SET status = '$status' WHERE artwork_id = $artwork_id";
    if ($conn->query($update_sql)) {
        $status_message = "Artwork status updated successfully!";
    } else {
        $status_error = "Error updating status: " . $conn->error;
    }
}

// Get all artworks with category and artist details (FIXED QUERY)
$artworks_sql = "SELECT a.*, c.name as category_name, s.name as subcategory_name, 
                        ar.artist_name, ar.email as artist_email,
                        0 as total_sold,
                        0 as total_sales_value
                 FROM artworks a 
                 LEFT JOIN categories c ON a.category_id = c.category_id 
                 LEFT JOIN subcategories s ON a.subcategory_id = s.subcategory_id
                 LEFT JOIN artists ar ON a.artist_id = ar.artist_id
                 ORDER BY a.created_at DESC";
$artworks_result = $conn->query($artworks_sql);

// Get statistics
$total_artworks_sql = "SELECT COUNT(*) as total FROM artworks";
$total_artworks_result = $conn->query($total_artworks_sql);
$total_artworks = $total_artworks_result->fetch_assoc()['total'];

$total_artists_sql = "SELECT COUNT(DISTINCT artist_id) as total FROM artworks";
$total_artists_result = $conn->query($total_artists_sql);
$total_artists = $total_artists_result->fetch_assoc()['total'];

$total_categories_sql = "SELECT COUNT(DISTINCT category_id) as total FROM artworks";
$total_categories_result = $conn->query($total_categories_sql);
$total_categories = $total_categories_result->fetch_assoc()['total'];

$total_value_sql = "SELECT SUM(price) as total FROM artworks";
$total_value_result = $conn->query($total_value_sql);
$total_value = $total_value_result->fetch_assoc()['total'];

// Get sales statistics (FIXED - using default values since order_items doesn't exist)
$total_sold = 0;
$total_sales = 0;

// Get popular categories
$popular_categories_sql = "SELECT c.name, COUNT(a.artwork_id) as artwork_count 
                           FROM categories c 
                           LEFT JOIN artworks a ON c.category_id = a.category_id 
                           GROUP BY c.category_id 
                           ORDER BY artwork_count DESC 
                           LIMIT 5";
$popular_categories_result = $conn->query($popular_categories_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artify Admin Panel - Artworks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            display: flex;
            min-height: 100vh;
        }
        
        /* 3D Font Effects */
        .text-3d-light {
            text-shadow: 0 1px 0 #e6e6e6, 
                         0 2px 0 #d9d9d9,
                         0 3px 0 #ccc,
                         0 4px 0 #bfbfbf,
                         0 5px 0 #b3b3b3,
                         0 6px 1px rgba(0,0,0,.1),
                         0 0 5px rgba(0,0,0,.1),
                         0 1px 3px rgba(0,0,0,.2),
                         0 3px 5px rgba(0,0,0,.15);
        }
        
        .text-3d-dark {
            color: var(--dark);
            text-shadow: 0 1px 0 #252548, 
                         0 2px 0 #212142,
                         0 3px 0 #1d1d3c,
                         0 4px 0 #191936,
                         0 5px 0 #151530,
                         0 6px 1px rgba(0,0,0,.2),
                         0 0 5px rgba(0,0,0,.1),
                         0 1px 3px rgba(0,0,0,.3),
                         0 3px 5px rgba(0,0,0,.25);
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: linear-gradient(135deg, var(--dark) 0%, #3d3d72 100%);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            margin-bottom: 25px;
            border-radius: 10px;
            color: white;
        }
        
        .header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.15);
            border-radius: 25px;
            padding: 8px 18px;
            width: 300px;
            backdrop-filter: blur(5px);
        }
        
        .search-box input {
            border: none;
            background: transparent;
            padding: 8px;
            width: 100%;
            outline: none;
            color: white;
        }
        
        .search-box input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid var(--orange2);
            box-shadow: 0 0 10px rgba(255, 183, 102, 0.5);
        }
        
        .notification {
            position: relative;
            margin-right: 20px;
        }
        
        .notification i {
            font-size: 1.2rem;
            color: white;
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.5);
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--orange1);
            color: white;
            font-size: 0.7rem;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        /* Stats and Chart Layout */
        .stats-chart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }
        
        .chart-title {
            text-align: center;
            margin-bottom: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .chart-wrapper {
            position: relative;
            height: 200px;
            width: 100%;
        }
        
        .chart-legend {
            display: flex;
            flex-direction: column;
            margin-top: 15px;
            gap: 8px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 0.8rem;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .card {
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-card {
            display: flex;
            align-items: center;
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.8rem;
            margin-right: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .artwork-icon {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .artist-icon {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .category-icon {
            background: linear-gradient(135deg, var(--orange2) 0%, #ffca93 100%);
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .value-icon {
            background: linear-gradient(135deg, var(--bluegreen) 0%, #d6f5e8 100%);
            color: var(--dark);
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .sales-icon {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .sold-icon {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .section-title {
            margin: 30px 0 20px;
            color: var(--dark);
            padding-bottom: 12px;
            border-bottom: 2px solid var(--orange2);
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 3px rgba(0,0,0,0.1);
        }
        
        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .data-table th, .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
        }
        
        .data-table th {
            background: linear-gradient(135deg, var(--dark) 0%, #3d3d72 100%);
            color: white;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .data-table tr {
            transition: background-color 0.2s;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background-color: rgba(194, 237, 218, 0.2);
        }
        
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .published {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
        }
        
        .pending {
            background: linear-gradient(135deg, var(--orange2) 0%, #ffca93 100%);
            color: var(--dark);
        }
        
        .sold-out {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .low-stock {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
        
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .edit-btn {
            background: linear-gradient(135deg, var(--bluegreen) 0%, #d6f5e8 100%);
            color: var(--dark);
        }
        
        .delete-btn {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
        }
        
        .view-btn {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
        }
        
        .related-btn {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
        }
        
        /* Artwork Image Styles */
        .artwork-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid var(--orange2);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .artwork-image-placeholder {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--light) 0%, #e9e9e9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            border: 2px dashed #ccc;
        }
        
        /* Notification Styles */
        .notification-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .success {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
        }
        
        .error {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
        }
        
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal {
            background: white;
            border-radius: 12px;
            padding: 25px;
            width: 500px;
            max-width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }
        
        .modal h3 {
            margin-bottom: 15px;
            color: var(--dark);
            border-bottom: 2px solid var(--orange2);
            padding-bottom: 10px;
        }
        
        .modal p {
            margin-bottom: 10px;
            color: #666;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
        }
        
        /* Status Select */
        .status-select {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background: white;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .main-content.active {
                margin-left: 250px;
            }
            
            .stats-chart-container {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .search-box {
                width: 200px;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 576px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-box {
                width: 100%;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <h2 class="text-3d-light">Artworks Management</h2>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search artworks..." id="artworkSearch">
            </div>
            <div class="user-info">
                
                <img src="https://ui-avatars.com/api/?name=Admin+User&background=f43a09&color=fff" alt="Admin User">
                <span>Admin User</span>
            </div>
        </div>

        <!-- Notification Messages -->
        <?php if (isset($delete_message)): ?>
            <div class="notification-message success">
                <i class="fas fa-check-circle"></i> <?php echo $delete_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($delete_error)): ?>
            <div class="notification-message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $delete_error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($status_message)): ?>
            <div class="notification-message success">
                <i class="fas fa-check-circle"></i> <?php echo $status_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($status_error)): ?>
            <div class="notification-message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $status_error; ?>
            </div>
        <?php endif; ?>

        <!-- Artworks Stats with Chart -->
        <div class="stats-chart-container">
            <!-- Left Side - Statistics Cards -->
            <div class="stats-container">
                <div class="card stat-card">
                    <div class="card-icon artwork-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div>
                        <div class="number text-3d-dark"><?php echo $total_artworks; ?></div>
                        <div class="label">Total Artworks</div>
                    </div>
                </div>
                
                <div class="card stat-card">
                    <div class="card-icon artist-icon">
                        <i class="fas fa-user-alt"></i>
                    </div>
                    <div>
                        <div class="number text-3d-dark"><?php echo $total_artists; ?></div>
                        <div class="label">Artists</div>
                    </div>
                </div>
                
                <div class="card stat-card">
                    <div class="card-icon sales-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div>
                        <div class="number text-3d-dark"><?php echo $total_sold; ?></div>
                        <div class="label">Artworks Sold</div>
                    </div>
                </div>
                
                <div class="card stat-card">
                    <div class="card-icon value-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div>
                        <div class="number text-3d-dark">₹<?php echo number_format($total_sales, 2); ?></div>
                        <div class="label">Total Sales</div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Compact Chart -->
            <div class="chart-container">
                <div class="chart-title">Popular Categories</div>
                <div class="chart-wrapper">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Artworks List -->
        <h3 class="section-title text-3d-dark">All Artworks</h3>
        <div class="card">
            <table class="data-table" id="artworksTable">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Artist</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock/Sold</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                   <?php
                        if ($artworks_result && $artworks_result->num_rows > 0) {
                            while($row = $artworks_result->fetch_assoc()) {
                                // Determine status based on stock and sales
                                $status = 'published';
                                $status_class = 'published';
                                $status_text = 'Available';
                                
                                if ($row['stock_quantity'] == 0) {
                                    $status = 'sold-out';
                                    $status_class = 'sold-out';
                                    $status_text = 'Sold Out';
                                } elseif ($row['stock_quantity'] <= 2) {
                                    $status = 'low-stock';
                                    $status_class = 'low-stock';
                                    $status_text = 'Low Stock';
                                }
                                
                                $image_html = '';
                                if (!empty($row['image_url'])) {
                                    $image_html = '<img src="' . htmlspecialchars($row['image_url']) . '" alt="' . htmlspecialchars($row['title']) . '" class="artwork-image">';
                                } else {
                                    $image_html = '<div class="artwork-image-placeholder"><i class="fas fa-image"></i></div>';
                                }
                                
                                echo "<tr>
                                    <td>{$image_html}</td>
                                    <td>" . htmlspecialchars($row['title']) . "</td>
                                    <td>" . htmlspecialchars($row['artist_name']) . "</td>
                                    <td>" . htmlspecialchars($row['category_name']) . 
                                         (($row['subcategory_name']) ? " / " . htmlspecialchars($row['subcategory_name']) : "") . "</td>
                                    <td>₹" . number_format($row['price'], 2) . "</td>
                                    <td>
                                        <strong>Stock:</strong> {$row['stock_quantity']}<br>
                                        <strong>Sold:</strong> {$row['total_sold']}
                                    </td>
                                    <td>
                                        <select class='status-select' onchange='updateStatus({$row['artwork_id']}, this.value)'>
                                            <option value='published' " . ($status == 'published' ? 'selected' : '') . ">Available</option>
                                            <option value='low-stock' " . ($status == 'low-stock' ? 'selected' : '') . ">Low Stock</option>
                                            <option value='sold-out' " . ($status == 'sold-out' ? 'selected' : '') . ">Sold Out</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button class='action-btn view-btn' onclick='viewArtwork(" . json_encode($row) . ")'><i class='fas fa-eye'></i> View</button>
                                        <button class='action-btn related-btn' onclick='showRelatedArtworks({$row['artwork_id']}, \"{$row['category_id']}\")'><i class='fas fa-link'></i> Related</button>
                                        <button class='action-btn edit-btn'><i class='fas fa-edit'></i> Edit</button>
                                        <button class='action-btn delete-btn' onclick='confirmDelete({$row['artwork_id']}, \"" . htmlspecialchars(addslashes($row['title'])) . "\")'><i class='fas fa-trash'></i> Delete</button>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align: center; padding: 40px;'>
                                <i class='fas fa-palette' style='font-size: 3rem; color: var(--orange2); margin-bottom: 15px; display: block;'></i>
                                <h3>No Artworks Found</h3>
                                <p>There are currently no artworks in the database.</p>
                            </td></tr>";
                        }
                     ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete artwork: <strong id="artworkTitle"></strong>?</p>
            <p style="color: var(--orange1); font-weight: 600;"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone!</p>
            <div class="modal-actions">
                <button class="btn" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" id="confirmDeleteBtn">Delete Artwork</button>
            </div>
        </div>
    </div>

    <!-- Artwork Details Modal -->
    <div class="modal-overlay" id="artworkModal">
        <div class="modal">
            <h3 id="modalArtworkTitle">Artwork Details</h3>
            <div id="artworkDetails">
                <!-- Details will be populated by JavaScript -->
            </div>
            <div class="modal-actions">
                <button class="btn" onclick="closeArtworkModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Related Artworks Modal -->
    <div class="modal-overlay" id="relatedModal">
        <div class="modal">
            <h3>Related Artworks</h3>
            <div id="relatedArtworks">
                <!-- Related artworks will be populated by JavaScript -->
            </div>
            <div class="modal-actions">
                <button class="btn" onclick="closeRelatedModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('artworkSearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#artworksTable tbody tr');
            
            rows.forEach(row => {
                const title = row.cells[1].textContent.toLowerCase();
                const artist = row.cells[2].textContent.toLowerCase();
                const category = row.cells[3].textContent.toLowerCase();
                
                if (title.includes(searchValue) || artist.includes(searchValue) || category.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Delete confirmation functionality
        let artworkIdToDelete = null;
        
        function confirmDelete(id, title) {
            artworkIdToDelete = id;
            document.getElementById('artworkTitle').textContent = title;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            artworkIdToDelete = null;
        }
        
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (artworkIdToDelete) {
                window.location.href = '?delete_id=' + artworkIdToDelete;
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Status update functionality
        function updateStatus(artworkId, status) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const artworkIdInput = document.createElement('input');
            artworkIdInput.name = 'artwork_id';
            artworkIdInput.value = artworkId;
            
            const statusInput = document.createElement('input');
            statusInput.name = 'status';
            statusInput.value = status;
            
            const submitInput = document.createElement('input');
            submitInput.name = 'update_status';
            submitInput.value = '1';
            
            form.appendChild(artworkIdInput);
            form.appendChild(statusInput);
            form.appendChild(submitInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Artwork details modal functionality
        function viewArtwork(artwork) {
            const modal = document.getElementById('artworkModal');
            const title = document.getElementById('modalArtworkTitle');
            const details = document.getElementById('artworkDetails');
            
            title.textContent = artwork.title;
            
            // Format dimensions
            let dimensions = "N/A";
            if (artwork.width_cm > 0 && artwork.height_cm > 0) {
                dimensions = artwork.width_cm + "×" + artwork.height_cm;
                if (artwork.depth_cm > 0) {
                    dimensions += "×" + artwork.depth_cm;
                }
                dimensions += " cm";
            }
            
            // Format description or use placeholder
            let description = artwork.description || "No description available.";
            
            // Build details HTML
            details.innerHTML = `
                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        ${artwork.image_url ? 
                            `<img src="${artwork.image_url}" alt="${artwork.title}" style="width: 100%; border-radius: 8px; border: 2px solid var(--orange2);">` : 
                            `<div style="width: 100%; height: 200px; background: linear-gradient(135deg, var(--light) 0%, #e9e9e9 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999; border: 2px dashed #ccc;">
                                <i class="fas fa-image" style="font-size: 3rem;"></i>
                            </div>`
                        }
                    </div>
                    <div style="flex: 2;">
                        <p><strong>Artist:</strong> ${artwork.artist_name}</p>
                        <p><strong>Email:</strong> ${artwork.artist_email}</p>
                        <p><strong>Category:</strong> ${artwork.category_name} ${artwork.subcategory_name ? '/ ' + artwork.subcategory_name : ''}</p>
                        <p><strong>Medium:</strong> ${artwork.medium || 'Not specified'}</p>
                        <p><strong>Price:</strong> ₹${parseFloat(artwork.price).toFixed(2)}</p>
                        <p><strong>Dimensions:</strong> ${dimensions}</p>
                        <p><strong>Stock:</strong> ${artwork.stock_quantity} available</p>
                        <p><strong>Sold:</strong> ${artwork.total_sold} units</p>
                        <p><strong>Sales Value:</strong> ₹${parseFloat(artwork.total_sales_value).toFixed(2)}</p>
                        <p><strong>Created:</strong> ${new Date(artwork.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <p><strong>Description:</strong></p>
                    <p style="background: var(--light); padding: 15px; border-radius: 8px; border-left: 4px solid var(--orange2);">${description}</p>
                </div>
            `;
            
            modal.style.display = 'flex';
        }
        
        function closeArtworkModal() {
            document.getElementById('artworkModal').style.display = 'none';
        }
        
        // Related artworks functionality
        function showRelatedArtworks(artworkId, categoryId) {
            const modal = document.getElementById('relatedModal');
            const relatedContainer = document.getElementById('relatedArtworks');
            
            // In a real application, you would fetch related artworks from the server
            // For now, we'll show a simulated response
            relatedContainer.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-link" style="font-size: 3rem; color: var(--orange2); margin-bottom: 15px;"></i>
                    <h4>Related Artworks Feature</h4>
                    <p>This would show artworks from the same category, artist, or similar price range.</p>
                    <p><strong>Artwork ID:</strong> ${artworkId}</p>
                    <p><strong>Category ID:</strong> ${categoryId}</p>
                    <div style="margin-top: 20px; padding: 15px; background: var(--light); border-radius: 8px;">
                        <p><strong>Implementation Notes:</strong></p>
                        <ul style="text-align: left; margin-top: 10px;">
                            <li>Show artworks by same artist</li>
                            <li>Show artworks in same category</li>
                            <li>Show artworks with similar price range</li>
                            <li>Show recently viewed together items</li>
                        </ul>
                    </div>
                </div>
            `;
            
            modal.style.display = 'flex';
        }
        
        function closeRelatedModal() {
            document.getElementById('relatedModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        document.getElementById('artworkModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeArtworkModal();
            }
        });
        
        document.getElementById('relatedModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRelatedModal();
            }
        });

        // Initialize category chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('categoryChart').getContext('2d');
            
            // Sample data - in real application, this would come from PHP
            const categoryData = {
                labels: ['Paintings', 'Sculptures', 'Showpieces', 'Other'],
                datasets: [{
                    data: [65, 15, 15, 5],
                    backgroundColor: [
                        '#f43a09',
                        '#68d388', 
                        '#ffb766',
                        '#9b59b6'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            };
            
            const categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: categoryData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '60%'
                }
            });
        });
    </script>
</body>
</html>