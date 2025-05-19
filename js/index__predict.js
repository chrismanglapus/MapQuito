function showPredictionGraph(barangayName, historicalData, predictionData) {
  const canvas = document.getElementById("predictChart");
  const ctx = canvas.getContext("2d");

  if (window.predictChartInstance) {
    window.predictChartInstance.destroy();
  }

  // Get current year - adjust if your data uses a different year field or format
  const currentYear = new Date().getFullYear();

  // Filter historical data for current year only
  // Adjust 'year' or 'YEAR' depending on your data field name
  const filteredHistorical = historicalData.filter((d) => {
    const yr = d.year || d.YEAR;
    return yr === currentYear || yr === currentYear - 1;
  });

  const histWeeks = filteredHistorical.map((d) => parseInt(d.morbidity_week));
  const histCases = filteredHistorical.map((d) => d.cases);

  const predWeeks = predictionData.map((d) => parseInt(d.morbidity_week));
  const predCases = predictionData.map((d) => d.predicted_cases);
  const actualPredCases = predictionData.map((d) =>
    d.actual_cases !== undefined ? d.actual_cases : null
  );

  const predStartWeek = predWeeks.length > 0 ? predWeeks[0] : null;
  if (!predStartWeek) return;

  // Use predStartWeek directly as selectedWeek (no subtract 1)
  const highlightWeek = predStartWeek - 1 === 0 ? 52 : predStartWeek - 1;

  const weeksInYear = 53;

  let startWeek = selectedWeek - 5;
  let endWeek = selectedWeek + 5;

  const allWeeks = [];

  for (let i = startWeek; i <= endWeek; i++) {
    // Prevent wraparound for week 1 and week 52
    if (selectedWeek === 1 && i < 1) continue;
    if (selectedWeek === 52 && i > 52) continue;

    let week = i;
    if (week < 1 || week > weeksInYear) continue; // additional safeguard

    allWeeks.push(week);
  }

  console.log("Historical weeks:", histWeeks);
  console.log("Prediction weeks:", predWeeks);
  console.log("Selected Week:", selectedWeek);
  console.log("Weeks Range:", allWeeks);

  // Map combined data: use historical data if available for that week,
  // else use actual predicted cases if available
  const allCasesCombined = allWeeks.map((w) => {
    const histIndex = histWeeks.findIndex((hw) => hw === w);
    const predIndex = predWeeks.findIndex((pw) => pw === w);

    if (histIndex !== -1 && histCases[histIndex] != null) {
      return histCases[histIndex];
    } else if (predIndex !== -1 && actualPredCases[predIndex] !== null) {
      return actualPredCases[predIndex];
    } else {
      return null;
    }
  });

  // Predicted cases for all weeks (null if no prediction)
  const allCasesPredicted = allWeeks.map((w) => {
    const predIndex = predWeeks.findIndex((pw) => pw === w);
    return predIndex !== -1 ? predCases[predIndex] : null;
  });

  const highlightIndex = allWeeks.findIndex((w) => w === highlightWeek);

  const chartTitle = `5-Week Forecast: Trends from Week ${
    allWeeks[0]
  } to Week ${allWeeks[allWeeks.length - 1]}`;

  window.predictChartInstance = new Chart(ctx, {
    type: "line",
    data: {
      labels: allWeeks.map((w) => `Week ${w}`),
      datasets: [
        {
          label: "Predicted",
          data: allCasesPredicted,
          borderColor: "rgb(33, 25, 255)",
          borderDash: [5, 5],
          tension: 0.1,
          fill: false,
          pointRadius: 6,
          pointBackgroundColor: "rgb(33, 25, 255)",
          cubicInterpolationMode: "monotone",
          pointHoverRadius: 15,
        },
        {
          label: "Historical & Actual",
          data: allCasesCombined,
          fill: true,
          type: "bar",
          backgroundColor: "#00A650",
          borderColor: "#00703C",
          borderWidth: 1,
          borderRadius: 5,
          hoverBackgroundColor: "rgba(0, 132, 255, 0.37)",
          hoverBorderColor: "rgb(0, 64, 121)",
          hoverBorderWidth: 4,
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
