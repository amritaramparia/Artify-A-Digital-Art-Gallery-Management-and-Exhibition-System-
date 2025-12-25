<?php
// FILE: get_subcategories.php

header('Content-Type: application/json');

$servername = "localhost";
$username = "root"; // CHANGE THIS TO YOUR DATABASE USERNAME IF DIFFERENT
$password = "";    // CHANGE THIS TO YOUR DATABASE PASSWORD IF YOU HAVE ONE
$dbname = "art";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error for debugging but provide a generic message to the user
    error_log("Database Connection Failed: " . $conn->connect_error);
    die("Connection failed. Please try again later.");
}


$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);

$subcategories = [];

if ($category_id) {
    // Use prepared statement to securely fetch subcategories
    $query = "SELECT subcategory_id, name FROM subcategories WHERE category_id = ? ORDER BY name";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $subcategories[] = $row;
        }
        $stmt->close();
    } else {
        // Log database error
        error_log("Subcategory query failed: " . $conn->error);
    }
}

// Output JSON array of subcategories
echo json_encode($subcategories);

$conn->close();
?>