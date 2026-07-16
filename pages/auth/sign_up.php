<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/auth/sign_up.php
Description: sign up page
First Written on: Thursday, 18-May-2026
Edited on: Wednesday, 27-May-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/auth.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/welcome.css">
    <title>Sign up</title>
</head>


<body>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/welcome.php'); ?>

    <div class="sign_in-bg">

        <div class="sign_in-container">

            <div class="sign_up-ui pixel-panel">
                
                <div class="sign_in-ui-header">
                    <span class="title pixel-title">Sign up</span>

                    <span class="subtitle">Create a New Account</span>
                </div>

                <div class="sign_in-ui-form">
                    <form action="/Implose.gg-src/actions/auth/sign_up.php" method="post">

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

                        <!-- Confirm Password -->
                        <div class="form-field">
                            <label for="confirm_password">Confirm Password</label>

                            <div class="txt-container">
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm password" required>
                                <button type="button" class="clear-btn" data-target="confirm_password">
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

                        <!-- sign in button -->
                        <button type="submit" class="sign_in-btn btn-red">
                            Sign Up
                        </button>

                    </form>

                </div>
                
            </div>

            <!-- sign in -->
            <a class="signin-btn btn-pixel" href="/Implose.gg-src/pages/auth/sign_in.php">
                Sign in
            </a>


        </div>

    </div>

</body>
</html>
