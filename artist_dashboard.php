<?php
// artist-dashboard.php
session_start();

// Check if user is artist and logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['artist_id'])) {
    header("Location: login.php");
    exit();
}

$artist_id = $_SESSION['artist_id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "art";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle order confirmation
if (isset($_POST['confirm_order'])) {
    $order_id = intval($_POST['order_id']);
    $confirmed_price = floatval($_POST['confirmed_price']);
    
    // Update order status and artist amount
    $update_sql = "UPDATE orders SET status = 'artist_confirmed', artist_amount = ?, artist_confirmed_date = NOW() WHERE order_id = ? AND artist_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("dii", $confirmed_price, $order_id, $artist_id);
    
    if ($stmt->execute()) {
        // Create notification for admin
        $admin_notification = "INSERT INTO notifications (artist_id, order_id, message, is_read) 
                              VALUES (?, ?, ?, 0)";
        $admin_stmt = $conn->prepare($admin_notification);
        $message = "Artist confirmed order #" . $order_id . " for Rs." . $confirmed_price;
        $admin_stmt->bind_param("iis", $artist_id, $order_id, $message);
        $admin_stmt->execute();
        $admin_stmt->close();
        
        $_SESSION['success'] = "Order confirmed successfully!";
    }
    $stmt->close();
}

// Handle order rejection
if (isset($_POST['reject_order'])) {
    $order_id = intval($_POST['order_id']);
    $rejection_reason = $conn->real_escape_string($_POST['rejection_reason']);
    
    // Update order status to cancelled
    $update_sql = "UPDATE orders SET status = 'cancelled', artist_confirmed_date = NOW() WHERE order_id = ? AND artist_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $order_id, $artist_id);
    
    if ($stmt->execute()) {
        // Create notification for admin about rejection
        $admin_notification = "INSERT INTO notifications (artist_id, order_id, message, is_read) 
                              VALUES (?, ?, ?, 0)";
        $admin_stmt = $conn->prepare($admin_notification);
        $message = "Artist rejected order #" . $order_id . ". Reason: " . $rejection_reason;
        $admin_stmt->bind_param("iis", $artist_id, $order_id, $message);
        $admin_stmt->execute();
        $admin_stmt->close();
        
        $_SESSION['success'] = "Order rejected successfully!";
    }
    $stmt->close();
}

// Fetch pending orders for this artist
$orders_sql = "SELECT o.*, a.title as artwork_title, a.image_url, 
               u.first_name, u.last_name, u.email as user_email,
               od.address, od.city, od.state, od.phone
               FROM orders o
               JOIN artworks a ON o.artwork_id = a.artwork_id
               JOIN register_data u ON o.user_id = u.id
               JOIN order_details od ON o.order_id = od.order_id
               WHERE o.artist_id = ? AND o.status = 'pending'
               ORDER BY o.order_date DESC";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $artist_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Fetch confirmed orders
$confirmed_sql = "SELECT o.*, a.title as artwork_title, a.image_url, 
                  u.first_name, u.last_name
                  FROM orders o
                  JOIN artworks a ON o.artwork_id = a.artwork_id
                  JOIN register_data u ON o.user_id = u.id
                  WHERE o.artist_id = ? AND o.status = 'artist_confirmed'
                  ORDER BY o.artist_confirmed_date DESC";
$confirmed_stmt = $conn->prepare($confirmed_sql);
$confirmed_stmt->bind_param("i", $artist_id);
$confirmed_stmt->execute();
$confirmed_result = $confirmed_stmt->get_result();

// Fetch completed orders
$completed_sql = "SELECT o.*, a.title as artwork_title, a.image_url, 
                  u.first_name, u.last_name
                  FROM orders o
                  JOIN artworks a ON o.artwork_id = a.artwork_id
                  JOIN register_data u ON o.user_id = u.id
                  WHERE o.artist_id = ? AND o.status = 'completed'
                  ORDER BY o.admin_approved_date DESC";
$completed_stmt = $conn->prepare($completed_sql);
$completed_stmt->bind_param("i", $artist_id);
$completed_stmt->execute();
$completed_result = $completed_stmt->get_result();

// Calculate artist earnings
$earnings_sql = "SELECT 
                 SUM(artist_amount) as total_earnings,
                 COUNT(*) as total_orders,
                 SUM(admin_commission) as total_commission
                 FROM orders 
                 WHERE artist_id = ? AND status IN ('artist_confirmed', 'completed')";
$earnings_stmt = $conn->prepare($earnings_sql);
$earnings_stmt->bind_param("i", $artist_id);
$earnings_stmt->execute();
$earnings_result = $earnings_stmt->get_result();
$earnings = $earnings_result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Dashboard | Art Gallery</title>
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .section {
            background: white;
            padding: 30px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
        }
        .order-card {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
        }
        .order-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-confirm {
            background: #68d388;
            color: white;
        }
        .btn-reject {
            background: #f43a09;
            color: white;
        }
        .btn-secondary {
            background: #2c2c54;
            color: white;
        }
        .price-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            width: 200px;
        }
        .rejection-reason {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            min-height: 80px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="dashboard-container">
        <h1>Artist Dashboard</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Earnings Overview -->
        <div class="section">
            <h2>Your Earnings Overview</h2>
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Earnings</h3>
                    <div class="stat-number">Rs.<?php echo number_format($earnings['total_earnings'] ?? 0, 2); ?></div>
                    <p>Amount you've earned</p>
                </div>
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <div class="stat-number"><?php echo $earnings['total_orders'] ?? 0; ?></div>
                    <p>Confirmed orders</p>
                </div>
                <div class="stat-card">
                    <h3>Platform Commission</h3>
                    <div class="stat-number">Rs.<?php echo number_format($earnings['total_commission'] ?? 0, 2); ?></div>
                    <p>Paid to platform</p>
                </div>
            </div>
        </div>
        
        <!-- Pending Orders Section -->
        <div class="section">
            <h2>Pending Orders ⏳</h2>
            <p>These orders are waiting for your confirmation</p>
            
            <?php if ($orders_result->num_rows > 0): ?>
                <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div style="display: flex; gap: 20px; align-items: start;">
                            <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($order['artwork_title']); ?>" 
                                 class="order-image">
                            <div style="flex: 1;">
                                <h3><?php echo htmlspecialchars($order['artwork_title']); ?></h3>
                                <p><strong>Order ID:</strong> #<?php echo $order['order_id']; ?></p>
                                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                <p><strong>Customer Email:</strong> <?php echo htmlspecialchars($order['user_email']); ?></p>
                                <p><strong>Customer Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['address'] . ', ' . $order['city'] . ', ' . $order['state']); ?></p>
                                <p><strong>Order Total:</strong> Rs.<?php echo number_format($order['final_amount'], 2); ?></p>
                                <p><strong>Your Potential Earnings (80%):</strong> Rs.<?php echo number_format($order['artist_amount'], 2); ?></p>
                                <p><strong>Platform Commission (20%):</strong> Rs.<?php echo number_format($order['admin_commission'], 2); ?></p>
                                
                                <!-- Confirmation Form -->
                                <form method="POST" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <label><strong>Confirm Your Price (80% of total):</strong></label><br>
                                    <input type="number" name="confirmed_price" 
                                           value="<?php echo number_format($order['artist_amount'], 2); ?>" 
                                           step="0.01" min="0" class="price-input" required>
                                    <br>
                                    <button type="submit" name="confirm_order" class="btn btn-confirm">
                                        ✅ Confirm Order & Accept Rs.<?php echo number_format($order['artist_amount'], 2); ?>
                                    </button>
                                </form>
                                
                                <!-- Rejection Form -->
                                <form method="POST" style="margin-top: 15px; padding: 15px; background: #fff5f5; border-radius: 5px;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <label><strong>Reject Order (Please provide reason):</strong></label><br>
                                    <textarea name="rejection_reason" class="rejection-reason" 
                                              placeholder="Please provide reason for rejecting this order..." required></textarea>
                                    <br>
                                    <button type="submit" name="reject_order" class="btn btn-reject">
                                        ❌ Reject Order
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No pending orders at the moment.</p>
            <?php endif; ?>
        </div>
        
        <!-- Confirmed Orders Section -->
        <div class="section">
            <h2>Confirmed Orders ✅</h2>
            <p>Orders you've confirmed - waiting for admin approval</p>
            
            <?php if ($confirmed_result->num_rows > 0): ?>
                <?php while ($order = $confirmed_result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div style="display: flex; gap: 20px; align-items: start;">
                            <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($order['artwork_title']); ?>" 
                                 class="order-image">
                            <div style="flex: 1;">
                                <h3><?php echo htmlspecialchars($order['artwork_title']); ?></h3>
                                <p><strong>Order ID:</strong> #<?php echo $order['order_id']; ?></p>
                                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                <p><strong>Confirmed Amount:</strong> Rs.<?php echo number_format($order['artist_amount'], 2); ?></p>
                                <p><strong>Platform Commission:</strong> Rs.<?php echo number_format($order['admin_commission'], 2); ?></p>
                                <p><strong>Confirmed Date:</strong> <?php echo $order['artist_confirmed_date']; ?></p>
                                <p><strong>Status:</strong> <span class="status-badge status-confirmed">Waiting for Admin Approval</span></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No confirmed orders yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Completed Orders Section -->
        <div class="section">
            <h2>Completed Orders 🎉</h2>
            <p>Orders completed and paid out</p>
            
            <?php if ($completed_result->num_rows > 0): ?>
                <?php while ($order = $completed_result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div style="display: flex; gap: 20px; align-items: start;">
                            <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($order['artwork_title']); ?>" 
                                 class="order-image">
                            <div style="flex: 1;">
                                <h3><?php echo htmlspecialchars($order['artwork_title']); ?></h3>
                                <p><strong>Order ID:</strong> #<?php echo $order['order_id']; ?></p>
                                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                <p><strong>Your Earnings:</strong> Rs.<?php echo number_format($order['artist_amount'], 2); ?></p>
                                <p><strong>Platform Commission:</strong> Rs.<?php echo number_format($order['admin_commission'], 2); ?></p>
                                <p><strong>Completed Date:</strong> <?php echo $order['admin_approved_date']; ?></p>
                                <p><strong>Status:</strong> <span class="status-badge status-completed">Completed & Paid</span></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No completed orders yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include('footer.html'); ?>
</body>
</html>