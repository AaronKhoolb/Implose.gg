<!--
Programmer Name: Max
Program Name: /pages/public/about_us.php
Description: Guest About Us page. Introduces the platform and shows 
             the development team behind Implose.gg.
First Written on: Monday, 26-May-2026
Edited on: Wednesday, 2-Jul-2026
-->

<?php
$current_page = 'guest_about';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
        include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); 
    ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/components/guest_navi.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/about_us.css">

    <title>About Us | Implose.gg</title>
</head>
<body>
    
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/public/guest_navi.php'); ?>



    <!-- MAIN CONTENT -->
    <main class="about-container">
        
        <section class="about-card">
            <h1 class="about-title">WHO WE ARE</h1>
            <p class="about-description">
                Welcome to <span class="highlight-text">Implose.gg</span>. This project was developed as part of an academic initiative to design and implement an interactive and engaging web-based platform.
            </p>
            <p class="about-description">
                The purpose of this system is to assist students by providing a structured, gamified environment that enhances user engagement, simplifies interaction, and improves overall user experience. By integrating gaming elements with modern web technologies, the platform aims to make digital systems more intuitive, enjoyable, and effective for users.
            </p>
            <p class="about-description">
                In addition, the platform incorporates AI-based analysis to evaluate user performance, identify individual weaknesses, and provide personalized feedback. This enables students to better understand their learning gaps and improve through targeted recommendations and continuous progress tracking.
            </p>
            
            <div class="about-stats">
                <div class="stat-box">
                    <h3>Interactive</h3>
                    <p>Learning Experience</p>
                </div>
                <div class="stat-box">
                    <h3>AI-Powered</h3>
                    <p>Performance Analysis</p>
                </div>
            </div>
        </section>

        <section class="staff-section">
            <h2 class="staff-title">MEET OUR TEAM</h2>
            <p class="staff-subtitle">The development team behind Implose.gg</p>

            <div class="staff-grid">
                <div class="staff-card-item">
                    <h3 class="staff-name">Khoo Lay Bin</h3>
                    <span class="staff-role">Project Leader</span>
                    <p class="staff-bio">
                        Oversees overall project direction, coordinates team activities, and ensures all development milestones are achieved. Responsible for reviewing documentation and code to maintain quality, consistency, and alignment with project requirements.
                    </p>
                </div>

                <div class="staff-card-item">
                    <h3 class="staff-name">Damian Loh Yi Feng</h3>
                    <span class="staff-role">Full-Stack Developer</span>
                    <p class="staff-bio">
                        Responsible for both front-end and back-end development, including system integration, server-side logic, and user interface implementation.
                    </p>
                </div>

                <div class="staff-card-item">
                    <h3 class="staff-name">Chong Ray Han</h3>
                    <span class="staff-role">System Developer</span>
                    <p class="staff-bio">
                        Designs and develops core system functionalities, implements algorithms, and enhances system performance and features.
                    </p>
                </div>

                <div class="staff-card-item">
                    <h3 class="staff-name">Chong Jun Yoong</h3>
                    <span class="staff-role">Database Administrator</span>
                    <p class="staff-bio">
                        Designs and manages the database architecture, ensures data integrity and security, optimizes complex queries, and oversees data consistency, backup, and recovery processes to support reliable system performance.
                    </p>
                </div>

                <div class="staff-card-item">
                    <h3 class="staff-name">Ng Jiunn Chyn</h3>
                    <span class="staff-role">UI/UX Designer</span>
                    <p class="staff-bio">
                        Designs user interfaces and user experience, develops wireframes, and ensures visual consistency and usability across the platform.
                    </p>
                </div>

                <div class="staff-card-item">
                    <h3 class="staff-name">Lim Jin Xiang</h3>
                    <span class="staff-role">Front-End Developer</span>
                    <p class="staff-bio">
                        Develops and maintains responsive front-end interfaces, ensures cross-device compatibility, and enhances overall user experience.
                    </p>
                </div>
            </div>
        </section>

    </main>

    <!-- GLOBAL GUEST FOOTER -->
    <footer class="guest-footer">
        <p> 2026 Implose.gg. All rights reserved.</p>
    </footer>

</body>
</html>