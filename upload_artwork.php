<?php
session_start(); 

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "art";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}

$errors = [];
$success_message = '';
$max_file_size = 5 * 1024 * 1024; 
$upload_dir = 'images/artworks/';

// --- Get logged-in artist ID from session ---
if (isset($_SESSION['artist_id'])) {
    $uploaded_artist_id = $_SESSION['artist_id'];
} else {
    // For testing, you can hardcode an artist ID that exists in your database
    // But in production, redirect to login if no session
    $uploaded_artist_id = 1; // Temporary for testing
    // Uncomment the line below in production:
    // header("Location: login.php");
    // exit();
}

// --- 1. Fetch Categories for the main dropdown ---
$categories = [];
$cat_query = "SELECT category_id, name FROM categories ORDER BY name";
$cat_result = $conn->query($cat_query);
if ($cat_result) {
    while($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    $errors[] = "Error fetching categories: " . $conn->error;
}

// Store submitted data for sticky form fields
$form_data = $_POST;

// --- 2. Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and validate inputs
    $title = trim($form_data['title'] ?? '');
    $description = trim($form_data['description'] ?? '');
    $price = filter_var($form_data['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $quantity = filter_var($form_data['quantity'] ?? 0, FILTER_VALIDATE_INT);
    $category_id = filter_var($form_data['category_id'] ?? 0, FILTER_VALIDATE_INT);
    $subcategory_id = filter_var($form_data['subcategory_id'] ?? 0, FILTER_VALIDATE_INT);
    $medium = trim($form_data['medium'] ?? '');
    $width = filter_var($form_data['width_cm'] ?? 0, FILTER_VALIDATE_FLOAT);
    $height = filter_var($form_data['height_cm'] ?? 0, FILTER_VALIDATE_FLOAT);
    $depth = filter_var($form_data['depth_cm'] ?? 0, FILTER_VALIDATE_FLOAT);

    // Basic validation
    if (empty($title)) $errors[] = "Artwork Title is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if ($price === false || $price < 0) $errors[] = "A valid Price is required.";
    if ($quantity === false || $quantity < 1) $errors[] = "Quantity must be at least 1.";
    if ($category_id === false || $category_id == 0) $errors[] = "Category is required.";
    if ($subcategory_id === false || $subcategory_id == 0) $errors[] = "Style/Subcategory is required.";
    if (empty($medium)) $errors[] = "Medium is required (e.g., Oil on Canvas).";
    if ($width === false || $width <= 0) $errors[] = "Valid Width (cm) is required.";
    if ($height === false || $height <= 0) $errors[] = "Valid Height (cm) is required.";

    $image_url = null;

    // --- File Upload Handling ---
    if (empty($errors)) {
        
        // 1. Ensure upload directory exists and is writable
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $errors[] = "Failed to create upload directory '$upload_dir'. Check file permissions.";
            }
        }
        
        // 2. Check if directory is writable
        if (is_dir($upload_dir) && !is_writable($upload_dir)) {
            $errors[] = "Upload directory '$upload_dir' is not writable. Check folder permissions.";
        }
        
        // 3. Process the file
        if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] === UPLOAD_ERR_OK) {
            
            $file = $_FILES['artwork_image'];
            $file_info = pathinfo($file['name']);
            $file_extension = strtolower($file_info['extension'] ?? '');
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($file_extension, $allowed_ext)) {
                $errors[] = "Invalid file type. Only JPG, PNG, GIF, WEBP are allowed.";
            } else if ($file['size'] > $max_file_size) {
                $errors[] = "File is too large. Max size is 5MB.";
            } else {
                // Create a unique file name
                $new_filename = uniqid('art_') . '_' . time() . '.' . $file_extension;
                $target_file = $upload_dir . $new_filename;

                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    $image_url = $target_file;
                    error_log("File uploaded successfully to: " . $target_file);
                } else {
                    $errors[] = "Failed to move uploaded file. Check folder permissions for '$upload_dir'.";
                    error_log("File upload failed. Temp: " . $file['tmp_name'] . " -> Target: " . $target_file);
                }
            }
        } else {
            $upload_error = $_FILES['artwork_image']['error'] ?? 'unknown';
            if ($upload_error === UPLOAD_ERR_NO_FILE) {
                $errors[] = "Artwork image is required.";
            } else {
                $errors[] = "Artwork image upload failed. Error code: " . $upload_error;
            }
        }
    }

    // --- Debug: Check what we have before database insertion ---
    error_log("Debug - Title: $title, Artist ID: $uploaded_artist_id, Category: $category_id, Subcategory: $subcategory_id");
    error_log("Debug - Image URL: " . ($image_url ?? 'NULL'));

    // --- 3. Insert into Database using Prepared Statement ---
    if (empty($errors) && $image_url) {
        $insert_query = "INSERT INTO artworks 
            (title, artist_id, category_id, subcategory_id, description, price, stock_quantity, image_url, medium, width_cm, height_cm, depth_cm, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($insert_query);
        
        if ($stmt) {
            $stmt->bind_param("siiisdisdddd", 
                $title, $uploaded_artist_id, $category_id, $subcategory_id, $description, $price, $quantity, $image_url, $medium, $width, $height, $depth
            );
            
            if ($stmt->execute()) {
                $success_message = "Artwork submitted successfully!";
                // Clear form data after success
                $form_data = [];
            } else {
                $errors[] = "Database insertion failed: " . $stmt->error;
                // Clean up the uploaded file if DB insertion failed
                if (file_exists($image_url)) {
                    unlink($image_url);
                }
            }
            $stmt->close();
        } else {
            $errors[] = "SQL Prepare Error: " . $conn->error;
        }
    } else {
        error_log("Form errors: " . implode(", ", $errors));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Artwork - Art Gallery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        
        :root { 
            --orange1: #f43a09; 
            --orange2: #ffb766; 
            --bluegreen: #c2edda; 
            --green: #68d388; 
            --dark: #2c2c54; 
            --light: #f5f5f5; }
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Poppins', 
            sans-serif;
         }

        body {
         background-color: var(--light); 
         color: var(--dark);
         line-height: 1.6; }
        .submission-container { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
        .submission-header { text-align: center; margin-bottom: 40px; }
        .submission-header h1 { color: var(--dark); font-size: 2.5rem; font-weight: 600; margin-bottom: 10px; }
        .submission-header p { color: var(--dark); opacity: 0.8; font-size: 1.1rem; }
        .submission-form { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 8px 25px rgba(44, 44, 84, 0.1); }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark); font-size: 1rem; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; transition: all 0.3s ease; background-color: var(--light); }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--orange1); background-color: white; }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .file-upload { border: 2px dashed #e0e0e0; padding: 30px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; }
        .file-upload:hover { border-color: var(--orange1); background-color: rgba(244, 58, 9, 0.05); }
        .file-upload input { display: none; }
        .file-upload-label { display: flex; flex-direction: column; align-items: center; gap: 10px; cursor: pointer; }
        .upload-icon { font-size: 2rem; color: var(--orange1); }
        .file-name { margin-top: 10px; font-size: 0.9rem; color: var(--dark); opacity: 0.7; }
        .submit-btn { background: var(--orange1); color: white; border: none; padding: 15px 40px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; width: 100%; margin-top: 10px; }
        .submit-btn:hover { background: var(--dark); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(44, 44, 84, 0.2); }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .dimension-group { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .dimension-group input { padding: 8px; text-align: center; }
        .dimension-label { font-size: 0.9rem; color: #555; margin-top: 5px; text-align: center; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .submission-form { padding: 30px 20px; }
            .submission-header h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="submission-container">
        <div class="submission-header">
            <h1>Submit Your Artwork</h1>
            <p>Share your creativity with the world</p>
        </div>

        <div class="submission-form">
            <?php 
            if (!empty($errors)) {
                echo "<div class='message error'><strong>Submission Failed:</strong><ul>";
                foreach ($errors as $err) {
                    echo "<li>" . htmlspecialchars($err) . "</li>";
                }
                echo "</ul></div>";
            }
            if (!empty($success_message)) {
                echo "<div class='message success'>{$success_message}</div>";
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label for="title">Artwork Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($form_data['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (Rs.) *</label>
                        <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($form_data['price'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Stock Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="1" step="1" value="<?php echo htmlspecialchars($form_data['quantity'] ?? 1); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required onchange="loadSubcategories(this.value)">
                            <option value="">Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" 
                                    <?php echo (isset($form_data['category_id']) && $form_data['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="subcategory_id">Style / Subcategory *</label>
                        <select id="subcategory_id" name="subcategory_id" required>
                            <option value="">Select Category First</option>
                            </select>
                    </div>
                </div>

                <div class="form-row">
                     <div class="form-group">
                        <label for="medium">Medium *</label>
                        <input type="text" id="medium" name="medium" value="<?php echo htmlspecialchars($form_data['medium'] ?? ''); ?>" placeholder="e.g., Oil on Canvas, Digital" required>
                    </div>
                    
                    <div class="form-group" style="grid-column: span 1;">
                        <label>Size / Dimensions (cm) *</label>
                        <div class="dimension-group">
                            <div>
                                <input type="number" id="width_cm" name="width_cm" min="0.01" step="0.01" value="<?php echo htmlspecialchars($form_data['width_cm'] ?? ''); ?>" required>
                                <div class="dimension-label">Width</div>
                            </div>
                            <div>
                                <input type="number" id="height_cm" name="height_cm" min="0.01" step="0.01" value="<?php echo htmlspecialchars($form_data['height_cm'] ?? ''); ?>" required>
                                <div class="dimension-label">Height</div>
                            </div>
                            <div>
                                <input type="number" id="depth_cm" name="depth_cm" min="0.00" step="0.01" value="<?php echo htmlspecialchars($form_data['depth_cm'] ?? 0); ?>" required>
                                <div class="dimension-label">Depth</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Upload Artwork Image (Required) *</label>
                    <div class="file-upload">
                        <input type="file" id="artwork_image" name="artwork_image" accept="image/jpeg,image/png,image/gif,image/webp" required onchange="displayFileName(this, 'file-name')">
                        <label for="artwork_image" class="file-upload-label">
                            <span class="upload-icon">🖼️</span>
                            <span>Click to upload artwork image</span>
                            <span style="font-size: 0.8rem; opacity: 0.7;">Supported formats: JPG, PNG, JPEG, GIF, WebP (Max 5MB)</span>
                        </label>
                        <div id="file-name" class="file-name"></div>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Submit Artwork</button>
            </form>
        </div>
    </div>

    <script>
       
        function loadSubcategories(categoryId) {
            const subcategorySelect = document.getElementById('subcategory_id');
            const previouslySelected = "<?php echo htmlspecialchars($form_data['subcategory_id'] ?? ''); ?>";

            if (!categoryId) {
                subcategorySelect.innerHTML = '<option value="">Select Category First</option>';
                return;
            }
            
            subcategorySelect.innerHTML = '<option value="">Loading Styles...</option>';
            
            // AJAX request to get_subcategories.php (Assuming this file still exists)
            fetch('get_subcategories.php?category_id=' + categoryId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    subcategorySelect.innerHTML = '<option value="">Select Style/Subcategory</option>';
                    if (data.length === 0) {
                         subcategorySelect.innerHTML += '<option disabled>No styles found for this category</option>';
                    } else {
                         data.forEach(subcat => {
                            const option = document.createElement('option');
                            option.value = subcat.subcategory_id;
                            option.textContent = subcat.name;
                            // Re-select if applicable (on form error)
                            if (subcat.subcategory_id == previouslySelected) {
                                option.selected = true;
                            }
                            subcategorySelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading subcategories:', error);
                    subcategorySelect.innerHTML = '<option value="">Error loading styles</option>';
                });
        }

        /**
         * Displays the selected file's name and size.
         */
        function displayFileName(input, targetId) {
            const fileNameDisplay = document.getElementById(targetId);
            if (input.files.length > 0) {
                const file = input.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // in MB
                fileNameDisplay.textContent = `Selected file: ${file.name} (${fileSize} MB)`;
            } else {
                fileNameDisplay.textContent = '';
            }
        }
        
        // Initial load check for sticky form fields (in case of submission error)
        document.addEventListener('DOMContentLoaded', () => {
            const initialCategoryId = document.getElementById('category_id').value;
            if (initialCategoryId) {
                // Manually trigger subcategory load if a category was selected
                loadSubcategories(initialCategoryId);
            }
        });
    </script>
</body>
</html>