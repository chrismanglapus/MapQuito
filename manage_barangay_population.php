<?php
require('main/session_users.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Barangay Population | MapQuito</title>

    <!-- Styles -->
    <link rel="stylesheet" href="css/main.css" />
    <link rel="stylesheet" href="css/manage_barangay_population.css" />
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
</head>

<body class="theme-orange">

    <main class="barangay-list" id="barangayList" style="padding: 1rem;">

        <header class="barangay-list-header">
            <h1>Edit Barangay Population</h1>
        </header>

        <!-- Save Button (placed above the table) -->
        <div class="center-btn">
            <button type="submit" form="populationForm" class="btn-tertiary">
                Save Changes
            </button>
        </div>

        <!-- Editable Table -->
        <div id="populationEditTable" class="table-container">
            <!-- Table is inserted here -->
        </div>

        <!-- Status Message -->
        <p id="populationStatus" style="margin-top: 1rem; text-align: center;"></p>

    </main>

    <script>
        function loadPopulationData() {
            fetch("get_barangay_population.php")
                .then((response) => response.json())
                .then((data) => {
                    const tableContainer = document.getElementById("populationEditTable");

                    tableContainer.innerHTML = `
                    <form id="populationForm">
                        <table>
                            <thead>
                                <tr>
                                    <th>Barangay</th>
                                    <th>Population</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${Object.entries(data)
                                    .map(
                                        ([barangay, pop]) => `
                                        <tr>
                                            <td>${barangay}</td>
                                            <td>
                                                <input type="number" name="population[${barangay}]" value="${pop}" min="0" required>
                                            </td>
                                        </tr>
                                    `
                                    )
                                    .join("")}
                            </tbody>
                        </table>
                    </form>
                    `;

                    document.getElementById("populationForm").addEventListener("submit", function(e) {
                        e.preventDefault();
                        const formData = new FormData(this);

                        fetch("save_barangay_population.php", {
                                method: "POST",
                                body: formData,
                            })
                            .then((response) => response.json())
                            .then((result) => {
                                const modal = document.getElementById("successModal");
                                const message = document.getElementById("modalMessage");

                                message.textContent = result.message;
                                modal.style.display = "flex";

                                // Auto-close modal after 3 seconds
                                setTimeout(() => {
                                    modal.style.display = "none";
                                }, 3000);
                            })

                            .catch(() => {
                                const status = document.getElementById("populationStatus");
                                status.textContent = "An error occurred while saving.";
                                status.style.color = "red";
                            });
                    });
                })
                .catch(() => {
                    document.getElementById("populationEditTable").innerHTML =
                        '<p style="color:red;">Failed to load data.</p>';
                });
        }

        // Automatically load table on page load
        document.addEventListener("DOMContentLoaded", loadPopulationData);
    </script>

    <!-- Success Modal -->
    <div id="successModal" class="population-modal">
        <div class="population-modal__content">
            <p id="modalMessage">Success!</p>
        </div>
    </div>

</body>

</html>