<!--
Programmer Name: Max
Program Name: /pages/public/index.php
Description: Guest Home page. Shows the platform slogan, feature cards, 
             how-it-works steps and final call-to-action for new visitors.
First Written on: Monday, 26-May-2026
Edited on: Wednesday, 2-Jul-2026
-->

<?php
$current_page = 'guest_home';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/components/guest_navi.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/guest_homepage.css">
    <title>Implose.gg - Level Up Your Learning</title>
</head>
<body>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/public/guest_navi.php'); ?>


    <!-- Hero section with slogan -->
    <section class="hero-section">
        <h1 class="hero-slogan">LEVEL UP YOUR LEARNING</h1>
        <p class="hero-subtitle">
            A gamified learning platform where students earn XP,
            unlock achievements, and get AI-powered feedback to master any subject.
        </p>
        <div class="hero-buttons">
            <a href="/Implose.gg-src/pages/auth/sign_up.php" class="hero-btn-primary">Sign Up Free</a>
            <a href="#features" class="hero-btn-secondary">Learn More</a>
        </div>
    </section>


    <!-- What we offer (features) -->
    <section class="features-section" id="features">
        <h2 class="section-title">WHAT WE OFFER</h2>
        <p class="section-subtitle">Everything you need to make learning stick.</p>

        <div class="features-grid">

            <div class="feature-card">
                <img src="/Implose.gg-src/assets/images/icons/faq_card_gameplay.svg" alt="Gamified">
                <h3>Gamified Learning</h3>
                <p>Study with a game-like design. Track your daily streak and grow your points.</p>
            </div>

            <div class="feature-card">
                <img src="/Implose.gg-src/assets/images/icons/faq_card_analysis.svg" alt="AI Analysis">
                <h3>AI Analysis</h3>
                <p>Our AI spots your weak subjects and suggests exactly what to review.</p>
            </div>

            <div class="feature-card">
                <img src="/Implose.gg-src/assets/images/icons/fire.svg" alt="Streaks">
                <h3>Daily Streaks</h3>
                <p>Sign in every day to keep your streak alive and earn daily check-in points.</p>
            </div>

            <div class="feature-card">
                <img src="/Implose.gg-src/assets/images/icons/achievements.svg" alt="Achievements">
                <h3>Achievements</h3>
                <p>Unlock pixel badges for reaching milestones as you use the platform.</p>
            </div>

            <div class="feature-card">
                <img src="/Implose.gg-src/assets/images/icons/users2.svg" alt="Quiz Rooms">
                <h3>Live Quiz Rooms</h3>
                <p>Host a room, share a code, and challenge your friends in real time.</p>
            </div>

            <div class="feature-card">
                <img src="/Implose.gg-src/assets/images/icons/book.svg" alt="Free For Students">
                <h3>Free For Students</h3>
                <p>All core features free forever. No credit card and no ads.</p>
            </div>
        </div>
    </section>


    <!-- How it works -->
    <section class="steps-section">
        <h2 class="section-title">HOW IT WORKS</h2>
        <p class="section-subtitle">Get started in 4 easy steps.</p>

        <div class="steps-grid">

            <div class="step-card">
                <span class="step-number">01</span>
                <h3>Sign Up Free</h3>
                <p>Create an account with just your email address.</p>
            </div>

            <div class="step-card">
                <span class="step-number">02</span>
                <h3>Pick A Course</h3>
                <p>Choose from Python, Math, English, and more.</p>
            </div>

            <div class="step-card">
                <span class="step-number">03</span>
                <h3>Take Quizzes</h3>
                <p>Answer questions and test your knowledge on the topics you are learning.</p>
            </div>

            <div class="step-card">
                <span class="step-number">04</span>
                <h3>Earn Rewards</h3>
                <p>Grow your daily streak and points, then redeem them in the rewards shop.</p>
            </div>
        </div>
    </section>


    <!-- Final CTA -->
    <section class="final-cta-section">
        <h2>Ready to start your quest?</h2>
        <p>Join thousands of students turning study time into XP.</p>
        <a href="/Implose.gg-src/pages/auth/sign_up.php" class="hero-btn-primary">Sign Up Free</a>
    </section>


    <footer class="guest-footer">
        <p>&copy; 2026 Implose.gg. All rights reserved.</p>
    </footer>

</body>
</html>
