<?php
session_start();
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "art";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle order completion by admin
if (isset($_POST['complete_order'])) {
    $order_id = intval($_POST['order_id']);
    
    $update_sql = "UPDATE orders SET status = 'completed', admin_approved_date = NOW() WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Order marked as completed!";
    } else {
        $_SESSION['error'] = "Failed to complete order";
    }
    $stmt->close();
    header("Location: orders.php");
    exit();
}

// Handle order cancellation by admin
if (isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);
    $cancellation_reason = $conn->real_escape_string($_POST['cancellation_reason']);
    
    $update_sql = "UPDATE orders SET status = 'cancelled', admin_approved_date = NOW() WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Order cancelled successfully!";
    } else {
        $_SESSION['error'] = "Failed to cancel order";
    }
    $stmt->close();
    header("Location: orders.php");
    exit();
}

// Get filter parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'artist_confirmed';

// Build SQL query based on filter
$orders_sql = "SELECT o.*, a.title as artwork_title, a.image_url, 
               ar.artist_name, ar.email as artist_email,
               u.first_name, u.last_name, u.email as user_email,
               od.phone, od.address, od.city, od.state
               FROM orders o
               JOIN artworks a ON o.artwork_id = a.artwork_id
               JOIN artists ar ON o.artist_id = ar.artist_id
               JOIN register_data u ON o.user_id = u.id
               JOIN order_details od ON o.order_id = od.order_id";
               
if ($status_filter != 'all') {
    $orders_sql .= " WHERE o.status = ?";
}

$orders_sql .= " ORDER BY o.order_date DESC";

$orders_stmt = $conn->prepare($orders_sql);
if ($status_filter != 'all') {
    $orders_stmt->bind_param("s", $status_filter);
}
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(final_amount) as total_revenue,
    SUM(admin_commission) as total_commission,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN status = 'artist_confirmed' THEN 1 END) as confirmed_orders,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
    FROM orders";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Fetch recent artist-confirmed orders
$recent_confirmed_sql = "SELECT o.*, a.title as artwork_title, ar.artist_name
                        FROM orders o
                        JOIN artworks a ON o.artwork_id = a.artwork_id
                        JOIN artists ar ON o.artist_id = ar.artist_id
                        WHERE o.status = 'artist_confirmed'
                        ORDER BY o.artist_confirmed_date DESC
                        LIMIT 5";
