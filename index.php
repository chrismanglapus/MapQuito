<?php
session_start();
require('connection.php');
require('header.php');
require('menu.php');
include('urban_zones.php'); // Include the zones file
include('rural_zones.php'); // Include the zones file

// Combine both arrays
$zones = array_merge($rural_zones, $urban_zones);

// Fetch unique years from the database
$uniqueYears = array();

$sqlYears = "SELECT DISTINCT YEAR(date_added) as year FROM heatmap_data";
$resultYears = mysqli_query($conn, $sqlYears);

while ($rowYear = mysqli_fetch_assoc($resultYears)) {
    $uniqueYears[] = $rowYear['year'];
}

// Get the selected year from the user
$selectedYear = isset($_GET['selected_year']) ? $_GET['selected_year'] : 'all';

// Fetch cases data for the selected year from the database
$casesData = array();

if ($selectedYear == 'all') {
    $sql = "SELECT barangay, SUM(cases) as total_cases FROM heatmap_data GROUP BY barangay";
} else {
    $sql = "SELECT barangay, SUM(cases) as total_cases FROM heatmap_data WHERE YEAR(date_added) = '$selectedYear' GROUP BY barangay";
}

$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $barangay = $row['barangay'];
    $cases = $row['total_cases'];
    $casesData[$barangay] = $cases;
}
?>

<div class="map-container">
    <div id="map" class="map"></div>
    <div id="barangayContainer" class="barangay-container">Barangay: Hover over any zone to see more info.</div>
    <!-- Add the year dropdown menu -->
    <div id="yearFilterContainer" class="year-filter-container">
        <label for="selectedYear">Select Year:</label>
        <select id="selectedYear" onchange="updateMap()">
            <option value="all" <?php echo ($selectedYear == 'all') ? ' selected' : ''; ?>>All Years</option>
            <?php
            foreach ($uniqueYears as $year) {
                if ($year != '2023') {
                    echo "<option value=\"$year\"" . ($selectedYear == $year ? ' selected' : '') . ">$year</option>";
                }
            }
            ?>
            <option value="2023" <?php echo ($selectedYear == '2023') ? ' selected' : ''; ?>>2023</option>
        </select>
    </div>
    <div id="preventiveMeasuresContainer" class="preventive-measures-container"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/ol@v8.2.0/dist/ol.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ol@v8.2.0/ol.css">

