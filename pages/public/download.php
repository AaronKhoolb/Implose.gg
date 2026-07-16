<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/public/download.php
Description: Install PWA guide
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/download.css">

    <title>Install Implose.gg</title>
</head>


<body class="download-body">

    <!-- Back -->
    <a href="/Implose.gg-src/index.php" class="btn-pixel download-back-btn" aria-label="Back">
        <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="close">
    </a>


    <main class="download-shell">

        <header class="download-header">
            <h1 class="pixel-title">Install Implose.gg</h1>
            <p class="download-subtitle">Add Implose.gg to your home screen for a native-app feel. Follow the guide for your device.</p>
        </header>


        <section class="download-grid">

            <!-- Android + Chrome -->
            <article class="download-card pixel-panel">
                <div class="download-card-head">
                    <span class="platform-title pixel-title">Android</span>
                    <span class="browser-badge btn-pixel btn-pixel-yellow">Requires Chrome</span>
                </div>

                <ol class="download-steps">
                    <li>Open this site in <b>Google Chrome</b>.</li>
                    <li>Tap the <b>⋮</b> menu at the top right.</li>
                    <li>Choose <b>Install app</b> (or <b>Add to Home screen</b>).</li>
                    <li>Tap <b>Install</b> to confirm.</li>
                </ol>
            </article>


            <!-- Mac + Safari -->
            <article class="download-card pixel-panel">
                <div class="download-card-head">
                    <span class="platform-title pixel-title">Mac</span>
                    <span class="browser-badge btn-pixel btn-pixel-yellow">Requires Safari</span>
                </div>

                <ol class="download-steps">
                    <li>Open this site in <b>Safari</b>.</li>
                    <li>Click <b>File</b> in the top menu bar.</li>
                    <li>Choose <b>Add to Dock…</b></li>
                    <li>Click <b>Add</b> to confirm.</li>
                </ol>
            </article>

        </section>

    </main>

</body>
</html>
