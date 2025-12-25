<?php
// user_session.php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'art';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$isArtist = false;
$isAdmin = false;
$userName = "Guest";
$userInitial = "G";

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Check if user is an approved artist
    $artistCheckSql = "SELECT * FROM artists WHERE user_id = ? AND approval_status = 'approved'";
    $stmt = $conn->prepare($artistCheckSql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $artistResult = $stmt->get_result();
    
    if ($artistResult->num_rows > 0) {
        $isArtist = true;
        $artistData = $artistResult->fetch_assoc();
        $userName = $artistData['artist_name'];
        $userInitial = strtoupper(substr($userName, 0, 1));
    } else {
        // Get regular user name
        $userSql = "SELECT first_name, last_name FROM register_data WHERE id = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows > 0) {
            $userData = $userResult->fetch_assoc();
            $userName = $userData['first_name'] . ' ' . $userData['last_name'];
            $userInitial = strtoupper(substr($userName, 0, 1));
        }
    }
}

// Check if admin is logged in
if (isset($_SESSION['admin_id'])) {
    $isAdmin = true;
    $userName = "Admin";
    $userInitial = "A";
}
?>