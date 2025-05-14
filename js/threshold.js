const thresholdsCache = {}; // Global cache for thresholds
let weeklyData = {}; // Define weeklyData globally
if (typeof selectedYear === "undefined") {
    selectedYear = 2024; // Set default if not defined
}

fetch("/mapquito/phps/fetch__trend_data.php")
    .then(response => response.json())
    .then(data => {
        if (!data.weekly_data || Object.keys(data.weekly_data).length === 0) {
            console.error("🚨 No weekly data received!");
            return;
        }

        // ✅ Store weekly data in global scope
        window.weeklyData = data.weekly_data;
        console.log("✅ Weekly data successfully stored:", window.weeklyData);

        // ✅ Call update only when data is ready
        if (typeof updateDengueInsight === "function") {
            updateDengueInsight();
        }
    })
    .catch(error => console.error("🚨 Error fetching data:", error));


// Function to calculate thresholds
function getThresholdForWeek(selectedYear, selectedWeek) {
    // ✅ Ensure weekly data is available before running
    if (!window.weeklyData || Object.keys(window.weeklyData).length === 0) {
        console.error("🚨 weeklyData is empty! Ensure data is loaded.");
        return null;
    }

    console.log(`📌 Calculating threshold for Year ${selectedYear}, Week ${selectedWeek}`);

    const historicalYears = [];
    for (let year = selectedYear - 1; year >= selectedYear - 5; year--) {
        if (window.weeklyData[year]) {
            historicalYears.push(year);
        }
    }

    if (historicalYears.length === 0) {
        console.warn("⚠ No historical data available.");
        return null;
    }

    const weekCases = historicalYears.map(year => {
        const weekData = window.weeklyData[year]?.find(entry => entry.week === selectedWeek);
        return weekData ? parseFloat(weekData.total_cases) : 0.0;
    });

    if (weekCases.length === 0) {
        console.warn("⚠ No past data for this week.");
        return null;
    }

    const sum = weekCases.reduce((acc, val) => acc + val, 0.0);
    const mean = sum / historicalYears.length;
    const variance = weekCases.reduce((acc, val) => acc + Math.pow(val - mean, 2), 0.0) / (weekCases.length - 1);
    const sd = Math.sqrt(variance);

    return {
        mean: parseFloat(mean.toFixed(2)),
        sd: parseFloat(sd.toFixed(4)),
        alertThreshold: Math.round(mean + sd),
        epidemicThreshold: Math.round(mean + (2 * sd)),
        minnoofcases: Math.round(mean - (2 * sd)),
        maxnoofcases: Math.round(mean + (2 * sd))
    };
}

function checkDengueRisk(selectedYear, selectedWeek) {
    const currentCases = window.weeklyData[selectedYear]?.find(entry => entry.week === selectedWeek)?.total_cases || 0;
    const thresholds = getThresholdForWeek(selectedYear, selectedWeek);

    if (selectedYear < 2021) {
        console.warn("⚠ Unable to compute thresholds.");
        return "⚠️ Insufficient data to assess risk. Try selecting a more recent year.";
    }

    console.log(`🦟 Cases this week: ${currentCases}, Thresholds:`, thresholds);

    if (currentCases === 0) {
        return "🦟 No dengue cases reported this week. Stay safe and maintain precautions!";
    } else if (currentCases < thresholds.alertThreshold) {
        return `✅ Good news! Only ${currentCases} cases this week. Below the alert level! Keep up preventive measures.`;
    } else if (currentCases >= thresholds.alertThreshold && currentCases < thresholds.epidemicThreshold) {
        return `⚠️ Warning! ${currentCases} cases recorded this week. Close to the alert threshold. Stay cautious and eliminate mosquito breeding sites.`;
    } else {
        return `🚨 HIGH ALERT! ${currentCases} cases—above the epidemic threshold! Immediate action is needed. Use repellents and ensure clean surroundings.`;
    }
}


// Trigger modal update when the user selects a week
document.getElementById("selectedWeek").addEventListener("change", function () {
    const currentWeek = parseInt(this.value);

    // ✅ Ensure `totalCasesYearData` is defined
    if (typeof totalCasesYearData === "undefined") {
        console.error("🚨 totalCasesYearData is not defined!");
        return;
    }

    const currentCases = totalCasesYearData[selectedYear]?.[currentWeek] || 0;

    const message = checkDengueRisk(selectedYear, currentWeek, currentCases);

    // Update modal text dynamically
    document.getElementById("modal-text").innerText = message;

    // Show modal (assuming Bootstrap modal is used)
    $("#dengueRiskModal").modal("show");
});
