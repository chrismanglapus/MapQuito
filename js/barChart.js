document.addEventListener("DOMContentLoaded", function () {
  fetch("phps/fetch__bar_data.php")
    .then((response) => response.json())
    .then((data) => {
      const years = data.years;
      const datasets = data.datasets;

      const validYears = years.filter((year) => {
        const yearData = datasets.find((ds) => ds.year == year)?.data || [];
        return yearData.some(
          (entry) => entry.barangay && entry.barangay.trim() !== ""
        );
      });

      const yearSelectBar = document.getElementById("yearSelectBar");
      const totalCasesText = document.getElementById("totalCasesText");

      let chartInstance;
      const ctx = document.getElementById("barChart").getContext("2d");

      function createChart(type) {
        if (chartInstance) {
          chartInstance.destroy();
        }

        chartInstance = new Chart(ctx, {
          type: type,
          data: {
            labels: [],
            datasets: [],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: 20 },
            plugins: {
              title: {
                display: true,
                text: "Barangay Dengue Case Distribution",
                font: {
                  size: 22,
                  weight: "bold",
                  family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                },
                color: "#2c3e50",
                padding: { top: 10, bottom: 20 },
              },
              subtitle: {
                display: true,
                text: "Percentages indicate share of total cases for the selected year",
                font: {
                  size: 14,
                  family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                },
                color: "#34495e",
                padding: { bottom: 20 },
              },
              legend: {
                position: "right",
                labels: {
                  boxWidth: 20,
                  padding: 10,
                  font: {
                    size: 14,
                    family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
                    weight: "600",
                  },
                  color: "#34495e",
                },
              },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const value = context.raw;
                    const percentage = (
                      (value / context.chart._totalCasesSum) *
                      100
                    ).toFixed(1);
                    return `${context.label}: ${value} cases (${percentage}%)`;
                  },
                },
                backgroundColor: "#2c3e50",
                titleFont: { size: 14, weight: "bold" },
                bodyFont: { size: 13 },
                padding: 10,
              },
              datalabels: {
                color: "#ffffff", // main label color
                font: (context) => {
                  const chartType = context.chart.config.type;
                  return {
                    weight: "bold",
                    size: chartType === "bar" ? 12 : 20, // smaller for bar, larger for doughnut
                  };
                },
                formatter: (value, context) => {
                  const total = context.chart._totalCasesSum;
                  const percentage = ((value / total) * 100).toFixed(1);
                  return `${percentage}%`;
                },
                // Add stroke (text outline)
                textStrokeColor: "#000000",
                textStrokeWidth: 3,
              },
            },
            animation: {
              animateRotate: true,
              animateScale: true,
            },
          },
          plugins: [ChartDataLabels],
        });
      }

      function updateChart(selectedYear) {
        const displayMode =
          document.getElementById("displayModeSelect")?.value || "top";

        const yearData =
          datasets.find((ds) => ds.year == selectedYear)?.data || [];
        if (!yearData.length) {
          console.error("No data found for year:", selectedYear);
          return;
        }

        const totalCasesSum = yearData.reduce(
          (sum, entry) => sum + Number(entry.total_cases || 0),
          0
        );
        totalCasesText.innerHTML = `Total Cases this Year: <span class="case-number">${totalCasesSum}</span>`;

        const validEntries = yearData.filter(
          (entry) => entry.barangay && entry.barangay.trim() !== ""
        );

        let selectedEntries = [];

        if (displayMode === "bottom") {
          selectedEntries = validEntries
            .filter((entry) => entry.total_cases > 0)
            .sort((a, b) => a.total_cases - b.total_cases)
            .slice(0, 10);
        } else if (displayMode === "all") {
          selectedEntries = validEntries.filter(
            (entry) => entry.total_cases > 0
          );
        } else {
          // top 10
          selectedEntries = validEntries
            .filter((entry) => entry.total_cases > 0)
            .sort((a, b) => b.total_cases - a.total_cases)
            .slice(0, 10);
        }

        selectedEntries.sort((a, b) => a.barangay.localeCompare(b.barangay));

        const labels = selectedEntries.map((entry) => entry.barangay);
        const data = selectedEntries.map((entry) => entry.total_cases);

        const chartType = displayMode === "all" ? "bar" : "doughnut";
        createChart(chartType);

        function getColor(index) {
          const brightColors = [
            "#FF1744", // neon red
            "#D500F9", // neon purple
            "#00E676", // neon green
            "#00B0FF", // neon blue
            "#FFEA00", // neon yellow
            "#FF4081", // pink
            "#69F0AE", // mint green
            "#FFD600", // bright gold
            "#7C4DFF", // bright violet
            "#18FFFF", // electric cyan
            "#FF9100", // bright orange
            "#F50057", // hot pink
            "#76FF03", // lime
            "#40C4FF", // sky blue
            "#E040FB", // lavender neon
            "#00E5FF", // bright aqua
            "#FF3D00", // intense orange-red
            "#B388FF", // soft violet
            "#C6FF00", // neon lime
            "#304FFE", // vivid indigo
          ];
          return brightColors[index % brightColors.length];
        }

        chartInstance._totalCasesSum = totalCasesSum;

        chartInstance.data.labels = labels;
        chartInstance.data.datasets = [
          {
            label: `Barangays with Cases in ${selectedYear}`,
            data: data,
            backgroundColor: selectedEntries.map((_, i) => getColor(i)),
            borderColor: "#fff",
            borderWidth: 2,
            hoverOffset: 15, // enlarges segment on hover
          },
        ];
        chartInstance.update();

        updateTable(validEntries);
      }

      function updateTable(entries) {
        const sortOption =
          document.getElementById("sortTable")?.value || "cases_desc";
        const sorted = [...entries];

        switch (sortOption) {
          case "cases_asc":
            sorted.sort((a, b) => a.total_cases - b.total_cases);
            break;
          case "alpha_asc":
            sorted.sort((a, b) => a.barangay.localeCompare(b.barangay));
            break;
          case "alpha_desc":
            sorted.sort((a, b) => b.barangay.localeCompare(a.barangay));
            break;
          default:
            sorted.sort((a, b) => b.total_cases - a.total_cases);
        }

        const tableBody = document.querySelector("#barangayTable tbody");

        tableBody.style.opacity = 0;
        setTimeout(() => {
          tableBody.innerHTML = "";

          sorted.forEach((entry) => {
            const row = document.createElement("tr");

            const barangayCell = document.createElement("td");
            barangayCell.textContent = entry.barangay;

            const casesCell = document.createElement("td");
            const cases = Number(entry.total_cases);
            casesCell.textContent = cases;
            casesCell.classList.add("cases");
            if (cases > 0) {
              casesCell.classList.add("nonzero");
            }

            row.appendChild(barangayCell);
            row.appendChild(casesCell);
            tableBody.appendChild(row);
          });

          tableBody.style.opacity = 1;
        }, 300);
      }

      if (yearSelectBar && validYears.length > 0) {
        validYears.forEach((year) => {
          let option = document.createElement("option");
          option.value = year;
          option.textContent = year;
          yearSelectBar.appendChild(option);
        });

        let currentYear = validYears[0];
        yearSelectBar.value = currentYear;
        yearSelectBar.addEventListener("change", function () {
          updateChart(this.value);
        });
      } else {
        console.error("No valid data years available");
        return;
      }

      document
        .getElementById("displayModeSelect")
        .addEventListener("change", function () {
          const selectedYear = yearSelectBar.value;
          updateChart(selectedYear);
        });

      document
        .getElementById("sortTable")
        .addEventListener("change", function () {
          const selectedYear = yearSelectBar.value;
          const yearData =
            datasets.find((ds) => ds.year == selectedYear)?.data || [];
          const validEntries = yearData.filter(
            (entry) => entry.barangay && entry.barangay.trim() !== ""
          );
          updateTable(validEntries);
        });

      updateChart(validYears[0]);
    })
    .catch((err) => console.error("Error loading chart data:", err));
});
