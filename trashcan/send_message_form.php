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

// Function to send a message
function sendMessage($receiverUsername, $message) {
    global $conn;

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, date_time) VALUES ((SELECT id FROM admin_users WHERE username = ?), (SELECT id FROM admin_users WHERE username = ?), ?, NOW())");
    
    // Check if the prepare statement was successful
    if ($stmt === false) {
        return "Error: " . $conn->error;
    }

    $stmt->bind_param("sss", $_SESSION['username'], $receiverUsername, $message);

    if ($stmt->execute()) {
        return "Message sent successfully";
    } else {
        return "Error: " . $stmt->error;
    }

    $stmt->close();
}


// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $receiverUsername = $_POST["receiver"];
    $message = $_POST["message"];

    // Send a message
    $successMessage = sendMessage($receiverUsername, $message);
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
    <title>Message Form</title>
</head>
<body>
    <div class="form-container">
        <h2>Compose Message</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="message-form">
            <div class="form-group">
                <label for="receiver">Receiver:</label>
                <input type="text" name="receiver" required>
            </div>

            <div class="form-group">
                <label for="message">Message:</label>
                <textarea name="message" rows="4" cols="50" required></textarea>
            </div>

            <div class="send-message">
                <input type="submit" value="Send Message">
            </div>
        </form>

        <?php
        // Display success message if set
        if (isset($successMessage)) {
            echo "<p>$successMessage</p>";
            // Add JavaScript to redirect or refresh the page after a delay
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'message_form.php';
                    }, 3000);
                  </script>";
        }
        ?>
    </div>
</body>
</html>

<?php
require('footer.php');
?>
