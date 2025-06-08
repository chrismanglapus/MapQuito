// "casesData" is now assumed to be defined globally by the PHP-embedded script.

var maxValue = Math.max(
  ...Object.entries(casesData).map(([barangay, data]) => {
    return data.rate || 0;
  }),
  0
);

function getSeverityColor({
  rate = 0,
  alert_threshold = 0,
  epidemic_threshold = 0,
}) {
  let fillColor;
  const strokeColor = "rgba(75, 75, 75, 0.5)";

  if (rate === 0) {
    fillColor = "rgba(255, 255, 255, 0.2)"; // No cases: white
  } else if (epidemic_threshold === 0 && alert_threshold === 0) {
    fillColor = "rgba(107, 114, 128, 0.2)"; // Data unavailable: gray
  } else if (rate >= epidemic_threshold && epidemic_threshold > 0) {
    fillColor = "rgba(220, 38, 38, 0.4)"; // Epidemic: red
  } else if (rate >= alert_threshold && alert_threshold > 0) {
    fillColor = "rgba(245, 158, 11, 0.3)"; // Alert: yellow
  } else {
    fillColor = "rgba(22, 163, 74, 0.3)"; // Low risk: green
  }

  return { fillColor, strokeColor };
}

function createZonePolygon(coordinates, barangayData, barangayName) {
  var polygonCoords = coordinates.map(function (coord) {
    return ol.proj.fromLonLat(coord);
  });

  // Pass in the data for this barangay to getSeverityColor
  const { fillColor, strokeColor } = getSeverityColor(barangayData);

  var zoneStyle = new ol.style.Style({
    fill: new ol.style.Fill({
      color: fillColor,
    }),
    stroke: new ol.style.Stroke({
      color: strokeColor,
      width: 2,
    }),
  });

  var zone = new ol.layer.Vector({
    source: new ol.source.Vector({
      features: [
        new ol.Feature({
          geometry: new ol.geom.Polygon([polygonCoords]),
          barangayName: barangayName,
        }),
      ],
    }),
    style: zoneStyle,
  });

  return zone;
}

function updateMap() {
  const params = new URLSearchParams({
    selected_year: document.getElementById("selectedYear").value,
    selected_week: document.getElementById("selectedWeek").value,
  });
  window.location.href = `${window.location.pathname}?${params}`;
}

