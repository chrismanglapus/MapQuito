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
    <title>Charts | Cases Per Barangay</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/charts.css">
</head>

<body>
    <div class="chart-main" id="chartMain">
        <div class="chart-container">

            <h2>Total Cases Per Barangay</h2>

            <div class="bar-chart-filters">
                <div class="filter-group">
                    <label for="yearSelectBar">Select Year:</label>
                    <select id="yearSelectBar"></select>
                </div>

                <div class="filter-group">
                    <label for="displayModeSelect">Display Mode:</label>
                    <select id="displayModeSelect">
                        <option value="top">Top 10</option>
                        <option value="bottom">Bottom 10</option>
                        <option value="all">Barangays</option>
                    </select>
                </div>
            </div>

            <div class="barChart-container">
                <canvas id="barChart"></canvas>
            </div>

            <div class="totalCasesText" id="totalCasesText">
                Total Cases this Year: <span class="case-number">0</span>
            </div>

            <div class="barangay-table-section">
                <h3>Barangay List</h3>

                <label for="sortTable">Sort Table:</label>
                <select id="sortTable">
                    <option value="cases_desc">By Cases (High to Low)</option>
                    <option value="cases_asc">By Cases (Low to High)</option>
                    <option value="alpha_asc" selected>Alphabetically (A-Z)</option>
                    <option value="alpha_desc">Alphabetically (Z-A)</option>
                </select>

                <div class="table-container">
                    <table id="barangayTable">
                        <thead>
                            <tr>
                                <th>Barangay</th>
                                <th>Total Cases</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Rows will be inserted dynamically via JS -->
                        </tbody>
                    </table>
                </div>

            </div>

            <script src="js/barChart.js" defer></script>
        </div>

    </div>

</body>

</html>