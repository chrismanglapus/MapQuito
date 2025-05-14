<!DOCTYPE html>
<html>

<head>
    <title>Weekly Trend Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            width: 100%;
            overflow-x: auto;
        }
    </style>
</head>

<body>

    <div class="chart-container">
        <canvas id="myChart" width="800" height="400"></canvas>
    </div>

    <?php
    // Database credentials
    $servername = "localhost"; // Change this if your database is hosted on a different server
    $username = "root"; // Your database username
    $password = ""; // Your database password
    $dbname = "mapquitodb"; // Your database name

    // Connect to the database
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch weekly trend data
    $sql = "SELECT WEEK(date_added) as week_number, SUM(cases) as total_cases FROM heatmap_data GROUP BY WEEK(date_added)";
    $result = $conn->query($sql);

    // Initialize arrays to store data for the chart
    $weekNumbers = [];
    $totalCases = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $weekNumbers[] = $row["week_number"];
            $totalCases[] = $row["total_cases"];
        }
    } else {
        echo "0 results";
    }

    // Close connection
    $conn->close();
    ?>

    <script>
        var weekNumbers = <?php echo json_encode($weekNumbers); ?>;
        var totalCases = <?php echo json_encode($totalCases); ?>;

        // Create data points for the chart
        var data = {
            labels: weekNumbers.map(function(week) {
                return "Week " + week;
            }),
            datasets: [{
                label: 'Total Cases',
                data: totalCases,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        };

        // Create the chart
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Perform Holt-Winters exponential smoothing for forecasting
        var alpha = 0.2; // Smoothing parameter
        var beta = 0.1; // Trend parameter
        var gamma = 0.1; // Seasonality parameter
        var seasonLength = 52; // Length of seasonality (assuming yearly seasonality)

        var forecastWeeks = [];
        var forecastData = [];

        // Initialize level, trend, and seasonal indices based on the last available data point
        var lastIndex = totalCases.length - 1;
        var level = totalCases[lastIndex];
        var trend = totalCases[lastIndex] - totalCases[lastIndex - 1];
        var seasonalIndices = totalCases.slice(Math.max(0, lastIndex - seasonLength), lastIndex).map(function(value) {
            return value / level;
        });

        // Debugging
        console.log("Initial level:", level);
        console.log("Initial trend:", trend);
        console.log("Initial seasonal indices:", seasonalIndices);

        // Forecast for the next 6 weeks instead of 5
        for (var i = 1; i <= 6; i++) {
            var lastWeek = weekNumbers[weekNumbers.length - 1] + i;
            forecastWeeks.push("Week " + lastWeek);

            // Debugging
            console.log("Forecasting for Week", lastWeek);

            // Calculate forecast using Holt-Winters method
            var forecast = (level + i * trend) * seasonalIndices[(seasonalIndices.length + i - 1) % seasonLength];

            // Debugging
            console.log("Forecast value:", forecast);

            // Check if the forecast value is NaN
            if (!isNaN(forecast)) {
                forecastData.push(forecast);

                // Update level, trend, and seasonal indices
                var newLevel = alpha * totalCases[totalCases.length - 1] / seasonalIndices[(seasonalIndices.length + i - 1) % seasonLength] + (1 - alpha) * (level + trend);
                var newTrend = beta * (newLevel - level) + (1 - beta) * trend;
                var newSeasonalIndex = gamma * totalCases[totalCases.length - 1] / newLevel + (1 - gamma) * seasonalIndices[(seasonalIndices.length + i - 1) % seasonLength];

                // Debugging
                console.log("New level:", newLevel);
                console.log("New trend:", newTrend);
                console.log("New seasonal index:", newSeasonalIndex);

                level = newLevel;
                trend = newTrend;
                seasonalIndices[(seasonalIndices.length + i - 1) % seasonLength] = newSeasonalIndex;
            } else {
                // If the forecast value is NaN, skip this iteration
                console.warn("Forecast value is NaN, skipping this iteration");
                continue;
            }
        }

        // Update the chart with the forecast
        myChart.data.datasets.push({
            label: 'Forecast',
            data: forecastData,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            fill: false
        });

        // Update the labels for the forecast weeks
        myChart.data.labels = myChart.data.labels.concat(forecastWeeks);

        // Update the chart
        myChart.update();
    </script>

</body>

</html>