function initMap() {
  var extent = ol.proj.transformExtent(
    [120.25, 16.55, 120.47, 16.66],
    "EPSG:4326",
    "EPSG:3857"
  );
  var map = new ol.Map({
    target: "map",
    layers: [
      new ol.layer.Tile({
        source: new ol.source.OSM(),
      }),
    ],
    view: new ol.View({
      center: ol.proj.fromLonLat([120.33105411545219, 16.615613723234485]),
      zoom: 14, // Default zoom level
      minZoom: 13, // Restrict zooming in beyond this level
      maxZoom: 15, // Allow zooming out, but not too much zooming in
      extent: extent, // Restrict panning beyond a certain area
    }),
  });

  var vectorLayerGroup = new ol.layer.Group({
    layers: [],
  });
  map.addLayer(vectorLayerGroup);

  // Create an object to store original styles of zones
  var originalStyles = {};

  // Add the updated zone layers based on the casesData
  for (var barangay in casesData) {
    if (casesData.hasOwnProperty(barangay)) {
      var coordinates = zones[barangay]; // Use the coordinates for the barangay
      if (coordinates) {
        var barangayData = casesData[barangay];
        var zoneLayer = createZonePolygon(coordinates, barangayData, barangay);

        // Store the original style of each zone for later use in hover effect
        originalStyles[barangay] = zoneLayer.getStyle();

        vectorLayerGroup.getLayers().push(zoneLayer); // Add the new zone layer to the group
      }
    }
  }

  // Variable to track the currently hovered barangay
  var currentHoveredFeature = null;

  map.on("pointermove", function (event) {
    var feature = map.forEachFeatureAtPixel(event.pixel, function (feature) {
      return feature;
    });

    if (feature) {
      map.getTargetElement().style.cursor = "pointer"; // Change cursor to pointer when hovering over a zone

      var barangayName = feature.get("barangayName");
      var barangayData = casesData[barangayName];

      if (currentHoveredFeature !== barangayName) {
        if (currentHoveredFeature) {
          var previousFeature = vectorLayerGroup
            .getLayers()
            .getArray()
            .find((layer) => {
              return layer
                .getSource()
                .getFeatures()
                .some((f) => f.get("barangayName") === currentHoveredFeature);
            });
          if (previousFeature) {
            previousFeature
              .getSource()
              .getFeatures()
              .forEach(function (f) {
                f.setStyle(originalStyles[f.get("barangayName")]);
              });
          }
        }

        feature.setStyle(
          new ol.style.Style({
            stroke: new ol.style.Stroke({
              color: "rgba(0, 0, 255, 0.7)",
              width: 3,
            }),
            fill: new ol.style.Fill({
              color: "rgba(0, 195, 255, 0.29)",
            }),
          })
        );

        var totalCasesInYear = totalCasesYearData[barangayName] || 0;
        var casesForWeek = barangayData ? barangayData.total_cases : 0;

        currentHoveredFeature = barangayName;
        document.getElementById("barangayContainer").innerHTML = `
          <div class="cardHeading"><span>${barangayName}</span></div>
          <div class="cardDesc"><span>${casesForWeek} cases</span></div>
          <div class="cardDesc"><span>Total of ${totalCasesInYear} cases.</span></div>
        `;
      }
    } else {
      map.getTargetElement().style.cursor = ""; // Reset cursor when not hovering over a zone

      vectorLayerGroup.getLayers().forEach(function (layer) {
        layer
          .getSource()
          .getFeatures()
          .forEach(function (feature) {
            feature.setStyle(originalStyles[feature.get("barangayName")]);
          });
      });

      currentHoveredFeature = null;
      document.getElementById("barangayContainer").innerHTML = `
        <p class="cardHeading">Want to see case info?</p>
        <p class="cardDesc">Just hover over a barangay!</p>
        <p class="cardDesc">For more info, just click on the barangay zone.</p>
      `;
    }
  });

  map.on("click", (event) => {
    const feature = map.forEachFeatureAtPixel(event.pixel, (f) => f);
    if (!feature) return;

    const barangayName = feature.get("barangayName");
    const selectedYear = document.getElementById("selectedYear").value;
    const selectedWeek =
      document.getElementById("selectedWeek").value || currentWeek;
    const barangayData = casesData[barangayName];

    if (!barangayData) return;

    const modal = document.getElementById("modal-info");
    modal.style.display = "flex";

    const gridTitle = document.querySelector(".grid-title");
    const info1 = document.querySelector(".info1");
    const info2 = document.querySelector(".info2");
    const info3 = document.querySelector(".info3");

    info1.innerHTML = info2.innerHTML = info3.innerHTML = "";

    fetch("phps/get_population.php")
      .then((response) => response.json())
      .then((popData) => {
        const popCount = popData[barangayName];
        const populationInfo = popCount ? `<p>Population: ${popCount}</p>` : "";

        gridTitle.innerHTML = `
        <section aria-label="Information about Barangay ${barangayName}">
          <h2>Barangay ${barangayName}</h2>
          <div class="grid-title-sub">
            <p>Year ${selectedYear} | Week ${selectedWeek}</p>
            ${populationInfo}
          </div>
        </section>
      `;
      })
      .catch((error) => {
        console.error("Population fetch error:", error);

        // Fallback: Show grid title without population
        gridTitle.innerHTML = `
          <h2>Barangay ${barangayName}</h2>
          <div class="grid-title-sub">
            <p>Year ${selectedYear}</p>
            <p>Week ${selectedWeek}</p>
          </div>
        `;
      });

    info1.innerHTML = `<span>Cases in Week ${selectedWeek}</span><h1>${barangayData.total_cases}</h1>`;
    info2.innerHTML = `<span>Cases in ${selectedYear}</span><h1>${
      totalCasesYearData[barangayName] || 0
    }</h1>`;

    fetch(
      `index__fetch_prediction_data.php?barangay=${barangayName}&year=${selectedYear}&week=${selectedWeek}`
    )
      .then((response) => response.json())
      .then((data) => {
        document.querySelector(".predict-graph").innerHTML =
          '<canvas id="predictChart" class="predict-chart-canvas"></canvas>';

        if (data?.status === "success") {
          if (!Array.isArray(data.predictions)) {
            console.error("Prediction data is not an array:", data.predictions);
            return;
          }

          if (!Array.isArray(data.historical)) {
            console.error("Historical data is not an array:", data.historical);
            return;
          }

          const historicalArray =
            data.flat_historical || Object.values(data.historical).flat();

          // Pass selectedYear explicitly as the 4th parameter
          showPredictionGraph(
            barangayName,
            historicalArray,
            data.predictions,
            selectedWeek,
            selectedYear
          );
        } else {
          console.warn(
            "Prediction fetch failed or returned unexpected format."
          );
        }
      })
      .catch((error) => {
        console.error("Fetch error:", error);
      });

    fetch(
      `api_heatmap.php?selected_year=${selectedYear}&selected_week=${selectedWeek}`
    )
      .then((response) => response.json())
      .then((data) => {
        const barangayData = data.casesData[barangayName];

        if (!barangayData) {
          info3.innerHTML = `<span>Threshold data not available</span>`;
          return;
        }

        const {
          total_cases: cases,
          alert_threshold_rate,
          epidemic_threshold_rate,
          rate,
        } = barangayData;

        let preventionMessage = "";

        if (cases === 0) {
          preventionMessage = `
            <h3 style="color: #2563eb; display: flex; align-items: center; justify-content: center; gap: 8px;">
              🛡️ No Dengue Cases
            </h3>
            <p>No dengue cases have been reported in this barangay this week.</p>
            <p><strong>Preventive Reminders:</strong></p>
            <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
              <li style="margin-bottom: 6px;">🛡️ Keep surroundings clean and dry to prevent mosquito breeding.</li>
              <li style="margin-bottom: 6px;">🛡️ Encourage residents to use mosquito repellent and nets.</li>
              <li>🛡️ Monitor symptoms and seek medical help if needed.</li>
            </ul>
          `;
        } else if (
          rate >= epidemic_threshold_rate &&
          epidemic_threshold_rate > 0
        ) {
          preventionMessage = `
            <h3 style="color: #dc2626; display: flex; align-items: center; justify-content: center; gap: 8px;">
              🚨 Dengue Epidemic Alert!
            </h3>
            <p>Surpassing the epidemic threshold of <strong>${epidemic_threshold_rate}</strong> cases per 1,000 population.</p>
            <br>
            <p><strong>Immediate Actions Required:</strong></p>
            <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
              <li style="margin-bottom: 6px;">🔴 Launch emergency mosquito control operations, including fogging.</li>
              <li style="margin-bottom: 6px;">🔴 Urge residents to seek medical attention for any dengue-like symptoms.</li>
              <li style="margin-bottom: 6px;">🔴 Enforce barangay-level inspections and cleanup campaigns.</li>
              <li>🔴 Mobilize health workers to monitor and contain outbreaks.</li>
            </ul>
          `;
        } else if (rate >= alert_threshold_rate && alert_threshold_rate > 0) {
          preventionMessage = `
            <h3 style="color: #f59e0b; display: flex; align-items: center; justify-content: center; gap: 8px;">
              ⚠️ Dengue Alert Level
            </h3>
            <p>Reached the alert threshold of <strong>${alert_threshold_rate}</strong> cases per 1,000 population.</p>
            <br>
            <p><strong>Suggested Preventive Actions:</strong></p>
            <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
              <li style="margin-bottom: 6px;">🟠 Intensify clean-up drives in all communities.</li>
              <li style="margin-bottom: 6px;">🟠 Conduct public awareness campaigns on dengue symptoms and prevention.</li>
              <li style="margin-bottom: 6px;">🟠 Report any suspected dengue cases promptly.</li>
              <li>🟠 Promote the use of mosquito nets and repellents across all barangays.</li>
            </ul>
          `;
        } else if (rate === 0 && cases > 0) {
          preventionMessage = `
            <h3 style="color: #6b7280; display: flex; align-items: center; justify-content: center; gap: 8px;">
              🧐 Data Unavailable
            </h3>
            <p>There are dengue cases, but the rate data is missing or could not be computed.</p>
            <p><strong>Suggested Actions:</strong></p>
            <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
              <li style="margin-bottom: 6px;">📊 Monitor for any increase in dengue symptoms locally.</li>
              <li style="margin-bottom: 6px;">📢 Maintain preventive practices to keep risk low.</li>
              <li>🕵️ Encourage reporting of suspected dengue cases.</li>
            </ul>
          `;
        } else {
          preventionMessage = `
            <h3 style="color: #16a34a; display: flex; align-items: center; justify-content: center; gap: 8px;">
              ✅ Dengue Risk Low
            </h3>
            <p>Below the alert threshold of <strong>${alert_threshold_rate}</strong> cases per 1,000 population.</p>
            <br>
            <p><strong>Keep up with these preventive measures:</strong></p>
            <ul style="text-align: left; padding-left: 20px; list-style-type: none;">
              <li style="margin-bottom: 6px;">✅ Dispose of containers that collect water (e.g., tires, buckets).</li>
              <li style="margin-bottom: 6px;">✅ Wear protective clothing and apply insect repellent.</li>
              <li>✅ Encourage community involvement in sanitation efforts.</li>
            </ul>
          `;
        }

        info3.innerHTML = `<div class="prevention-message" style="padding: 1em; border-radius: 8px;">${preventionMessage}</div>`;

        return fetch(
          `index__fetch_trend_data.php?barangay=${barangayName}&year=${selectedYear}`
        );
      })
      .then((response) => response.json())
      .then((trendData) => {
        const trendContainer = document.querySelector(".trend-graph");
        if (trendContainer) {
          trendContainer.innerHTML = `<canvas id="trendChart" class="trend-chart-canvas"></canvas>`;
          showTrendGraph(barangayName, trendData);
        }
      })
      .catch((error) => {
        console.error("Error fetching data:", error);
      });
  });
}

initMap();
