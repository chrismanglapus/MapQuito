
// Example: Update chart when barangay is clicked
function showPredictionGraph(barangayName, predictionData) {
    var ctx = document.getElementById('predictChart').getContext('2d');

    // Destroy the previous chart instance (if any)
    if (window.myPredictionChart) {
        window.myPredictionChart.destroy();
    }

    // Create the updated chart
    window.myPredictionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: predictionData.weeks,
            datasets: [{
                label: `${barangayName} - Predicted Cases`,
                data: predictionData.cases,
                borderColor: 'rgba(255, 99, 132, 1)',
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
                        text: 'Number of Cases'
                    },
                    beginAtZero: true
                }
            }
        }
    });
}
