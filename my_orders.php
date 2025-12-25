<?php
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

$user_id = $_SESSION['user_id'];

// Fetch user's orders
$sql = "SELECT o.*, a.title, a.image_url, ar.artist_name 
        FROM orders o 
        JOIN artworks a ON o.artwork_id = a.artwork_id 
        JOIN artists ar ON o.artist_id = ar.artist_id 
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Artify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --orange1: #f43a09;
            --orange2: #ffb766;
            --bluegreen: #c2edda;
            --green: #68d388;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://wallpaperset.com/w/full/8/6/9/51764.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            background: linear-gradient(120deg, var(--orange1), var(--orange2));
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 18px;
        }
        
        .content {
            padding: 40px;
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--orange1);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-btn:hover {
            text-decoration: underline;
        }
        
        .orders-container {
            margin-top: 20px;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 5px solid var(--orange2);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .order-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid var(--bluegreen);
        }
        
        .order-details {
            flex-grow: 1;
        }
        
        .order-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .order-artist, .order-date {
            color: #666;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .order-price {
            font-size: 18px;
            font-weight: 600;
            color: var(--orange1);
            margin: 10px 0;
        }
        
        .order-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .no-orders {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        
        .no-orders i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .order-card {
                flex-direction: column;
                text-align: center;
            }
            
            .order-image {
                width: 150px;
                height: 150px;
            }
            
            .header, .content {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
            <p>Track your purchased artworks</p>
        </div>
        
        <div class="content">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            
            <div class="orders-container">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($order = $result->fetch_assoc()): ?>
                        <div class="order-card">
                            <img src="<?php echo $order['image_url']; ?>" 
                                 alt="<?php echo $order['title']; ?>" 
                                 class="order-image"
                                 onerror="this.src='https://via.placeholder.com/100x100?text=Artwork'">
                            
                            <div class="order-details">
                                <h3 class="order-title"><?php echo htmlspecialchars($order['title']); ?></h3>
                                <p class="order-artist">Artist: <?php echo htmlspecialchars($order['artist_name']); ?></p>
                                <p class="order-date">Order Date: <?php echo date('F d, Y', strtotime($order['order_date'])); ?></p>
                                
                                <div class="order-price">
                                    ₹<?php echo number_format($order['final_amount'], 2); ?>
                                </div>
                                
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                </span>
                            </div>
                            
                            <div style="text-align: right;">
                                <div style="font-size: 14px; color: #666; margin-bottom: 10px;">
                                    Order ID: #<?php echo $order['order_id']; ?>
                                </div>
                                <?php if ($order['status'] == 'completed'): ?>
                                    <i class="fas fa-check-circle" style="color: var(--green); font-size: 24px;"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Orders Yet</h3>
                        <p>You haven't placed any orders yet.</p>
                        <a href="index.php" style="display: inline-block; margin-top: 20px; 
                           background: var(--orange1); color: white; padding: 10px 20px; 
                           border-radius: 5px; text-decoration: none;">
                            Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>