<?php
require('connection.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MapQuito</title>
    <link rel="stylesheet" href="css/styles.css">
    <script>
        // JavaScript function to toggle the dropdown
        function toggleDropdown() {
            var dropdownContent = document.getElementById("dropdown-content");
            if (dropdownContent) {
                dropdownContent.classList.toggle("show");
            }
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</head>

<body>
    <div class="top-panel">
        <div class="logo-title">
                <img src="assets/newlogo.png" alt="Logo">
        </div>

        <div class="login-button">
            <?php
            if (isset($_SESSION['username'])) {
                echo '<div class="dropdown">';
                echo '<button class="dropbtn" onclick="toggleDropdown()">Welcome, ' . htmlspecialchars($_SESSION['username']) . '! </button>';
                echo '<div class="dropdown-content" id="dropdown-content">';
                echo '<a href="logout.php">Logout</a>';
                // Add additional dropdown items as needed
                echo '</div>';
                echo '</div>';
            } else {
                echo '<a href="login.php">LOGIN </a>';
            }
            ?>
        </div>
    </div>
</body>

</html>
