// "casesData" is now assumed to be defined globally by the PHP-embedded script.

var maxValue = Math.max(
  ...Object.entries(casesData).map(([barangay, data]) => {
    return data.rate || 0;
  }),
  0
);

function getSeverityColor({
  total_cases = 0,
  alert_threshold_count = 0,
  epidemic_threshold_count = 0,
}) {
  let fillColor;
  const strokeColor = "rgba(75, 75, 75, 0.5)";

  if (total_cases === 0) {
    fillColor = "rgba(255, 255, 255, 0.2)"; // No cases: white
  } else if (epidemic_threshold_count === 0 && alert_threshold_count === 0) {
    fillColor = "rgba(107, 114, 128, 0.2)"; // Data unavailable: gray
  } else if (total_cases >= epidemic_threshold_count && epidemic_threshold_count > 0) {
    fillColor = "rgba(220, 38, 38, 0.4)"; // Epidemic: red
  } else if (total_cases >= alert_threshold_count && alert_threshold_count > 0) {
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

    gridTitle.innerHTML = `<h2>Barangay ${barangayName}</h2><p>Year ${selectedYear}</p><p>Week ${selectedWeek}</p>`;
    info1.innerHTML = `<span>Cases in Week ${selectedWeek}</span><h1>${barangayData.total_cases}</h1>`;
    info2.innerHTML = `<span>Cases in ${selectedYear}</span><h1>${totalCasesYearData[barangayName] || 0
      }</h1>`;

    // Fetch Predictions
    fetch(`index__fetch_prediction_data.php?barangay=${barangayName}&year=${selectedYear}&week=${selectedWeek}`)
    .then((response) => response.json())
    .then((data) => {
      console.log("Fetched prediction data:", data); // ✅ Add this
      document.querySelector(".predict-graph").innerHTML =
        '<canvas id="predictChart" class="predict-chart-canvas"></canvas>';
        if (data?.status === "success" && data.predictions) {
          showPredictionGraph(
            barangayName,
            data.historical,
            data.predictions,
          );
        }        
    });
  
    // First fetch: Get dengue threshold data for preventive measures
    fetch(
      `api_heatmap.php?selected_year=${selectedYear}&selected_week=${selectedWeek}`
    )
      .then((response) => response.json())
      .then((data) => {
        const barangayData = data.casesData[barangayName];

        // --- Update Preventive Measures ---
        if (barangayData) {
          // Extract both the rate and the count thresholds from barangayData
          const {
            total_cases: cases,
            rate,
            alert_threshold, // rate-based threshold (cases per 1,000)
            epidemic_threshold, // rate-based threshold (cases per 1,000)
            alert_threshold_count, // actual threshold in cases
            epidemic_threshold_count, // actual threshold in cases
          } = barangayData;
          let preventionMessage = "";

          if (rate === 0) {
            preventionMessage = `
            <h3 style="color: #6b7280;">🧐 Data Unavailable</h3>
            <p>Currently, there is insufficient data to assess the dengue risk level for this barangay.</p>
            <p><strong>What You Can Do:</strong></p>
            <ul style="text-align: left; padding-left: 20px; list-style-type: disc; margin-top: 8px;">
              <li>📊 Stay updated on dengue risk levels.</li>
              <li>📢 Maintain cleanliness to prevent mosquito breeding.</li>
              <li>🕵️ Monitor and report any suspected dengue cases.</li>
            </ul>
          `;
          } else if (
            cases >= epidemic_threshold_count &&
            epidemic_threshold_count > 0
          ) {
            preventionMessage = `
            <h3 style="color: #dc2626;">🚨 Dengue Epidemic Alert!</h3>
            <p>This barangay has reported <strong>${cases}</strong> cases, surpassing the epidemic threshold (<strong>${epidemic_threshold_count}</strong> cases).</p>
            <p><strong>Immediate Actions Required:</strong></p>
            <ul style="text-align: left; padding-left: 20px; list-style-type: disc; margin-top: 8px;">
              <li>🔴 Implement urgent mosquito control measures, including fogging and spraying.</li>
              <li>🔴 Conduct barangay-wide cleanup operations to eliminate breeding sites.</li>
              <li>🔴 Strengthen surveillance and early case detection.</li>
              <li>🔴 Advise residents to seek medical attention at the first sign of symptoms.</li>
            </ul>
          `;
          } else if (
            cases >= alert_threshold_count &&
            alert_threshold_count > 0
          ) {
            preventionMessage = `
            <h3 style="color: #f59e0b;">⚠️ Dengue Alert Level</h3>
            <p>This barangay has recorded <strong>${cases}</strong> cases, reaching the alert threshold (<strong>${alert_threshold_count}</strong> cases).</p>
            <p><strong>Recommended Preventive Actions:</strong></p>
            <ul style="text-align: left; padding-left: 20px; list-style-type: disc; margin-top: 8px;">
              <li>🟠 Strengthen mosquito control efforts (remove stagnant water, apply larvicides).</li>
              <li>🟠 Conduct awareness campaigns on dengue prevention.</li>
              <li>🟠 Encourage timely reporting of new cases to health officials.</li>
              <li>🟠 Promote the use of mosquito nets and repellents among residents.</li>
            </ul>
          `;
          } else {
            preventionMessage = `
            <h3 style="color: #16a34a;">✅ Dengue Risk Low</h3>
            <p>This barangay has reported <strong>${cases}</strong> cases, which remains below the alert threshold (<strong>${alert_threshold_count}</strong> cases).</p>
            <p><strong>Ongoing Preventive Measures:</strong></p>
            <ul style="text-align: left; padding-left: 20px; list-style-type: disc; margin-top: 8px;">
              <li>✅ Regularly inspect and clean water storage areas.</li>
              <li>✅ Encourage residents to wear protective clothing and apply mosquito repellent.</li>
              <li>✅ Foster community involvement in sanitation efforts.</li>
            </ul>
          `;
          }

          info3.innerHTML = `<div class="prevention-message" style="padding: 1em; border-radius: 8px;">${preventionMessage}</div>`;
        } else {
          info3.innerHTML = `<span>Threshold data not available</span>`;
        }

        return fetch(`index__fetch_trend_data.php?barangay=${barangayName}&year=${selectedYear}`)
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
