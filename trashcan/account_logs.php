<?php
session_start();
require('connection.php');
require('header.php');
require('menu.php');

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

// Alter the table to add login_date and logout_date columns
$sqlAlterTable = "ALTER TABLE admin_users ADD COLUMN login_date DATETIME, ADD COLUMN logout_date DATETIME";
$conn->query($sqlAlterTable);

// Function to update login date
function updateLoginDate($userId)
{
    global $conn;
    $sqlUpdateLoginDate = "UPDATE admin_users SET login_date = NOW() WHERE id = $userId";
    $conn->query($sqlUpdateLoginDate);
}

// Function to update logout date
function updateLogoutDate($userId)
{
    global $conn;
    $sqlUpdateLogoutDate = "UPDATE admin_users SET logout_date = NOW() WHERE id = $userId";
    $conn->query($sqlUpdateLogoutDate);
}

// Check if user is logging in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    updateLoginDate($userId);
} elseif (isset($_SESSION['logout_requested'])) {
    // Check if user is logging out
    $userId = $_SESSION['logout_requested'];
    updateLogoutDate($userId);
    // Destroy the session
    session_destroy();
}

// Function to retrieve user logs
function getUserLogs()
{
    global $conn;

    $logs = array();

    // SQL query to retrieve user logs
    $sql = "SELECT id, username, mobile, email, 
            DATE_FORMAT(login_date, '%Y-%m-%d %h:%i %p') as formatted_login_date, 
            DATE_FORMAT(logout_date, '%Y-%m-%d %h:%i %p') as formatted_logout_date
            FROM admin_users";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    return $logs;
}

$userLogs = getUserLogs();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <title>User Logs</title>
</head>

<body>
    <div class="table-container">
        <h2>User Logs</h2>
        <?php
        if (!empty($userLogs)) {
            echo "<table><tr><th>ID</th><th>Username</th><th>Mobile</th><th>Email</th><th>Login Date</th><th>Logout Date</th></tr>";

            foreach ($userLogs as $log) {
                echo "<tr><td>" . $log["id"] . "</td><td>" . $log["username"] . "</td><td>" . $log["mobile"] . "</td><td>" . $log["email"] . "</td><td>" . $log["formatted_login_date"] . "</td><td>" . $log["formatted_logout_date"] . "</td></tr>";
            }

            echo "</table>";
        } else {
            echo "<p>No user logs available.</p>";
        }
        ?>

    </div>

</body>

</html>

<?php
require('footer.php');
?>
