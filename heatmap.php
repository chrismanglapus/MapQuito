<?php
require('main/session_not.php');
include('phps/data__heatmap.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MapQuito</title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/heatmap.css">
    <link rel="stylesheet" href="css/modals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v8.2.0/ol.css">

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.3.0"></script>

</head>

<body class="theme-orange">

    <!-- Map Container -->
    <div id="map" class="map"></div>

    <!-- Information Panel -->
    <div class="info-container" id="infoContainer">

        <!-- Filter Section -->
        <div id="filterContainer" class="filter-container">
            <label for="selectedYear" class="year-label">Select Year:</label>
            <select id="selectedYear" class="year-select" onchange="updateMap()">
                <?php if (!empty($uniqueYears)) : ?>
                    <?php foreach ($uniqueYears as $year) : ?>
                        <option value="<?= $year ?>" <?= ($selectedYear == $year) ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                <?php else : ?>
                    <option value="">No Data Available</option>
                <?php endif; ?>
            </select>

            <label for="selectedWeek" class="week-label">Select Morbidity Week:</label>
            <select id="selectedWeek" class="week-select" onchange="updateMap()">
                <?php foreach ($uniqueWeeks as $week) : ?>
                    <option value="<?= $week ?>" <?= ($selectedWeek == $week) ? 'selected' : '' ?>>Week <?= $week ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Barangay Info Card -->
        <div class="card" id="barangayContainer">
            <p class="cardHeading">Want to see case info?</p>
            <p class="cardDesc">Just hover over a barangay!</p>
            <p class="cardDesc">For more info, just click on the barangay zone.</p>
        </div>

    </div>

    <!-- Threshold Legend -->
    <div class="threshold-legend" id="thresholdContainer">
        <div class="NoCases">
            <div style="display: inline-flex; width: 1vw; height: 1vw; background: rgba(255, 217, 0, 0); border-radius: 50%; border: 0.2vw solid rgba(136, 136, 136, 0.5)"></div>
            <p>No Cases</p>
        </div>
        <div class="NoOutbreaks">
            <div style="display: inline-flex; width: 1vw; height: 1vw; background: rgba(0, 255, 0, 0.2); border-radius: 50%; border: 0.2vw solid rgba(136, 136, 136, 0.5)"></div>
            <p>No Outbreaks</p>
        </div>
        <div class="Alert">
            <div style="display: inline-flex; width: 1vw; height: 1vw; background: rgba(255, 255, 0, 0.3); border-radius: 50%; border: 0.2vw solid rgba(136, 136, 136, 0.5)"></div>
            <p>Alert Threshold</p>
        </div>
        <div class="Epidemic">
            <div style="display: inline-flex; width: 1vw; height: 1vw; background: rgba(255, 0, 0, 0.4); border-radius: 50%; border: 0.2vw solid rgba(136, 136, 136, 0.5)"></div>
            <p>Epidemic Threshold</p>
        </div>
    </div>

    <!-- Generative AI Modal -->
    <button class="cta-btn">
        <img src="assets/lamap.png" alt="cta">
        <span class="tooltip">Psst... I’ve got the latest dengue news! Wanna hear it?</span>
    </button>

    <div id="cta-details" class="cta-modal">
        <div class="cta-modal-content">
            <img src="assets/lamap.png" alt="Lamap Logo" class="cta-modal-logo">
            <div id="modal-text">Fetching dengue insights...</div>
            <button id="close-cta-modal" class="close-btn">Close</button>
        </div>
    </div>

    <!-- Barangay Information Modal -->
    <div id="modal-info" class="modal-info">
        <div class="modal-content-info">
            <div class="parent">
                <div class="grid-title"></div>
                <div class="info1"></div>
                <div class="info2"></div>
                <div class="info3"></div>

                <div class="predict-graph">
                    <canvas id="predictChart" class="predict-chart-canvas"></canvas>
                </div>

                <div class="trend-graph">
                    <canvas id="trendChart" class="trend-chart-canvas"></canvas>
                    <div id="chart-tooltip"></div>
                </div>
            </div>
            <button id="close-info-modal" class="close-info-modal-btn">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

</body>

<!-- External Scripts -->
<script src="https://cdn.jsdelivr.net/npm/ol@v8.2.0/dist/ol.js"></script>

<!-- PHP Data to JavaScript -->
<script>
    // Embed the JSON object; make sure $casesData is defined in your earlier code.
    var jsonCasesData = <?= json_encode(['casesData' => $casesData]) ?>;
    var casesData = jsonCasesData.casesData;

    var selectedYear = <?= json_encode($selectedYear) ?>;
    var selectedWeek = <?= json_encode($selectedWeek) ?>;
    var totalCases = <?= json_encode($totalCases) ?>;
    var zones = <?= json_encode($zones) ?>;
    var totalCasesYearData = <?= $totalCasesYearDataJS ?>;
</script>


<!-- Custom Scripts -->
<script src="js/index__trend.js" defer></script>
<script src="js/index__predict.js" defer></script>
<script src="js/threshold.js" defer></script>
<script src="js/heatmap.js" defer></script>
<script src="js/modals.js" defer></script>

</html>