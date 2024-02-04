<?php
session_start();
require('connection.php');
require('header.php');
require('menu.php');

// Initialize $searchDate
$searchDate = isset($_POST['searchDate']) ? $_POST['searchDate'] : "";

// Initialize $barangayFilter
$barangayFilter = "";

// Function to retrieve heatmap data with pagination and filtering
function getHeatmapDataWithPagination($page, $perPage, $searchDate = "", $barangayFilter = "")
{
    global $conn;

    $heatmapData = array();
    $condition = "";

    // Check if a search date is provided
    if (!empty($searchDate)) {
        $condition = "WHERE DATE(date_added) = '" . date('Y-m-d', strtotime($searchDate)) . "'";
    }

    // Add the barangay filter to the condition
    if (!empty($barangayFilter)) {
        $barangayCondition = empty($condition) ? "WHERE" : " AND";
        $condition .= "$barangayCondition barangay = '$barangayFilter'";
    }

    // If there's a barangay filter, get all data for that barangay without pagination
    if (!empty($barangayFilter)) {
        $sql = "SELECT id, barangay, cases, date_added FROM heatmap_data $condition ORDER BY date_added DESC";
    } else {
        // If there's no barangay filter, use pagination
        $startIndex = ($page - 1) * $perPage;
        $sql = "SELECT id, barangay, cases, date_added FROM heatmap_data $condition ORDER BY date_added DESC LIMIT $startIndex, $perPage";
    }

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $heatmapData[] = $row;
        }
    }

    return $heatmapData;
}

// Function to get total rows considering filters
function getTotalRowsWithFilters($searchDate = "", $barangayFilter = "")
{
    global $conn;

    $condition = "";

    // Check if a search date is provided
    if (!empty($searchDate)) {
        $condition = "WHERE DATE(date_added) = '" . date('Y-m-d', strtotime($searchDate)) . "'";
    }

    // Add the barangay filter to the condition
    if (!empty($barangayFilter)) {
        $barangayCondition = empty($condition) ? "WHERE" : " AND";
        $condition .= "$barangayCondition barangay = '$barangayFilter'";
    }

    // SQL query to count total rows with filters
    $sql = "SELECT COUNT(*) AS total FROM heatmap_data $condition";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return $row['total'];
    }

    return 0;
}

// Set the number of items per page
$itemsPerPage = 100;

// Initialize variables
$heatmapData = array();
$totalItems = 0;
$totalPages = 0;
$currentpage = 1;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle button actions here (EDIT, DELETE, etc.)
    // You can use the data ID to identify the specific heatmap data

    if (isset($_POST["edit"])) {
        // Handle edit button
        $dataIdToEdit = $_POST["edit"];
        // Use JavaScript to redirect to edit_heatmap_data.php with data ID
        echo "<script>window.location.href='edit_heatmap_data.php?data_id=$dataIdToEdit';</script>";
        exit();
    }

    if (isset($_POST["delete"])) {
        // Handle delete button
        $dataIdToDelete = $_POST["delete"];
        // Use JavaScript to handle the deletion
        echo "<script>confirmDelete($dataIdToDelete);</script>";
    }

    if (isset($_POST["clearSearch"])) {
        // Clear the search date
        $heatmapData = getHeatmapDataWithPagination(1, $itemsPerPage);
    }
}

// Check if the form is submitted with a search date and barangay filter
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["searchButton"])) {
    $searchDate = $_POST["searchDate"];
    // Use the null coalescing operator to set a default value for $barangayFilter
    $barangayFilter = $_POST["barangayFilter"] ?? "";

    if (!empty($barangayFilter)) {
        // If there's a barangay filter, get all data for that barangay without pagination
        $heatmapData = getHeatmapDataWithPagination(1, $totalItems, $searchDate, $barangayFilter);
        $totalItems = getTotalRowsWithFilters($searchDate, $barangayFilter);
        $totalPages = 1;  // Set total pages to 1 when not using pagination
    } else {
        // If there's no barangay filter, use pagination
        $heatmapData = getHeatmapDataWithPagination(1, $itemsPerPage, $searchDate, $barangayFilter);
        $totalItems = getTotalRowsWithFilters($searchDate, $barangayFilter);
        $totalPages = ceil($totalItems / $itemsPerPage);
    }
} else {
    // Default behavior without search
    $heatmapData = getHeatmapDataWithPagination(1, $itemsPerPage);
    $totalItems = getTotalRowsWithFilters();
    $totalPages = ceil($totalItems / $itemsPerPage);
}


// Get the current page number
$currentpage = isset($_GET['page']) ? $_GET['page'] : 1;

// Retrieve the heatmap data for the current page with filters
$heatmapData = getHeatmapDataWithPagination($currentpage, $itemsPerPage, $searchDate, $barangayFilter);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- Include jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <title>Heatmap Management</title>
    <style>
        h2 {
            font-size: 30px;
            color: #3498db;
            outline: auto;
            text-align: center;
            background-color: white;
        }
    </style>
</head>

