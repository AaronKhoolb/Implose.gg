<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/auth/verify_otp.php
Description: verify otp for login, pswd reset, registration
First Written on: Thursday, 18-May-2026
Edited on: Tuesday, 26-May-2026
-->


<?php

    session_start();

    if (!isset($_SESSION['verify_user_id']) || !isset($_SESSION['verify_purpose']) || !isset($_SESSION['otp_email']) || !isset($_SESSION['otp_expires_at']) || !isset($_SESSION['resend_available_at'])) {
        header("Location: /Implose.gg-src/pages/auth/sign_in.php");
        exit();
    }

    $otp_email = $_SESSION['otp_email'];
    $verify_purpose = $_SESSION['verify_purpose'];
    $otp_expires_at = $_SESSION['otp_expires_at'];
    $resend_available_at = $_SESSION['resend_available_at'];

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/auth.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/welcome.css">
    <title>Verify OTP</title>
</head>


<body>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/welcome.php'); ?>

    <div class="sign_in-bg">

        <div class="sign_in-container">

                <div class="verify-otp-ui pixel-panel">
                    
                    <div class="sign_in-ui-header">
                        <span class="verify-otp-title pixel-title">
                            <?php
                                if ($verify_purpose == 'register') {
                                    echo "Complete Your Registration";
                                } else if ($verify_purpose == 'reset_password') {
                                    echo "Reset Your Password";
                                } else if ($verify_purpose == 'login') {
                                    echo "Secure Login Verification";
                                }
                            ?>
                        </span>

                        <span class="subtitle">
                            <?php
                                if ($verify_purpose == 'register') {
                            ?>
                                    We've sent a 6-digit verification code to
                                    <br>
                                    <span class='email-text'>
                                        <?php echo htmlspecialchars($otp_email); ?>
                                    </span>
                                    <br>
                                    Please enter it below to complete your registration.
                            <?php
                                } else if ($verify_purpose == 'reset_password') {
                            ?>
                                    We've sent a 6-digit verification code to
                                    <br>
                                    <span class='email-text'>
                                        <?php echo htmlspecialchars($otp_email); ?>
                                    </span>
                                    <br>
                                    Enter the verification code to continue resetting your password.
                            <?php
                                } else if ($verify_purpose == 'login') {
                            ?>
                                    We've sent a 6-digit verification code to
                                    <br>
                                    <span class='email-text'>
                                        <?php echo htmlspecialchars($otp_email); ?>
                                    </span>
                                    <br>
                                    Enter the verification code to continue logging in.
                            <?php
                                }
                            ?>
                        </span>
                    </div>

                    <div class="sign_in-ui-form">
                        <form action="/Implose.gg-src/actions/auth/verify_otp.php" method="post">

                            <!-- OTP CODE -->
                            <div class="form-field otp-container">
                                <label for="otp_code">Verification Code</label>

                                <div class="txt-container">
                                    <input type="text" name="otp_code" id="otp_code" placeholder="Enter verification code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
                                </div>
                            </div>

                            <!-- Error -->
                            <?php
                                if (isset($_GET['error'])) {
                            ?>

                                    <div class="message">
                                        
                                        <?php
                                            echo htmlspecialchars($_GET['error']);
                                        ?>

                                    </div>

                            <?php
                                }
                            ?>

                            <!-- submit button -->
                            <button type="submit" class="sign_in-btn btn-red">
                                Verify Code
                            </button>

                        </form>

                    </div>
                    
                </div>

                <!-- resend otp -->
                <div class="resend-otp-ui pixel-panel">
                    <span class="timer">Resend available: <span id="timer"></span></span>

                    <a id="resend-btn" class="resend-btn disabled" href="/Implose.gg-src/actions/auth/resend_otp.php">
                        Resend code
                    </a>
                </div>


                <script>
                    let secondsLeft = <?php echo max(0, $_SESSION['resend_available_at'] - time()); ?>;
                </script>

                <script src="/Implose.gg-src/assets/js/auth/verify_otp.js"></script>

        </div>

    </div>

</body>
</html>