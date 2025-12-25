<?php
// payment-success.php - UPDATED
session_start();

// Get parameters
$order_id = $_GET['order_id'] ?? '';
$status = $_GET['status'] ?? 'pending';
$source = $_GET['source'] ?? 'single';
$order_count = $_GET['order_count'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Submitted | Art Gallery</title>
    <style>
        :root {
            --orange1: #f43a09;
            --orange2: #ffb766;
            --bluegreen: #c2edda;
            --green: #68d388;
            --dark: #2c2c54;
            --light: #f5f5f5;
        }
        
        body {
            background-color: var(--light);
            color: var(--dark);
            min-height: 100vh;
        }

        .success-container {
            max-width: 800px;
            margin: 50px auto;
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(44, 44, 84, 0.1);
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--orange2);
            margin-bottom: 20px;
        }
        
        .success-title {
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .status-pending {
            color: var(--orange2);
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .order-details {
            text-align: left;
            background: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
            border-left: 4px solid var(--bluegreen);
        }
        
        .order-details h3 {
            color: var(--dark);
            margin-bottom: 15px;
            border-bottom: 2px solid var(--bluegreen);
            padding-bottom: 10px;
        }
        
        .order-details p {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .order-details ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        .order-details li {
            margin-bottom: 5px;
        }
        
        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--dark);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--orange1);
        }
        
        .btn:hover {
            background: var(--orange1);
            transform: translateY(-2px);
        }
        
        .btn-primary:hover {
            background: var(--dark);
        }
        
        .alert-info {
            background-color: rgba(194, 237, 218, 0.3);
            border-left: 4px solid var(--green);
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .success-container {
                margin: 20px;
                padding: 20px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    
    <div class="success-container">
        <div class="success-icon">⏳</div>
        <h1 class="success-title">Order Submitted Successfully!</h1>
        <p class="status-pending">Order Status: <?php echo ucfirst($status); ?></p>
        
        <?php if ($source === 'cart'): ?>
            <div class="alert-info">
                <strong><i class="fas fa-info-circle"></i> Multiple Orders Created</strong>
                <p>Since your cart contained items from different artists, <?php echo $order_count; ?> separate orders have been created. Each artist will manage their own order independently.</p>
            </div>
        <?php endif; ?>
        
        <p>Your order has been submitted to the artist for confirmation.</p>
        
        <?php if ($order_id): ?>
            <div class="order-details">
                <h3>Order Details:</h3>
                <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order_id); ?></p>
                <p><strong>Status:</strong> Waiting for artist confirmation</p>
                <p><strong>Next Steps:</strong> The artist will review your order and confirm it within 24-48 hours.</p>
                
                <?php if ($source === 'single'): ?>
                    <p><strong>Commission Breakdown:</strong></p>
                    <ul>
                        <li>Artist will receive: 80% of the artwork price</li>
                        <li>Platform commission: 20% of the artwork price</li>
                    </ul>
                    <p><small><em>Note: Shipping and taxes are additional and not included in commission calculation.</em></small></p>
                <?php else: ?>
                    <p><strong>Commission Breakdown (per item):</strong></p>
                    <ul>
                        <li>Each artist receives: 80% of their artwork price</li>
                        <li>Platform commission: 20% of each artwork price</li>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="artgallary.php" class="btn">Continue Shopping</a>
            <?php if ($source === 'cart'): ?>
                <a href="my_orders.php" class="btn">View All Orders</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include('footer.html'); ?>
</body>
</html>