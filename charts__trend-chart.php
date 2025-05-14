<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dengue Cases Trend</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>

<body>

    <h2>Dengue Cases Trend by Morbidity Week</h2>
    <select id="yearSelectTrend"></select>

    <div class="trendChart-container">
        <canvas id="trendChart"></canvas>

        <div id="dynamic-legend">
            <div class="legend-item">
                <div style="display: inline-flex; width: 12px; height: 12px; background: rgba(255, 215, 0, 0.9); border-radius: 50%;"></div>
                <strong>Alert Threshold:</strong> <span id="alert-threshold"></span>
            </div>
            <div class="legend-item">
                <div style="display: inline-flex; width: 12px; height: 12px; background: #DC143C; border-radius: 50%;"></div>
                <strong>Epidemic Threshold:</strong> <span id="epidemic-threshold"></span>
            </div>
        </div>
    </div>

    <div id="chart-tooltip" class="chart-tooltip"></div>

    <script src="js/trendChart.js" defer></script>

</body>

</html>