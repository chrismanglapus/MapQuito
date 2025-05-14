<?php
session_start();
require('main/connection.php');
require('main/header.php');
include('zones/urban_zones.php'); // Include the zones file
include('zones/rural_zones.php'); // Include the zones file

// Combine both arrays
$zones = array_merge($rural_zones, $urban_zones);

// Fetch unique years from the database
$uniqueYears = array();

$sqlYears = "SELECT DISTINCT YEAR(date_added) as year FROM heatmap_data";
$resultYears = mysqli_query($conn, $sqlYears);

while ($rowYear = mysqli_fetch_assoc($resultYears)) {
    $uniqueYears[] = $rowYear['year'];
}

// Get the latest year
$latestYear = max($uniqueYears);

// Get the selected year from the URL parameter
$selectedYear = isset($_GET['selected_year']) ? $_GET['selected_year'] : $latestYear;

// Generate all weeks (1–52 or 1–53) for the selected year
$allWeeks = range(1, 52); // Default to 52 weeks
$uniqueWeeks = array();

// Check if the selected year has 53 weeks
$lastWeekOfYear = date("W", strtotime("December 28, $selectedYear")); // December 28 is always in the last week of the year
if ($lastWeekOfYear == 53) {
    $allWeeks[] = 53; // Add Week 53 if the year has 53 weeks
}

// Fetch the weeks that actually have data
$sqlWeeks = "SELECT DISTINCT WEEK(date_added, 1) as morbidity_week 
             FROM heatmap_data 
             WHERE YEAR(date_added) = '$selectedYear' 
             ORDER BY morbidity_week ASC";
$resultWeeks = mysqli_query($conn, $sqlWeeks);

$weeksWithData = array();
while ($rowWeek = mysqli_fetch_assoc($resultWeeks)) {
    $weeksWithData[] = $rowWeek['morbidity_week'];
}

// Get the selected week from the URL parameter
$selectedWeek = isset($_GET['selected_week']) ? $_GET['selected_week'] : max($weeksWithData);

// Fetch cases data for the selected year and morbidity week from the database
$casesData = array();

$sql = "SELECT barangay, SUM(cases) as total_cases, MAX(date_added) as latest_date 
        FROM heatmap_data 
        WHERE YEAR(date_added) = '$selectedYear' 
        AND WEEK(date_added, 1) = '$selectedWeek'
        GROUP BY barangay";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $barangay = $row['barangay'];
    $cases = $row['total_cases'];
    $latestDate = $row['latest_date']; // Fetch or calculate this field if needed
    $casesData[$barangay] = array(
        'total_cases' => $cases,
        'latest_date' => $latestDate
    );
}

// Fetch the maximum case count for the selected year and morbidity week
$maxCasesQuery = "SELECT MAX(total_cases) as max_cases 
                  FROM (SELECT SUM(cases) as total_cases 
                        FROM heatmap_data 
                        WHERE YEAR(date_added) = '$selectedYear' 
                        AND WEEK(date_added, 1) = '$selectedWeek'
                        GROUP BY barangay) as total_cases_subquery";

$maxCasesResult = mysqli_query($conn, $maxCasesQuery);

// Check for query error
if (!$maxCasesResult) {
    die('Error in query: ' . mysqli_error($conn));
}

// Fetch the max value from the result
$maxCases = 0;
if ($maxCasesResult) {
    $row = mysqli_fetch_assoc($maxCasesResult);
    $maxCases = $row['max_cases'];
}

// Fetch total cases for the selected year and morbidity week
$totalCasesQuery = "SELECT SUM(cases) as total_cases, MAX(date_added) as latest_date
                    FROM heatmap_data
                    WHERE YEAR(date_added) = '$selectedYear'
                    AND WEEK(date_added, 1) = '$selectedWeek'";

$totalCasesResult = mysqli_query($conn, $totalCasesQuery);
$totalCasesData = mysqli_fetch_assoc($totalCasesResult);

$totalCases = $totalCasesData['total_cases'];
$latestDate = $totalCasesData['latest_date']; // Fetch the latest date added

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Page</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body class="map-container">
    <div id="map" class="map"></div>
    <div id="barangayContainer" class="barangay-container">Hover over any zone to see more info.</div>
    <!-- Add the year dropdown menu -->
    <div id="yearFilterContainer" class="year-filter-container">
        <label for="selectedYear">Select Year:</label>
        <select id="selectedYear" onchange="updateMap()">
            <?php
            foreach ($uniqueYears as $year) {
                echo "<option value=\"$year\"" . ($selectedYear == $year ? ' selected' : '') . ">$year</option>";
            }
            ?>
        </select>
        <label for="selectedWeek">Select Morbidity Week:</label>
        <select id="selectedWeek" onchange="updateMap()">
            <?php
            foreach ($allWeeks as $week) {
                $isDisabled = !in_array($week, $weeksWithData); // Disable weeks without data
                $isSelected = ($selectedWeek == $week);
                echo "<option value=\"$week\"" . ($isSelected ? ' selected' : '') . ($isDisabled ? ' disabled' : '') . ">Week $week</option>";
            }
            ?>
        </select>
    </div>
    <div id="preventiveMeasuresContainer" class="preventive-measures-container"></div>
    <div id="graphPanel" class="graph-panel">
        <div class="panel-header">
            <h3>Graphs for Selected Barangay</h3>
            <div>
                <button id="togglePanelSize" class="toggle-button">⛶</button>
                <button id="closePanel" class="toggle-button">×</button>
            </div>
        </div>
        <div id="graphContent">
            <p>No reported cases:</p>
            <p>Continue regular preventive measures.</p>
            <p><strong>Selected Barangay: SEVILLA</strong></p>
        </div>
    </div>
