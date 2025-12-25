<?php
// payment-process.php - UNIFIED VERSION
session_start();

// Check if user is logged in
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

// Determine the source (cart or single item)
$source = isset($_GET['source']) ? $_GET['source'] : 'single';
$order_ids = [];
$errors = [];

if ($source === 'cart') {
    // ==================== CART CHECKOUT PROCESS ====================
    
    // Check if cart data exists in session
    if (!isset($_SESSION['order_data'])) {
        header("Location: checkout-cart.php");
        exit();
    }
    
    $order_data = $_SESSION['order_data'];
    $cart_items = $order_data['cart_items'];
    $first_name = $order_data['first_name'];
    $last_name = $order_data['last_name'];
    $email = $order_data['email'];
    $phone = $order_data['phone'];
    $address = $order_data['address'];
    $city = $order_data['city'];
    $state = $order_data['state'];
    $country = $order_data['country'];
    $pin_code = $order_data['pin_code'];
    $shipping_method = $order_data['shipping_method'];
    $total_amount = $order_data['total_amount'];
    $shipping = $order_data['shipping'];
    $tax = $order_data['tax'];
    $subtotal = $order_data['subtotal'];
    
    $conn->begin_transaction();
    
    try {
        foreach ($cart_items as $item) {
            // Calculate item total
            $item_total = $item['price'] * $item['quantity'];
            $item_tax = $item_total * 0.05; // 5% GST
            
            // Calculate shipping cost per item (proportional)
            $item_shipping_ratio = ($subtotal > 0) ? ($item_total / $subtotal) : 1;
            $item_shipping = $shipping * $item_shipping_ratio;
            
            // Calculate artist amount (80% of price) and admin commission (20%)
            $artist_amount = $item_total * 0.80;
            $admin_commission = $item_total * 0.20;
            
            // Final amount for this item
            $item_final_amount = $item_total + $item_tax + $item_shipping;
            
            // Check if orders table has the required columns
            $check_columns = $conn->query("SHOW COLUMNS FROM orders LIKE 'artist_id'");
            
            if ($check_columns->num_rows > 0) {
                // Table has new structure - use full query
                $order_sql = "INSERT INTO orders (user_id, artist_id, artwork_id, total_amount, shipping_amount, tax_amount, final_amount, artist_amount, admin_commission, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                $order_stmt = $conn->prepare($order_sql);
                $order_stmt->bind_param("iiidddddd", 
                    $_SESSION['user_id'], 
                    $item['artist_id'], 
                    $item['artwork_id'],
                    $item_total,
                    $item_shipping,
                    $item_tax,
                    $item_final_amount,
                    $artist_amount,
                    $admin_commission
                );
            } else {
                // Table has old structure - use basic query
                $order_sql = "INSERT INTO orders (user_id, artwork_id, total_amount, shipping_amount, tax_amount, final_amount, status) 
                             VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                $order_stmt = $conn->prepare($order_sql);
                $order_stmt->bind_param("iidddd", 
                    $_SESSION['user_id'], 
                    $item['artwork_id'],
                    $item_total,
                    $item_shipping,
                    $item_tax,
                    $item_final_amount
                );
            }
            
            if ($order_stmt->execute()) {
                $order_id = $conn->insert_id;
                $order_ids[] = $order_id;
                
                // Save order details
                $check_order_details = $conn->query("SHOW TABLES LIKE 'order_details'");
                if ($check_order_details->num_rows > 0) {
                    $details_sql = "INSERT INTO order_details (order_id, first_name, last_name, email, phone, address, city, state, pin_code, country, shipping_method, payment_method) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'online')";
                    $details_stmt = $conn->prepare($details_sql);
                    $details_stmt->bind_param("issssssssss", 
                        $order_id,
                        $first_name,
                        $last_name,
                        $email,
                        $phone,
                        $address,
                        $city,
                        $state,
                        $pin_code,
                        $country,
                        $shipping_method
                    );
                    $details_stmt->execute();
                    $details_stmt->close();
                }
                
                // Create notification for artist
                $check_notifications = $conn->query("SHOW TABLES LIKE 'notifications'");
                if ($check_notifications->num_rows > 0) {
                    $notification_sql = "INSERT INTO notifications (artist_id, order_id, message) 
                                       VALUES (?, ?, ?)";
                    $notification_stmt = $conn->prepare($notification_sql);
                    $message = "New order received for your artwork: " . $item['title'] . ". Order ID: " . $order_id;
                    $notification_stmt->bind_param("iis", $item['artist_id'], $order_id, $message);
                    $notification_stmt->execute();
                    $notification_stmt->close();
                }
                
            } else {
                throw new Exception("Order creation failed for item: " . $item['title']);
            }
            $order_stmt->close();
        }
        
        // Clear cart after successful orders
        $clear_cart_sql = "DELETE FROM cart WHERE user_id = ?";
        $clear_stmt = $conn->prepare($clear_cart_sql);
        $clear_stmt->bind_param("i", $_SESSION['user_id']);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        // Clear session cart data
        unset($_SESSION['cart_items']);
        unset($_SESSION['cart_total']);
        unset($_SESSION['cart_count']);
        unset($_SESSION['order_data']);
        
        $conn->commit();
        
        // Redirect to success page with first order ID
        $first_order_id = !empty($order_ids) ? $order_ids[0] : 0;
        header("Location: payment-success.php?order_id=" . $first_order_id . "&status=pending&source=cart&order_count=" . count($order_ids));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "Error processing cart order: " . $e->getMessage();
        error_log($e->getMessage());
        $_SESSION['checkout_errors'] = $errors;
        header("Location: checkout-cart.php");
        exit();
    }
    
} else {
    // ==================== SINGLE ITEM CHECKOUT PROCESS ====================
    
    // Get data from POST
    $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
    $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
    $user_id = $_SESSION['user_id'];
    
    // Get shipping and contact details from POST
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $city = $conn->real_escape_string($_POST['city']);
    $state = $conn->real_escape_string($_POST['state']);
    $pin_code = $conn->real_escape_string($_POST['pin_code']);
    $country = $conn->real_escape_string($_POST['country']);
    $shipping_method = isset($_POST['shipping']) ? $conn->real_escape_string($_POST['shipping']) : 'standard';
    $payment_method = isset($_POST['payment']) ? $conn->real_escape_string($_POST['payment']) : 'online';
    
    if ($artwork_id > 0 && $user_id > 0) {
        // Fetch artwork details with artist information
        $sql = "SELECT a.*, ar.artist_id, ar.artist_name 
                FROM artworks a 
                JOIN artists ar ON a.artist_id = ar.artist_id 
                WHERE a.artwork_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $artwork_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $artwork = $result->fetch_assoc();
            $artist_id = $artwork['artist_id'];
            
            // Calculate amounts
            $subtotal = $artwork['price'];
            $tax = $subtotal * 0.05;
            $shipping = 0;
            
            // Calculate shipping based on country and method
            if ($country === 'India') {
                $shipping = ($shipping_method === 'express') ? 200 : 0;
            } else {
                $shipping = ($shipping_method === 'express') ? 1245 : 560;
            }
            
            // Recalculate total with actual shipping
            $total_amount = $subtotal + $tax + $shipping;
            
            // Calculate artist amount (80%) and admin commission (20%)
            $artist_amount = $subtotal * 0.80;
            $admin_commission = $subtotal * 0.20;
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Check if orders table has the new columns
                $check_columns = $conn->query("SHOW COLUMNS FROM orders LIKE 'artist_id'");
                if ($check_columns->num_rows > 0) {
                    // Table has new structure - use full query
                    $order_sql = "INSERT INTO orders (user_id, artist_id, artwork_id, total_amount, shipping_amount, tax_amount, final_amount, artist_amount, admin_commission, status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                    $order_stmt = $conn->prepare($order_sql);
                    $order_stmt->bind_param("iiidddddd", $user_id, $artist_id, $artwork_id, $subtotal, $shipping, $tax, $total_amount, $artist_amount, $admin_commission);
                } else {
                    // Table has old structure - use basic query
                    $order_sql = "INSERT INTO orders (user_id, artwork_id, total_amount, shipping_amount, tax_amount, final_amount, status) 
                                 VALUES (?, ?, ?, ?, ?, ?, 'pending')";
                    $order_stmt = $conn->prepare($order_sql);
                    $order_stmt->bind_param("iidddd", $user_id, $artwork_id, $subtotal, $shipping, $tax, $total_amount);
                }
                
                if ($order_stmt->execute()) {
                    $order_id = $conn->insert_id;
                    
                    // Save order details if table exists
                    $check_order_details = $conn->query("SHOW TABLES LIKE 'order_details'");
                    if ($check_order_details->num_rows > 0) {
                        $details_sql = "INSERT INTO order_details (order_id, first_name, last_name, email, phone, address, city, state, pin_code, country, shipping_method, payment_method) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $details_stmt = $conn->prepare($details_sql);
                        $details_stmt->bind_param("isssssssssss", $order_id, $first_name, $last_name, $email, $phone, $address, $city, $state, $pin_code, $country, $shipping_method, $payment_method);
                        $details_stmt->execute();
                        $details_stmt->close();
                    }
                    
                    // Create notification for artist if table exists
                    $check_notifications = $conn->query("SHOW TABLES LIKE 'notifications'");
                    if ($check_notifications->num_rows > 0) {
                        $notification_sql = "INSERT INTO notifications (artist_id, order_id, message) 
                                           VALUES (?, ?, ?)";
                        $notification_stmt = $conn->prepare($notification_sql);
                        $message = "New order received for your artwork: " . $artwork['title'] . ". Order ID: " . $order_id;
                        $notification_stmt->bind_param("iis", $artist_id, $order_id, $message);
                        $notification_stmt->execute();
                        $notification_stmt->close();
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Redirect to success page
                    header("Location: payment-success.php?order_id=" . $order_id . "&status=pending&source=single");
                    exit();
                    
                } else {
                    throw new Exception("Order creation failed: " . $order_stmt->error);
                }
                $order_stmt->close();
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                error_log($e->getMessage());
                header("Location: checkout.php?artwork_id=" . $artwork_id . "&error=order_failed");
                exit();
            }
        } else {
            // Artwork not found
            header("Location: checkout.php?artwork_id=" . $artwork_id . "&error=artwork_not_found");
            exit();
        }
        $stmt->close();
    } else {
        // Invalid parameters
        header("Location: checkout.php?artwork_id=" . $artwork_id . "&error=invalid_parameters");
        exit();
    }
}

$conn->close();
?>