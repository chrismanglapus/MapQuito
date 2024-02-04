<?php
// Include necessary files and initialize the session
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

// Function to retrieve trend data with a filter for years
function getTrendData($startYear, $endYear)
{
    global $conn;

    $trendData = array();

    // Generate an array of all months
    $allMonths = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

    // SQL query to retrieve trend data with a filter for years
    $sql = "SELECT DATE_FORMAT(date_added, '%b') AS month, COALESCE(SUM(cases), 0) AS total_cases 
            FROM heatmap_data 
            WHERE YEAR(date_added) BETWEEN $startYear AND $endYear
            GROUP BY MONTH(date_added)
            ORDER BY MONTH(date_added)";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Create an associative array to store data for quick lookup
        $dataLookup = array();
        while ($row = $result->fetch_assoc()) {
            $dataLookup[$row['month']] = $row['total_cases'];
        }

        // Fill in missing months with 0 cases
        foreach ($allMonths as $month) {
            $trendData[] = array(
                'month' => $month,
                'total_cases' => isset($dataLookup[$month]) ? $dataLookup[$month] : 0
            );
        }
    } else {
        // If there is no data for the specified years, fill in all months with 0 cases
        foreach ($allMonths as $month) {
            $trendData[] = array(
                'month' => $month,
                'total_cases' => 0
            );
        }
    }

    return $trendData;
}

// Get start and end years from the form (assuming POST method)
$startYear = isset($_POST['start_year']) ? $_POST['start_year'] : 2019;
$endYear = isset($_POST['end_year']) ? $_POST['end_year'] : 2023;

// Get trend data with the specified year range
$trendData = getTrendData($startYear, $endYear);

// TREND CHART 2019 to 2024
$trendData2019 = getTrendData(2019, 2019);
$trendData2020 = getTrendData(2020, 2020);
$trendData2021 = getTrendData(2021, 2021);
$trendData2022 = getTrendData(2022, 2022);
$trendData2023 = getTrendData(2023, 2023);
$trendData2024 = getTrendData(2024, 2024);

// Get PHP heatmap data and convert it to JavaScript array
$heatmapData = [];
$sqlHeatmap = "SELECT barangay, SUM(cases) as total_cases FROM heatmap_data GROUP BY barangay ORDER BY total_cases DESC";
$resultHeatmap = $conn->query($sqlHeatmap);
while ($rowHeatmap = $resultHeatmap->fetch_assoc()) {
    $heatmapData[] = $rowHeatmap;
}

function getBarChartData($startYear, $endYear)
{
    global $conn;

    $barChartData = array();

    // Fetch unique barangays
    $sqlBarangays = "SELECT DISTINCT barangay FROM heatmap_data";
    $resultBarangays = $conn->query($sqlBarangays);

    $barangays = array();
    while ($rowBarangay = $resultBarangays->fetch_assoc()) {
        $barangays[] = $rowBarangay['barangay'];
    }

    // Loop through barangays to build the final structure
    foreach ($barangays as $barangay) {
        // SQL query to retrieve yearly cases for the specific barangay
        $sqlCases = "SELECT YEAR(date_added) AS year, SUM(cases) AS total_cases 
                     FROM heatmap_data 
                     WHERE barangay = '$barangay' AND YEAR(date_added) BETWEEN $startYear AND $endYear
                     GROUP BY YEAR(date_added)
                     ORDER BY YEAR(date_added)";

        $resultCases = $conn->query($sqlCases);

        $yearlyData = array_fill_keys(range($startYear, $endYear), 0);

        while ($rowCases = $resultCases->fetch_assoc()) {
            $yearlyData[$rowCases['year']] = $rowCases['total_cases'];
        }

        $barChartData[] = array(
            'barangay' => $barangay,
            'data' => array_values($yearlyData)
        );
    }

    return $barChartData;
}

// BAR CHART 2019 to 2024
$barChartData2019 = getBarChartData(2019, 2019);
$barChartData2020 = getBarChartData(2020, 2020);
$barChartData2021 = getBarChartData(2021, 2021);
$barChartData2022 = getBarChartData(2022, 2022);
$barChartData2023 = getBarChartData(2023, 2023);
$barChartData2024 = getBarChartData(2024, 2024);

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <!-- Include Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/chartjs-plugin-scrollbar"></script>
    <title>Trend Chart</title>
    <style>
        h2 {
            font-size: 30px;
            color: #3498db;
            outline: auto;
            text-align: center;
            background-color: white;
        }
    </style>
</head>

