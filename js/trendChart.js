document.addEventListener("DOMContentLoaded", function() {
    const yearSelectTrend = document.getElementById("yearSelectTrend");
    let trendChart = null;
    let cachedData = null;
    let thresholdsCache = {}; // Cache for thresholds to avoid recalculating them repeatedly

    // Function to check if a year is a leap year
    function isLeapYear(year) {
        return (year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0));
    }

    function getMaxWeeksInYear(year) {
        const jan1 = new Date(year, 0, 1).getDay(); // Day of week for Jan 1
        const dec31 = new Date(year, 11, 31).getDay(); // Day of week for Dec 31
                
        // 53 weeks only if Jan 1 is Thursday or Dec 31 is Thursday (in leap year)
        if ((jan1 === 4 || dec31 === 4) && isLeapYear(year)) {
            return 53;
            }
        return 52;
    }
                
    // Fetch available years and initialize the chart
    fetch("phps/fetch__trend_data.php")
        .then(response => response.json())
        .then(data => {
            cachedData = data; // Cache the fetched data
            const years = data.years;
            years.forEach(year => {
                const option = document.createElement("option");
                option.value = year;
                option.textContent = year;
                yearSelectTrend.appendChild(option);
            });

            // Load the latest year's data initially
            updateChart(years[0], data.weekly_data);
        })
        .catch(error => console.error("Error fetching data:", error));

    yearSelectTrend.addEventListener("change", function() {
        updateChart(this.value, cachedData.weekly_data);
    });

    function calculateThresholds(weeklyData, selectedYear) {
        if (thresholdsCache[selectedYear]) {
            return thresholdsCache[selectedYear];
        }

        const historicalYears = [];
        for (let year = selectedYear - 1; year >= selectedYear - 5; year--) {
            if (weeklyData[year]) {
                historicalYears.push(year);
            }
        }

        const maxWeeks = getMaxWeeksInYear(selectedYear);
        const thresholds = Array.from({ length: maxWeeks }, (_, weekIndex) => {
        
            const weekCases = historicalYears.map(year => {
                const weekData = weeklyData[year]?.find(entry => entry.week === weekIndex + 1);
                return weekData ? parseFloat(weekData.total_cases) : 0.0;
            });

            const sum = weekCases.reduce((acc, val) => acc + val, 0.0);
            const mean = sum / historicalYears.length;
            const variance = weekCases.reduce((acc, val) => acc + Math.pow(val - mean, 2), 0.0) / (weekCases.length - 1);
            const sd = Math.sqrt(variance);

            const alertThreshold = Math.round(mean + sd);
            const epidemicThreshold = Math.round(mean + (2 * sd));
            const minnoofcases = Math.round(mean - (2 * sd));
            const maxnoofcases = Math.round(mean + (2 * sd));

            return {
                mean: parseFloat(mean.toFixed(2)),
                sd: parseFloat(sd.toFixed(4)),
                alertThreshold,
                epidemicThreshold,
                minnoofcases,
                maxnoofcases
            };
        });

        thresholdsCache[selectedYear] = thresholds;
        return thresholds;
    }

    function updateChart(selectedYear, weeklyData) {
        if (!weeklyData || !weeklyData[selectedYear]) {
            console.error("Invalid data structure for weeklyData:", weeklyData);
            return;
        }

        if (trendChart) {
            trendChart.destroy();
        }

        function getMorbidityWeeks(year) {
            if (cachedData && cachedData.weekly_data[year]) {
                const availableWeeks = cachedData.weekly_data[year].map(entry => entry.week);
                return availableWeeks.map(week => `Week ${week}`);
            }
            return [];
        }
        

        const morbidityWeeks = getMorbidityWeeks(selectedYear);
        const cases = weeklyData[selectedYear].slice(0, morbidityWeeks.length).map(entry => parseFloat(entry.total_cases) || 0.0);
        const thresholds = calculateThresholds(weeklyData, selectedYear).slice(0, morbidityWeeks.length);

        const previousYear = selectedYear - 1;
const prevYearMaxWeeks = isLeapYear(previousYear) ? 53 : 52;
const prevYearCases = weeklyData[previousYear] 
    ? weeklyData[previousYear].slice(0, prevYearMaxWeeks).map(entry => parseFloat(entry.total_cases) || 0.0) 
    : [];

        const ctx = document.getElementById("trendChart").getContext("2d");
        trendChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: morbidityWeeks,
                datasets: [{
                        label: `Cases in ${selectedYear}`,
                        data: cases,
                        backgroundColor: "#00A650",
                        borderColor: "#00703C",
                        borderWidth: 1,
                        borderRadius: 5,
                        fill: true,
                        order: 2,
                        hoverBackgroundColor: "rgba(0, 132, 255, 0.37)", // Darker green when hovered
                        hoverBorderColor: "rgb(0, 64, 121)", // Yellow border when hovered
                        hoverBorderWidth: 4 // Thicker border on hover
                    },
                    {
                        label: `Cases in ${previousYear}`,
                        data: prevYearCases,
                        backgroundColor: "rgba(0, 132, 255, 0.37)",
                        fill: true,
                        tension: 0.1,
                        order: 1,
                        type: "line",
                        hidden: true
                    },
                    {
                        label: "Alert Threshold",
                        data: thresholds.map(t => t.alertThreshold),
                        borderColor: "rgba(255, 215, 0, 0.9)",
                        borderWidth: 3,
                        fill: false,
                        type: "line",
                        order: 0
                    },
                    {
                        label: "Epidemic Threshold",
                        data: thresholds.map(t => t.epidemicThreshold),
                        borderColor: "#DC143C",
                        borderWidth: 3,
                        fill: false,
                        type: "line",
                        order: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index', // Highlights all data points on the same X-axis
                    axis: 'x', // Tracks only along the X-axis
                    intersect: false // Allows tooltip to show even when not directly on a point
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        enabled: false,
                        external: function(context) {
                            let tooltipEl = document.getElementById("chart-tooltip");
                            if (context.tooltip.opacity === 0) {
                                tooltipEl.style.opacity = "0";
                                return;
                            }
                            const weekIndex = context.tooltip.dataPoints[0].dataIndex;
                            if (weekIndex >= morbidityWeeks.length) return;
                                                        const casesData = context.tooltip.dataPoints.find(dp => dp.dataset.label.includes("Cases in"));
                            if (!casesData) {
                                tooltipEl.style.opacity = "0";
                                return;
                            }
                            const selectedYearLabel = casesData.dataset.label;
                            const selectedYearValue = casesData.raw || 0;
                            const threshold = thresholds[weekIndex];
                            let thresholdText = "";
                            if (selectedYearValue >= threshold.epidemicThreshold) {
                                thresholdText = `<span style="color:red;">🚨 EPIDEMIC THRESHOLD!</span>`;
                            } else if (selectedYearValue >= threshold.alertThreshold) {
                                thresholdText = `<span style="color:orange;">⚠️ ALERT THRESHOLD!</span>`;
                            } else {
                                thresholdText = `<span style="color:green;">✅ BELOW THRESHOLD</span>`;
                            }

                            tooltipEl.innerHTML = `
                                <div style="font-size: 18px; font-weight: bold; padding-bottom:;">WEEK ${weekIndex + 1}</div>
                                <div style="font-size: 20px; font-weight: bold;">${selectedYearLabel}: 
                                    <span style="color:#FFD700;">${selectedYearValue} cases</span>
                                </div>
                                <div style="margin-top: 5px; font-size: 18px;">${thresholdText}</div>
                                `;

                            tooltipEl.style.opacity = "1";

                            // DYNAMIC LEGEND=============================================================================================

                            let legendEl = document.getElementById("dynamic-legend");

                            const tooltipData = context.tooltip.dataPoints.find(dp => dp.dataset.label.includes("Cases in"));
                            if (!tooltipData) {
                                tooltipEl.style.opacity = "0";
                                return;
                            }

                            updateLegend(weekIndex, thresholds);

                            document.getElementById("alert-threshold").innerText = thresholds[weekIndex].alertThreshold;
                            document.getElementById("epidemic-threshold").innerText = thresholds[weekIndex].epidemicThreshold;
                            // document.getElementById("min-cases").innerText = thresholds[weekIndex].minnoofcases;
                            // document.getElementById("max-cases").innerText = thresholds[weekIndex].maxnoofcases; 

                            const weekLabel = `WEEK ${weekIndex + 1}`;

                        }
                    }
                }
            }
        });
    }

    function updateLegend(weekIndex, thresholds) {
        const dynamicLegend = document.getElementById("dynamic-legend");

        if (weekIndex === null) {
            dynamicLegend.innerHTML = `
                    <div class="legend-item">
                        <div style="width: 12px; height: 12px; background: rgba(255, 215, 0, 0.9); border-radius: 50%;"></div>
                        <strong>Alert Threshold:</strong> <span id="alert-threshold"></span>
                    </div>
                    <div class="legend-item">
                        <div style="width: 12px; height: 12px; background: #DC143C; border-radius: 50%;"></div>
                        <strong>Epidemic Threshold:</strong> <span id="epidemic-threshold"></span>
                    </div>
                    <div class="legend-item">
                        <div style="width: 12px; height: 12px; background: blue; border-radius: 50%;"></div>
                        <strong>Min No. of Cases:</strong> <span id="min-cases"></span>
                    </div>
                    <div class="legend-item"> */
                      <div style="width: 12px; height: 12px; background: purple; border-radius: 50%;"></div>
                        <strong>Max No. of Cases:</strong> <span id="max-cases"></span>
                    </div>
                `;
        } else {
            document.getElementById("alert-threshold").textContent = thresholds[weekIndex].alertThreshold;
            document.getElementById("epidemic-threshold").textContent = thresholds[weekIndex].epidemicThreshold;
            //  document.getElementById("min-cases").textContent = thresholds[weekIndex].minnoofcases;
            //  document.getElementById("max-cases").textContent = thresholds[weekIndex].maxnoofcases;
        }
    }


});