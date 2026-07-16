<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/pdf_viewer.php
Description: PDF viewer using Mozilla pdf.js
            ?file=<path>  - PDF path
            ?back=<url>   - back link URL
First Written on: Monday, 29-Jun-2026
Edited on: Monday, 29-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

        $file = $_GET['file'];
        $back = $_GET['back'];

        $viewer_url = "/Implose.gg-src/assets/lib/pdfjs/web/viewer.html?file=/Implose.gg-src/$file";
    ?>

    <title>PDF Viewer</title>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/pdf_viewer.css">
</head>


<body>
    <div class="main-content">

        <a class="pdf-back" href="<?php echo $back; ?>">
            <img src="/Implose.gg-src/assets/images/icons/chevron-down.svg" alt="back">
            Back
        </a>

        <iframe class="pdf-frame" src="<?php echo $viewer_url; ?>"></iframe>

    </div>
</body>
</html>
