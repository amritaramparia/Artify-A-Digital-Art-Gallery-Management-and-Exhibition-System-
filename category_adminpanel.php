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

// Handle add category
if (isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    
    if (!empty($category_name)) {
        // Check if category already exists
        $check_sql = "SELECT * FROM categories WHERE name = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $category_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $insert_sql = "INSERT INTO categories (name) VALUES (?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("s", $category_name);
            
            if ($stmt->execute()) {
                $category_message = "Category added successfully!";
            } else {
                $category_error = "Error adding category: " . $conn->error;
            }
        } else {
            $category_error = "Category already exists!";
        }
        $stmt->close();
    } else {
        $category_error = "Category name cannot be empty!";
    }
}

// Handle add subcategory
if (isset($_POST['add_subcategory'])) {
    $subcategory_name = trim($_POST['subcategory_name']);
    $category_id = intval($_POST['category_id']);
    
    if (!empty($subcategory_name) && $category_id > 0) {
        // Check if subcategory already exists in the same category
        $check_sql = "SELECT * FROM subcategories WHERE name = ? AND category_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("si", $subcategory_name, $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $insert_sql = "INSERT INTO subcategories (category_id, name) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("is", $category_id, $subcategory_name);
            
            if ($stmt->execute()) {
                $subcategory_message = "Subcategory added successfully!";
            } else {
                $subcategory_error = "Error adding subcategory: " . $conn->error;
            }
        } else {
            $subcategory_error = "Subcategory already exists in this category!";
        }
        $stmt->close();
    } else {
        $subcategory_error = "Subcategory name and category selection are required!";
    }
}

// Handle move subcategory to main category
if (isset($_POST['move_subcategory'])) {
    $subcategory_id = intval($_POST['subcategory_id']);
    $new_category_id = intval($_POST['new_category_id']);
    
    if ($subcategory_id > 0 && $new_category_id > 0) {
        $update_sql = "UPDATE subcategories SET category_id = ? WHERE subcategory_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $new_category_id, $subcategory_id);
        
        if ($stmt->execute()) {
            $move_message = "Subcategory moved successfully!";
        } else {
            $move_error = "Error moving subcategory: " . $conn->error;
        }
        $stmt->close();
    } else {
        $move_error = "Invalid subcategory or category selection!";
    }
}

