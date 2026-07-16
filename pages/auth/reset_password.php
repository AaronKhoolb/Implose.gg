<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/auth/reset_password.php
Description: reset password page
First Written on: Wednesday, 26-May-2026
Edited on: Wednesday, 27-May-2026
-->

<?php
session_start();

if (!isset($_SESSION['reset_user_id'])) {
    header("Location: /Implose.gg-src/pages/auth/forgot_password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/auth.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/welcome.css">
    <title>Reset Password</title>
</head>


<body>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/welcome.php'); ?>

    <div class="sign_in-bg">

        <div class="sign_in-container">

            <div class="reset_password-ui pixel-panel">
                
                <div class="sign_in-ui-header">
                    <span class="title pixel-title">Reset Your Password</span>

                    <span class="subtitle">Enter your new password below. <br> Make sure it's strong and secure.</span>
                </div>

                <div class="sign_in-ui-form">
                    <form action="/Implose.gg-src/actions/auth/reset_password.php" method="post">

                        <!-- Password -->
                        <div class="form-field">
                            <label for="password">New Password</label>

                            <div class="txt-container">
                                <input type="password" name="password" id="password" placeholder="Enter new password" required>
                                <button type="button" class="clear-btn" data-target="password">
                                    <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-field">
                            <label for="confirm_password">Confirm Password</label>

                            <div class="txt-container">
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
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
                            Reset Password
                        </button>

                    </form>

                </div>
                
            </div>

        </div>

    </div>

</body>
</html>
