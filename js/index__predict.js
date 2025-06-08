function showPredictionGraph(
  barangayName,
  historicalData,
  predictionData,
  selectedWeek = null,
  selectedYear = null
) {
  const canvas = document.getElementById("predictChart");
  const ctx = canvas.getContext("2d");

  if (window.predictChartInstance) {
    window.predictChartInstance.destroy();
  }

  // Parse historicalData if it's a JSON string
  if (typeof historicalData === "string") {
    try {
      historicalData = JSON.parse(historicalData);
    } catch (err) {
      console.error("Failed to parse historicalData:", err);
      return;
    }
  }

  if (!Array.isArray(historicalData)) {
    console.error("historicalData must be an array");
    return;
  }

  if (!Array.isArray(predictionData)) {
    console.error("predictionData must be an array");
    return;
  }

  const yearBase = Number(selectedYear || new Date().getFullYear());
  const yearsToShow = [yearBase, yearBase - 1, yearBase - 2];

  console.log(
    "Sample historicalData entries years:",
    historicalData.slice(0, 10).map((d) => d.year || d.YEAR)
  );

  let filteredHistorical = historicalData.filter((d) => {
    const yr = Number(d.year || d.YEAR);
    return yr === yearBase;
  });

  const histMap = {};
  filteredHistorical.forEach((d) => {
    const year = d.year || d.YEAR;
    const week = parseInt(d.morbidity_week);
    const key = `${year}-${week}`;
    histMap[key] = d.cases;
  });

  console.log("Selected year:", yearBase);
  console.log("Years to show:", yearsToShow);
  console.log("Filtered historical data count:", filteredHistorical.length);
  console.log("Filtered historical sample:", filteredHistorical.slice(0, 3));
  console.log("Historical Map keys sample:", Object.keys(histMap).slice(0, 5));

  const predWeeks = predictionData.map((d) => parseInt(d.morbidity_week));
  const predCases = predictionData.map((d) => d.predicted_cases);
  const actualPredCases = predictionData.map((d) =>
    d.actual_cases !== undefined ? d.actual_cases : null
  );

  const predStartWeek = predWeeks.length > 0 ? predWeeks[0] : null;
  if (!predStartWeek) return;

  const highlightWeek = predStartWeek - 1 === 0 ? 52 : predStartWeek - 1;

  const weeksInYear = 53;

  // Estimate selectedWeek if not passed
  if (!selectedWeek) selectedWeek = highlightWeek;

  // --- NEW: Determine last week with actual data ---

  // Historical weeks with data (for selected year and previous years)
  const historicalWeeksWithData = filteredHistorical
    .filter((d) => d.cases != null && d.cases !== "")
    .map((d) => parseInt(d.morbidity_week));

  // Predicted weeks with data
  const predictionWeeksWithData = predictionData
    .filter((d) => d.predicted_cases != null && d.predicted_cases !== "")
    .map((d) => parseInt(d.morbidity_week));

  // Calculate max weeks from historical and prediction data
  const maxHistWeek = historicalWeeksWithData.length
    ? Math.max(...historicalWeeksWithData)
    : 0;
  const maxPredWeek = predictionWeeksWithData.length
    ? Math.max(...predictionWeeksWithData)
    : 0;

  const lastDataWeek = Math.max(maxHistWeek, maxPredWeek, selectedWeek);

  // Define start and end weeks for chart window (showing 10 weeks before last data week)
  const minHistWeek = historicalWeeksWithData.length
    ? Math.min(...historicalWeeksWithData)
    : 1;

  let startWeek = lastDataWeek - 10;
  if (startWeek < 1) startWeek = 1;
  if (startWeek < minHistWeek) startWeek = minHistWeek;

  let endWeek = lastDataWeek;
  if (endWeek > weeksInYear) endWeek = weeksInYear;

  const allWeeks = [];
  for (let i = startWeek; i <= endWeek; i++) {
    allWeeks.push(i);
  }

  // Find max predicted week within current allWeeks window
  const predWeeksInWindow = predWeeks.filter((w) => allWeeks.includes(w));
  const maxPredWeekInWindow = predWeeksInWindow.length
    ? Math.max(...predWeeksInWindow)
    : predStartWeek;

  // Find index of predStartWeek and maxPredWeekInWindow in allWeeks
  const predStartIndex = allWeeks.findIndex((w) => w === predStartWeek);
  const predEndIndex = allWeeks.findIndex((w) => w === maxPredWeekInWindow);

  // Combine historical and prediction data for all weeks, prioritizing selectedYear,
  // then previous years in yearsToShow, then actual predicted cases
  const allCasesCombined = allWeeks.map((w) => {
    // Try selectedYear first
    let key = `${yearBase}-${w}`;
    if (histMap[key] != null) return histMap[key];

    // Then try previous years in order
    for (let y = 1; y < yearsToShow.length; y++) {
      key = `${yearBase - y}-${w}`;
      if (histMap[key] != null) return histMap[key];
    }

    // Then actual cases from prediction data if available
    const predIndex = predWeeks.findIndex((pw) => pw === w);
    if (predIndex !== -1 && actualPredCases[predIndex] !== null)
      return actualPredCases[predIndex];

    return null;
  });

  const allCasesPredicted = allWeeks.map((w) => {
    const predIndex = predWeeks.findIndex((pw) => pw === w);
    return predIndex !== -1 ? predCases[predIndex] : null;
  });

  const highlightIndex = allWeeks.findIndex((w) => w === highlightWeek);

  const chartTitle = `Forecast: Trends from Week ${allWeeks[0]} to Week ${
    allWeeks[allWeeks.length - 1]
  } (Year ${yearBase})`;

  window.predictChartInstance = new Chart(ctx, {
    type: "line",
    data: {
      labels: allWeeks.map((w) => `Week ${w}`),
      datasets: [
        {
          label: "Historical & Actual",
          data: allCasesCombined,
          borderColor: "#4682b4",
          borderWidth: 3,
          backgroundColor: "rgba(70, 130, 180, 0.15)",
          fill: true,
          cubicInterpolationMode: "monotone",
          tension: 0.4,
          pointRadius: 4,
          pointStyle: "circle",
          pointBackgroundColor: "#4682b4",
          pointBorderColor: "#ffffff",
          pointBorderWidth: 2,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: "rgb(0, 64, 121)",
          pointHoverBorderColor: "rgba(0, 132, 255, 0.37)",
        },
        {
          label: "Predicted",
          data: allCasesPredicted,
          borderColor: "#28a745",
          borderWidth: 5,
          borderDash: [10, 6],
          fill: false,
          tension: 0.1,
          cubicInterpolationMode: "default",
          pointRadius: 7,
          pointStyle: "rectRot",
          pointBackgroundColor: "#ffffff",
          pointBorderColor: "#28a745",
          pointBorderWidth: 3,
          pointHoverRadius: 9,
          pointHoverBackgroundColor: "#28a745",
          pointHoverBorderColor: "#ffffff",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: "index",
        axis: "x",
        intersect: false,
      },
      plugins: {
        title: {
          display: true,
          text: chartTitle,
          font: {
            size: 16,
          },
        },
        annotation: {
          annotations: {
            highlightBeforePrediction: {
              type: "box",
              xMin: highlightIndex - 0.5,
              xMax: highlightIndex + 0.5,
              backgroundColor: "rgba(0, 132, 255, 0.19)",
              borderWidth: 0,
            },
            predictedRangeBox: {
              type: "box",
              xMin: predStartIndex - 0.5,
              xMax: predEndIndex + 0.5,
              backgroundColor: "rgba(40, 167, 69, 0.08)",
              borderWidth: 0,
              label: {
                enabled: true,
                content: "Predicted Range",
                position: "start",
                backgroundColor: "rgba(40, 167, 69, 0.4)",
                color: "#fff",
                font: {
                  weight: "bold",
                  size: 10,
                },
              },
            },
          },
        },
      },
      scales: {
        x: {
          title: {
            display: true,
            text: "Morbidity Week",
          },
          grid: {
            display: false,
          },
        },
        y: {
          title: {
            display: true,
            text: "Number of Cases",
          },
          beginAtZero: true,
          grid: {
            display: false,
          },
          ticks: {
            stepSize: 1,
            callback: function (value) {
              return Number.isInteger(value) ? value : null;
            },
          },
        },
      },
    },
  });
}
