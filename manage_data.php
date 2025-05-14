<?php
require('main/session_users.php');
include('phps/php__manage_data.php')
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/manage_data.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <title>Manage Data | MapQuito</title>
</head>

<body>
    <main class="manage-data" id="manageData">
        <!-- Page Header -->
        <header class="manage-data__header">
            <h1>Dengue Cases Management Page</h1>
        </header>
        <!-- Data Management Section -->
        <section class="manage-data__section">
            <!-- ADD DATA BUTTON -->
            <button id="addDataBtn" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#c9c9c9">
                    <path d="M440-440H240q-17 0-28.5-11.5T200-480q0-17 11.5-28.5T240-520h200v-200q0-17 11.5-28.5T480-760q17 0 28.5 11.5T520-720v200h200q17 0 28.5 11.5T760-480q0 17-11.5 28.5T720-440H520v200q0 17-11.5 28.5T480-200q-17 0-28.5-11.5T440-240v-200Z" />
                </svg>
                <span>Add More Data</span>
            </button>
            <!-- EXPORT DATA BUTTON -->
            <button id="exportBtn" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#c9c9c9">
                    <path d="M480-480ZM320-183l-90 90q-12 12-28 11.5T174-94q-11-12-11.5-28t11.5-28l90-90h-50q-17 0-28.5-11.5T174-280q0-17 11.5-28.5T214-320h146q17 0 28.5 11.5T400-280v146q0 17-11.5 28.5T360-94q-17 0-28.5-11.5T320-134v-49ZM200-400q-17 0-28.5-11.5T160-440v-360q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H520q-17 0-28.5-11.5T480-120q0-17 11.5-28.5T520-160h200v-440H560q-17 0-28.5-11.5T520-640v-160H240v360q0 17-11.5 28.5T200-400Z" />
                </svg>
                <span>Export Data</span>
            </button>
        </section>
        <section class="manage-data__modal">
            <!-- ADD DATA MODAL -->
            <div id="addDataModal" class="modal-card" style="display: none;">
                <div class="modal-content">
                    <header class="modal-header">
                        <h3 class="modal-title">Upload Excel Data</h3>
                    </header>
                    <form id="addDataForm" enctype="multipart/form-data">
                        <div class="modal-card__header">
                            <label for="dataYear">Year of Data:</label>
                            <input type="number" name="dataYear" id="dataYear" min="2019" max="2100" required>

                            <label for="excelFile">Upload Excel file:</label>
                            <input type="file" name="excelFile" id="excelFile" required>
                        </div>
                        <div class="modal-card__buttons">
                            <button type="submit" class="btn-primary">
                                <span class="text">Confirm</span>
                                <span class="icon"><svg
                                        viewBox="0 0 24 24"
                                        height="24"
                                        width="24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                    </svg></span>
                            </button>
                            <button type="button" id="closeAddDataModal" class="btn-secondary">
                                <span class="text">Cancel</span>
                                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                        <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                        <p id="uploadStatus"></p>
                    </form>
                </div>
            </div>
            <!-- EXPORT DATA MODAL -->
            <div id="exportModal" class="modal-card" style="display: none;">
                <div class="modal-content">
                    <header class="modal-header">
                        <h3 class="modal-title">Export Data</h3>
                    </header>
                    <div class="exportDataForm">
                        <label for="exportYear">Year:</label>
                        <select id="exportYear">
                            <option value="" disabled selected>Select a Year to Export</option>
                            <?php
                            foreach ($years as $year) {
                                echo "<option value=\"$year\">$year</option>";
                            }
                            ?>
                        </select>
                        <div class="modal-card__buttons">
                            <button type="submit" class="btn-primary" id="confirmExport">
                                <span class="text">Confirm</span>
                                <span class="icon"><svg
                                        viewBox="0 0 24 24"
                                        height="24"
                                        width="24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
                                    </svg></span>
                            </button>
                            <button type="button" id="closeModal" class="btn-secondary">
                                <span class="text">Cancel</span>
                                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                        <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="modalOverlay" class="modal-overlay" style="display: none;"></div>
        </section>

        <!-- Year Selection -->
        <aside class="year-selection">
            <!-- Year Selection Header -->
            <header class="year-selection__header">
                <h2>Select Year</h2>
            </header>
            <div class="year-container">
                <?php
                if (!empty($years)) {
                    foreach ($years as $year) {
                        echoYearButton($year);
                    }
                }
                ?>
            </div>
        </aside>

        <?php function echoYearButton($year)
        {
            if (hasBarangayData($year)) {
                echo "<form method='get' action='manage_data__barangay_list.php'>
                    <input type='hidden' name='year' value='" . htmlspecialchars($year, ENT_QUOTES) . "'>
                    <button type='submit' class='year-btn'>" . $year . "</button>
                    </form>";
            } else {
                echo "<form method='get' action='manage_data__barangay_data.php'>
                    <input type='hidden' name='year' value='" . htmlspecialchars($year, ENT_QUOTES) . "'>
                    <button type='submit' class='year-btn'>" . $year . " </button>
                    </form>";
            }
        } ?>

    </main>

    <script src="js/manage_data.js"></script>

</body>

</html>