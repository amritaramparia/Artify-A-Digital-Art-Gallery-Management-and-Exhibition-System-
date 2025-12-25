<?php
session_start();

// Security checks
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['cart_count']) || $_SESSION['cart_count'] == 0) {
    header("Location: cart.php");
    exit();
}

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

// Fetch user details
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT first_name, last_name, email FROM register_data WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();
$stmt->close();

// Use cart data from session
$cart_items = $_SESSION['cart_items'];
$cart_total = $_SESSION['cart_total'];
$cart_count = $_SESSION['cart_count'];

// Calculate base totals
$tax_rate = 0.05; // 5% GST for India
$tax = $cart_total * $tax_rate;
$subtotal = $cart_total;
$shipping = 0; // Will be calculated based on country

// Initialize errors array
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_checkout'])) {
    // Validate and sanitize input
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $address2 = trim($_POST['address2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pin_code = trim($_POST['pin_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $shipping_method = $_POST['shipping'] ?? 'standard';
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    
    // Basic validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State is required";
    if (empty($pin_code)) $errors[] = "PIN code is required";
    if (empty($country)) $errors[] = "Country is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    
    // Validate phone number format
    if (!empty($phone) && !preg_match('/^[6-9]\d{9}$/', preg_replace('/\D/', '', $phone))) {
        $errors[] = "Please enter a valid 10-digit Indian phone number";
    }
    
    // Validate PIN code format
    if (!empty($pin_code) && !preg_match('/^\d{6}$/', $pin_code)) {
        $errors[] = "Please enter a valid 6-digit PIN code";
    }
    
    // Calculate shipping based on country and method
    if (empty($errors)) {
        if ($country === 'India') {
            $shipping = ($shipping_method === 'express') ? 200 : 0;
        } else {
            $shipping = ($shipping_method === 'express') ? 1245 : 560;
        }
        
        // Recalculate total amount with shipping
        $total_amount = $subtotal + $tax + $shipping;
        
        // Debug: Check if cart items exist
        if (empty($cart_items)) {
            $errors[] = "Your cart is empty. Please add items before checkout.";
        }
    }
    
    if (empty($errors)) {
        // Process order for all cart items
        $conn->begin_transaction();
        
        try {
            $order_ids = []; // Store all created order IDs
            
            // Create order for each item (since each may have different artists)
            foreach ($cart_items as $item) {
                // Calculate item total
                $item_total = $item['price'] * $item['quantity'];
                $item_tax = $item_total * $tax_rate;
                
                // Calculate shipping cost per item (proportional)
                $item_shipping_ratio = ($subtotal > 0) ? ($item_total / $subtotal) : 1;
                $item_shipping = $shipping * $item_shipping_ratio;
                
                // Calculate artist amount (80% of price) and admin commission (20%)
                $artist_amount = $item_total * 0.80;
                $admin_commission = $item_total * 0.20;
                
                // Final amount for this item
                $item_final_amount = $item_total + $item_tax + $item_shipping;
                
                // Create order for this item
                $order_sql = "INSERT INTO orders (user_id, artist_id, artwork_id, total_amount, shipping_amount, tax_amount, final_amount, artist_amount, admin_commission, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                $stmt = $conn->prepare($order_sql);
                $stmt->bind_param("iiidddddd", 
                    $user_id, 
                    $item['artist_id'], 
                    $item['artwork_id'],
                    $item_total, // total_amount (item price)
                    $item_shipping,   // shipping_amount for this item
                    $item_tax,   // tax_amount for this item
                    $item_final_amount, // final_amount for this item
                    $artist_amount, // artist_amount
                    $admin_commission // admin_commission
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create order for item: " . $item['title']);
                }
                
                $order_id = $stmt->insert_id;
                $order_ids[] = $order_id;
                
                // Insert order details
                $detail_sql = "INSERT INTO order_details (order_id, first_name, last_name, email, phone, address, city, state, pin_code, country, shipping_method, payment_method) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'online')";
                $stmt2 = $conn->prepare($detail_sql);
                $stmt2->bind_param("issssssssss", 
                    $order_id,
                    $first_name,
                    $last_name,
                    $user['email'],
                    $phone,
                    $address,
                    $city,
                    $state,
                    $pin_code,
                    $country,
                    $shipping_method
                );
                
                if (!$stmt2->execute()) {
                    throw new Exception("Failed to create order details");
                }
                $stmt2->close();
                $stmt->close();
                
                // Create notification for artist
                $notification_sql = "INSERT INTO notifications (artist_id, order_id, message) 
                                   VALUES (?, ?, ?)";
                $stmt3 = $conn->prepare($notification_sql);
                $message = "New order received for your artwork: " . $item['title'] . ". Order ID: " . $order_id;
                $stmt3->bind_param("iis", $item['artist_id'], $order_id, $message);
                $stmt3->execute();
                $stmt3->close();
            }
            
            // Clear cart after successful orders
            $clear_cart_sql = "DELETE FROM cart WHERE user_id = ?";
            $stmt4 = $conn->prepare($clear_cart_sql);
            $stmt4->bind_param("i", $user_id);
            $stmt4->execute();
            $stmt4->close();
            
            // Store order data in session for payment process
            $_SESSION['order_data'] = [
                'order_ids' => $order_ids,
                'total_amount' => $total_amount,
                'shipping' => $shipping,
                'tax' => $tax,
                'subtotal' => $subtotal,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $user['email'],
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'pin_code' => $pin_code,
                'shipping_method' => $shipping_method,
                'cart_items' => $cart_items,
                'cart_count' => $cart_count
            ];
            
            $_SESSION['order_success'] = true;
            
            $conn->commit();
            
            // Redirect to payment process
            header("Location: payment-process.php?source=cart");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error processing order. Please try again. Error: " . $e->getMessage();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Art Gallery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            padding: 20px;
            min-height: 100vh;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
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

        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
        }

        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--dark);
            border-bottom: 2px solid var(--bluegreen);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--orange1);
        }

        .user-email {
            background-color: var(--light);
            padding: 12px;
            border-radius: 5px;
            border: 2px solid #ddd;
            font-weight: 500;
            color: var(--dark);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .checkbox-group input {
            width: auto;
        }

        .shipping-method,
        .payment-method {
            background: rgba(194, 237, 218, 0.2);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .method-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .method-option.selected {
            border-color: var(--orange1);
            background-color: rgba(244, 58, 9, 0.05);
        }

        .method-option input {
            margin-right: 10px;
        }

        .method-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .method-price {
            font-weight: bold;
            color: var(--green);
        }

        .artwork-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .artwork-image {
            width: 80px;
            height: 80px;
            border-radius: 5px;
            overflow: hidden;
        }

        .artwork-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .artwork-details {
            flex: 1;
        }

        .artwork-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .artwork-artist {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .artwork-price {
            font-weight: bold;
            color: var(--orange1);
        }

        .artwork-quantity {
            color: #666;
            font-size: 0.9rem;
        }

        .price-breakdown {
            margin: 20px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .price-row.total {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--dark);
            border-bottom: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid var(--bluegreen);
        }

        .pay-now-btn {
            width: 100%;
            background: var(--orange1);
            color: white;
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .pay-now-btn:hover {
            background: var(--dark);
            transform: translateY(-2px);
        }

        .pay-now-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
        }

        .secure-payment {
            text-align: center;
            margin-top: 15px;
            color: #666;
            font-size: 0.9rem;
        }

        .payment-icons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 10px 0;
        }

        .payment-icon {
            background: var(--light);
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .error-message {
            text-align: center;
            padding: 20px;
            background: rgba(244, 58, 9, 0.1);
            border-radius: 5px;
            margin: 20px 0;
            color: var(--orange1);
            font-weight: 600;
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

        .alert-error {
            background: var(--orange1);
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: static;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php');?>
    
    <div class="checkout-container">
        <div class="checkout-form">
            <a href="cart.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Cart
            </a>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <h1 class="section-title">Contact</h1>
            
            <form id="checkoutForm" method="POST" action="">
                <input type="hidden" name="process_checkout" value="1">
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="user-email" id="userEmail">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                    <small style="color: #666; font-size: 0.9rem;">Email cannot be changed for order communication</small>
                </div>

                <h1 class="section-title">Delivery</h1>
                
                <div class="form-group">
                    <label for="country">Country/Region *</label>
                    <select id="country" name="country" required>
                        <option value="">Select Country</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First name *</label>
                        <input type="text" id="first_name" name="first_name" required 
                               placeholder="Enter your first name" 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : htmlspecialchars($user['first_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last name *</label>
                        <input type="text" id="last_name" name="last_name" required 
                               placeholder="Enter your last name" 
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : htmlspecialchars($user['last_name']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="company">Company (optional)</label>
                    <input type="text" id="company" name="company" placeholder="Optional" 
                           value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="address">Address *</label>
                    <input type="text" id="address" name="address" required 
                           placeholder="Street address" 
                           value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="address2">Apartment, suite, etc. (optional)</label>
                    <input type="text" id="address2" name="address2" placeholder="Optional"
                           value="<?php echo isset($_POST['address2']) ? htmlspecialchars($_POST['address2']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <select id="city" name="city" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="state">State/Province *</label>
                        <select id="state" name="state" required>
                            <option value="">Select State</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="pin_code">ZIP/Postal Code *</label>
                        <input type="text" id="pin_code" name="pin_code" required 
                               placeholder="Enter postal code" 
                               value="<?php echo isset($_POST['pin_code']) ? htmlspecialchars($_POST['pin_code']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone" required 
                               placeholder="Enter phone number" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="save_address" name="save_address" checked>
                    <label for="save_address">Save this information for next time</label>
                </div>

                <h1 class="section-title">Shipping method</h1>
                
                <div class="shipping-method">
                    <div class="method-option <?php echo (!isset($_POST['shipping']) || $_POST['shipping'] === 'standard') ? 'selected' : ''; ?>" 
                         onclick="selectShipping('standard')">
                        <div class="method-info">
                            <input type="radio" name="shipping" value="standard" 
                                   <?php echo (!isset($_POST['shipping']) || $_POST['shipping'] === 'standard') ? 'checked' : ''; ?>>
                            <span>Standard Shipping</span>
                        </div>
                        <div class="method-price" id="shippingPrice">--</div>
                    </div>
                    <div class="method-option <?php echo (isset($_POST['shipping']) && $_POST['shipping'] === 'express') ? 'selected' : ''; ?>" 
                         onclick="selectShipping('express')">
                        <div class="method-info">
                            <input type="radio" name="shipping" value="express" 
                                   <?php echo (isset($_POST['shipping']) && $_POST['shipping'] === 'express') ? 'checked' : ''; ?>>
                            <span>Express Shipping</span>
                        </div>
                        <div class="method-price" id="expressShippingPrice">--</div>
                    </div>
                </div>

                <h1 class="section-title">Payment</h1>
                
                <div class="payment-method">
                    <div class="secure-payment">
                        All transactions are secure and encrypted.
                    </div>
                    
                    <div class="method-option selected">
                        <div class="method-info">
                            <input type="radio" name="payment" value="online" checked>
                            <span>Online Payment (Cards, UPI, Net Banking)</span>
                        </div>
                    </div>
                    
                    <div class="payment-icons">
                        <div class="payment-icon">UPI</div>
                        <div class="payment-icon">NetBanking</div>
                        <div class="payment-icon">Cards</div>
                    </div>
                    
                    <div class="secure-payment">
                        After clicking "Pay now", you will be redirected to complete your purchase securely.
                    </div>
                </div>

                <input type="hidden" name="total_amount" id="total_amount" value="<?php echo number_format($subtotal + $tax, 2); ?>">

                <button type="submit" class="pay-now-btn" id="submitBtn">
                    Pay Now - Rs.<span id="displayTotal"><?php echo number_format($subtotal + $tax, 2); ?></span>
                </button>
            </form>
        </div>

        <div class="order-summary">
            <h2 class="section-title">Order Summary</h2>
            
            <?php if (!empty($cart_items)): ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class='artwork-item'>
                        <div class='artwork-image'>
                            <?php
                            $image_url = $item['image_url'];
                            $imageDisplay = "";
                            if (!empty($image_url)) {
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
                                    $imageDisplay = "<img src='{$found_path}' alt='{$item['title']}' />";
                                } else {
                                    $imageDisplay = "<div style='width:80px;height:80px;background:var(--bluegreen);display:flex;align-items:center;justify-content:center;border-radius:5px;color:var(--dark);font-size:12px;'>Image</div>";
                                }
                            } else {
                                $imageDisplay = "<div style='width:80px;height:80px;background:var(--bluegreen);display:flex;align-items:center;justify-content:center;border-radius:5px;color:var(--dark);font-size:12px;'>Art</div>";
                            }
                            echo $imageDisplay;
                            ?>
                        </div>
                        <div class='artwork-details'>
                            <div class='artwork-title'><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class='artwork-artist'>by <?php echo htmlspecialchars($item['artist_name']); ?></div>
                            <div class='artwork-quantity'>Qty: <?php echo $item['quantity']; ?></div>
                            <div class='artwork-price'>Rs.<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="error-message">
                    <p>No items in cart. Please go back and add items to your cart.</p>
                    <a href="artgallary.php" class="back-btn">Browse Artworks</a>
                </div>
            <?php endif; ?>

            <div class="price-breakdown">
                <div class="price-row">
                    <span>Subtotal (<?php echo $cart_count; ?> items)</span>
                    <span id="subtotalDisplay">Rs.<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="price-row">
                    <span>Estimated taxes (5% GST)</span>
                    <span id="taxDisplay">Rs.<?php echo number_format($tax, 2); ?></span>
                </div>
                <div class="price-row">
                    <span>Shipping</span>
                    <span id="shippingCostDisplay">Select country</span>
                </div>
                <div class="price-row total">
                    <span>Total</span>
                    <span id="totalAmountDisplay">Rs.<?php echo number_format($subtotal + $tax, 2); ?></span>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Country, State, City data
        const locationData = {
            "India": {
                states: {
                    "Gujarat": ["Ahmedabad", "Surat", "Vadodara", "Rajkot", "Bhavnagar", "Jamnagar"],
                    "Maharashtra": ["Mumbai", "Pune", "Nagpur", "Thane", "Nashik", "Aurangabad"],
                    "Delhi": ["New Delhi", "Delhi Cantonment", "Narela"],
                    "Karnataka": ["Bangalore", "Mysore", "Hubli", "Belgaum", "Mangalore"],
                    "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai", "Tiruchirappalli"],
                    "Bihar": ["Patna", "Gaya", "Bhagalpur", "Muzaffarpur", "Darbhanga"],
                    "Uttar Pradesh": ["Lucknow", "Kanpur", "Varanasi", "Agra", "Prayagraj"],
                    "Rajasthan": ["Jaipur", "Jodhpur", "Udaipur", "Kota", "Bikaner"]
                }
            },
            "United States": {
                states: {
                    "California": ["Los Angeles", "San Francisco", "San Diego", "San Jose", "Sacramento"],
                    "New York": ["New York City", "Buffalo", "Rochester", "Albany", "Syracuse"],
                    "Texas": ["Houston", "Dallas", "Austin", "San Antonio", "Fort Worth"]
                }
            },
            "United Kingdom": {
                states: {
                    "England": ["London", "Manchester", "Birmingham", "Liverpool", "Leeds"],
                    "Scotland": ["Edinburgh", "Glasgow", "Aberdeen", "Dundee", "Inverness"],
                    "Wales": ["Cardiff", "Swansea", "Newport", "Bangor"]
                }
            },
            "Canada": {
                states: {
                    "Ontario": ["Toronto", "Ottawa", "Mississauga", "Hamilton", "London"],
                    "Quebec": ["Montreal", "Quebec City", "Laval", "Gatineau"],
                    "British Columbia": ["Vancouver", "Victoria", "Surrey", "Burnaby"]
                }
            },
            "Australia": {
                states: {
                    "New South Wales": ["Sydney", "Newcastle", "Wollongong", "Central Coast"],
                    "Victoria": ["Melbourne", "Geelong", "Ballarat", "Bendigo"],
                    "Queensland": ["Brisbane", "Gold Coast", "Sunshine Coast", "Cairns"]
                }
            }
        };

        // Price calculations
        const subtotal = <?php echo $subtotal; ?>;
        const taxAmount = <?php echo $tax; ?>;
        let shippingCost = 0;
        let totalAmount = subtotal + taxAmount;

        // Initialize countries dropdown
        function initializeCountries() {
            const countrySelect = document.getElementById('country');
            const countries = Object.keys(locationData);
            
            // Clear existing options except first
            while (countrySelect.options.length > 1) {
                countrySelect.remove(1);
            }
            
            countries.forEach(country => {
                const option = document.createElement('option');
                option.value = country;
                option.textContent = country;
                countrySelect.appendChild(option);
            });
            
            // Set previously selected country or default to India
            <?php if (isset($_POST['country']) && !empty($_POST['country'])): ?>
                countrySelect.value = '<?php echo addslashes($_POST['country']); ?>';
            <?php else: ?>
                countrySelect.value = 'India';
            <?php endif; ?>
            
            loadStates();
            updateShipping();
        }

        // Update states based on selected country
        function loadStates() {
            const country = document.getElementById('country').value;
            const stateSelect = document.getElementById('state');
            const citySelect = document.getElementById('city');
            
            // Clear existing options
            stateSelect.innerHTML = '<option value="">Select State</option>';
            citySelect.innerHTML = '<option value="">Select City</option>';
            
            if (country && locationData[country]) {
                const states = Object.keys(locationData[country].states);
                states.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state;
                    option.textContent = state;
                    stateSelect.appendChild(option);
                });
                
                // Set previously selected state
                <?php if (isset($_POST['state']) && !empty($_POST['state'])): ?>
                    stateSelect.value = '<?php echo addslashes($_POST['state']); ?>';
                    loadCities();
                <?php endif; ?>
            }
        }

        // Update cities based on selected state
        function loadCities() {
            const country = document.getElementById('country').value;
            const state = document.getElementById('state').value;
            const citySelect = document.getElementById('city');
            
            citySelect.innerHTML = '<option value="">Select City</option>';
            
            if (country && state && locationData[country] && locationData[country].states[state]) {
                const cities = locationData[country].states[state];
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
                
                // Set previously selected city
                <?php if (isset($_POST['city']) && !empty($_POST['city'])): ?>
                    citySelect.value = '<?php echo addslashes($_POST['city']); ?>';
                <?php endif; ?>
            }
        }

        function updateShipping() {
            const country = document.getElementById('country').value;
            const shippingPrice = document.getElementById('shippingPrice');
            const expressShippingPrice = document.getElementById('expressShippingPrice');
            const shippingCostDisplay = document.getElementById('shippingCostDisplay');
            
            // Get the selected shipping method
            const selectedShipping = document.querySelector('input[name="shipping"]:checked');
            const shippingMethod = selectedShipping ? selectedShipping.value : 'standard';
            
            if (country === 'India') {
                if (shippingMethod === 'express') {
                    shippingCost = 200;
                    shippingPrice.textContent = '₹200';
                    expressShippingPrice.textContent = '₹200';
                    shippingCostDisplay.textContent = '₹200';
                } else {
                    shippingCost = 0;
                    shippingPrice.textContent = 'FREE';
                    expressShippingPrice.textContent = '₹200';
                    shippingCostDisplay.textContent = 'FREE';
                }
            } else if (country) {
                // International shipping
                if (shippingMethod === 'express') {
                    shippingCost = 1245;
                    shippingPrice.textContent = '₹1,245';
                    expressShippingPrice.textContent = '₹1,245';
                    shippingCostDisplay.textContent = '₹1,245';
                } else {
                    shippingCost = 560;
                    shippingPrice.textContent = '₹560';
                    expressShippingPrice.textContent = '₹1,245';
                    shippingCostDisplay.textContent = '₹560';
                }
            } else {
                shippingCost = 0;
                shippingPrice.textContent = '--';
                expressShippingPrice.textContent = '--';
                shippingCostDisplay.textContent = '--';
            }
            
            updateTotal();
        }

        // Shipping method selection
        function selectShipping(method) {
            document.querySelectorAll('.method-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            const radio = event.currentTarget.querySelector('input[type="radio"]');
            radio.checked = true;
            
            updateShipping();
        }

        // Calculate total amount
        function calculateTotal() {
            totalAmount = subtotal + taxAmount + shippingCost;
        }

        // Update all price displays
        function updateTotal() {
            calculateTotal();
            
            // Update breakdown display
            document.getElementById('subtotalDisplay').textContent = 'Rs.' + subtotal.toFixed(2);
            document.getElementById('taxDisplay').textContent = 'Rs.' + taxAmount.toFixed(2);
            document.getElementById('shippingCostDisplay').textContent = (shippingCost === 0 ? 'FREE' : 'Rs.' + shippingCost.toFixed(2));
            document.getElementById('totalAmountDisplay').textContent = 'Rs.' + totalAmount.toFixed(2);
            
            // Update payment button
            document.getElementById('displayTotal').textContent = totalAmount.toFixed(2);
            
            // Update hidden form field
            document.getElementById('total_amount').value = totalAmount.toFixed(2);
        }

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const requiredFields = [
                'first_name', 'last_name', 'address', 'country', 
                'state', 'city', 'pin_code', 'phone'
            ];
            
            let isValid = true;
            let firstInvalidField = null;
            
            for (const fieldName of requiredFields) {
                const field = document.getElementById(fieldName);
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--orange1)';
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                } else {
                    field.style.borderColor = '#ddd';
                }
            }
            
            // Validate phone number
            const phone = document.getElementById('phone').value;
            const phoneRegex = /^[6-9]\d{9}$/;
            if (phone && !phoneRegex.test(phone.replace(/\D/g, ''))) {
                isValid = false;
                document.getElementById('phone').style.borderColor = 'var(--orange1)';
                if (!firstInvalidField) {
                    firstInvalidField = document.getElementById('phone');
                }
            }
            
            // Validate PIN code
            const pinCode = document.getElementById('pin_code').value;
            const pinRegex = /^\d{6}$/;
            if (pinCode && !pinRegex.test(pinCode)) {
                isValid = false;
                document.getElementById('pin_code').style.borderColor = 'var(--orange1)';
                if (!firstInvalidField) {
                    firstInvalidField = document.getElementById('pin_code');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                return;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = 'Processing... <i class="fas fa-spinner fa-spin"></i>';
            submitBtn.disabled = true;
        });

        // Real-time form validation
        document.querySelectorAll('input[required], select[required]').forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.style.borderColor = 'var(--orange1)';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '#ddd';
                }
            });
        });

        // Phone number validation
        document.getElementById('phone').addEventListener('blur', function() {
            const phone = this.value;
            const phoneRegex = /^[6-9]\d{9}$/;
            if (phone && !phoneRegex.test(phone.replace(/\D/g, ''))) {
                this.style.borderColor = 'var(--orange1)';
            } else if (phone) {
                this.style.borderColor = '#ddd';
            }
        });

        // PIN code validation
        document.getElementById('pin_code').addEventListener('blur', function() {
            const pinCode = this.value;
            const pinRegex = /^\d{6}$/;
            if (pinCode && !pinRegex.test(pinCode)) {
                this.style.borderColor = 'var(--orange1)';
            } else if (pinCode) {
                this.style.borderColor = '#ddd';
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeCountries();
            
            // Add event listeners for country and state changes
            document.getElementById('country').addEventListener('change', function() {
                loadStates();
                updateShipping();
            });
            
            document.getElementById('state').addEventListener('change', function() {
                loadCities();
                updateShipping();
            });
            
            // Add event listener for city change
            document.getElementById('city').addEventListener('change', function() {
                updateShipping();
            });
            
            // Update shipping when page loads
            updateShipping();
        });
    </script>
    
    <?php include('footer.html'); ?>
</body>
</html>