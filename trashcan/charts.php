<?php
session_start();
require('main/connection.php');
require('main/header.php');

//TREND DATA FILTER
//=============================================================================================================================================================================
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

//GETTING THE 5 YEARS
//=============================================================================================================================================================================
function getStartAndEndYears($defaultEndYear = 2023)
{
    $endYear = isset($_POST['end_year']) ? $_POST['end_year'] : $defaultEndYear;
    $startYear = max(date('Y') - 5, $endYear - 5);

    return array($startYear, $endYear);
}
list($startYear, $endYear) = getStartAndEndYears();


// Get trend data with the specified year range
$trendData = getTrendData($startYear, $endYear);

//TREND CHART
//=============================================================================================================================================================================
$trendDataALL = getTrendData($startYear, $endYear);
$trendDataP = getTrendData(2019, $endYear);
$trendData2019 = getTrendData(2019, 2019);
$trendData2020 = getTrendData(2020, 2020);
$trendData2021 = getTrendData(2021, 2021);
$trendData2022 = getTrendData(2022, 2022);
$trendData2023 = getTrendData(2023, 2023);

// Get PHP heatmap data and convert it to JavaScript array
$heatmapData = [];
$sqlHeatmap = "SELECT barangay, SUM(cases) as total_cases FROM heatmap_data GROUP BY barangay ORDER BY total_cases DESC";
$resultHeatmap = $conn->query($sqlHeatmap);
while ($rowHeatmap = $resultHeatmap->fetch_assoc()) {
    $heatmapData[] = $rowHeatmap;
}
//BAR CHART
//=============================================================================================================================================================================
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
$barChartData = getBarChartData(2019, 2024);
$barChartData2019 = getBarChartData(2019, 2019);
$barChartData2020 = getBarChartData(2020, 2020);
$barChartData2021 = getBarChartData(2021, 2021);
$barChartData2022 = getBarChartData(2022, 2022);
$barChartData2023 = getBarChartData(2023, 2023);
$barLabels = array_column($barChartData, 'barangay');

//RPEDICTION FORMULA
//=============================================================================================================================================================================
// Function to perform exponential smoothing and generate forecast for each month
function exponentialSmoothing($data, $alpha, $forecastMonths)
{
    $smoothed = array();
    $smoothed[0] = $data[0]; // Initial value
    for ($i = 1; $i <= count($data) + $forecastMonths; $i++) {
        if ($i <= count($data)) {
            $smoothed[$i] = $alpha * $data[$i - 1] + (1 - $alpha) * $smoothed[$i - 1];
        } else {
            // Forecast for subsequent months based on the last smoothed value
            $smoothed[$i] = $alpha * $smoothed[$i - 1] + (1 - $alpha) * $smoothed[$i - count($data)];
        }
    }
    return $smoothed;
}

// Function to retrieve the latest 5 years of data dynamically
function getLatest5YearsData()
{
    global $conn;

    $latest5YearsData = array();

    // SQL query to retrieve the latest year available in the data
    $sqlLatestYear = "SELECT MAX(YEAR(date_added)) AS latest_year FROM heatmap_data";
    $resultLatestYear = $conn->query($sqlLatestYear);
    $rowLatestYear = $resultLatestYear->fetch_assoc();
    $latestYear = $rowLatestYear['latest_year'];

    // Calculate the start year (latest year minus 4)
    $startYear = $latestYear - 4;

    // SQL query to retrieve the data for the last 5 years
    $sql = "SELECT DATE_FORMAT(date_added, '%b') AS month, YEAR(date_added) AS year, COALESCE(SUM(cases), 0) AS total_cases 
            FROM heatmap_data 
            WHERE YEAR(date_added) BETWEEN $startYear AND $latestYear
            GROUP BY YEAR(date_added), MONTH(date_added)
            ORDER BY YEAR(date_added), MONTH(date_added)";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Loop through the results and store them in the array
        while ($row = $result->fetch_assoc()) {
            $latest5YearsData[] = $row;
        }
    }

    return $latest5YearsData;
}

// Get the latest 5 years of data dynamically
$latest5YearsData = getLatest5YearsData();

// Perform exponential smoothing on the latest 5 years of data
$alpha = 0.5; // You can adjust the smoothing parameter as needed
$forecastMonths = 12; // For predicting the whole year
$smoothedData = exponentialSmoothing(array_column($latest5YearsData, 'total_cases'), $alpha, $forecastMonths);

// Get the forecasted values for the upcoming year (2024)
$forecastedCases = array_slice($smoothedData, -12); // Assuming 12 months forecast