<body>
    <div class="heatmap-container">
        <h2>HEATMAP DATA</h2>
        <a href="heatmap_form.php" class="create-account-btn">Add Data</a>
        <div class="search-container">
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="flex-container">
                <div class="flex-item">
                    <label for="searchDate">Date:</label>
                    <input class='search' type="text" id="searchDate" name="searchDate">
                </div>
                <div class="flex-item">
                    <!-- Add the following dropdown menu for barangays -->
                    <label for="barangayFilter">Barangay:</label>
                    <select name="barangayFilter" id="barangayFilter" class="search">
                        <option value="">All Barangays</option>
                        <?php
                        // Retrieve distinct barangays from the database
                        $sqlBarangays = "SELECT DISTINCT barangay FROM heatmap_data";
                        $resultBarangays = $conn->query($sqlBarangays);

                        if ($resultBarangays->num_rows > 0) {
                            while ($row = $resultBarangays->fetch_assoc()) {
                                // Check if $barangayFilter is set and matches the current row's barangay
                                $selected = isset($barangayFilter) && $barangayFilter == $row['barangay'] ? 'selected' : '';
                                echo "<option value='" . $row['barangay'] . "' $selected>" . $row['barangay'] . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="flex-item">
                    <button class='search-button' type="submit" name="searchButton">Search</button>
                    <button class='search-button' type="submit" name="clearSearch">Clear Search</button>
                    <input type="hidden" name="page" value="<?php echo $currentpage; ?>">
                </div>
            </form>
        </div>

        <?php
        if (!empty($heatmapData)) {
            // Display pagination links
            echo "<div class='pagination-buttons'>";
            $baseURL = $_SERVER['PHP_SELF'] . "?searchDate=$searchDate&barangayFilter=$barangayFilter";

            if ($currentpage > 1) {
                echo "<a href='" . $baseURL . "&page=" . ($currentpage - 1) . "'>Previous Page</a>";
            }

            for ($i = 1; $i <= $totalPages; $i++) {
                echo "<a href='" . $baseURL . "&page=" . $i . "' " . ($i == $currentpage ? "class='active'" : "") . ">$i</a>";
            }

            if ($currentpage < $totalPages) {
                echo "<a href='" . $baseURL . "&page=" . ($currentpage + 1) . "'>Next Page</a>";
            }

            echo "</div>";
            
            echo "<table>
                    <tr>
                        <th>Barangay</th>
                        <th>Cases</th>
                        <th>Date Added</th>
                        <th>Action</th>
                    </tr>";

            foreach ($heatmapData as $data) {
                echo "<tr>
                        <td>" . $data["barangay"] . "</td>
                        <td>" . $data["cases"] . "</td>
                        <td>" . date('F d, Y', strtotime($data["date_added"])) . "</td>
                        <td>";
                echo "<div class='action-buttons'>";
                echo "<form method='post' action='" . $_SERVER["PHP_SELF"] . '?page=' . $currentpage . "' style='display:inline;'>";
                echo "<button class='edit-button' name='edit' value='" . $data["id"] . "'>EDIT</button>";
                echo "<button class='delete-button' name='delete' value='" . $data["id"] . "' onclick='confirmDelete(" . $data["id"] . ")'>DELETE</button>";
                echo "</form>";
                echo "</div>";
                echo "</td></tr>";
            }

            echo "</table>";
        } else {
            echo "<p>No heatmap data available. Add some now!</p>";
        }
        ?>

    </div>

    <script>
        $(document).ready(function() {
            // Function to check if a date has data
            function dateHasData(date) {
                // Format the date to match your SQL date format
                var formattedDate = $.datepicker.formatDate('yy-mm-dd', date);

                // You need to modify this function based on your data structure
                // Check if the date has data in your SQL database
                <?php
                // Retrieve dates with data from the database
                $sqlDatesWithData = "SELECT DISTINCT DATE(date_added) AS date_with_data FROM heatmap_data";
                $resultDatesWithData = $conn->query($sqlDatesWithData);

                $datesWithEventData = array();

                if ($resultDatesWithData->num_rows > 0) {
                    while ($row = $resultDatesWithData->fetch_assoc()) {
                        $datesWithEventData[] = $row['date_with_data'];
                    }
                }

                // Convert PHP array to a JavaScript array
                echo "var datesWithEventData = " . json_encode($datesWithEventData) . ";";
                ?>

                // Check if the formatted date is in the array of dates with data
                return datesWithEventData.includes(formattedDate);
            }

            // Datepicker initialization
            $("#searchDate").datepicker({
                dateFormat: 'yy-mm-dd',
                beforeShowDay: function(date) {
                    // Check if the date has data, and add the highlighted class
                    if (dateHasData(date)) {
                        return [true, 'highlighted-date'];
                    }
                    return [true, ''];
                },
            });
        });

        function confirmDelete(dataId) {
            if (confirm("Are you sure you want to delete this data?")) {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        if (this.responseText.trim() === "success") {
                            alert("Data deleted successfully.");
                            // Reload the page to reflect the changes
                            location.reload();
                        } else {
                            alert("Error deleting data.");
                        }
                    }
                };

                // Send a request to delete_heatmap_data.php with data ID
                xmlhttp.open("GET", 'delete_heatmap_data.php?data_id=' + dataId, true);
                xmlhttp.send();
            }
        }
    </script>
</body>

</html>

<?php
require('footer.php');
?>