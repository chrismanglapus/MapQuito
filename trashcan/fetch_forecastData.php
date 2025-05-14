<?php
include 'main/connection.php';

// Function to fetch historical data and predict the next 5 weeks using Holt-Winters Exponential Smoothing
function fetchHistoricalAndPredictedData($conn)
{
    // Fetch historical data
    $historical_sql = "SELECT WEEK(date_added) AS week, SUM(cases) AS total_cases
                      FROM heatmap_data
                      WHERE date_added <= (SELECT MAX(date_added) FROM heatmap_data WHERE WEEK(date_added) = 37 AND YEAR(date_added) = 2023)
                      GROUP BY WEEK(date_added)
                      ORDER BY WEEK(date_added) DESC
                      LIMIT 5";
    $historical_result = $conn->query($historical_sql);

    $historical_data = array();
    if ($historical_result) {
        if ($historical_result->num_rows > 0) {
            while ($row = $historical_result->fetch_assoc()) {
                $historical_data[] = array(
                    'week' => $row['week'],
                    'total_cases' => $row['total_cases']
                );
            }
        } else {
            echo json_encode(array("error" => "No historical data fetched from the database."));
            return;
        }
    } else {
        echo json_encode(array("error" => "Error executing historical SQL query: " . $conn->error));
        return;
    }

    // Predict the next 5 weeks using Holt-Winters Exponential Smoothing
    $historical_cases = array_column($historical_data, 'total_cases');
    $predicted_data = holtWintersExponentialSmoothing($historical_cases, 5);

    // Combine historical and predicted data
    $data = array_merge($historical_data, $predicted_data);

    // Output JSON
    echo json_encode($data);
}

// Function to perform Holt-Winters Exponential Smoothing
function holtWintersExponentialSmoothing($data, $forecastPeriod)
{
    $alpha = 0.1; // Smoothing parameter for level
    $beta = 0.1; // Smoothing parameter for trend
    $gamma = 0.1; // Smoothing parameter for seasonality
    $seasonalityPeriod = 1; // Assuming no seasonality for weekly data

    // Initialize level, trend, and seasonal components
    $level = $data[0];
    $trend = ($data[1] - $data[0]) / 2;
    $seasonalComponents = array_fill(0, $seasonalityPeriod, 0);

    // Perform Holt-Winters Exponential Smoothing
    $predicted_data = array();
    foreach ($data as $index => $value) {
        // Update level, trend, and seasonal components
        $previousLevel = $level;
        $level = $alpha * $value + (1 - $alpha) * ($level + $trend);
        $trend = $beta * ($level - $previousLevel) + (1 - $beta) * $trend;
        $seasonalComponents[$index % $seasonalityPeriod] = $gamma * ($value - $previousLevel) + (1 - $gamma) * $seasonalComponents[$index % $seasonalityPeriod];

        // Predict the next value
        if ($index >= $seasonalityPeriod - 1) {
            $nextIndex = $index + 1;
            $predictedValue = $level + $nextIndex * $trend + $seasonalComponents[$nextIndex % $seasonalityPeriod];
            $predicted_data[] = array(
                'week' => $nextIndex + 37, // Week number relative to the base week
                'total_cases' => round($predictedValue)
            );
        }
    }

    // Return the predicted data for the forecast period
    return array_slice($predicted_data, -$forecastPeriod);
}

fetchHistoricalAndPredictedData($conn);
?>
