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
</head>

<body>
    <div class="left-panel">
        <div class="menu-title">MENU</div>
        <?php
        // Check if the key exists in the $_SESSION array
        if (isset($_SESSION['ADMIN_ROLE'])) {
            // Check user role and display menus accordingly
            if ($_SESSION['ADMIN_ROLE'] == 0) {
                // Super Admin
                echo '
                <a href="index.php" class="menu-button"><img src="assets/dashboard.png" alt="Dashboard Icon"><span>Dashboard</span></a>
                <a href="charts.php" class="menu-button"><img src="assets/chart.png" alt="Dashboard Icon"><span>Charts</span></a>
                <a href="heatmap_management.php" class="menu-button"><img src="assets/map.png" alt="Dashboard Icon"><span>Heatmap Management</span></a>
                <a href="account_management.php" class="menu-button"><img src="assets/account.png" alt="Dashboard Icon"><span>Account Management</span></a>
            ';
            } elseif ($_SESSION['ADMIN_ROLE'] == 1) {
                // Admin
                echo '
                <a href="index.php" class="menu-button"><img src="assets/dashboard.png" alt="Dashboard Icon"><span>Dashboard</span></a>
                <a href="charts.php" class="menu-button"><img src="assets/chart.png" alt="Dashboard Icon"><span>Charts</span></a>
                <a href="heatmap_management.php" class="menu-button"><img src="assets/map.png" alt="Dashboard Icon"><span>Heatmap Management</span></a>
            ';
            }
            // Add more conditions for other roles if needed
        } else {
            // Handle the case where 'ADMIN_ROLE' is not set in the session
            echo '
            <a href="index.php" class="menu-button"><img src="assets/dashboard.png" alt="Dashboard Icon"><span>Dashboard</span></a>
            <a href="charts.php" class="menu-button"><img src="assets/chart.png" alt="Dashboard Icon"><span>Charts</span></a>
            ';
        }
        ?>
    </div>
</body>

</html>