<?php
session_start();
require('main/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disclaimer</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body class="disclaimer-container">
    <div id="disclaimer" class="disclaimer">

        <header class="disclaimer-header">
            <h1>Disclaimer</h1>
            <p>Important Terms & Conditions for Using MapQuito</p>
        </header>

        <section class="disclaimer-content">

            <div class="disclaimer-item">
                <h2>General Information</h2>
                <p>MapQuito is a platform providing real-time data to help monitor dengue transmission in San Fernando, La Union. While we strive for accuracy, the data provided on this platform is subject to change and should not be the sole basis for health-related decisions.</p>
            </div>

            <div class="disclaimer-item">
                <h2>Accuracy of Data</h2>
                <p>The information presented on MapQuito is obtained from various reliable sources, including local health authorities. However, the platform does not guarantee the accuracy, completeness, or timeliness of the data. Users are encouraged to verify the data with local health officials.</p>
            </div>

            <div class="disclaimer-item">
                <h2>Liability</h2>
                <p>MapQuito and its creators are not liable for any direct or indirect damages arising from the use of the platform. The platform is provided "as is," and users assume all risks associated with its use.</p>
            </div>

            <div class="disclaimer-item">
                <h2>Changes to Disclaimer</h2>
                <p>MapQuito reserves the right to modify this disclaimer at any time. Users will be notified of any significant changes through updates on this page.</p>
            </div>

        </section>
    </div>

    <script>
        // Wait for the DOM to fully load
        document.addEventListener('DOMContentLoaded', function() {
            // You can add any interactive JavaScript logic here if needed
        });
    </script>
</body>

</html>

<?php
require('main/footer.php');
?>