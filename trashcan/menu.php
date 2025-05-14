<?php
require('main/connection.php');
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
    <div id="loader"></div>
    <div class="left-panel" id="left-panel">
        <div class="menu-title">MENU</div>
        <?php
        // Get the current page name
        $current_page = basename($_SERVER['PHP_SELF']);

        if (isset($_SESSION['ADMIN_ROLE'])) {
            // Check user role and display menus accordingly
            $commonMenus = '
                <a href="index.php" class="menu-button ' . (($current_page == 'index.php') ? 'active' : '') . '"><img src="assets/dashboard.png" alt="Dashboard Icon"><span>Dashboard</span></a>
                <a href="chart.html.php" class="menu-button ' . (($current_page == 'chart.html.php') ? 'active' : '') . '"><img src="assets/chart.png" alt="Dashboard Icon"><span>Charts</span></a>
            ';

            if ($_SESSION['ADMIN_ROLE'] == 0) {
                // Super Admin
                echo $commonMenus . '
                    <a href="heatmap_management.php" class="menu-button ' . (($current_page == 'heatmap_management.php') ? 'active' : '') . '"><img src="assets/map.png" alt="Dashboard Icon"><span>Heatmap Management</span></a>
                    <a href="account_management.php" class="menu-button ' . (($current_page == 'account_management.php') ? 'active' : '') . '"><img src="assets/account.png" alt="Dashboard Icon"><span>Account Management</span></a>
                ';
            } elseif ($_SESSION['ADMIN_ROLE'] == 1) {
                // Admin
                echo $commonMenus . '
                    <a href="heatmap_management.php" class="menu-button ' . (($current_page == 'heatmap_management.php') ? 'active' : '') . '"><img src="assets/map.png" alt="Dashboard Icon"><span>Heatmap Management</span></a>
                ';
            }
        } else {
            // Default menus for users without specific roles
            echo '
                <a href="index.php" class="menu-button ' . (($current_page == 'index.php') ? 'active' : '') . '"><img src="assets/dashboard.png" alt="Dashboard Icon"><span>Dashboard</span></a>
                <a href="chart.html.php" class="menu-button ' . (($current_page == 'chart.html.php') ? 'active' : '') . '"><img src="assets/chart.png" alt="Dashboard Icon"><span>Charts</span></a>
            ';
        }
        ?>
    </div>
    <script>
        window.addEventListener("load", function() {
            document.getElementById("loader").classList.add("hidden");
        });
    </script>

</body>

</html>