<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Include necessary scripts and stylesheets -->
</head>

<body class="chart-container">
    <div class="chart">
        <hr>
        <h2>Forecast Chart</h2>
        <div style="height: 600px;">
            <canvas id="forecastChart" class="forecastChart"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            fetch('fetch_forecastData.php')
                .then(response => response.json())
                .then(data => {
                    const predictedData = data;

                    const labels = predictedData.map(entry => `Week ${entry.week}`);
                    const cases = predictedData.map(entry => entry.total_cases);

                    const ctx = document.getElementById('forecastChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Total Cases',
                                data: cases,
                                borderColor: 'blue',
                                borderWidth: 2,
                                fill: false
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Week'
                                    }
                                },
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Total Cases'
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => console.error('Error fetching data:', error));
        });
    </script>
</body>

</html>
