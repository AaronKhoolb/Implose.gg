<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/account/account_security.php
Description: user account center - security (change pswd) page
First Written on: Friday, 19-Jun-2026
Edited on: Friday, 19-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');
    ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/complete_profile.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_account.css">
    <title>Account Center - Security</title>
</head>


<body>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">

        <!-- Top -->
        <div class="account-top">
            <span class="account-title pixel-title">Account Center</span>
            <span class="account-description">Manage your account settings</span>

            <hr>
        </div>

        <!-- Body -->
        <!-- Left nav -->
        <div class="account-body">
            <?php
                $current_page = 'account_security';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/account/nav.php');
            ?>


            <!-- Right content -->
            <div class="account-right">
                <div class="profile_setup-container acc-security-container pixel-panel">

                <div class="profile_setup-header">
                    <span class="title pixel-title">Account Security</span>

                    <span class="subtitle">Change your password</span>
                </div>

                <form action="/Implose.gg-src/actions/user/change_password.php" method="post" class="account-security-form">
                    
                    <!-- Old Password -->
                    <div class="form-field old_password">
                        <label for="old_password">Old Password</label>

                        <div class="txt-container">
                            <input type="password" name="old_password" id="old_password" placeholder="Enter your old password" required>
                            <button type="button" class="clear-btn" data-target="old_password">
                                <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="pswd-container">

                        <!-- New Password -->
                        <div class="form-field">
                            <label for="new_password">New Password</label>

                            <div class="txt-container">
                                <input type="password" name="new_password" id="new_password" placeholder="Enter your new password" required>
                                <button type="button" class="clear-btn" data-target="new_password">
                                    <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-field">
                            <label for="confirm_password">Confirm Password</label>

                            <div class="txt-container">
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="Enter your confirm password" required>
                                <button type="button" class="clear-btn" data-target="confirm_password">
                                    <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                            </div>
                        </div>
                    </div>

                    <div class="error_msg-container">
                        <?php
                            if (isset($_GET['error'])) {
                                echo htmlspecialchars($_GET['error']);
                            }
                        ?>
                    </div>

                    <button type="submit" class="submit_profile-btn btn-red">Save</button>

                </form>

            </div>
            </div>
        </div>

    </div>
</body>