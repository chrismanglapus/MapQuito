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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <title>Charts | Weekly Trend</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/charts.css">
</head>

<body>
    <div class="chart-main" id="chartMain">
        <div class="chart-container">

            <h2>Dengue Cases Trend by Morbidity Week</h2>

            <div class="bar-chart-filters">
                <div class="filter-group">
                    <label for="yearSelectBar">Select Year:</label>
                    <select id="yearSelectTrend"></select>
                </div>
            </div>

            <div class="trendChart-container">
                <canvas id="trendChart"></canvas>

                <div id="dynamic-legend">
                    <div class="legend-item">
                        <span class="legend-box" style="background-color: rgba(255, 215, 0, 0.9);"></span>
                        <strong>Alert Threshold:</strong>
                        <span id="alert-threshold" class="legend-value"></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-box" style="background-color: #DC143C;"></span>
                        <strong>Epidemic Threshold:</strong>
                        <span id="epidemic-threshold" class="legend-value"></span>
                    </div>
                </div>

            </div>

            <div id="chart-tooltip" class="chart-tooltip"></div>

            <script src="js/trendChart.js" defer></script>
        </div>
    </div>
</body>

</html>