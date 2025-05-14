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

// Function to retrieve messages
function getMessages()
{
    global $conn;

    $messages = array();

    // SQL query to retrieve messages with sender and receiver details
    $sql = "SELECT messages.id, messages.sender_id, messages.receiver_id, messages.message, messages.date_time, admin_users.username AS sender_username
            FROM messages
            INNER JOIN admin_users ON messages.sender_id = admin_users.id
            ORDER BY messages.date_time DESC";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }

    return $messages;
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle button actions here (SEND MESSAGE, etc.)
    // You can use the sender ID to identify the current user
    // ...

    if (isset($_POST["send_message"])) {
        // Handle send message button
        // Use JavaScript to redirect to send_message_form.php
        echo "<script>window.location.href='send_message_form.php';</script>";
        exit();
    }
}

$messages = getMessages();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <style>
        .message-container {
            text-align: center;
            margin-top: 20px; /* Add top margin for spacing */
        }

        .create-message-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            font-size: 1em;
            margin-bottom: 15px;
        }

        .message-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            max-width: 400px;
            margin: 0 auto;
        }

        .message-content {
            text-align: center;
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .sender-info,
        .time-info {
            font-size: 0.8em;
            color: #555;
        }

        .sender-info {
            margin-bottom: 5px;
        }

        .time-info {
            text-align: right;
        }
    </style>
    <title>Messaging</title>
</head>

<body>
    <div class="message-container">
        <h2>Messages</h2>
        <a href="send_message_form.php" class="create-message-btn">Create Message</a>
        <?php
        if (!empty($messages)) {
            foreach ($messages as $message) {
                echo '<div class="message-card">';
                echo '<div class="message-content">' . $message["message"] . '</div>';
                echo '<div class="sender-info">Sender: ' . $message["sender_username"] . '</div>';
                echo '<div class="time-info">' . $message["date_time"] . '</div>';
                echo '</div>';
                echo '<br>';
                echo '<br>';
            }
        } else {
            echo "<p>No messages available.</p>";
        }
        ?>
    </div>
</body>

</html>



<?php
require('footer.php');
?>
