<?php
session_start();
require('connection.php');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mapquitodb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["data_id"])) {
    $dataIdToDelete = $conn->real_escape_string($_GET["data_id"]);

    // Attempt to delete the heatmap data
    $deleteDataSql = "DELETE FROM heatmap_data WHERE id = ?";
    $stmtDeleteData = $conn->prepare($deleteDataSql);
    $stmtDeleteData->bind_param("i", $dataIdToDelete);
    $stmtDeleteData->execute();

    if ($stmtDeleteData->affected_rows > 0) {
        echo "success"; // Send success message
    } else {
        echo "error"; // Send error message
    }

    // Close the statements after use
    $stmtDeleteData->close();
}

// Close the database connection
$conn->close();
?>