<script>
    var selectedYear = <?php echo json_encode($selectedYear); ?>;
    var casesData = <?php echo json_encode($casesData); ?>;

    function createZonePolygon(coordinates, cases, barangayName, selectedYear) {
        var polygonCoords = coordinates.map(function(coord) {
            return ol.proj.fromLonLat(coord);
        });

        // Define color ranges based on the number of cases and selected year
        var fillColor;
        var strokeColor;

        if (selectedYear === 'all') {
            // Color conditions for 'All Years'
            if (cases == 0) {
                fillColor = 'rgba(255, 255, 255, 0.4)';
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            } else if (cases >= 1 && cases <= 33) {
                fillColor = 'rgba(0, 128, 0, 0.2)'; // GREEN
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            } else if (cases >= 34 && cases <= 66) {
                fillColor = 'rgba(255, 255, 0, 0.3)'; // Semi-transparent Yellow
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            } else {
                fillColor = 'rgba(255, 0, 0, 0.4)'; // Semi-transparent Red
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            }
        } else if (selectedYear === '2019' || selectedYear === '2020' || selectedYear === '2021' || selectedYear === '2022' || selectedYear === '2023') {
            //2019
            if (cases == 0) {
                fillColor = 'rgba(255, 255, 255, 0.4)';
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            } else if (cases >= 1 && cases <= 6) {
                fillColor = 'rgba(0, 128, 0, 0.2)'; // GREEN
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            } else if (cases >= 7 && cases <= 12) {
                fillColor = 'rgba(255, 255, 0, 0.3)'; // Semi-transparent Yellow
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            } else {
                fillColor = 'rgba(255, 0, 0, 0.4)'; // Semi-transparent Red
                strokeColor = 'rgba(255, 255, 255, 0.4)';
            }
        }

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

    function initMap() {
        // Define the bounding box for San Fernando City, La Union, Philippines
        var extent = ol.proj.transformExtent([120.25, 16.55, 120.47, 16.66], 'EPSG:4326', 'EPSG:3857');

        var map = new ol.Map({
            target: 'map',
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM()
                }),
                <?php
                foreach ($zones as $barangay => $coordinates) {
                    $cases = isset($casesData[$barangay]) ? $casesData[$barangay] : 0;
                    echo "createZonePolygon(" . json_encode($coordinates) . ", $cases, '$barangay', '$selectedYear'),";
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

        // Hover interaction
        var hoverInteraction = new ol.interaction.Select({
            condition: ol.events.condition.pointerMove,
            layers: map.getLayers().getArray(),
            style: function(feature) {
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

        hoverInteraction.on('select', function(event) {
            var barangayContainer = document.getElementById('barangayContainer');
            var preventiveMeasuresContainer = document.getElementById('preventiveMeasuresContainer');

            if (event.selected.length > 0) {
                var barangayName = event.selected[0].get('barangayName');

                if (casesData[barangayName] !== undefined) {
                    var currentCasesText = (selectedYear == new Date().getFullYear()) ? 'Current Cases' : ' Cases';

                    barangayContainer.innerHTML =
                        'Barangay ' + barangayName +
                        '<br>' +
                        currentCasesText + ': ' + casesData[barangayName] +
                        '<br>' +
                        'As of ' + (selectedYear == 'all' ? getLatestYear() : selectedYear);

                    // Display preventive measures based on the number of cases
                    var cases = casesData[barangayName];
                    if (selectedYear === 'all') {
                        if (cases >= 1 && cases <= 33) {
                            preventiveMeasuresContainer.innerHTML =
                                '<a>Low Severity: </a>' +
                                '<br>' +
                                'Educate, clean, and distribute repellents.';
                        } else if (cases >= 34 && cases <= 66) {
                            // Add medium severity preventive measures here
                            preventiveMeasuresContainer.innerHTML =
                                '<b>Mid Severity: </b>' +
                                '<br>' +
                                'Check-ups, control, hotline.';
                        } else {
                            // Add high severity preventive measures here
                            preventiveMeasuresContainer.innerHTML =
                                '<c>High Severity: </c>' +
                                '<br>' +
                                'Respond, alert, mobilize, evacuate.';
                        }
                    } else if (selectedYear === '2019' || '2020' || '2021' || '2022' || '2023') {
                        if (cases === 0) {
                            console.log('No Dengue Case. Good Job!');
                            preventiveMeasuresContainer.innerHTML =
                                '<a>No Dengue Case: </a>' +
                                '<br>' +
                                'Good Job!';
                        } else if (cases >= 1 && cases <= 6) {
                            preventiveMeasuresContainer.innerHTML =
                                '<a>Low Severity: </a>' +
                                '<br>' +
                                'Educate, clean, and distribute repellents.';
                        } else if (cases >= 7 && cases <= 12) {
                            // Add medium severity preventive measures here
                            preventiveMeasuresContainer.innerHTML =
                                '<b>Mid Severity: </b>' +
                                '<br>' +
                                'Check-ups, control, hotline.';
                        } else {
                            // Add high severity preventive measures here
                            preventiveMeasuresContainer.innerHTML =
                                '<c>High Severity: </c>' +
                                '<br>' +
                                'Respond, alert, mobilize, evacuate.';
                        }
                    }
                    preventiveMeasuresContainer.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.4)'; // Add shadow when hovering
                    preventiveMeasuresContainer.style.backgroundColor = 'rgba(255, 255, 255, 1)'; // Set alpha value to 1 for solid background
                } else {
                    barangayContainer.innerHTML = 'No data as of now.';
                    preventiveMeasuresContainer.innerHTML = '';
                    preventiveMeasuresContainer.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.4)'; // Add shadow when hovering
                    preventiveMeasuresContainer.style.backgroundColor = 'rgba(255, 255, 255, 1)'; // Set alpha value to 0 for transparent background
                }
            } else {
                barangayContainer.innerHTML = 'Hover over any zone to see more info.';
                preventiveMeasuresContainer.innerHTML = '';
                preventiveMeasuresContainer.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0)'; // Add shadow when hovering
                preventiveMeasuresContainer.style.backgroundColor = 'rgba(255, 255, 255, 0.0)'; // Set alpha value to 0 for transparent background
            }
        });

        map.addInteraction(hoverInteraction);
    }

    function getLatestYear() {
        // You may need to adjust this function based on how you determine the latest year in your data
        // Here, I assume $uniqueYears is an array of years obtained from your PHP code
        var latestYear = Math.max.apply(null, <?php echo json_encode($uniqueYears); ?>);
        return latestYear;
    }

    // Function to update the map when the year is changed
    function updateMap() {
        var selectedYear = document.getElementById('selectedYear').value;

        // Reload the page with the selected year as a parameter
        window.location.href = '?selected_year=' + encodeURIComponent(selectedYear);
    }

    initMap();
</script>

<?php
require_once 'footer.php';
?>