</body>

<script src="https://cdn.jsdelivr.net/npm/ol@v8.2.0/dist/ol.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v8.2.0/ol.css">

<script>
    var casesData = <?php echo json_encode($casesData); ?>;
    var maxValue = <?php echo ($maxCases > 0) ? $maxCases : 1; ?>; // Ensure it's at least 1
    var selectedYear = <?php echo json_encode($selectedYear); ?>;
    var selectedWeek = <?php echo json_encode($selectedWeek); ?>;
    var totalCases = <?php echo json_encode($totalCases); ?>;
    var latestDate = <?php echo json_encode($latestDate); ?>;

    console.log("Max Cases:", maxValue);  // Debugging log

    // Convert latest date to "Month Name Date, Year" format
    var formattedLatestDate = new Date(latestDate);
    var options = { year: 'numeric', month: 'long', day: 'numeric' };
    var formattedDate = formattedLatestDate.toLocaleDateString('en-US', options);

    function getSeverityColor(cases) {
        let fillColor;
        let strokeColor;

        const totalCases = cases?.total_cases || 0;

        if (totalCases === 0) {
            fillColor = 'rgba(255, 255, 255, 0.4)';
            strokeColor = 'rgba(255, 255, 255, 0.4)';
        } else {
            var lowThreshold = maxValue * 0.2;
            var midThreshold = maxValue * 0.6;

            if (totalCases <= lowThreshold) {
                fillColor = 'rgba(0, 128, 0, 0.2)';
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            } else if (totalCases > lowThreshold && totalCases <= midThreshold) {
                fillColor = 'rgba(255, 255, 0, 0.3)';
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            } else {
                fillColor = 'rgba(255, 0, 0, 0.4)';
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            }
        }

        return { fillColor, strokeColor };
    }


    function createZonePolygon(coordinates, cases, barangayName, year) {
        var polygonCoords = coordinates.map(function (coord) {
            return ol.proj.fromLonLat(coord);
        });

        const { fillColor, strokeColor } = getSeverityColor(cases);

        var zoneStyle = new ol.style.Style({
            fill: new ol.style.Fill({
                color: fillColor,
            }),
            stroke: new ol.style.Stroke({
                color: strokeColor,
                width: 2
            })
        });

        var zone = new ol.layer.Vector({
            source: new ol.source.Vector({
                features: [new ol.Feature({
                    geometry: new ol.geom.Polygon([polygonCoords]),
                    barangayName: barangayName
                })]
            }),
            style: zoneStyle
        });

        return zone;
    }

    function setDefaultPreventiveMeasures() {
        let overviewContent;
        if (totalCases === null || totalCases === 0) {
            overviewContent = `
            <d><strong>YEAR ${selectedYear} - MORBIDITY WEEK ${selectedWeek}</strong><br></d>
            No data available for Morbidity Week ${selectedWeek}.<br>
            Please select another week.
        `;
        } else {
            overviewContent = `
            <d><strong>YEAR ${selectedYear} - MORBIDITY WEEK ${selectedWeek}</strong><br></d>
            As of ${formattedDate}, there are <strong>${totalCases}</strong> cases this morbidity week.<br>
            Continue practicing preventive measures to mitigate outbreaks.
        `;
        }
        const preventiveMeasuresContainer = document.getElementById('preventiveMeasuresContainer');
        preventiveMeasuresContainer.innerHTML = overviewContent;
        preventiveMeasuresContainer.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.4)'; // Add shadow
        preventiveMeasuresContainer.style.backgroundColor = 'rgba(255, 255, 255, 1)'; // Solid background
        preventiveMeasuresContainer.style.display = 'block'; // Ensure it is visible
    }

    setDefaultPreventiveMeasures();

    function initMap() {
        var extent = ol.proj.transformExtent([120.25, 16.55, 120.47, 16.66], 'EPSG:4326', 'EPSG:3857');

        var map = new ol.Map({
            target: 'map',
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM()
                }),
                <?php
                foreach ($zones as $barangay => $coordinates) {
                    $cases = isset($casesData[$barangay]) ? $casesData[$barangay] : array('total_cases' => 0, 'latest_date' => null);
                    echo "createZonePolygon(" . json_encode($coordinates) . ", " . json_encode($cases) . ", '$barangay', $selectedYear),";
                }
                ?>
            ],
            view: new ol.View({
                center: ol.proj.fromLonLat([120.33105411545219, 16.615613723234485]),
                zoom: 14,
                minZoom: 12,
                maxZoom: 15,
                extent: extent // Set the extent to limit the movement
            })
        });

        // Click interaction
        var clickInteraction = new ol.interaction.Select({
            condition: ol.events.condition.click,
            layers: map.getLayers().getArray(),
            style: function (feature) {
                return [
                    new ol.style.Style({
                        fill: new ol.style.Fill({
                            color: 'rgba(255, 255, 255, 0.1)' // Highlight color
                        }),
                        stroke: new ol.style.Stroke({
                            color: 'black',
                            width: 2
                        })
                    })
                ];
            },
        });

        clickInteraction.on('select', function (event) {
            const graphPanel = document.getElementById('graphPanel');
            const graphContent = document.getElementById('graphContent');

            if (event.selected.length === 0) {
                // Hide the panel if no zone is selected
                graphPanel.style.display = 'none';
                return;
            }

            // When a barangay is clicked
            const barangayName = event.selected[0].get('barangayName');
            graphContent.innerHTML = `Selected Barangay: ${barangayName}`;
            graphPanel.style.display = 'block'; // Show the panel
        });

        map.addInteraction(clickInteraction);

        // Hover interaction (existing code)
        var hoverInteraction = new ol.interaction.Select({
            condition: ol.events.condition.pointerMove,
            layers: map.getLayers().getArray(),
            style: function (feature) {
                return [
                    new ol.style.Style({
                        fill: new ol.style.Fill({
                            color: 'rgba(255, 255, 255, 0.1)' // Hover color
                        }),
                        stroke: new ol.style.Stroke({
                            color: 'black',
                            width: 2
                        })
                    }),
                    new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 6,
                            fill: new ol.style.Fill({
                                color: 'rgba(0, 132, 255, 0.1)' // Hover color
                            }),
                            stroke: new ol.style.Stroke({
                                color: 'black',
                                width: 2
                            })
                        }),
                        geometry: feature.getGeometry()
                    })
                ];
            },
        });

        hoverInteraction.on('select', function (event) {
            const barangayContainer = document.getElementById('barangayContainer');
            const preventiveMeasuresContainer = document.getElementById('preventiveMeasuresContainer');

            if (event.selected.length === 0) {
                // Restore default content when no zone is hovered
                barangayContainer.innerHTML = 'Hover over any zone to see more info.';
                setDefaultPreventiveMeasures();
                return;
            }

            // When a barangay is hovered
            const barangayName = event.selected[0].get('barangayName');
            if (casesData[barangayName] !== undefined) {
                const cases = casesData[barangayName].total_cases;
                const latestDate = new Date(casesData[barangayName].latest_date);
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                const formattedDate = latestDate.toLocaleDateString('en-US', options);

                barangayContainer.innerHTML = `
                Barangay: ${barangayName}<br>
                Cases: ${cases}<br>
                Last Updated: ${formattedDate}<br>
                Morbidity Week: ${selectedWeek}
            `;
                preventiveMeasuresContainer.innerHTML = getPreventiveMeasures(cases);
            } else {
                barangayContainer.innerHTML = `
                Barangay: ${barangayName}<br>
                No Cases Recorded for Morbidity Week ${selectedWeek}.
            `;
                preventiveMeasuresContainer.innerHTML = `
                <d>No Severity:</d><br>
                • No reported cases in this barangay for Morbidity Week ${selectedWeek}.<br>
                • Continue regular preventive measures to avoid outbreaks.
            `;
            }
        });

        map.addInteraction(hoverInteraction);
    }

    function updateMap() {
        var selectedYear = document.getElementById('selectedYear').value;
        var selectedWeek = document.getElementById('selectedWeek').value;
        window.location.href = '?selected_year=' + encodeURIComponent(selectedYear) + '&selected_week=' + encodeURIComponent(selectedWeek);
    }

    initMap();

    // Add this to your existing JavaScript code
    document.getElementById('togglePanelSize').addEventListener('click', function () {
        const graphPanel = document.getElementById('graphPanel');
        graphPanel.classList.toggle('fullscreen'); // Toggle fullscreen class
        const toggleButton = document.getElementById('togglePanelSize');

        // Change the button icon based on the panel state
        if (graphPanel.classList.contains('fullscreen')) {
            toggleButton.textContent = '⛶'; // Fullscreen icon
        } else {
            toggleButton.textContent = '⛶'; // Normal screen icon (you can use a different icon if needed)
        }
    });
    document.getElementById('closePanel').addEventListener('click', function () {
        const graphPanel = document.getElementById('graphPanel');
        graphPanel.style.display = 'none'; // Hide the panel
    });

</script>

</html>

<?php
require('main/footer.php');
?>