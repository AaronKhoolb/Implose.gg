<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /includes/loader.php
Description: loading screen that will be included in head.php so that affected on all pages
First Written on: Sunday, 27-May-2026
Edited on: Sunday, 31-May-2026
-->

<link rel="stylesheet" href="/Implose.gg-src/assets/css/components/loader.css">

<div id="loader">
    <img class="loader" src="/Implose.gg-src/assets/images/loader/loader.webp" alt="Loading...">
</div>

<script>
    window.addEventListener("load", function() {
        setTimeout(function() {
            document.getElementById("loader").classList.add("hide");
        }, 1000);
    });
</script>
