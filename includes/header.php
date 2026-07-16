<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /includes/header.php
Description: will be included in all pages
            - scale for mobile
            - PWA
            - session check, db connection, otp, logs, loader
            - meta tags (charset, viewport, ico, SEO, OG)
            - link all global css, font, js script
First Written on: Thursday, 18-May-2026
Edited on: Sunday, 05-Jul-2026
-->

<?php 
    include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/auth_check.php');
?>

<meta charset="UTF-8">


<!-- viewport: zoom out on mobile -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
    if (screen.width <= 1000) {
        document.querySelector('meta[name="viewport"]').setAttribute('content', 'initial-scale=0.5');
    }
</script>

<!-- PWA -->
<link rel="manifest" href="/Implose.gg-src/manifest.json">
<meta name="theme-color" content="#000000">


<!-- SEO / OG -->
<?php
    $default_title = 'Implose.gg';
    $default_description = 'Implose.gg - A gamified e-learning platform. Join courses, earn coins, keep your streak, and level up your knowledge.';
    $og_image_url = 'https://' . $_SERVER['HTTP_HOST'] . '/Implose.gg-src/assets/images/logo/icon-512.png';

    $page_title = isset($og_title) ? $og_title : $default_title;
    $page_description = isset($og_description) ? $og_description : $default_description;
?>

<title><?php echo htmlspecialchars($page_title); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
<meta name="author" content="Khoo Lay Bin, Chong Ray Han, Chong Jun Yoong, Ng Jiun Chyn, Damian Loh Yi Feng">
<meta name="copyright" content="© 2026 Implose.gg">

<meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
<meta property="og:image" content="<?php echo $og_image_url; ?>">
<meta property="og:type" content="website">

<link rel="icon" href="/Implose.gg-src/assets/images/logo/logo.png">


<!-- Global PHP includes -->
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php'); ?>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php'); ?>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/send_otp_email.php'); ?>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/loader.php'); ?>


<!-- Global JavaScript -->
<script src="/Implose.gg-src/assets/js/global/clear_input.js"></script>
<script src="/Implose.gg-src/assets/js/global/share.js" defer></script>


<!-- Global CSS -->
<link rel="stylesheet" href="/Implose.gg-src/assets/css/global/fonts.css">

<?php
$path = $_SERVER['SCRIPT_FILENAME'];

if (str_contains($path, '/pages/admin/') || str_contains($path, '/pages/moderator/')) {
    echo '<link rel="stylesheet" href="/Implose.gg-src/assets/css/global/panel/input.css">';
    echo '<link rel="stylesheet" href="/Implose.gg-src/assets/css/global/panel/global.css">';
    echo '<link rel="stylesheet" href="/Implose.gg-src/assets/css/global/panel/clear_input.css">';
} else {
    echo '<link rel="stylesheet" href="/Implose.gg-src/assets/css/global/user/input.css">';
    echo '<link rel="stylesheet" href="/Implose.gg-src/assets/css/global/user/global.css">';
    echo '<link rel="stylesheet" href="/Implose.gg-src/assets/css/global/user/clear_input.css">';
}
?>