<body>
    <div class="chart-container">
        <h2>TREND CHART</h2>
        <canvas id="trendChart" class="chart"></canvas>
        <h2>BAR CHART</h2>
        <canvas id="barChart" class="chart"></canvas>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Get PHP data and convert it to JavaScript array
            var trendData = <?php echo json_encode($trendData); ?>;
            var trendData2019 = <?php echo json_encode($trendData2019); ?>;
            var trendData2020 = <?php echo json_encode($trendData2020); ?>;
            var trendData2021 = <?php echo json_encode($trendData2021); ?>;
            var trendData2022 = <?php echo json_encode($trendData2022); ?>;
            var trendData2023 = <?php echo json_encode($trendData2023); ?>;
            var trendData2024 = <?php echo json_encode($trendData2024); ?>;
            var heatmapData = <?php echo json_encode($heatmapData); ?>;

            // Prepare data for Chart.js
            var labels = trendData.map(function(item) {
                return item.month;
            });

            var data2019 = trendData2019.map(function(item) {
                return item.total_cases;
            });

            var data2020 = trendData2020.map(function(item) {
                return item.total_cases;
            });

            var data2021 = trendData2021.map(function(item) {
                return item.total_cases;
            });

            var data2022 = trendData2022.map(function(item) {
                return item.total_cases;
            });

            var data2023 = trendData2023.map(function(item) {
                return item.total_cases;
            });

            var data2024 = trendData2024.map(function(item) {
                return item.total_cases;
            });

            // Create a trend chart
            var ctxTrend = document.getElementById('trendChart').getContext('2d');
            var trendChart = new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: '2019',
                            data: data2019,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            fill: false
                        },
                        {
                            label: '2020',
                            data: data2020,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            fill: false
                        },
                        {
                            label: '2021',
                            data: data2021,
                            backgroundColor: 'rgba(255, 206, 86, 0.2)',
                            borderColor: 'rgba(255, 206, 86, 1)',
                            borderWidth: 1,
                            fill: false
                        },
                        {
                            label: '2022',
                            data: data2022,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            fill: false
                        },
                        {
                            label: '2023',
                            data: data2023,
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1,
                            fill: false
                        },
                        {
                            label: '2024',
                            data: data2024,
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1,
                            fill: false
                        }
                    ]
                },
                options: {
                    scales: {
                        x: {
                            type: 'category',
                            labels: labels,
                            ticks: {
                                maxRotation: 0,
                                autoSkip: true
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });

            // Get PHP data and convert it to JavaScript array
            var barChartData2019 = <?php echo json_encode($barChartData2019); ?>;
            var barChartData2020 = <?php echo json_encode($barChartData2020); ?>;
            var barChartData2021 = <?php echo json_encode($barChartData2021); ?>;
            var barChartData2022 = <?php echo json_encode($barChartData2022); ?>;
            var barChartData2023 = <?php echo json_encode($barChartData2023); ?>;
            var barChartData2024 = <?php echo json_encode($barChartData2024); ?>;

            // Prepare data for the bar chart
            var barLabels = barChartData2019.map(function(item) {
                return item.barangay;
            });

            var barData2019 = barChartData2019.map(function(item) {
                return item.data[0];
            });

            var barData2020 = barChartData2020.map(function(item) {
                return item.data[0];
            });

            var barData2021 = barChartData2021.map(function(item) {
                return item.data[0];
            });

            var barData2022 = barChartData2022.map(function(item) {
                return item.data[0];
            });

            var barData2023 = barChartData2023.map(function(item) {
                return item.data[0];
            });

            var barData2024 = barChartData2024.map(function(item) {
                return item.data[0];
            });

            // Create a bar chart with adjusted options
            var ctxBar = document.getElementById('barChart').getContext('2d');
            var barChart = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                            label: '2019',
                            data: barData2019,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '2020',
                            data: barData2020,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '2021',
                            data: barData2021,
                            backgroundColor: 'rgba(255, 206, 86, 0.2)',
                            borderColor: 'rgba(255, 206, 86, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '2022',
                            data: barData2022,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '2023',
                            data: barData2023,
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        },
                        {
                            label: '2024 (Predictive)',
                            data: barData2024,
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true
                        },
                        y: {
                            beginAtZero: true,
                        },
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        },
                        scrollbar: {
                            enabled: true,
                        },
                    },
                    layout: {
                        padding: {
                            top: 20,
                        },
                    },
                }
            });

        });
    </script>
</body>

</html>

<?php
// Include necessary footer file
require('footer.php');
?>