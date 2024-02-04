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

// Function to retrieve users with role 1
function getUsersWithRoleOne()
{
    global $conn;

    $users = array();

    // SQL query to retrieve users with role 1
    $sql = "SELECT id, fname, mi, lname, username, password, mobile, email, status FROM admin_users WHERE role = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }

    return $users;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle button actions here (EDIT, DELETE, etc.)
    // You can use the user ID to identify the specific user

    if (isset($_POST["edit"])) {
        // Handle edit button
        $userIdToEdit = $_POST["edit"];
        // Use JavaScript to redirect to edit_user.php with user ID
        echo "<script>window.location.href='edit_user.php?user_id=$userIdToEdit';</script>";
        exit();
    }

    if (isset($_POST["delete"])) {
        // Handle delete button
        $userIdToDelete = $_POST["delete"];
        // Use JavaScript to redirect to delete_user.php with user ID
        echo "<script>window.location.href='delete_user.php?user_id=$userIdToDelete';</script>";
        exit();
    }
}

$usersWithRoleOne = getUsersWithRoleOne();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <title>User Management</title>
    <style>
        h2 {
            font-size: 30px;
            color: #3498db;
            outline: auto;
            text-align: center;
            background-color: white;
        }
    </style>
</head>

<body>
    <div class="table-container">
        <h2>LIST OF USERS</h2>
        <a href="account_management_form.php" class="create-account-btn">Create Account</a>
        <?php
        if (!empty($usersWithRoleOne)) {
            echo "<table>
                    <tr>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>";

            foreach ($usersWithRoleOne as $user) {
                $statusClass = ($user["status"] == 1) ? 'active-button' : 'inactive-button';
                $statusText = ($user["status"] == 1) ? 'ACTIVE' : 'INACTIVE';

                echo "<tr>
                        <td>" . $user["fname"] . "</td>
                        <td>" . $user["lname"] . "</td>
                        <td>" . $user["username"] . "</td>
                        <td>" . $user["password"] . "</td>
                        <td>" . $user["mobile"] . "</td>
                        <td>" . $user["email"] . "</td>
                        <td>";
                echo "<div class='action-buttons'>";
                echo "<button class='toggle-button $statusClass' onclick='toggleUserStatus(" . $user["id"] . "," . $user["status"] . ")'>$statusText</button>";
                echo "<form method='post' action='" . $_SERVER["PHP_SELF"] . "' style='display:inline;'>";
                echo "<button class='edit-button' name='edit' value='" . $user["id"] . "'>EDIT</button>";
                echo "<button class='delete-button' name='delete' value='" . $user["id"] . "' onclick='return confirmDelete(" . $user["id"] . ")'>DELETE</button>";
                echo "</form>";
                echo "</div>";
                echo "</td></tr>";
            }

            echo "</table>";
        } else {
            echo "<p>No accounts created. Create one now!</p>";
        }
        ?>

    </div>

    <script src="javascripts/account_management.js"></script>

</body>

</html>

<?php
require('footer.php');
?>