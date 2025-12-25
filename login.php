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

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // First check if it's an admin login
    $admin_sql = "SELECT * FROM admin_users WHERE email = ?";
    $admin_stmt = $conn->prepare($admin_sql);
    $admin_stmt->bind_param("s", $email);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    
    if ($admin_result->num_rows == 1) {
        $admin = $admin_result->fetch_assoc();
        // Use the actual password from database instead of hardcoded value
        if ($password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['user_role'] = 'admin';
            header("Location: adminpanel.php");
            exit();
        }
    }
    
    // Check if user exists in register_data table
    $sql = "SELECT id, first_name, last_name, email, password FROM register_data WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password (using simple comparison for demo)
        if ($password === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Check if user is an approved artist
            $artistCheckSql = "SELECT * FROM artists WHERE user_id = ? AND approval_status = 'approved'";
            $artistStmt = $conn->prepare($artistCheckSql);
            $artistStmt->bind_param("i", $user['id']);
            $artistStmt->execute();
            $artistResult = $artistStmt->get_result();
            
            if ($artistResult->num_rows > 0) {
                $artistData = $artistResult->fetch_assoc();
                $_SESSION['artist_id'] = $artistData['artist_id'];
                $_SESSION['artist_name'] = $artistData['artist_name'];
                $_SESSION['is_artist'] = true;
                $_SESSION['user_role'] = 'artist';
                
                // Redirect to artist dashboard or homepage with artist header
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['is_artist'] = false;
                $_SESSION['user_role'] = 'user';
                // Redirect to regular user homepage
                header("Location: index.php");
                exit();
            }
        } else {
            $login_error = "Invalid email or password!";
        }
    } else {
        $login_error = "Invalid email or password!";
    }
    
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Artify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain the same */
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
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            background: linear-gradient(120deg, var(--orange1), var(--orange2));
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .login-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 18px;
        }
        
        .login-content {
            padding: 40px;
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
        
        .btn-login {
            background: var(--orange1);
            color: white;
            box-shadow: 0 4px 10px rgba(244, 58, 9, 0.4);
        }
        
        .btn-login:hover {
            background: #e03507;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(244, 58, 9, 0.5);
        }
        
        .btn-register {
            background: var(--green);
            color: white;
            box-shadow: 0 4px 10px rgba(104, 211, 136, 0.4);
            margin-top: 15px;
            text-decoration: none;
        }
        
        .btn-register:hover {
            background: #5abf78;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(104, 211, 136, 0.5);
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
            font-weight: 500;
        }
        
        .error {
            background: #ffcccc;
            color: #d8000c;
            border: 1px solid #f5c6cb;
        }
        
        .login-options {
            text-align: center;
            margin-top: 20px;
            color: #777;
        }
        
        .login-options a {
            color: var(--orange1);
            text-decoration: none;
        }
        
        .login-options a:hover {
            text-decoration: underline;
        }
        
        .admin-note {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background: #e7f3ff;
            border-radius: 8px;
            font-size: 14px;
            color: #0066cc;
        }
        
        @media (max-width: 500px) {
            .container {
                margin: 10px;
            }
            
            .login-header, .login-content {
                padding: 25px;
            }
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
        .btn { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-header">
            <h1><i class="fas fa-palette"></i> Welcome Back</h1>
            <p>Sign in to your Artify account</p>
        </div>
        
        <div class="login-content">
            <?php if ($login_error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
           <!-- <div class="admin-note">
                <i class="fas fa-info-circle"></i> Admin Test Accounts:<br>
                admin123@gmail.com / admin123<br>
                admin456@gmail.com / admin456
            </div>-->
            
            <div class="login-options">
                <p>Don't have an account? <a href="registration.php">Register now</a></p>
            </div>
            
            <a href="registration.php" class="btn btn-register">
                <i class="fas fa-user-plus"></i> Create New Account
            </a>
        </div>
    </div>
</body>
</html>