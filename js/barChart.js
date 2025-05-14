document.addEventListener("DOMContentLoaded", function() {

    // Fetch the data
    fetch('phps/fetch__bar_data.php')
        .then(response => response.json())
        .then(data => {

            const years = data.years; // List of years
            const datasets = data.datasets; // Datasets for each year (total cases per barangay)
            const barangays = data.barangays; // List of barangays

            // Filter out years with no barangay names
            const validYears = years.filter(year => {
                // Find the dataset for this year
                const yearData = datasets.find(ds => ds.year == year)?.data || [];

                // Check if any barangay in this year's data has a non-empty barangay name
                return yearData.some(entry =>
                    entry.barangay && entry.barangay.trim() !== ''
                );
            });

            // Populate the dropdown with available years that have data
            const yearSelectBar = document.getElementById("yearSelectBar");
            const totalCasesText = document.getElementById("totalCasesText");

            if (yearSelectBar && validYears.length > 0) {
                validYears.forEach(year => {
                    let option = document.createElement("option");
                    option.value = year;
                    option.textContent = year;
                    yearSelectBar.appendChild(option);
                });

                // Set the default year (assuming the first in the list is the most recent)
                let currentYear = validYears[0];
                yearSelectBar.value = currentYear;

                // Add event listener to yearSelectBar after it is populated
                yearSelectBar.addEventListener("change", function() {
                    updateChart(this.value); // Update chart when the year is changed
                });
            } else {
                console.error('No years with valid barangay data found');
                return;
            }

            const ctx = document.getElementById("barChart").getContext("2d");

            // Create the initial horizontal bar chart with empty data
            const barChart = new Chart(ctx, {
                type: 'bar', // Use 'bar' for horizontal bars
                data: {
                    labels: barangays, // List of barangays
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Allow the chart to grow in height as needed
                    indexAxis: 'y', // This is the key change to make the chart horizontal
                    scales: {
                        x: {
                            stacked: false,
                            ticks: {
                                beginAtZero: true // Ensure the x-axis starts from zero
                            }
                        },
                        y: {
                            position: 'left',
                            stacked: false,
                            ticks: {
                                autoSkip: false,
                                maxRotation: 0, // Keeps labels horizontal
                                minRotation: 0, // Prevents slanting
                                font: {
                                    size: 14, // Decrease the font size if necessary
                                },
                                padding: 20, // Add padding to prevent label overlap
                                offset: true // Moves labels away from bars
                            },
                            grid: {
                                display: true // Optionally hide the grid lines to give more space
                            },
                        }
                    },
                    elements: {
                        bar: {
                            borderWidth: 2,
                            borderRadius: 5, // Rounded corners
                            barThickness: 10, // Adjust to fit labels better
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            function updateChart(selectedYear) {
                // Find the dataset for the selected year
                const yearData = datasets.find(ds => ds.year == selectedYear)?.data || [];
                if (!yearData.length) {
                    console.error('No data found for year:', selectedYear);
                    return;
                }

                // Extract total cases for each barangay, filtering out those without a barangay name
                const totalCases = barangays
                    .filter(barangay => yearData.some(entry => entry.barangay === barangay && entry.barangay.trim() !== ''))
                    .map(barangay => {
                        const dataForBarangay = yearData.find(entry => entry.barangay === barangay);
                        return dataForBarangay ? dataForBarangay.total_cases : 0;
                    });

                const totalCasesSum = totalCases.reduce((sum, cases) => sum + Number(cases), 0);
                totalCasesText.textContent = `Total Cases this Year: ${totalCasesSum}`;

                // Get corresponding labels (barangay names) for the filtered data
                const filteredLabels = barangays
                    .filter(barangay => yearData.some(entry => entry.barangay === barangay && entry.barangay.trim() !== ''));

                // Update the chart with the new data
                barChart.data.labels = filteredLabels;
                barChart.data.datasets = [{
                    label: `Cases in ${selectedYear}`,
                    data: totalCases,
                    backgroundColor: "#00A650",
                    borderColor: "#00703C",
                    borderWidth: 1
                }];
                barChart.update();
            }

            // Initial chart load for the default year
            updateChart(validYears[0]);
        })
        .catch(error => console.error('Error fetching data:', error));
});