<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/account/index.php
Description: user account center - profile settings (edit profile) page
First Written on: Friday, 19-Jun-2026
Edited on: Friday, 21-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');
    ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/complete_profile.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_account.css">
    <title>Account Center - Profile Settings</title>
</head>


<body>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');

        // Fetch user profile data
        $user_id = $_SESSION['user_id'];

        $user_sql = "SELECT username, email_address, age, gender, avatar_path FROM USER_T WHERE user_id = '$user_id'";
        $user_result = mysqli_query($conn, $user_sql);
        $user = mysqli_fetch_assoc($user_result);

        $user_avatar = '/Implose.gg-src/' . $user['avatar_path'];
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
                $current_page = 'profile_settings';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/account/nav.php');
            ?>


            <!-- Right content -->
            <div class="account-right">
                <div class="profile_setup-container pixel-panel">

                <div class="profile_setup-header">
                    <span class="title pixel-title">Profile Settings</span>

                    <span class="subtitle">Manage your profile settings</span>
                </div>

                <form action="/Implose.gg-src/actions/user/update_profile.php" method="post" enctype="multipart/form-data" class="profile_setup-form">

                    <div class="profile_avatar-container pixel-panel">
                        <span class="avatar-title">Choose Your Avatar</span>

                        <div class="avatar-preview pixel-panel">
                            <img id="avatar_preview" src="<?php echo $user_avatar; ?>" alt="avatar_robot">
                        </div>

                        <div class="avatar-choice">
                            <label class="avatar_file pixel-panel <?php if (strpos($user['avatar_path'], 'assets/images/avatar_test/') !== 0) { echo 'active'; } ?>" for="avatar_file">
                                <input type="file" name="avatar_file" id="avatar_file" accept="image/*">

                                <img src="/Implose.gg-src/assets/images/icons/upload.svg" alt="upload.svg">
                            </label>

                            <div class="default-avatar-list">
                                <label class="avatar-option pixel-panel">
                                    <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_robot.png" <?php if ($user['avatar_path'] == 'assets/images/avatar_test/avatar_robot.png') { echo 'checked'; } ?>>
                                    <div class="small-avatar">
                                        <img src="/Implose.gg-src/assets/images/avatar_test/avatar_robot.png" alt="Robot">
                                    </div>
                                </label>

                                <label class="avatar-option pixel-panel">
                                    <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_red_hair.png" <?php if ($user['avatar_path'] == 'assets/images/avatar_test/avatar_red_hair.png') { echo 'checked'; } ?>>
                                    <div class="small-avatar">
                                        <img src="/Implose.gg-src/assets/images/avatar_test/avatar_red_hair.png" alt="Red Hair">
                                    </div>
                                </label>

                                <label class="avatar-option pixel-panel">
                                    <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_green_monster.png" <?php if ($user['avatar_path'] == 'assets/images/avatar_test/avatar_green_monster.png') { echo 'checked'; } ?>>
                                    <div class="small-avatar">
                                        <img src="/Implose.gg-src/assets/images/avatar_test/avatar_green_monster.png" alt="Green Monster">
                                    </div>
                                </label>

                                <label class="avatar-option pixel-panel">
                                    <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_blue_helmet.png" <?php if ($user['avatar_path'] == 'assets/images/avatar_test/avatar_blue_helmet.png') { echo 'checked'; } ?>>
                                    <div class="small-avatar">
                                        <img src="/Implose.gg-src/assets/images/avatar_test/avatar_blue_helmet.png" alt="Blue Helmet">
                                    </div>
                                </label>

                                <label class="avatar-option pixel-panel">
                                    <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_ninja.png" <?php if ($user['avatar_path'] == 'assets/images/avatar_test/avatar_ninja.png') { echo 'checked'; } ?>>
                                    <div class="small-avatar">
                                        <img src="/Implose.gg-src/assets/images/avatar_test/avatar_ninja.png" alt="Ninja">
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <script src="/Implose.gg-src/assets/js/global/choose_avatar.js"></script>

                    <div class="profile_info-container">

                        <div class="email-container">
                            <div class="form-field">
                                <label for="email">Email</label>

                                <div class="txt-container readonly-container">
                                    <input type="email" name="email" id="email" value="<?php echo $user['email_address']; ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="username-container">
                            <div class="form-field">
                                <label for="username">Username</label>

                                <div class="txt-container">
                                    <input type="text" name="username" id="username" placeholder="Enter your username" value="<?php echo $user['username']; ?>" required>
                                    <button type="button" class="clear-btn" data-target="username">
                                        <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                                </div>
                            </div>
                        </div>

                        <div class="details-container">

                            <div class="age-container form-field">
                                <label for="age">Age</label>

                                <div class="txt-container">
                                    <input type="number" name="age" id="age" placeholder="Enter your age" value="<?php echo $user['age']; ?>" required>
                                    <button type="button" class="clear-btn" data-target="age">
                                        <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                                </div>
                            </div>

                            <div class="gender-container">
                                <label class="gender-title">Gender</label>

                                <div class="gender-radio-btn">
                                    <label class="pixel-panel" for="male"><input type="radio" name="gender" id="male" value="male" <?php if ($user['gender'] == 'male') { echo 'checked'; } ?> required><img src="/Implose.gg-src/assets/images/icons/male.svg" alt="male.svg"><span>Male</span></label>

                                    <label class="pixel-panel" for="female"><input type="radio" name="gender" id="female" value="female" <?php if ($user['gender'] == 'female') { echo 'checked'; } ?> required><img src="/Implose.gg-src/assets/images/icons/female.svg" alt="female.svg"><span>Female</span></label>
                                </div>
                            </div>

                        </div>

                        <button type="submit" class="submit_profile-btn btn-red">Save Profile</button>

                    </div>

                </form>

            </div>
            </div>
        </div>

    </div>
</body>
