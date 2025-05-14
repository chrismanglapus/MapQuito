<?php
session_start();
require('main/navbar.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body class="faq-container">
    <div id="faq" class="faq">

        <header class="faq-header">
            <h1>Frequently Asked Questions</h1>
            <p>Your guide to understanding MapQuito</p>
        </header>

        <section class="faq-content">

            <div class="faq-item">
                <button class="faq-question">What is MapQuito?</button>
                <div class="faq-answer">
                    <p>MapQuito is a platform designed to visualize dengue transmission in the City of San Fernando, La
                        Union. It provides real-time data and insights to help the community and authorities combat the
                        spread of dengue.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">How does MapQuito help in fighting dengue?</button>
                <div class="faq-answer">
                    <p>MapQuito helps by offering an interactive heatmap of dengue cases, providing data-driven insights
                        for healthcare workers and residents to take preventive actions in real-time.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">Who can use MapQuito?</button>
                <div class="faq-answer">
                    <p>MapQuito is designed for use by local health authorities, community members, and anyone
                        interested in understanding the spread of dengue in San Fernando, La Union.</p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">Is MapQuito free to use?</button>
                <div class="faq-answer">
                    <p>Yes, MapQuito is completely free to use and is accessible to anyone with an internet connection.
                    </p>
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question">How accurate is the data shown on MapQuito?</button>
                <div class="faq-answer">
                    <p>The data displayed is based on real-time reports from local health offices, and we ensure its
                        accuracy through regular updates.</p>
                </div>
            </div>

        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqQuestions = document.querySelectorAll('.faq-question');

            faqQuestions.forEach(function(question) {
                question.addEventListener('click', function() {
                    const answer = this.nextElementSibling; // The next element is the answer div

                    // Toggle the display of the answer
                    if (answer.classList.contains('show')) {
                        answer.classList.remove('show'); // Hide the answer with animation
                        this.classList.remove('active'); // Remove the active class to reset arrow
                    } else {
                        answer.classList.add('show'); // Show the answer with animation
                        this.classList.add('active'); // Add active class to rotate the arrow
                    }
                });
            });
        });
    </script>
</body>

</html>