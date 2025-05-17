<?php
require('main/session_users.php');
include('phps/php__barangay_data.php')
    ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <link rel="stylesheet" type="text/css" href="css/barangay_data.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <title>Manage Data | MapQuito</title>
</head>

<body class="theme-orange">
    <main class="barangay-data" id="barangayData">
        <!-- ✅ Page Header -->
        <header class="barangay-data__header">
            <h1>Cases of Year <?php echo $year; ?> <?php echo $barangay ? "in Barangay $barangay" : ""; ?></h1>
        </header>

        <!-- ✅ Sticky Delete Button Container (NOW INSIDE FORM) -->
        <div class="sticky-container">
            <button type="button" class="back-btn" onclick="backBtn('<?php echo $year; ?>')">Back</button>
            <input type="hidden" name="year" value="<?php echo $year; ?>">
            <!-- Display the total cases -->
            <span class="total-cases-label">
                Total Cases:
                <strong class="total-cases-number <?php echo ($total_cases > 0) ? 'has-cases' : 'zero-cases'; ?>">
                    <?php echo number_format($total_cases ?? 0); ?>
                </strong>
            </span>
        </div>

        <!-- ✅ Table Section -->
        <section id="table-section">
            <div class="table-container">
                <?php
                if (!empty($cases)) {
                    echo "<table>
                    <tr>
                        <th>Year</th>
                        <th>Week</th>
                        <th>Cases</th>
                        <th></th>
                    </tr>";

                    foreach ($cases as $case) {
                        echo "<tr>
                        <td>" . $case["year"] . "</td>
                        <td>" . $case["morbidity_week"] . "</td>
                        <td>" . $case["cases"] . "</td>
                        <td>";

                        echo "<div class='action-buttons'>";

                        echo "<input type='hidden' name='case_id' value='" . $case["id"] . "'>";

                        echo "<button class='action-btn edit-btn' type='button' onclick='openEditModal(\"" . $case["id"] . "\")'>
                            <span class='text'>Edit</span>
                            <span class='icon'>
                            <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'>
                                <path fill='#fff' d='M3 17.25V21h3.75l11.06-11.06-3.75-3.75L3 17.25zm18-10.83c.39-.39.39-1.02 0-1.41l-2.12-2.12c-.39-.39-1.02-.39-1.41 0L15 5.34l3.75 3.75 2.25-2.67z'></path>
                            </svg>
                                </span>
                            </button>";

                        echo "</div>";
                        echo "</td></tr>";
                    }

                    echo "</table>";
                } else {
                    echo "<p>No cases available for this year" . ($barangay ? " and barangay $barangay" : "") . ".</p>";
                }
                ?>
            </div>

            <div class="modals">
                <!-- Edit Modal Overlay -->
                <div class="modal-overlay"></div>

                <div id="edit-modal" class="edit-modal-card"
                    style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000;">
                    <div class="edit-modal-header">
                        <div class="edit-image">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linejoin="round" stroke-linecap="round"
                                    stroke-width="1.5"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z">
                                </path>
                            </svg>
                        </div>
                    </div>
                    <div class="edit-modal-content">
                        <span class="edit-modal-title" id="edit-modal-title">Editing Week </span>
                        <!-- Title updated dynamically -->
                        <form id="edit-form">
                            <input type="hidden" name="case_id" id="edit-case-id">

                            <label for="edit-cases">Cases:</label>
                            <input type="number" name="cases" id="edit-cases" required>

                            <button type="submit" class="edit-save-btn">Save Changes</button>
                            <button type="button" class="edit-cancel-btn" onclick="closeEditModal()">Cancel</button>
                        </form>
                    </div>
                </div>

                <!-- Background Overlay -->
                <div id="edit-modal-overlay"
                    style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999;">
                </div>

            </div>

            <script src="js/barangay-data.js"></script>

        </section>
    </main>

</body>

</html>