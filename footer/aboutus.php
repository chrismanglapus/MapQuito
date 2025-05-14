<?php
session_start();
require('main/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body class="about-us-container">
    <div id="about-us" class="about-us">

        <header class="about-us-header">
            <h1><img src="assets/logologin.png" alt="Logo">About MapQuito</h1>
            <p>Visualizing dengue transmission in City of San Fernando, La Union</p>
        </header>

        <section class="about-us-content">

            <div class="about-us-intro">
                <h2>Our Mission</h2>
                <p>MapQuito is dedicated to helping communities monitor and combat dengue transmission by providing
                    real-time, interactive heatmaps of dengue cases in San Fernando, La Union. Our goal is to
                    support healthcare efforts with data-driven insights and promote awareness.</p>
            </div>

            <div class="about-us-team">
                <h2>The Team</h2>
                <div class="about-us-team-members">
                    <div class="about-us-team-member">
                        <img src="assets/richmond.png" alt="Richmond">
                        <h3>Richmond Corpuz</h3>
                        <p>Project Lead & Developer</p>
                    </div>
                    <div class="about-us-team-member">
                        <img src="assets/christian.png" alt="Christian">
                        <h3>Christian Jose Manglapus</h3>
                        <p>Data Analyst</p>
                    </div>
                    <div class="about-us-team-member">
                        <img src="assets/mark.png" alt="Mark">
                        <h3>Mark John Paul Rosario</h3>
                        <p>UI/UX Designer</p>
                    </div>
                </div>
            </div>


            <div class="about-us-technology">
                <h2>Our Technology</h2>
                <div class="technology-intro">
                    <p>MapQuito leverages a combination of modern tools and technologies to deliver a reliable and
                        interactive platform for visualizing dengue transmission. Below are the system requirements and
                        the
                        tools we used to bring our vision to life:</p>
                </div>

                <div class="technology-list">
                    <div class="technology-item">
                        <h3>XAMPP</h3>
                        <p>A free and open-source cross-platform web server solution stack that includes Apache, MySQL,
                            PHP, and Perl. XAMPP provides the local server environment for developing, testing, and
                            running the MapQuito platform.</p>
                    </div>
                    <div class="technology-item">
                        <h3>SQLYog</h3>
                        <p>SQLYog is a powerful database management tool for MySQL that we used to manage and structure
                            our project's data. It allows for efficient database design and queries, ensuring smooth
                            interactions between the system and the database.</p>
                    </div>
                    <div class="technology-item">
                        <h3>Visual Studio</h3>
                        <p>Visual Studio is an integrated development environment (IDE) used for developing, debugging,
                            and testing the MapQuito platform. It supports PHP, HTML, CSS, and JavaScript, providing an
                            efficient workspace for development.</p>
                    </div>
                    <div class="technology-item">
                        <h3>PHP</h3>
                        <p>PHP is a server-side scripting language that powers the backend of MapQuito. It handles
                            business logic, database interactions, and dynamic content generation, ensuring the
                            platform’s functionality and interactivity.</p>
                    </div>
                    <div class="technology-item">
                        <h3>HTML</h3>
                        <p>HTML is the backbone of the MapQuito platform, providing structure and content for web pages.
                            It enables the creation of interactive and dynamic content, helping users to navigate the
                            platform efficiently.</p>
                    </div>
                    <div class="technology-item">
                        <h3>CSS</h3>
                        <p>CSS is used to style the MapQuito platform, ensuring a visually appealing and responsive
                            design. It defines the layout, colors, fonts, and overall user experience of the web
                            application.</p>
                    </div>
                </div>
            </div>

        </section>
    </div>
</body>

</html>

<?php
require('main/footer.php');
?>