$recent_confirmed_result = $conn->query($recent_confirmed_sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders Management | Art Gallery</title>
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

        .main-content.active {
            margin-left: 0;
        }

        /* Header Styles */
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

        .dashboard-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        .section {
            background: white;
            padding: 25px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .section h2 {
            color: var(--dark);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--orange1);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--dark), #3d3d72);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .order-card {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            background: #f9f9f9;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .order-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid var(--orange2);
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #5ac777 0%, #68d388 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--orange1) 0%, #ff6b3d 100%);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #e03507 0%, #f43a09 100%);
        }

        .btn-info {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
        }

        .status-pending { 
            background: linear-gradient(135deg, var(--orange2) 0%, #ffca93 100%);
            color: var(--dark); 
        }
        .status-confirmed { 
            background: linear-gradient(135deg, var(--bluegreen) 0%, #d6f5e8 100%);
            color: var(--dark); 
        }
        .status-completed { 
            background: linear-gradient(135deg, var(--green) 0%, #83da9c 100%);
            color: white; 
        }
        .status-cancelled { 
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24; 
        }

        .filters {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.3s ease;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--orange1);
            color: white;
            border-color: var(--orange1);
        }

        .grid-3col {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
        }

        th {
            background: linear-gradient(135deg, var(--dark) 0%, #3d3d72 100%);
            color: white;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        tr:hover {
            background: rgba(194, 237, 218, 0.2);
        }

        .success-message {
            background: var(--green);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .error-message {
            background: var(--orange1);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .commission-breakdown {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
            border-left: 4px solid var(--orange2);
        }

        .commission-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }

        .commission-total {
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: bold;
        }

        .cancellation-reason {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            min-height: 100px;
            font-family: inherit;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        .modal-content h3 {
            margin-bottom: 15px;
            color: var(--dark);
            border-bottom: 2px solid var(--orange2);
            padding-bottom: 10px;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
            
            .main-content.active {
                margin-left: 250px;
            }
            
            .grid-3col {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
            
            .filters {
                justify-content: center;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-box {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.9rem;
            }
            
            .filters {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <h2>📊 Orders Management</h2>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search orders..." id="orderSearch">
            </div>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=Admin+User&background=f43a09&color=fff" alt="Admin User">
                <span>Admin User</span>
            </div>
        </div>

        <div class="dashboard-container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message">
                    ✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    ❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Overview -->
            <div class="section">
                <h2>📊 Orders Overview</h2>
                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Total Orders</h3>
                        <div class="stat-number"><?php echo $stats['total_orders'] ?? 0; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Revenue</h3>
                        <div class="stat-number">Rs.<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Commission</h3>
                        <div class="stat-number">Rs.<?php echo number_format($stats['total_commission'] ?? 0, 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Pending</h3>
                        <div class="stat-number"><?php echo $stats['pending_orders'] ?? 0; ?></div>
                    </div>
                </div>
            </div>

            <!-- Recent Artist-Confirmed Orders -->
            <div class="section">
                <h2>🔄 Orders Waiting for Approval</h2>
                <p>These orders have been confirmed by artists and are waiting for your approval</p>
                
                <?php if ($recent_confirmed_result->num_rows > 0): ?>
                    <div class="grid-3col">
                        <?php while ($order = $recent_confirmed_result->fetch_assoc()): ?>
                            <div class="order-card">
                                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
    
                                    <div>
                                        <strong><?php echo htmlspecialchars($order['artwork_title']); ?></strong><br>
                                        <small>by <?php echo htmlspecialchars($order['artist_name']); ?></small>
                                    </div>
                                </div>
                                <div class="commission-breakdown">
                                    <div class="commission-row">
                                        <span>Order Total:</span>
                                        <span>Rs.<?php echo number_format($order['final_amount'], 2); ?></span>
                                    </div>
                                    <div class="commission-row">
                                        <span>Artist (80%):</span>
                                        <span>Rs.<?php echo number_format($order['artist_amount'], 2); ?></span>
                                    </div>
                                    <div class="commission-row commission-total">
                                        <span>Platform (20%):</span>
                                        <span>Rs.<?php echo number_format($order['admin_commission'], 2); ?></span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px; margin-top: 15px;">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <button type="submit" name="complete_order" class="btn btn-success">
                                            ✅ Approve
                                        </button>
                                    </form>
                                    <button class="btn btn-danger" onclick="showCancelForm(<?php echo $order['order_id']; ?>)">
                                        ❌ Cancel
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div style="font-size: 3rem; color: var(--orange2); margin-bottom: 15px;">📭</div>
                        <p>No orders waiting for approval</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- All Orders -->
            <div class="section">
                <h2>📦 All Orders</h2>
                
                <div class="filters">
                    <a href="orders.php?status=all" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                        All
                    </a>
                    <a href="orders.php?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                        Pending
                    </a>
                    <a href="orders.php?status=artist_confirmed" class="filter-btn <?php echo $status_filter == 'artist_confirmed' ? 'active' : ''; ?>">
                        Artist Confirmed
                    </a>
                    <a href="orders.php?status=completed" class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                        Completed
                    </a>
                    <a href="orders.php?status=cancelled" class="filter-btn <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                        Cancelled
                    </a>
                </div>

                <?php if ($orders_result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Artwork</th>
                                    <th>Customer</th>
                                    <th>Artist</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <?php if (!empty($order['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($order['artwork_title']); ?>" 
                                                         class="order-image">
                                                <?php else: ?>
                                                    <div style="width: 70px; height: 70px; background: linear-gradient(135deg, var(--light) 0%, #e9e9e9 100%); border-radius: 5px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--orange2);">
                                                        <i class="fas fa-image" style="font-size: 1.5rem; color: #999;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($order['artwork_title']); ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($order['artist_name']); ?>
                                        </td>
                                        <td>
                                            Rs.<?php echo number_format($order['final_amount'], 2); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch($order['status']) {
                                                case 'pending': $status_class = 'status-pending'; break;
                                                case 'artist_confirmed': $status_class = 'status-confirmed'; break;
                                                case 'completed': $status_class = 'status-completed'; break;
                                                case 'cancelled': $status_class = 'status-cancelled'; break;
                                            }
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($order['status'] == 'artist_confirmed'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <button type="submit" name="complete_order" class="btn btn-success" title="Approve Order">
                                                        ✅
                                                    </button>
                                                </form>
                                                <button class="btn btn-danger" onclick="showCancelForm(<?php echo $order['order_id']; ?>)" title="Cancel Order">
                                                    ❌
                                                </button>
                                            <?php else: ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div style="font-size: 3rem; color: var(--orange2); margin-bottom: 15px;">📭</div>
                        <p>No orders found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cancellation Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <h3>Cancel Order</h3>
            <form id="cancelForm" method="POST">
                <input type="hidden" name="order_id" id="cancel_order_id">
                <div style="margin-bottom: 15px;">
                    <label for="cancellation_reason">Reason for Cancellation:</label>
                    <textarea name="cancellation_reason" id="cancellation_reason" class="cancellation-reason" 
                              placeholder="Please provide reason for cancelling this order..." required></textarea>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="cancel_order" class="btn btn-danger">Confirm Cancellation</button>
                    <button type="button" class="btn btn-info" onclick="hideCancelForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('orderSearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            
            rows.forEach(row => {
                const orderId = row.cells[0].textContent.toLowerCase();
                const artwork = row.cells[1].textContent.toLowerCase();
                const customer = row.cells[2].textContent.toLowerCase();
                const artist = row.cells[3].textContent.toLowerCase();
                
                if (orderId.includes(searchValue) || artwork.includes(searchValue) || 
                    customer.includes(searchValue) || artist.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function showCancelForm(orderId) {
            document.getElementById('cancel_order_id').value = orderId;
            document.getElementById('cancelModal').style.display = 'flex';
        }

        function hideCancelForm() {
            document.getElementById('cancelModal').style.display = 'none';
            document.getElementById('cancellation_reason').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCancelForm();
            }
        });
    </script>
</body>
</html>