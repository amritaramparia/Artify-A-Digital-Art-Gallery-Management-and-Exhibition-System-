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

// Check what action to perform
$action = isset($_GET['action']) ? $_GET['action'] : 'logout';

if ($action === 'delete' && isset($_SESSION['user_id'])) {
    // Delete user account
    $user_id = $_SESSION['user_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete from artists table if exists
        $delete_artist = $conn->prepare("DELETE FROM artists WHERE user_id = ?");
        $delete_artist->bind_param("i", $user_id);
        $delete_artist->execute();
        
        // Then delete from register_data
        $delete_user = $conn->prepare("DELETE FROM register_data WHERE id = ?");
        $delete_user->bind_param("i", $user_id);
        $delete_user->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Destroy session
        session_destroy();
        
        // Redirect with success message
        header("Location: index.php?message=account_deleted");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: profile.php?error=delete_failed");
        exit();
    }
    
} else {
    // Regular logout - just destroy session
    session_destroy();
    header("Location: index.php?message=logged_out");
    exit();
}

$conn->close();
?>