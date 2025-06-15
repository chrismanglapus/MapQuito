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
    <link rel="stylesheet" href="css/manage_data_modal.css">
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
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-input-icon lucide-file-input">
                    <path d="M4 22h14a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v4" />
                    <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                    <path d="M2 15h10" />
                    <path d="m9 18 3-3-3-3" />
                </svg>
                <span>Add More Data</span>
            </button>

            <!-- EXPORT DATA BUTTON -->
            <button id="exportBtn" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-output-icon lucide-file-output">
                    <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                    <path d="M4 7V4a2 2 0 0 1 2-2 2 2 0 0 0-2 2" />
                    <path d="M4.063 20.999a2 2 0 0 0 2 1L18 22a2 2 0 0 0 2-2V7l-5-5H6" />
                    <path d="m5 11-3 3" />
                    <path d="m5 17-3-3h10" />
                </svg>
                <span>Export Data</span>
            </button>

            <!-- EDIT POPULATION BUTTON -->
            <button id="editPopulationBtn" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-pen-icon lucide-file-pen">
                    <path d="M12.5 22H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v9.5" />
                    <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                    <path d="M13.378 15.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z" />
                </svg>
                <span>Edit Barangay Population</span>
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
                                <span class="icon"><svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
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
                                <span class="icon"><svg viewBox="0 0 24 24" height="24" width="24" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.707 19.121a.997.997 0 0 1-1.414 0l-5.646-5.647a1.5 1.5 0 0 1 0-2.121l.707-.707a1.5 1.5 0 0 1 2.121 0L9 14.171l9.525-9.525a1.5 1.5 0 0 1 2.121 0l.707.707a1.5 1.5 0 0 1 0 2.121z"></path>
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
            <!-- EDIT POPULATION MODAL -->
            <div id="editPopulationModal" class="modal-card" style="display: none;">
                <div class="modal-content">
                    <header class="modal-header">
                        <h3 class="modal-title">Edit Barangay Population</h3>
                    </header>
                    <div class="modal-body">
                        <!-- Placeholder: future form goes here -->
                        <p>This feature will allow you to update barangay population data.</p>
                    </div>
                    <div class="modal-card__buttons">
                        <button type="button" id="closeEditPopulationModal" class="btn-secondary">
                            <span class="text">Close</span>
                            <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                    <path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path>
                                </svg>
                            </span>
                        </button>
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