// Add the forecasted values to the data array for next year
$trendData20xx = array();
$months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
for ($i = 0; $i < $forecastMonths; $i++) {
    $trendData20xx[] = array(
        'month' => $months[$i],
        'total_cases' => $forecastedCases[$i]
    );
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Chart</title>
</head>

<body class="chart-container">
    <div class="chart">
        <h2>Yearly Trend</h2>
        <canvas id="trendChart" class="trendChart"></canvas>
        <hr>
        <h2>Cases per Barangay</h2>
        <div class="box">
            <div class="subbox">
                <canvas id="barChart" class="trendChart"></canvas>
            </div>
        </div>
    </div>
</body>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        //TREND CHART
        //=============================================================================================================================================================================
        var trendData = <?php echo json_encode($trendData); ?>;
        var trendDataALL = <?php echo json_encode($trendDataALL); ?>;
        var trendData2019 = <?php echo json_encode($trendData2019); ?>;
        var trendData2020 = <?php echo json_encode($trendData2020); ?>;
        var trendData2021 = <?php echo json_encode($trendData2021); ?>;
        var trendData2022 = <?php echo json_encode($trendData2022); ?>;
        var trendData2023 = <?php echo json_encode($trendData2023); ?>;
        var trendData20xx = <?php echo json_encode($trendData20xx); ?>;
        var heatmapData = <?php echo json_encode($heatmapData); ?>;

        // Prepare data for Chart.js
        var labels = trendData.map(function(item) {
            return item.month;
        });

        var dataALL = trendDataALL.map(function(item) {
            return item.total_cases;
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

        var data20xx = trendData20xx.map(function(item) {
            return item.total_cases;
        });

        // Create a trend chart
        var ctxTrend = document.getElementById('trendChart').getContext('2d');
        var trendChart = new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'All Years',
                        data: dataALL,
                        backgroundColor: 'rgba(255, 255, 255, 0)',
                        borderColor: 'rgba(0, 0, 0, 0.5)',
                        borderWidth: 4,
                        fill: false,
                        hidden: true
                    }, {
                        label: '2019',
                        data: data2019,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: false,
                        hidden: true
                    },
                    {
                        label: '2020',
                        data: data2020,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        fill: false,
                        hidden: true
                    },
                    {
                        label: '2021',
                        data: data2021,
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1,
                        fill: false,
                        hidden: true
                    },
                    {
                        label: '2022',
                        data: data2022,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        fill: false,
                        hidden: true
                    },
                    {
                        label: '2023',
                        data: data2023,
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        fill: false,
                        hidden: false
                    },
                    {
                        label: 'Next Year Prediction',
                        data: data20xx,
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderDash: [5, 5],
                        fill: false,
                        hidden: true
                    }
                ]
            },
            options: {
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
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
                    },
                    zoom: {
                        zoom: {
                            wheel: {
                                enabled: true,
                            },
                            pinch: {
                                enabled: true
                            },
                            mode: 'xy',
                        },
                    },
                }
            }
        });

        //BAR CHART
        //=============================================================================================================================================================================
        var barLabels = <?php echo json_encode($barLabels); ?>;
        var barData = <?php echo json_encode(array_column($barChartData, 'data')); ?>;

        // Split barData into datasets for each year
        var barData2019 = barData.map(function(item) {
            return item[0];
        });
        var barData2020 = barData.map(function(item) {
            return item[1];
        });
        var barData2021 = barData.map(function(item) {
            return item[2];
        });
        var barData2022 = barData.map(function(item) {
            return item[3];
        });
        var barData2023 = barData.map(function(item) {
            return item[4];
        });
        var barData2024 = barData.map(function(item) {
            return item[5];
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
                        borderWidth: 1,
                        hidden: true
                    },
                    {
                        label: '2020',
                        data: barData2020,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        hidden: true
                    },
                    {
                        label: '2021',
                        data: barData2021,
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1,
                        hidden: true
                    },
                    {
                        label: '2022',
                        data: barData2022,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        hidden: true
                    },
                    {
                        label: '2023',
                        data: barData2023,
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        hidden: false
                    },
                    {
                        label: 'Next Year Prediction',
                        data: barData2024,
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1,
                        hidden: true
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            maxRotation: 0,
                            autoSkip: false
                        }
                    },
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                },
                layout: {
                    padding: {
                        top: 20,
                    },
                },
            }

        });
        var subbox = document.querySelector('.subbox');
        subbox.style.height = '365px'
        if (barChart.data.labels.length > 3) {
            var newHeight = 365 + ((barChart.data.labels.length - 3) * 20);
            subbox.style.height = `${newHeight}px`;
        }
    });
</script>

</html>

<?php
require('main/footer.php');
?>