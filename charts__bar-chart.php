<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yearly Bar Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <h2>Total Cases Per Barangay</h2>
    <select id="yearSelectBar"></select>
    <div id="totalCasesText">Total Cases this Year: 0</div>
    <div class="barChart-container">
        <canvas id="barChart"></canvas>
    </div>

    <script src="js/barChart.js" defer></script>

</body>

</html>