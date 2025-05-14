function showPredictionGraph(barangayName, historicalData, predictionData) {
  const canvas = document.getElementById("predictChart");
  const ctx = canvas.getContext("2d");

  if (window.predictChartInstance) {
    window.predictChartInstance.destroy();
  }

  const histWeeks = historicalData.map((d) => parseInt(d.morbidity_week));
  const histCases = historicalData.map((d) => d.cases);

  const predWeeks = predictionData.map((d) => parseInt(d.morbidity_week));
  const predCases = predictionData.map((d) => d.predicted_cases);
  const actualPredCases = predictionData.map((d) =>
    d.actual_cases !== undefined ? d.actual_cases : null
  );

  const predStartWeek = predWeeks.length > 0 ? parseInt(predWeeks[0]) : null;
  if (!predStartWeek) return;

  const selectedWeek = predStartWeek - 1;

  // Enforce strict range without wraparound
  const startWeek = Math.max(selectedWeek - 5, 1);
  const endWeek = Math.min(selectedWeek + 5, 52);

  const allWeeks = [];
  for (let w = startWeek; w <= endWeek; w++) {
    allWeeks.push(w);
  }

  // Filter data strictly within range
  const allCasesCombined = allWeeks.map((w) => {
    const histIndex = histWeeks.findIndex((hw) => hw === w);
    const predIndex = predWeeks.findIndex((pw) => pw === w);

    if (histIndex !== -1) {
      return histCases[histIndex];
    } else if (predIndex !== -1 && actualPredCases[predIndex] !== null) {
      return actualPredCases[predIndex];
    } else {
      return null;
    }
  });

  const allCasesPredicted = allWeeks.map((w) => {
    const predIndex = predWeeks.findIndex((pw) => pw === w);
    return predIndex !== -1 ? predCases[predIndex] : null;
  });

  const chartTitle = `5-Week Forecast: Trends from Week ${startWeek} to Week ${endWeek}`;

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
            highlightPredictionWeek: {
              type: "box",
              xMin: allWeeks.indexOf(selectedWeek) - 0.5,
              xMax: allWeeks.indexOf(selectedWeek) + 0.5,
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
