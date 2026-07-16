<?php
/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /includes/db.php
Description: db connection credentials
First Written on: Thursday, 18-May-2026
Edited on: Wednesday, 27-May-2026
*/

    mysqli_report(MYSQLI_REPORT_OFF);

    $env = parse_ini_file(__DIR__ . '/../.env');

    $db_host = $env["DB_HOST"];
    $db_port = $env["DB_PORT"];
    $db_name = $env["DB_NAME"];
    $db_username = $env["DB_USERNAME"];
    $db_password = $env["DB_PASSWORD"];


    $conn = mysqli_connect($db_host, $db_username, $db_password, $db_name, $db_port);

    if (!$conn) {
?>
        <link rel="stylesheet" href="/Implose.gg-src/assets/css/global/fonts.css">
        <link rel="stylesheet" href="/Implose.gg-src/assets/css/global/user/global.css">
        <link rel="stylesheet" href="/Implose.gg-src/assets/css/components/db_error.css">

        <div class="error-overlay">

            <div class="error-header-nav logo">
                <img width="50px" src="/Implose.gg-src/assets/images/logo/logo.png" alt="logo.png">
                <span>Implose.gg</span>
            </div>

            <div class="error-container">

                <div class="error-image">
                    <img src="/Implose.gg-src/assets/images/ui/db/db_err.png" alt="db_err.png">

                    <span class="error-title pixel-title">Database Error</span>

                    <span class="error-description">Ooops! Something went wrong while connecting to our database...</span>
                </div>


                <div class="error-details pixel-panel">

                    <div class="error-details-icon">
                        <img src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="info.circle.svg">
                        <span class="error-code">Error code:</span>
                    </div>

                    <hr>
                
                    <div class="error-details-message">
                        <span class="error-message">


                            <?php
                                echo mysqli_connect_error();
                            ?>


                        </span>
                    </div>
                </div>

            </div>

            <a href="/Implose.gg-src/index.php" class="btn-red">
                <span>
                    <img src="/Implose.gg-src/assets/images/icons/arrow.clockwise.svg" alt="">
                </span>

                <span>Retry</span>
            </a>

        </div>
         
        
<?php
        exit;
    }

    echo "<script>console.log('Database connection successful');</script>";
?>
