// Helper: Convert HSL values + alpha to rgba()
function hslToRgba(h, s, l, alpha) {
  // from https://stackoverflow.com/a/9493060
  s /= 100; l /= 100;
  const k = n => (n + h / 30) % 12;
  const a = s * Math.min(l, 1 - l);
  const f = n =>
    l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)));
  const [r, g, b] = [f(0), f(8), f(4)].map(v => Math.round(v * 255));
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function showTrendGraph(barangayName, data) {
  const weeks = data.labels.map(w => `Week ${w}`);
  const years = data.datasets.map(ds => ds.label);
  
  // generate one distinct hue per year
  const palette = years.map((yr, i) => {
    const hue = Math.round((i * 360) / years.length);
    return {
      border: `hsl(${hue}, 75%, 50%)`,
      background: hslToRgba(hue, 75, 50, 0.2)
    };
  });

  const chartDatasets = data.datasets.map((ds, idx) => ({
    label: ds.label,
    data: ds.data,
    type: "line",
    borderColor: palette[idx].border,
    borderWidth: 3,
    backgroundColor: palette[idx].background,
    fill: true,
    cubicInterpolationMode: "monotone",
    pointRadius: 4,
    pointHoverRadius: 6,
    pointHoverBackgroundColor: "rgb(0, 64, 121)",
    pointHoverBorderColor: "rgba(0, 132, 255, 0.37)",
    hidden: String(ds.label) !== String(selectedYear)
  }));

  const ctx = document.getElementById("trendChart").getContext("2d");
  if (window.trendChartInstance) window.trendChartInstance.destroy();

  window.trendChartInstance = new Chart(ctx, {
    type: "line",
    data: { labels: weeks, datasets: chartDatasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        x: { grid: { display: true } },
        y: { beginAtZero: true, grid: { display: true } }
      },
      plugins: {
        title: { display: true, text: "Dengue Case Trends", font: { size: 16 } },
        legend: { display: true },
        annotation: {
          annotations: {
            highlightPeakWeek: {
              type: "box",
              xMin: weeks.indexOf(`Week ${selectedWeek}`) - 0.5,
              xMax: weeks.indexOf(`Week ${selectedWeek}`) + 0.5,
              backgroundColor: "rgba(0, 132, 255, 0.19)",
              borderWidth: 0
            }
          }
        },
        tooltip: { enabled: true }
      }
    }
  });
}
