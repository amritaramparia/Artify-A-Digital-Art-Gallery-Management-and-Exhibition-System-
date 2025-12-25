<?php
    
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();
}
// Database configuration
$host = 'localhost';
$dbname = 'art';
$username = 'root';
$password = '';

// Initialize variables
$error = '';
$success = '';
$first_name = '';
$last_name = '';
$email = '';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);
    // Check if email exists
    $check = $conn->query("SELECT id FROM register_data WHERE email = '$email'");
    
    if ($check->num_rows > 0) {
        $error = "Email already exists";
    } else {
        // Insert new user
        $sql = "INSERT INTO register_data (first_name, last_name, email, password) 
                VALUES ('$first_name', '$last_name', '$email', '$password')";
        
        if ($conn->query($sql) === TRUE) {
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            
            // Redirect to profile page
            header("Location: profile.php");
            exit();
        } else {
            $error = "Error: " . $conn->error;
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
    <title>Art Portal Registration</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 20%, rgba(0, 0, 0, 0.8) 100%);
            z-index: -1;
        }
        
        .container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            min-height: 550px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .left-panel {
            flex: 1;
            background: linear-gradient(120deg, rgba(244, 58, 9, 0.85), rgba(255, 183, 102, 0.85));
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://wallpaperset.com/w/full/8/6/9/51764.jpg') no-repeat center center;
            background-size: cover;
            opacity: 0.2;
            z-index: -1;
        }
        
        .right-panel {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(255, 255, 255, 0.98);
        }
        
        .reviews-container {
            position: relative;
            height: 350px;
            overflow: hidden;
        }
        
        .review {
            position: absolute;
            width: 100%;
            padding: 25px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.5s, opacity 0.5s;
            opacity: 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .review.active {
            opacity: 1;
            transform: translateY(0);
        }
        
        .review.next {
            transform: translateY(50px);
        }
        
        .review.prev {
            transform: translateY(-50px);
        }
        
        .review-text {
            font-style: italic;
            margin-bottom: 15px;
            font-size: 16px;
            line-height: 1.6;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .review-author {
            display: flex;
            align-items: center;
        }
        
        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--orange1);
            font-weight: bold;
            font-size: 18px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
        }
        
        .author-details {
            display: flex;
            flex-direction: column;
        }
        
        .author-name {
            font-weight: 600;
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .author-title {
            font-size: 14px;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .review-indicators {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            margin: 0 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
        }
        
        .indicator.active {
            background: white;
            transform: scale(1.3);
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-title h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--orange1);
        }
        
        .form-title p {
            color: #777;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group i {
            position: absolute;
            left: 15px;
            top: 14px;
            color: #999;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        input:focus {
            border-color: var(--orange2);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 183, 102, 0.3);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .btn-primary {
            background: var(--green);
            color: white;
            box-shadow: 0 4px 10px rgba(104, 211, 136, 0.4);
        }
        
        .btn-primary:hover {
            background: #5abf78;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(104, 211, 136, 0.5);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
        }
        
        .success {
            background: var(--bluegreen);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #ffcccc;
            color: #d8000c;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .left-panel {
                order: 2;
                padding: 30px;
            }
            
            .right-panel {
                order: 1;
                padding: 30px;
            }
            
            .reviews-container {
                height: 250px;
            }
            
            body {
                background-attachment: scroll;
            }
        }
        
        @media (max-width: 500px) {
            .left-panel, .right-panel {
                padding: 20px;
            }
            
            .review-text {
                font-size: 14px;
            }
            
            .form-title h2 {
                font-size: 24px;
            }
            
            .container {
                border-radius: 15px;
            }
        }
           .login-prompt {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            color: #777;
            font-size: 16px;
            animation: fadeIn 0.5s ease-out 0.6s both;
        }

            .login-prompt a {
                color: var(--orange1);
                text-decoration: none;
                font-weight: 500;
                transition: color 0.3s;
            }

            .login-prompt a:hover {
                color: var(--orange2);
                text-decoration: underline;
            }
        
        
        /* Animation for form elements */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group, .btn, .message {
            animation: fadeIn 0.5s ease-out;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .btn { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="reviews-container">
                <div class="review active">
                    <p class="review-text">"This art portal transformed my creative journey. I've connected with amazing artists and found incredible inspiration for my work!"</p>
                    <div class="review-author">
                        <div class="avatar">S</div>
                        <div class="author-details">
                            <span class="author-name">Sarah Johnson</span>
                            <span class="author-title">Digital Artist</span>
                        </div>
                    </div>
                </div>
                
                <div class="review">
                    <p class="review-text">"As a traditional painter, I was skeptical about digital platforms, but this portal exceeded my expectations. The community is so supportive!"</p>
                    <div class="review-author">
                        <div class="avatar">M</div>
                        <div class="author-details">
                            <span class="author-name">Michael Chen</span>
                            <span class="author-title">Oil Painter</span>
                        </div>
                    </div>
                </div>
                
                <div class="review">
                    <p class="review-text">"I've sold more artwork through this portal in 3 months than I did in 2 years through galleries. The exposure to international buyers is incredible."</p>
                    <div class="review-author">
                        <div class="avatar">A</div>
                        <div class="author-details">
                            <span class="author-name">Aisha Patel</span>
                            <span class="author-title">Mixed Media Artist</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="review-indicators">
                <div class="indicator active" data-index="0"></div>
                <div class="indicator" data-index="1"></div>
                <div class="indicator" data-index="2"></div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="form-title">
                <h2>Create Your Account</h2>
                <p>Join our creative community today</p>
            </div>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="first_name" name="first_name" placeholder="First Name" value="<?php echo htmlspecialchars($first_name); ?>" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="last_name" name="last_name" placeholder="Last Name" value="<?php echo htmlspecialchars($last_name); ?>" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Register Now
                </button>
                 <div class="login-prompt">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                 </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Review carousel functionality
            const reviews = document.querySelectorAll('.review');
            const indicators = document.querySelectorAll('.indicator');
            let currentReview = 0;
            
            function showReview(index) {
                reviews.forEach(review => review.classList.remove('active', 'next', 'prev'));
                indicators.forEach(indicator => indicator.classList.remove('active'));
                
                reviews[index].classList.add('active');
                indicators[index].classList.add('active');
                
                // Set classes for animation
                const nextIndex = (index + 1) % reviews.length;
                const prevIndex = (index - 1 + reviews.length) % reviews.length;
                
                reviews[nextIndex].classList.add('next');
                reviews[prevIndex].classList.add('prev');
                
                currentReview = index;
            }
            
            // Auto-rotate reviews
            setInterval(() => {
                showReview((currentReview + 1) % reviews.length);
            }, 5000);
            
            // Click on indicators
            indicators.forEach(indicator => {
                indicator.addEventListener('click', function() {
                    showReview(parseInt(this.getAttribute('data-index')));
                });
            });
        });
    </script>
</body>
</html>
