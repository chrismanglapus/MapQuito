<?php
session_start();
require('main/connection.php');
require('main/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charts | MapQuito</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/charts.css">
</head>

<body>
    <div class="chart-main" id="chartMain">
        <div class="chart-container">
            <?php include('charts__trend-chart.php'); ?>
        </div>
        <div class="chart-container">
            <?php include('charts__bar-chart.php'); ?>
        </div>
    </div>
</body>

</html>