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

// Handle remove from cart
if (isset($_POST['remove_from_cart']) && isset($_SESSION['user_id'])) {
    $cart_id = intval($_POST['cart_id']);
    $user_id = $_SESSION['user_id'];
    
    // Verify ownership before deleting
    $check_sql = "SELECT * FROM cart WHERE cart_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $delete_sql = "DELETE FROM cart WHERE cart_id = ?";
        $stmt2 = $conn->prepare($delete_sql);
        $stmt2->bind_param("i", $cart_id);
        if ($stmt2->execute()) {
            $_SESSION['success_message'] = "Item removed from cart successfully!";
        } else {
            $_SESSION['error_message'] = "Error removing item from cart.";
        }
        $stmt2->close();
    } else {
        $_SESSION['error_message'] = "Item not found in your cart.";
    }
    $stmt->close();
    header("Location: cart.php");
    exit();
}

// Handle update quantity via AJAX
if (isset($_POST['update_quantity_ajax']) && isset($_SESSION['user_id'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    $user_id = $_SESSION['user_id'];
    
    // Verify ownership and update
    $check_sql = "SELECT * FROM cart WHERE cart_id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        if ($quantity > 0) {
            $update_sql = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
            $stmt2 = $conn->prepare($update_sql);
            $stmt2->bind_param("ii", $quantity, $cart_id);
            if ($stmt2->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
            }
            $stmt2->close();
        } else {
            // Remove item if quantity is 0
            $delete_sql = "DELETE FROM cart WHERE cart_id = ?";
            $stmt2 = $conn->prepare($delete_sql);
            $stmt2->bind_param("i", $cart_id);
            if ($stmt2->execute()) {
                echo json_encode(['success' => true, 'removed' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Remove failed']);
            }
            $stmt2->close();
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Item not found']);
    }
    $stmt->close();
    exit();
}

// Fetch cart items for the logged-in user
$cart_items = [];
$cart_total = 0;
$cart_count = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT c.cart_id, c.quantity, a.artwork_id, a.title, a.price, a.image_url, ar.artist_name, a.stock_quantity, ar.artist_id 
            FROM cart c 
            JOIN artworks a ON c.artwork_id = a.artwork_id 
            JOIN artists ar ON a.artist_id = ar.artist_id 
            WHERE c.user_id = ? 
            ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while($item = $result->fetch_assoc()) {
            $item_total = $item['price'] * $item['quantity'];
            $cart_total += $item_total;
            $cart_count += $item['quantity'];
            $cart_items[] = $item;
        }
    }
    $stmt->close();
}

// Store cart data in session for checkout
$_SESSION['cart_total'] = $cart_total;
$_SESSION['cart_count'] = $cart_count;
$_SESSION['cart_items'] = $cart_items;

// Calculate only subtotal and tax in cart (shipping will be calculated in checkout)
$tax_rate = 0.05; // 5% GST for India
$tax = $cart_total * $tax_rate;
$subtotal = $cart_total;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | Art Gallery</title>
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

        .cart-container {
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

        .cart-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
        }

        .cart-items {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.15);
        }

        .cart-summary {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.15);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto auto;
            gap: 20px;
            padding: 25px 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--bluegreen), var(--green));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-size: 0.9rem;
            text-align: center;
            padding: 10px;
        }

        .item-details {
            flex: 1;
        }

        .item-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
            cursor: pointer;
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
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-input {
            width: 80px;
            padding: 10px;
            border: 2px solid var(--bluegreen);
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .quantity-input:focus {
            outline: none;
            border-color: var(--orange1);
        }

        .remove-form {
            display: inline;
        }

        .remove-btn {
            background: var(--orange1);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            background: #d63208;
            transform: translateY(-2px);
        }

        .summary-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .summary-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--orange1);
            margin: 25px 0;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }

        .checkout-btn {
            background: linear-gradient(135deg, var(--orange1), var(--orange2));
            color: white;
            border: none;
            padding: 18px;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .checkout-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(244, 58, 9, 0.4);
        }

        .checkout-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
        }

        .continue-shopping {
            display: block;
            text-align: center;
            color: var(--dark);
            text-decoration: none;
            padding: 15px;
            border: 2px solid var(--dark);
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .continue-shopping:hover {
            background: var(--dark);
            color: white;
            transform: translateY(-2px);
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1 / -1;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(44, 44, 84, 0.1);
        }

        .empty-cart-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ccc;
        }

        .empty-cart h2 {
            color: var(--dark);
            margin-bottom: 15px;
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

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @media (max-width: 968px) {
            .cart-content {
                grid-template-columns: 1fr;
            }
            
            .cart-summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 100px 1fr;
                gap: 15px;
            }
            
            .quantity-controls {
                grid-column: 1 / -1;
                justify-content: space-between;
                margin-top: 15px;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="cart-container">
        <div class="page-header">
            <h1 class="page-title">Shopping Cart</h1>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <script>
                showAlert('<?php echo $_SESSION['success_message']; ?>', 'success');
            </script>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <script>
                showAlert('<?php echo $_SESSION['error_message']; ?>', 'error');
            </script>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2>Your cart is empty</h2>
                <p>Browse our collection and add some beautiful artworks to your cart!</p>
                <a href="artgallary.php" class="continue-shopping" style="margin-top: 20px; display: inline-block; width: auto;">
                    Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items" id="cart-items-container">
                    <?php foreach ($cart_items as $item): 
                        $item_total = $item['price'] * $item['quantity'];
                    ?>
                        <div class="cart-item" id="cart-item-<?php echo $item['cart_id']; ?>">
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
                                        echo "<div class='image-placeholder'>Image<br>Not Found</div>";
                                    }
                                } else {
                                    echo "<div class='image-placeholder'>No Image</div>";
                                }
                                ?>
                            </div>
                            
                            <div class="item-details">
                                <div class="item-title" onclick="viewArtworkDetails(<?php echo $item['artwork_id']; ?>)">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </div>
                                <div class="item-artist">by <?php echo htmlspecialchars($item['artist_name']); ?></div>
                                <div class="item-price">Rs.<span class="item-price-value" id="price-<?php echo $item['cart_id']; ?>"><?php echo number_format($item_total, 2); ?></span></div>
                            </div>
                            
                            <div class="quantity-controls">
                                <input type="number" 
                                       class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" 
                                       max="<?php echo $item['stock_quantity']; ?>"
                                       data-cart-id="<?php echo $item['cart_id']; ?>"
                                       data-price="<?php echo $item['price']; ?>"
                                       onchange="updateQuantity(<?php echo $item['cart_id']; ?>, this.value)">
                                
                                <form method="POST" class="remove-form">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="submit" name="remove_from_cart" class="remove-btn">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h2 class="summary-title">Order Summary</h2>
                    
                    <div class="summary-row">
                        <span>Items (<span id="summary-count"><?php echo $cart_count; ?></span>):</span>
                        <span>Rs.<span id="summary-subtotal"><?php echo number_format($subtotal, 2); ?></span></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax (5% GST):</span>
                        <span>Rs.<span id="summary-tax"><?php echo number_format($tax, 2); ?></span></span>
                    </div>
                    
                    <div class="summary-row">
                        <span style="color: #666; font-size: 0.9rem; font-style: italic;">
                            <i class="fas fa-info-circle"></i> Shipping will be calculated at checkout
                        </span>
                        <span style="color: #666; font-size: 0.9rem;">--</span>
                    </div>
                    
                    <div class="summary-total">
                        <span>Subtotal:</span>
                        <span>Rs.<span id="summary-total"><?php echo number_format($subtotal + $tax, 2); ?></span></span>
                    </div>
                    
                    <a href="checkout-cart.php" class="continue-shopping" id="checkout-btn" <?php echo $cart_count == 0 ? 'disabled' : ''; ?>>
                        Proceed to Checkout (Rs.<span id="checkout-total"><?php echo number_format($subtotal + $tax, 2); ?></span>)
                    </a>
                    
                    <a href="artgallary.php" class="continue-shopping">
                        Continue Shopping
                    </a>
                </div>
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

        function updateQuantity(cartId, newQuantity) {
            const quantityInput = document.querySelector(`[data-cart-id="${cartId}"]`);
            const cartItem = document.getElementById(`cart-item-${cartId}`);
            const price = parseFloat(quantityInput.dataset.price);
            
            // Show loading state
            cartItem.classList.add('loading');
            
            // Update individual item price immediately for better UX
            const itemTotal = price * newQuantity;
            document.getElementById(`price-${cartId}`).textContent = itemTotal.toFixed(2);
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('update_quantity_ajax', 'true');
            formData.append('cart_id', cartId);
            formData.append('quantity', newQuantity);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.removed) {
                        // Remove item from DOM
                        cartItem.remove();
                        showAlert('Item removed from cart', 'success');
                    } else {
                        showAlert('Quantity updated successfully', 'success');
                    }
                    // Reload page to get updated totals
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert(data.error || 'Error updating quantity', 'error');
                    // Reset input to original value
                    quantityInput.value = quantityInput.defaultValue;
                    // Reset price display
                    const originalTotal = price * quantityInput.defaultValue;
                    document.getElementById(`price-${cartId}`).textContent = originalTotal.toFixed(2);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Network error. Please try again.', 'error');
                quantityInput.value = quantityInput.defaultValue;
                // Reset price display
                const originalTotal = price * quantityInput.defaultValue;
                document.getElementById(`price-${cartId}`).textContent = originalTotal.toFixed(2);
            })
            .finally(() => {
                cartItem.classList.remove('loading');
            });
        }

        // Real-time input validation
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('quantity-input')) {
                const cartId = e.target.dataset.cartId;
                const newQuantity = parseInt(e.target.value);
                const price = parseFloat(e.target.dataset.price);
                const maxStock = parseInt(e.target.max);
                
                if (newQuantity > maxStock) {
                    e.target.value = maxStock;
                    showAlert(`Maximum available stock is ${maxStock}`, 'error');
                }
                
                if (newQuantity < 1) {
                    e.target.value = 1;
                }
                
                // Update individual item total in real-time
                const validQuantity = Math.min(Math.max(1, newQuantity), maxStock);
                const itemTotal = price * validQuantity;
                document.getElementById(`price-${cartId}`).textContent = itemTotal.toFixed(2);
            }
        });
    </script>
    
    <?php include('footer.html'); ?>
</body>
</html>