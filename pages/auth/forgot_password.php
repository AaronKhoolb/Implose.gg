<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/auth/forgot_password.php
Description: password recovery
First Written on: Tuesday, 26-May-2026
Edited on: Wednesday, 27-May-2026
-->


<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/auth.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/welcome.css">
    <title>Forgot Password</title>
</head>


<body>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/welcome.php'); ?>

    <div class="sign_in-bg">

        <div class="sign_in-container">

                <div class="forgot_password-ui pixel-panel">
                    
                    <div class="sign_in-ui-header">
                        <span class="title pixel-title">Forgot Password</span>

                        <span class="subtitle">No worries! Enter your email address and we'll send you an OTP code to reset your password.</span>
                    </div>

                    <div class="sign_in-ui-form">
                        <form action="/Implose.gg-src/actions/auth/forgot_password.php" method="post">

                            <!-- Email -->
                            <div class="form-field">
                                <label for="email">Email</label>

                                <div class="txt-container">
                                    <input type="text" name="email" id="email" placeholder="Enter email address" required>
                                    <button type="button" class="clear-btn" data-target="email">
                                        <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
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
                                Send OTP Code
                            </button>

                        </form>

                    </div>
                    
                </div>

                <!-- sign in btn -->
                <a class="signin-btn btn-pixel" href="/Implose.gg-src/pages/auth/sign_in.php">
                    Sign in
                </a>

        </div>

    </div>

</body>
</html>