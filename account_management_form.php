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

// Function to create a new user account
function createAccount($username, $password, $email, $mobile, $lname, $mi, $fname)
{
    global $conn;

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, role, email, mobile, lname, mi, fname) VALUES (?, ?, 1, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $username, $password, $email, $mobile, $lname, $mi, $fname);

    if ($stmt->execute()) {
        return "Account created successfully";
    } else {
        return "Error: " . $stmt->error;
    }

    $stmt->close();
}


// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $superAdminUsername = $_POST["username"];
    $superAdminPassword = $_POST["password"];
    $superAdminEmail = $_POST["email"];
    $superAdminMobile = $_POST["mobile"];
    $superAdminLname = $_POST["lname"];
    $superAdminMi = $_POST["mi"];
    $superAdminFname = $_POST["fname"];

    // Create a admin account
    $successMessage = createAccount($superAdminUsername, $superAdminPassword, $superAdminEmail, $superAdminMobile, $superAdminLname, $superAdminMi, $superAdminFname);
}


// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <title>Account Management</title>
</head>

<body>
    <div class="form-container">
        <h2>Create Admin Account</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="account-form">
            <div class="form-group">
                <label for="fname">First Name:</label>
                <input type="text" name="fname" required>
            </div>

            <div class="form-group">
                <label for="mi">Middle Initial:</label>
                <input type="text" name="mi" maxlength="1">
            </div>

            <div class="form-group">
                <label for="lname">Last Name:</label>
                <input type="text" name="lname" required>
            </div>

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" pattern="[^\s]+" title="Spacebar not allowed" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="text" name="password" pattern="[^\s]+" title="Spacebar not allowed" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" pattern="[^\s]+" title="Spacebar not allowed" required>
            </div>

            <div class="form-group">
                <label for="mobile">Mobile:</label>
                <input type="text" name="mobile" maxlength="11" pattern="[^\s]+" title="Spacebar not allowed" required>
            </div>

            <div class="create-account">
                <input type="submit" value="Create Account">
            </div>
        </form>


        <?php
        // Display success message if set
        if (isset($successMessage)) {
            echo "<div id='update-container' style='text-align: center; margin-top: 15px;'>";
            echo "<p>$successMessage</p>";
            echo "<img src='assets/check.gif' id='loading-icon' alt='Check Icon' style='width: 50px;'>";
            // Add JavaScript to redirect or refresh the page after a delay
            echo "<script>
                    setTimeout(function() {
                        document.getElementById('update-container').style.display = 'none';
                        window.location.href = 'account_management.php'; // Replace 'your_redirect_page.php' with the actual page you want to redirect to
                    }, 5000); // 5000 milliseconds (5 seconds) delay before redirecting
                  </script>";
        }
        ?>

    </div>
</body>

</html>

<?php
require_once 'footer.php';
?>