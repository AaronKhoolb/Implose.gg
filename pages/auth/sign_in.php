<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/auth/sign_in.php
Description: login page
First Written on: Thursday, 18-May-2026
Edited on: Wednesday, 27-May-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');
        $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/Implose.gg-src/.env");
    ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/auth.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/welcome.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <title>Sign in</title>
</head>


<body>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/welcome.php'); ?>

    <div class="sign_in-bg">

        <div class="sign_in-container">

            <div class="sign_in-ui pixel-panel">
                
                <div class="sign_in-ui-header">
                    <span class="title pixel-title">Sign in</span>

                    <span class="subtitle">Sign in to continue your learning journey</span>
                </div>

                <div class="sign_in-ui-form">
                    <form action="/Implose.gg-src/actions/auth/sign_in.php" method="post">

                        <!-- Email -->
                        <div class="form-field">
                            <label for="email">Email</label>

                            <div class="txt-container">
                                <input type="text" name="email" id="email" placeholder="Enter email address" required>
                                <button type="button" class="clear-btn" data-target="email">
                                    <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="form-field">
                            <label for="password">Password</label>

                            <div class="txt-container">
                                <input type="password" name="password" id="password" placeholder="Enter password" required>
                                <button type="button" class="clear-btn" data-target="password">
                                    <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="message">

                            <?php
                                if (isset($_GET['message'])) {
                                    echo htmlspecialchars($_GET['message']);
                                }
                            ?>

                        </div>

                        

                        <!-- Cloudflare Turnstile Captcha -->
                        <div class="cf-turnstile-wrapper">
                            <div 
                                class="cf-turnstile"
                                data-sitekey="<?php echo $env['TURNSTILE_SITE_KEY']; ?>"
                                data-size="flexible"
                                data-theme="dark">
                            </div>
                        </div>

                        <!-- sign in button -->
                        <button type="submit" class="sign_in-btn btn-red" id="sign_in-btn">
                            Sign in
                        </button>

                    </form>

                </div>

            </div>

            <div class="sign_in-ui-footer">
                <!-- sign up -->
                <a href="/Implose.gg-src/pages/auth/sign_up.php" class="signup btn-pixel">
                    Sign up
                </a>

                <!-- forgot password -->
                <a href="/Implose.gg-src/pages/auth/forgot_password.php" class="forgot-password btn-pixel">
                    Forgot password?
                </a>
            </div>

        </div>

    </div>

</body>
</html>