// Handle delete category
if (isset($_GET['delete_category'])) {
    $category_id = intval($_GET['delete_category']);
    
    // First delete related subcategories
    $delete_subcategories_sql = "DELETE FROM subcategories WHERE category_id = ?";
    $stmt = $conn->prepare($delete_subcategories_sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    
    // Then delete the category
    $delete_sql = "DELETE FROM categories WHERE category_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $category_id);
    
    if ($stmt->execute()) {
        $delete_message = "Category and related subcategories deleted successfully!";
    } else {
        $delete_error = "Error deleting category: " . $conn->error;
    }
    $stmt->close();
}

// Handle delete subcategory
if (isset($_GET['delete_subcategory'])) {
    $subcategory_id = intval($_GET['delete_subcategory']);
    
    $delete_sql = "DELETE FROM subcategories WHERE subcategory_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $subcategory_id);
    
    if ($stmt->execute()) {
        $delete_message = "Subcategory deleted successfully!";
    } else {
        $delete_error = "Error deleting subcategory: " . $conn->error;
    }
    $stmt->close();
}

// Get all categories with their subcategories
$categories_sql = "SELECT c.*, 
                          COUNT(s.subcategory_id) as subcategory_count
                   FROM categories c 
                   LEFT JOIN subcategories s ON c.category_id = s.category_id 
                   GROUP BY c.category_id 
                   ORDER BY 
                     CASE 
                       WHEN c.name = 'Other' THEN 2
                       ELSE 1
                     END, c.name";
$categories_result = $conn->query($categories_sql);

// Get all categories for dropdown
$all_categories_sql = "SELECT * FROM categories WHERE category_id IN (1,2,3) ORDER BY name";
$all_categories_result = $conn->query($all_categories_sql);

// Get "Other" category subcategories for moving
$other_category_sql = "SELECT s.* FROM subcategories s 
                       JOIN categories c ON s.category_id = c.category_id 
                       WHERE c.name = 'Other' 
                       ORDER BY s.name";
$other_category_result = $conn->query($other_category_sql);

// Get statistics
$total_categories_sql = "SELECT COUNT(*) as total FROM categories";
$total_categories_result = $conn->query($total_categories_sql);
$total_categories = $total_categories_result->fetch_assoc()['total'];

$total_subcategories_sql = "SELECT COUNT(*) as total FROM subcategories";
$total_subcategories_result = $conn->query($total_subcategories_sql);
$total_subcategories = $total_subcategories_result->fetch_assoc()['total'];

$other_subcategories_count = $other_category_result->num_rows;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artify Admin Panel - Categories Management</title>
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
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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
        
        .category-icon {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
        }
        
        .subcategory-icon {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
        }
        
        .other-icon {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Form Styles */
        .form-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .form-title {
            margin-bottom: 20px;
            color: var(--dark);
            padding-bottom: 12px;
            border-bottom: 2px solid var(--orange2);
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--orange2);
            box-shadow: 0 0 0 3px rgba(255, 183, 102, 0.2);
            outline: none;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(244, 58, 9, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(104, 211, 136, 0.3);
        }
        
        /* Move Subcategory Section */
        .move-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .move-section .form-title {
            color: white;
            border-bottom-color: rgba(255,255,255,0.3);
        }
        
        .move-form {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr;
            gap: 15px;
            align-items: end;
        }
        
        /* Categories List */
        .section-title {
            margin: 30px 0 20px;
            color: var(--dark);
            padding-bottom: 12px;
            border-bottom: 2px solid var(--orange2);
            font-size: 1.4rem;
            font-weight: 700;
        }
        
        .category-item {
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .category-header {
            background: linear-gradient(135deg, var(--dark) 0%, #3d3d72 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .other-category .category-header {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
        }
        
        .category-name {
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        .category-count {
            background: var(--orange2);
            color: var(--dark);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .other-category .category-count {
            background: #e74c3c;
            color: white;
        }
        
        .subcategory-list {
            padding: 20px;
        }
        
        .subcategory-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s ease;
        }
        
        .subcategory-item:last-child {
            border-bottom: none;
        }
        
        .subcategory-item:hover {
            background-color: #f9f9f9;
        }
        
        .subcategory-name {
            font-size: 1.1rem;
            color: var(--dark);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
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
        
        .info {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .form-container {
                grid-template-columns: 1fr;
            }
            
            .move-form {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .category-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-danger, .btn-success {
                width: 100%;
            }
            
            .subcategory-item {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <h2>Categories Management</h2>
        </div>

        <!-- Notification Messages -->
        <?php if (isset($category_message)): ?>
            <div class="notification-message success">
                <i class="fas fa-check-circle"></i> <?php echo $category_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($category_error)): ?>
            <div class="notification-message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $category_error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($subcategory_message)): ?>
            <div class="notification-message success">
                <i class="fas fa-check-circle"></i> <?php echo $subcategory_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($subcategory_error)): ?>
            <div class="notification-message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $subcategory_error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($move_message)): ?>
            <div class="notification-message success">
                <i class="fas fa-check-circle"></i> <?php echo $move_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($move_error)): ?>
            <div class="notification-message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $move_error; ?>
            </div>
        <?php endif; ?>
        
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

        <!-- Statistics -->
        <div class="stats-container">
            <div class="card stat-card">
                <div class="card-icon category-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div class="number"><?php echo $total_categories; ?></div>
                    <div class="label">Total Categories</div>
                </div>
            </div>
            
            <div class="card stat-card">
                <div class="card-icon subcategory-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <div class="number"><?php echo $total_subcategories; ?></div>
                    <div class="label">Total Subcategories</div>
                </div>
            </div>
            
            <div class="card stat-card">
                <div class="card-icon other-icon">
                    <i class="fas fa-archive"></i>
                </div>
                <div>
                    <div class="number"><?php echo $other_subcategories_count; ?></div>
                    <div class="label">Subcategories in Other</div>
                </div>
            </div>
        </div>

        <!-- Move Subcategories from Other Category -->
        <?php if ($other_subcategories_count > 0): ?>
        <div class="move-section">
            <h3 class="form-title">
                <i class="fas fa-exchange-alt"></i> Move Subcategories from "Other" to Main Categories
            </h3>
            <form method="POST" action="" class="move-form">
                <div class="form-group">
                    <label for="subcategory_id">Select Subcategory from "Other"</label>
                    <select class="form-control" id="subcategory_id" name="subcategory_id" required>
                        <option value="">Select a subcategory</option>
                        <?php 
                        $conn = new mysqli($servername, $username, $password, $dbname);
                        $other_result = $conn->query($other_category_sql);
                        while($subcategory = $other_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $subcategory['subcategory_id']; ?>">
                                <?php echo htmlspecialchars($subcategory['name']); ?>
                            </option>
                        <?php 
                        endwhile;
                        $conn->close();
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="new_category_id">Move to Category</label>
                    <select class="form-control" id="new_category_id" name="new_category_id" required>
                        <option value="">Select target category</option>
                        <option value="1">Paintings</option>
                        <option value="2">Sculptures</option>
                        <option value="3">Showpieces & Decorative</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="move_subcategory" class="btn btn-success">
                        <i class="fas fa-arrow-right"></i> Move
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Add Forms -->
        <div class="form-container">
            <!-- Add Category Form -->
            <div class="form-card">
                <h3 class="form-title">Add New Category</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="category_name">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" 
                               placeholder="Enter category name" required>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </form>
            </div>

            <!-- Add Subcategory Form -->
            <div class="form-card">
                <h3 class="form-title">Add New Subcategory</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="category_id">Select Category</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php 
                            $conn = new mysqli($servername, $username, $password, $dbname);
                            $categories_dropdown = $conn->query($all_categories_sql);
                            while($category = $categories_dropdown->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php 
                            endwhile;
                            $conn->close();
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subcategory_name">Subcategory Name</label>
                        <input type="text" class="form-control" id="subcategory_name" name="subcategory_name" 
                               placeholder="Enter subcategory name" required>
                    </div>
                    <button type="submit" name="add_subcategory" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Subcategory
                    </button>
                </form>
            </div>
        </div>

        <!-- Categories List -->
        <h3 class="section-title">All Categories & Subcategories</h3>
        
        <?php if ($categories_result && $categories_result->num_rows > 0): ?>
            <?php while($category = $categories_result->fetch_assoc()): ?>
                <div class="category-item <?php echo $category['name'] == 'Other' ? 'other-category' : ''; ?>">
                    <div class="category-header">
                        <div class="category-name">
                            <?php echo htmlspecialchars($category['name']); ?>
                            <?php if ($category['name'] == 'Other'): ?>
                                <span style="font-size: 0.9rem; opacity: 0.8; display: block; margin-top: 5px;">
                                    <i class="fas fa-info-circle"></i> Temporary category for uncategorized items
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="category-count">
                            <?php echo $category['subcategory_count']; ?> Subcategories
                        </div>
                    </div>
                    
                    <div class="subcategory-list">
                        <?php
                        // Get subcategories for this category
                        $conn = new mysqli($servername, $username, $password, $dbname);
                        $subcategories_sql = "SELECT * FROM subcategories WHERE category_id = ? ORDER BY name";
                        $stmt = $conn->prepare($subcategories_sql);
                        $stmt->bind_param("i", $category['category_id']);
                        $stmt->execute();
                        $subcategories_result = $stmt->get_result();
                        
                        if ($subcategories_result->num_rows > 0):
                            while($subcategory = $subcategories_result->fetch_assoc()):
                        ?>
                            <div class="subcategory-item">
                                <div class="subcategory-name">
                                    <?php echo htmlspecialchars($subcategory['name']); ?>
                                </div>
                                <div class="action-buttons">
                                    <?php if ($category['name'] == 'Other'): ?>
                                        <button class="btn btn-success" 
                                                onclick="showMoveModal(<?php echo $subcategory['subcategory_id']; ?>, '<?php echo htmlspecialchars(addslashes($subcategory['name'])); ?>')">
                                            <i class="fas fa-arrow-right"></i> Move to Main
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-danger" 
                                            onclick="confirmDeleteSubcategory(<?php echo $subcategory['subcategory_id']; ?>, '<?php echo htmlspecialchars(addslashes($subcategory['name'])); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <div class="subcategory-item" style="text-align: center; color: #666;">
                                <i class="fas fa-info-circle"></i> No subcategories found for this category.
                            </div>
                        <?php
                        endif;
                        $stmt->close();
                        $conn->close();
                        ?>
                    </div>
                    
                    <?php if ($category['name'] != 'Other'): ?>
                    <div style="padding: 15px 20px; border-top: 1px solid #f0f0f0; text-align: right;">
                        <button class="btn btn-danger" 
                                onclick="confirmDeleteCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')">
                            <i class="fas fa-trash"></i> Delete Category
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <i class="fas fa-layer-group" style="font-size: 3rem; color: var(--orange2); margin-bottom: 15px;"></i>
                <h3>No Categories Found</h3>
                <p>There are currently no categories in the database.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Move Subcategory Modal -->
    <div id="moveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; width: 400px; max-width: 90%;">
            <h3 style="margin-bottom: 20px;">Move Subcategory</h3>
            <form method="POST" action="" id="moveForm">
                <input type="hidden" name="subcategory_id" id="modalSubcategoryId">
                <div class="form-group">
                    <label for="modalCategoryId">Move to Category</label>
                    <select class="form-control" id="modalCategoryId" name="new_category_id" required>
                        <option value="">Select target category</option>
                        <option value="1">Paintings</option>
                        <option value="2">Sculptures</option>
                        <option value="3">Showpieces & Decorative</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="hideMoveModal()" class="btn" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" name="move_subcategory" class="btn btn-success">Move</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Delete confirmation functions
        function confirmDeleteCategory(categoryId, categoryName) {
            if (confirm('Are you sure you want to delete the category "' + categoryName + '"?\n\nThis will also delete all subcategories under this category!\n\nThis action cannot be undone!')) {
                window.location.href = '?delete_category=' + categoryId;
            }
        }
        
        function confirmDeleteSubcategory(subcategoryId, subcategoryName) {
            if (confirm('Are you sure you want to delete the subcategory "' + subcategoryName + '"?\n\nThis action cannot be undone!')) {
                window.location.href = '?delete_subcategory=' + subcategoryId;
            }
        }
        
        // Move subcategory modal functions
        function showMoveModal(subcategoryId, subcategoryName) {
            document.getElementById('modalSubcategoryId').value = subcategoryId;
            document.getElementById('moveModal').style.display = 'flex';
        }
        
        function hideMoveModal() {
            document.getElementById('moveModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('moveModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideMoveModal();
            }
        });
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const inputs = this.querySelectorAll('input[required], select[required]');
                    let valid = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            valid = false;
                            input.style.borderColor = 'var(--orange1)';
                        } else {
                            input.style.borderColor = '#e0e0e0';
                        }
                    });
                    
                    if (!valid) {
                        e.preventDefault();
                        alert('Please fill in all required fields!');
                    }
                });
            });
        });
    </script>
</body>
</html>