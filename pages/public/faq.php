<!--
Programmer Name: Max
Program Name: /pages/public/faq.php
Description: Guest FAQ page. Shows a searchable list of frequently asked questions 
             grouped into categories (Account, Gameplay, AI Analysis, Bug Reports). 
             Includes a real-time search filter that scans all questions.
First Written on: Monday, 26-May-2026
Edited on: Wednesday, 2-Jul-2026
-->

<?php
$current_category = isset($_GET['category']) ? $_GET['category'] : 'main';
$current_page = 'guest_faq';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/faq.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/components/guest_navi.css">
    <title>Implose.gg - FAQ</title>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/public/guest_navi.php'); ?>


    <div id="main-view" style="display: <?php echo $current_category === 'main' ? 'block' : 'none'; ?>;">
        
        <section class="faq-search-container">
            <h1 class="faq-main-title">What can we help you with?</h1>
            <p class="faq-subtext">Have questions? We've got the answers.</p>
            <div class="faq-search-box-wrapper">
                <input type="text" id="faq-search-input" class="faq-search-input" placeholder="Type Your Question Here........">
            </div>

            <!-- Search results area (hidden when search box is empty) -->
            <div class="faq-search-results" id="faq-search-results" style="display: none;">
                <h2 class="faq-section-title">Search Results</h2>
                <div id="faq-search-results-list"></div>
                <div class="faq-no-results" id="faq-no-results" style="display: none;">
                    <p>Sorry, we couldn't find a question matching your search.</p>
                    <a href="contact.php" class="btn-contact-us">Contact Us</a>
                </div>
            </div>
        </section>

        <section class="faq-famous-question-section">
            <div class="question-item">
                <div class="question-header">
                    What is implose.gg?
                    <span class="faq-arrow">
                        <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">    
                    </span>
                </div>
                <div class="question-content">
                    <p>Implose.gg is an interactive gamified learning platform where students can track their educational progress, study complex topics, and get instant, clear explanations powered by AI helpers.</p>
                </div>
            </div>

            <div class="question-item">
                <div class="question-header">
                    How does the progress tracking work?
                    <span class="faq-arrow">
                        <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                    </span>
                </div>
                <div class="question-content">
                    <p>Your account keeps track of your daily streak and total points. Sign in every day to keep your streak going and earn check-in points. Points can be redeemed for items in the rewards shop.</p>
                </div>
            </div>

            <div class="question-item">
                <div class="question-header">
                    Is implose.gg free to use for students?
                    <span class="faq-arrow">
                        <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">    
                    </span>
                </div>
                <div class="question-content">
                    <p>Yes! The core platform features, skill paths, and community hubs are entirely free for students looking to improve their learning experience.</p>
                </div>
            </div>

            <div class="question-item">
                <div class="question-header">
                    How do i reset my password
                    <span class="faq-arrow">
                        <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                    </span>
                </div>
                <div class="question-content">
                    <p>Navigate to the Login page, click on "Forgot Password", and input your registered email address. We will instantly email you a secure link to create a new password.</p>
                </div>
            </div>

            <div class="question-item">
                <div class="question-header">
                    How do I get started on implose.gg?
                    <span class="faq-arrow">
                        <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                    </span>
                </div>
                <div class="question-content">
                    <p>Sign up for a free account, complete your profile, and head to the Courses page to pick a topic that interests you. Each course is broken into quizzes you can level up through, and your XP and streak grow the more you learn.</p>
                </div>
            </div>

            <div class="question-item">
                <div class="question-header">
                    Can I use implose.gg on my phone or tablet?
                    <span class="faq-arrow">
                        <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                    </span>
                </div>
                <div class="question-content">
                    <p>Yes! Implose.gg works on any modern web browser including phones, tablets, laptops, and desktops. Your progress syncs across all your devices automatically when you sign in.</p>
                </div>
            </div>

            <div class="question-item">
                <div class="question-header">
                    Do I need to install anything to use implose.gg?
                    <span class="faq-arrow">
                        <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                    </span>
                </div>
                <div class="question-content">
                    <p>No installation needed. Just open implose.gg in your web browser (Chrome, Firefox, Edge, or Safari) and sign in. Everything runs online so you can learn from any device.</p>
                </div>
            </div>
        </section>

        <div class="faq-section-heading-wrapper">
            <h2 class="faq-section-title">Popular Topics</h2>
        </div>

        <section class="faq-cards-section">
            <div class="faq-grid">
                <a href="faq.php?category=account" class="faq-card-block" data-category="account">
                    <span class="faq-card-title">Account & Login</span>
                    <img src="/Implose.gg-src/assets/images/icons/faq_card_account.svg" alt="Account" class="faq-card-img">
                </a>

                <a href="faq.php?category=gameplay" class="faq-card-block" data-category="gameplay">
                    <span class="faq-card-title">Gameplay & XP</span>
                    <img src="/Implose.gg-src/assets/images/icons/faq_card_gameplay.svg" alt="Gameplay" class="faq-card-img">
                </a>

                <a href="faq.php?category=ai_tutors" class="faq-card-block" data-category="ai_tutors">
                    <span class="faq-card-title">AI Analysis & Study</span>
                    <img src="/Implose.gg-src/assets/images/icons/faq_card_analysis.svg" alt="AI Helpers" class="faq-card-img">
                </a>

                <a href="faq.php?category=report_bug" class="faq-card-block" data-category="report_bug">
                    <span class="faq-card-title">About Bug</span>
                    <img src="/Implose.gg-src/assets/images/icons/faq_card_bug.svg" alt="Bug" class="faq-card-img">
                </a>
            </div>
        </section>

        <section class="faq-footer-contact-section">
            <h2 class="contact-title">Still have questions?</h2>
            <p class="contact-subtext">Can't find what you are looking for?</p>
            <a href="contact.php" class="btn-contact-us">Contact Us</a>
        </section>
    </div>


    <div id="subpage-view" class="faq-subpage-container" style="display: 
        <?php echo $current_category !== 'main' ? 'block' : 'none'; ?>;">
    
        <div class="faq-back-nav">
            <a href="faq.php?category=main" class="back-to-main-btn" data-category="main"><- Back to Help Center</a>
        </div>

        <h1 id="subpage-title">Loading Content...</h1>
        <p id="subpage-desc">Please wait...</p>
        
        <div id="subpage-content-area"></div>
    </div>


    <footer class="guest-footer">
        <p>Copyright © 2026, Implose.gg All Rights Reserved.</p>
    </footer>


    <script>
        document.querySelectorAll('.question-item').forEach(famous_question => {
            famous_question.querySelector('.question-header').addEventListener('click', () => {
                famous_question.classList.toggle('active');
            });
        });

        const faqDatabase = {
            account: {
                title: "Account & Security Support",
                desc: "Manage your student profile data, change avatars, reset forgotten passwords, or secure your learning logs.",
                html: `
                    <div class="subpage-faq-section account-section">
                        <div class="question-item">
                            <div class="question-header">
                                How do I reset my password if I am locked out?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">    
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Navigate to the Login page, click on "Forgot Password", and input your registered email address. We will instantly email you a secure link to create a new password.</p>
                            </div>
                        </div>


                        <div class="question-item">
                            <div class="question-header">
                                How do I change my profile avatar or display name?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">    
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Click on your profile icon in the top right corner, select "Settings", and you will see options to upload a custom avatar or unlock exclusive pixel avatars using your earned XP!</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                Will I lose my learning logs and streaks if I log out?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">    
                                </span>
                            </div>
                            <div class="question-content">
                                <p>No, your learning logs, daily streaks, and RPG progress are automatically saved to our cloud server. Just ensure you log back into the same account to continue your progress.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                Can I change the email address linked to my account?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">    
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Yes. Go to Account Settings > Security, and click "Update Email". For safety reasons, you will need to enter your current password and verify a confirmation code sent to your old email address first.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                How do I permanently delete my account and data?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>If you wish to close your account, navigate to Settings > Danger Zone and select "Delete Account". Please note that this action is permanent; all your unlocked achievements, XP, and learning logs will be wiped from our servers forever.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                How do I change my password while logged in?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Go to Account Settings > Security and click "Change Password". Enter your current password, then your new password twice to confirm. Make sure your new password is at least 8 characters with a mix of letters and numbers.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                How do I sign up for a new account?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Click the Sign Up button on the homepage, enter your email, and create a password. We will email you a verification code; enter it to activate your account. Then complete your profile and you are ready to start learning.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                Why can't I sign in to my account?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Double-check that your email and password are typed correctly. If you have forgotten your password, use the "Forgot Password" link on the sign-in page. If your account shows as suspended or banned, please reach out through Contact Us.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                Where can I see my progress and stats?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Your profile page shows your total XP, current streak, completed courses, achievements, and leaderboard rank. Click your profile icon in the top right of any page to open it.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                How do I sign out of my account?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Click your profile icon in the top right corner, then select "Sign Out" from the dropdown menu. You will be returned to the homepage and will need to sign in again next time you visit.</p>
                            </div>
                        </div>
                    </div>
                `
            },
            gameplay: {
                title: "Gameplay Paths & Rewards",
                desc: "Compete with other students, rank up on the weekly leaderboards, and unlock rare achievements as you learn.",
                html: `
                    <div class="subpage-faq-section gameplay-section">
                        <div class="question-item">
                            <div class="question-header">
                                How do daily streaks work?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Signing in every day maintains your Daily Streak. Each consecutive day you check in, your streak count grows. If you miss a day, the streak resets to zero and you can start a new one right away.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                What is the Weekly Leaderboard and how do I rank up?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>The Weekly Leaderboard pits you against other players globally. Every bit of XP you earn pushes you up the ranks. Finishing in the top positions at the end of the week awards premium badges and profile items.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                How do I unlock Achievements and badges?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Achievements are special badges you earn for reaching milestones on the platform, such as long daily streaks. Head to your Achievements page to see which ones you have unlocked and which ones are still waiting for you.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                How do I earn points?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>You earn points by signing in daily to check in. Every day you log in, your check-in reward is added to your total points. Save up your points and redeem them for items in the rewards shop.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                What happens if I miss a day on my streak?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>If you miss a day, your streak count resets to zero. You will lose your streak multiplier but keep all your earned XP, achievements, and levels. You can start a new streak right away!</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                Can I retake a quiz I've already finished?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Yes, you can retake any quiz as many times as you want to improve your score. Your highest score is the one that counts toward your XP and leaderboard ranking.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                Is there a time limit for each quiz question?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Some questions have a per-question time limit shown at the top of the screen during the quiz. If time runs out before you answer, the question is marked as missed and you move on to the next one.</p>
                            </div>
                        </div>
                    </div>
                `
            },
            ai_tutors: {
                title: "AI Analysis Core",
                desc: "Discover how our smart AI core analyzes your quiz results to pinpoint weak subjects and generate customized blueprints for your academic improvement.",
                html: `
                    <div class="subpage-faq-section ai-section">
                        <div class="question-item">
                            <div class="question-header">
                                How does the AI Analysis identify my weak subjects?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">    
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Our AI tracks your quiz performance, accuracy rates, and time spent on different topics. By running this data through our analysis model, it instantly spots which specific chapters or concepts are holding you back.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                What kind of improvement suggestions will I receive?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">    
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Instead of just showing your mistakes, the AI generates a customized study plan. It recommends targeted review materials, provides tailored practice sets, and suggests daily focus routines to patch up your knowledge gaps efficiently.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                How often does my AI academic report update?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Your analysis data updates dynamically in real-time! Every time you complete a level, finish an active quiz, or master a skill path, the AI recalibrates your stats to show your latest progress trends.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                Is my quiz data and progress private?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Yes. Your performance data is only used by the AI to generate your personal study suggestions, and it is never shared with other users or third parties. See our Privacy Policy for full details.</p>
                            </div>
                        </div>
                    </div>
                `
            },
            report_bug: {
                title: "Telemetry & Technical Bug Reporting",
                desc: "Spotted an unexpected visual glitch or locked stage? Submit telemetry data straight to our dev team.",
                html: `
                    <div class="subpage-faq-section bug-section">
                        <div class="question-item">
                            <div class="question-header">
                                How long does it take for a reported bug to be fixed?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Our dev team reviews submissions daily. Visual bugs are typically patched within 48 hours, while major path progression blocks are prioritized immediately.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                How do I report a bug I found?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>If you spot something wrong — a broken page, a missing icon, or a quiz that will not load — click Contact Us at the bottom of any page, select "Bug Report" as the topic, and describe what happened.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                What information should I include in a bug report?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Tell us: what page you were on, what you were trying to do, what happened instead, and which browser you are using (Chrome, Firefox, etc). A screenshot is super helpful if you can attach one.</p>
                            </div>
                        </div>

                        <div class="question-item">
                            <div class="question-header">
                                How can I contact the implose.gg support team?
                                <span class="faq-arrow">
                                    <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">
                                </span>
                            </div>
                            <div class="question-content">
                                <p>Click "Contact Us" at the bottom of any page to send us a message. We usually reply within 1-2 business days. For urgent issues, please include screenshots and as much detail as possible.</p>
                            </div>
                        </div>
                    </div>
                `
            }
        };

        function renderSubpage(category) {
            if (category === 'main') {
                document.getElementById('main-view').style.display = 'block';
                document.getElementById('subpage-view').style.display = 'none';
            } else if (faqDatabase[category]) {
                document.getElementById('main-view').style.display = 'none';
                document.getElementById('subpage-view').style.display = 'block';
                
                document.getElementById('subpage-title').innerText = faqDatabase[category].title;
                document.getElementById('subpage-desc').innerText = faqDatabase[category].desc;

                const contentArea = document.getElementById('subpage-content-area');
                contentArea.innerHTML = faqDatabase[category].html || '';

                contentArea.querySelectorAll('.question-item').forEach(item => {
                    item.querySelector('.question-header').addEventListener('click', () => {
                        item.classList.toggle('active');
                    });
                });
            }
            window.scrollTo({
                top: 0,
                behavior: 'instant'
            });
        }

        document.querySelectorAll('.faq-card-block, .back-to-main-btn').forEach(element => {
            element.addEventListener('click', function(e) {
                e.preventDefault(); 
                
                let targetCategory = this.getAttribute('data-category');
                renderSubpage(targetCategory);
             
                history.pushState({ category: targetCategory }, '', 'faq.php?category=' + targetCategory);
            });
        });

        const urlParams = new URLSearchParams(window.location.search);
        const urlCategory = urlParams.get('category');
        if (urlCategory && urlCategory !== 'main') {
            renderSubpage(urlCategory);
        }

        window.addEventListener('popstate', function(event) {
            location.reload();
        });
    </script>

    <script>
        /* ===========================================================
           FAQ Search Filter
           - Builds one list of every question (main page + all
             category pages from faqDatabase).
           - On every keystroke, shows only matching questions in a
             search results area.
           - When the search box is empty, hides the results area
             and shows the original sections again.
           - If a search has no matches, shows a "Contact Us" prompt.
           =========================================================== */
        (function () {
            const searchInput     = document.getElementById('faq-search-input');
            const resultsBox      = document.getElementById('faq-search-results');
            const resultsList     = document.getElementById('faq-search-results-list');
            const noResultsBox    = document.getElementById('faq-no-results');

            // Sections we hide while the user is searching
            const famousSection   = document.querySelector('.faq-famous-question-section');
            const headingWrapper  = document.querySelector('.faq-section-heading-wrapper');
            const cardsSection    = document.querySelector('.faq-cards-section');
            const footerSection   = document.querySelector('.faq-footer-contact-section');

            if (!searchInput || !resultsBox || !resultsList) return;

            // 1. Build a flat list of every question on the page.
            const allQuestions = [];

            // 1a. Add the 4 questions already in the main page HTML.
            document.querySelectorAll('.faq-famous-question-section .question-item').forEach(function (item) {
                const header = item.querySelector('.question-header');
                const body   = item.querySelector('.question-content');
                if (header && body) {
                    allQuestions.push({
                        question: header.textContent.trim(),
                        answer:   body.textContent.trim(),
                        category: 'Popular'
                    });
                }
            });

            // 1b. Add every question from the faqDatabase JS object.
            if (typeof faqDatabase === 'object') {
                Object.keys(faqDatabase).forEach(function (catKey) {
                    const cat = faqDatabase[catKey];
                    if (!cat || !cat.html) return;

                    const temp = document.createElement('div');
                    temp.innerHTML = cat.html;

                    temp.querySelectorAll('.question-item').forEach(function (item) {
                        const header = item.querySelector('.question-header');
                        const body   = item.querySelector('.question-content');
                        if (header && body) {
                            allQuestions.push({
                                question: header.textContent.trim(),
                                answer:   body.textContent.trim(),
                                category: cat.title || catKey
                            });
                        }
                    });
                });
            }

            function escapeHtml(text) {
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // 2. Show original sections / hide results
            function showOriginalView() {
                resultsBox.style.display = 'none';
                resultsList.innerHTML    = '';
                if (famousSection)  famousSection.style.display  = '';
                if (headingWrapper) headingWrapper.style.display = '';
                if (cardsSection)   cardsSection.style.display   = '';
                if (footerSection)  footerSection.style.display  = '';
            }

            function showSearchView() {
                resultsBox.style.display = 'block';
                if (famousSection)  famousSection.style.display  = 'none';
                if (headingWrapper) headingWrapper.style.display = 'none';
                if (cardsSection)   cardsSection.style.display   = 'none';
                if (footerSection)  footerSection.style.display  = 'none';
            }

            // 3. Render matches
            function renderMatches(matches) {
                if (matches.length === 0) {
                    resultsList.innerHTML = '';
                    noResultsBox.style.display = 'block';
                    return;
                }

                noResultsBox.style.display = 'none';

                let html = '';
                matches.forEach(function (q, idx) {
                    html += ''
                        + '<div class="question-item faq-search-result-item" style="animation-delay: ' + (idx * 50) + 'ms;">'
                        +     '<div class="question-header">'
                        +         escapeHtml(q.question)
                        +         '<span class="faq-search-result-cat">' + escapeHtml(q.category) + '</span>'
                        +         '<span class="faq-arrow">'
                        +             '<img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Arrow Down">'
                        +         '</span>'
                        +     '</div>'
                        +     '<div class="question-content"><p>' + escapeHtml(q.answer) + '</p></div>'
                        + '</div>';
                });
                resultsList.innerHTML = html;

                // Make each result expandable just like the originals
                resultsList.querySelectorAll('.question-item').forEach(function (item) {
                    item.querySelector('.question-header').addEventListener('click', function () {
                        item.classList.toggle('active');
                    });
                });
            }

            // 4. Wire up the input
            searchInput.addEventListener('input', function () {
                const term = searchInput.value.trim().toLowerCase();

                if (term === '') {
                    showOriginalView();
                    return;
                }

                const matches = allQuestions.filter(function (q) {
                    return q.question.toLowerCase().includes(term)
                        || q.answer.toLowerCase().includes(term);
                });

                showSearchView();
                renderMatches(matches);
            });
        })();
    </script>

</body>
</html>