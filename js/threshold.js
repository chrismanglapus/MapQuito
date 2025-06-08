const thresholdsCache = {}; // Global cache for thresholds
let weeklyData = {}; // Define weeklyData globally
if (typeof selectedYear === "undefined") {
  selectedYear = 2024; // Set default if not defined
}

fetch("/mapquito/phps/fetch__trend_data.php")
  .then((response) => response.json())
  .then((data) => {
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
  .catch((error) => console.error("🚨 Error fetching data:", error));

// Function to calculate thresholds
function getThresholdForWeek(selectedYear, selectedWeek) {
  // ✅ Ensure weekly data is available before running
  if (!window.weeklyData || Object.keys(window.weeklyData).length === 0) {
    console.error("🚨 weeklyData is empty! Ensure data is loaded.");
    return null;
  }

  console.log(
    `📌 Calculating threshold for Year ${selectedYear}, Week ${selectedWeek}`
  );

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

  const weekCases = historicalYears.map((year) => {
    const weekData = window.weeklyData[year]?.find(
      (entry) => entry.week === selectedWeek
    );
    return weekData ? parseFloat(weekData.total_cases) : 0.0;
  });

  if (weekCases.length === 0) {
    console.warn("⚠ No past data for this week.");
    return null;
  }

  const sum = weekCases.reduce((acc, val) => acc + val, 0.0);
  const mean = sum / historicalYears.length;
  const variance =
    weekCases.reduce((acc, val) => acc + Math.pow(val - mean, 2), 0.0) /
    (weekCases.length - 1);
  const sd = Math.sqrt(variance);

  return {
    mean: parseFloat(mean.toFixed(2)),
    sd: parseFloat(sd.toFixed(4)),
    alertThreshold: Math.round(mean + sd),
    epidemicThreshold: Math.round(mean + 2 * sd),
    minnoofcases: Math.round(mean - 2 * sd),
    maxnoofcases: Math.round(mean + 2 * sd),
  };
}

function checkDengueRisk(selectedYear, selectedWeek) {
  const currentCases =
    window.weeklyData[selectedYear]?.find(
      (entry) => entry.week === selectedWeek
    )?.total_cases || 0;
  const thresholds = getThresholdForWeek(selectedYear, selectedWeek);

  let preventionMessage = "";

  // Helper function for badge style
  function caseBadge(count, color) {
    return `<span style="
        font-size: 1.8em; 
        font-weight: 700; 
        color: white; 
        background-color: ${color}; 
        padding: 4px 12px; 
        border-radius: 16px;
        box-shadow: 0 0 8px ${color}80;
        margin-left: 8px;
        display: inline-block;
        min-width: 48px;
        text-align: center;
      ">${count}</span>`;
  }

  if (selectedYear < 2021 || !thresholds) {
    preventionMessage = `
        <h3 style="color: #6b7280; display: flex; align-items: center; justify-content: center; gap: 8px;">
        🧐 Data Unavailable
        </h3>  
        <p>Currently, there is insufficient data to assess the dengue risk level for this barangay.</p>
        <p><strong>What You Can Do:</strong></p>
        <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
          <li style="margin-bottom: 6px;">📊 Stay updated on dengue risk levels.</li>
          <li style="margin-bottom: 6px;">📢 Maintain cleanliness to prevent mosquito breeding.</li>
          <li>🕵️ Monitor and report any suspected dengue cases.</li>
        </ul>
      `;
  } else if (currentCases === 0) {
    preventionMessage = `
    <h3 style="color: #2563eb; display: flex; align-items: center; justify-content: center; gap: 8px;">
      🛡️ No Dengue Cases
    </h3>
    
    <p>No dengue cases have been reported citywide this week.</p>
    <p><strong>Preventive Reminders:</strong></p>
    <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
      <li style="margin-bottom: 6px;">🛡️ Keep surroundings clean and dry to prevent mosquito breeding.</li>
      <li style="margin-bottom: 6px;">🛡️ Encourage residents to use mosquito repellent and nets.</li>
      <li>🛡️ Monitor symptoms and seek medical help if needed.</li>
    </ul>
  `;
  
  } else if (currentCases < thresholds.alertThreshold) {
    preventionMessage = `
    <h3 style="color: #16a34a; display: flex; align-items: center; justify-content: center; gap: 8px;">
    ✅ Dengue Risk Low
  </h3>
  
        <div style="
          background-color: #d1fae5; 
          border-left: 6px solid #16a34a; 
          padding: 10px 15px; 
          margin-bottom: 12px;
          font-weight: 600;
          font-size: 1.1em;
          color: #065f46;
          display: flex;
          align-items: center;
        ">
          Cases this week: ${caseBadge(currentCases, "#16a34a")}
        </div>
        <p>Below the alert threshold (<strong>${
          thresholds.alertThreshold
        }</strong>).</p>
        <p><strong>Keep up with these preventive measures:</strong></p>
        <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
          <li style="margin-bottom: 6px;">✅ Dispose of containers that collect water (e.g., tires, buckets).</li>
          <li style="margin-bottom: 6px;">✅ Wear protective clothing and apply insect repellent.</li>
          <li>✅ Encourage community involvement in sanitation efforts.</li>
        </ul>
      `;
  } else if (
    currentCases >= thresholds.alertThreshold &&
    currentCases < thresholds.epidemicThreshold
  ) {
    preventionMessage = `
    <h3 style="color: #f59e0b; display: flex; align-items: center; justify-content: center; gap: 8px;">
    ⚠️ Dengue Alert Level
  </h3>
  
        <div style="
          background-color: #fef3c7; 
          border-left: 6px solid #f59e0b; 
          padding: 10px 15px; 
          margin-bottom: 12px;
          font-weight: 600;
          font-size: 1.1em;
          color: #92400e;
          display: flex;
          align-items: center;
        ">
          Cases this week: ${caseBadge(currentCases, "#f59e0b")}
        </div>
        <p>Reached the alert threshold (<strong>${
          thresholds.alertThreshold
        }</strong>).</p>
        <p><strong>Suggested Preventive Actions:</strong></p>
        <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
          <li style="margin-bottom: 6px;">🟠 Intensify clean-up drives in all communities.</li>
          <li style="margin-bottom: 6px;">🟠 Conduct public awareness campaigns on dengue symptoms and prevention.</li>
          <li style="margin-bottom: 6px;">🟠 Report any suspected dengue cases promptly.</li>
          <li>🟠 Promote the use of mosquito nets and repellents across all barangays.</li>
        </ul>
      `;
  } else {
    preventionMessage = `
    <h3 style="color: #dc2626; display: flex; align-items: center; justify-content: center; gap: 8px;">
    🚨 Dengue Epidemic Alert!
  </h3>
  
        <div style="
          background-color: #fee2e2; 
          border-left: 6px solid #dc2626; 
          padding: 10px 15px; 
          margin-bottom: 12px;
          font-weight: 600;
          font-size: 1.1em;
          color: #991b1b;
          display: flex;
          align-items: center;
        ">
          Cases this week: ${caseBadge(currentCases, "#dc2626")}
        </div>
        <p>Surpassing the epidemic threshold (<strong>${
          thresholds.epidemicThreshold
        }</strong>).</p>
        <p><strong>Immediate Actions Required:</strong></p>
        <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
          <li style="margin-bottom: 6px;">🔴 Launch emergency mosquito control operations, including fogging.</li>
          <li style="margin-bottom: 6px;">🔴 Urge residents to seek medical attention for any dengue-like symptoms.</li>
          <li style="margin-bottom: 6px;">🔴 Enforce barangay-level inspections and cleanup campaigns.</li>
          <li>🔴 Mobilize health workers to monitor and contain outbreaks.</li>
        </ul>
      `;
  }

  return preventionMessage;
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
  document.getElementById("modal-text").innerHTML = message; // ✅ This renders the HTML

  // Show modal (assuming Bootstrap modal is used)
  $("#dengueRiskModal").modal("show");
});
