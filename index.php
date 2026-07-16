<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /index.php
Description: Root landing page
First Written on: Thursday, 21-May-2026
Edited on: Sunday, 05-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/welcome.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/index.css">

    <title>Implose.gg</title>
</head>


<body>
    <!-- bg -->
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/welcome.php'); ?>


    <!-- Fullscreen tap-to-start button -->
    <a class="start-overlay" href="/Implose.gg-src/pages/auth/sign_in.php">
        <span class="start-hint pixel-title">Tap to start</span>
    </a>


    <!-- Bottom-left corner -->
    <div class="index-corner-actions">

        <!-- Info button -->
        <a href="/Implose.gg-src/pages/public/index.php" class="btn-pixel btn-pixel-yellow index-corner-btn index-info-btn" aria-label="About Implose.gg">
            <img src="/Implose.gg-src/assets/images/icons/infor.circle.solid.svg" alt="info">
        </a>

        <!-- Account centre -->
        <?php
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'user') {
        ?>
            <a href="/Implose.gg-src/pages/user/account/index.php" class="btn-pixel index-corner-btn index-account-btn" aria-label="Account Centre">
                <img src="/Implose.gg-src/assets/images/icons/users.svg" alt="account">
            </a>
        <?php
            }
        ?>

    </div>


    <!-- Bottom-right corner -->
    <div class="index-corner-actions index-corner-actions-right">

        <!-- Download button -->
        <a href="/Implose.gg-src/pages/public/download.php" class="btn-pixel index-corner-btn index-download-btn" aria-label="Download">
            <img src="/Implose.gg-src/assets/images/icons/download.svg" alt="download">
        </a>

    </div>

</body>
</html>
