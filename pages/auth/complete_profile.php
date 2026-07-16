<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/auth/complete_profile.php
Description: complete user profile setup page
First Written on: Sunday, 18-May-2026
Edited on: Wednesday, 21-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/complete_profile.css">
    <title>Profile Setup</title>
</head>


<body>
    <div class="profile_setup-container pixel-panel">

        <div class="profile_setup-header">
            <span class="title pixel-title">Profile Setup</span>

            <span class="subtitle">Set up your profile to enter the learning area</span>
        </div>

        <form action="/Implose.gg-src/actions/auth/profile_setup.php" method="post" enctype="multipart/form-data" class="profile_setup-form">

            <div class="profile_avatar-container pixel-panel">
                <span class="avatar-title">Choose Your Avatar</span>

                <div class="avatar-preview pixel-panel">
                    <img id="avatar_preview" src="/Implose.gg-src/assets/images/avatar_test/avatar_robot.png" alt="avatar_robot">
                </div>

                <div class="avatar-choice">
                    <label class="avatar_file pixel-panel" for="avatar_file">
                        <input type="file" name="avatar_file" id="avatar_file" accept="image/*">

                        <img src="/Implose.gg-src/assets/images/icons/upload.svg" alt="upload.svg">
                    </label>

                    <div class="default-avatar-list">
                        <label class="avatar-option pixel-panel">
                            <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_robot.png" checked>
                            <div class="small-avatar">
                                <img src="/Implose.gg-src/assets/images/avatar_test/avatar_robot.png" alt="Robot">
                            </div>
                        </label>

                        <label class="avatar-option pixel-panel">
                            <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_red_hair.png">
                            <div class="small-avatar">
                                <img src="/Implose.gg-src/assets/images/avatar_test/avatar_red_hair.png" alt="Red Hair">
                            </div>
                        </label>

                        <label class="avatar-option pixel-panel">
                            <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_green_monster.png">
                            <div class="small-avatar">
                                <img src="/Implose.gg-src/assets/images/avatar_test/avatar_green_monster.png" alt="Green Monster">
                            </div>
                        </label>

                        <label class="avatar-option pixel-panel">
                            <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_blue_helmet.png">
                            <div class="small-avatar">
                                <img src="/Implose.gg-src/assets/images/avatar_test/avatar_blue_helmet.png" alt="Blue Helmet">
                            </div>
                        </label>

                        <label class="avatar-option pixel-panel">
                            <input type="radio" name="avatar_choice" value="assets/images/avatar_test/avatar_ninja.png">
                            <div class="small-avatar">
                                <img src="/Implose.gg-src/assets/images/avatar_test/avatar_ninja.png" alt="Ninja">
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <script src="/Implose.gg-src/assets/js/global/choose_avatar.js"></script>

            <div class="profile_info-container">

                <div class="username-container">
                    <div class="form-field">
                        <label for="username">Username</label>

                        <div class="txt-container">
                            <input type="text" name="username" id="username" placeholder="Enter your username" required>
                            <button type="button" class="clear-btn" data-target="username">
                                <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                        </div>
                    </div>
                </div>

                <div class="details-container">

                    <div class="age-container form-field">
                        <label for="age">Age</label>

                        <div class="txt-container">
                            <input type="number" name="age" id="age" placeholder="Enter your age" required>
                            <button type="button" class="clear-btn" data-target="age">
                                <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button>
                        </div>
                    </div>

                    <div class="gender-container">
                        <label class="gender-title">Gender</label>

                        <div class="gender-radio-btn">
                            <label class="pixel-panel" for="male"><input type="radio" name="gender" id="male" value="male" required><img src="/Implose.gg-src/assets/images/icons/male.svg" alt="male.svg"><span>Male</span></label>

                            <label class="pixel-panel" for="female"><input type="radio" name="gender" id="female" value="female" required><img src="/Implose.gg-src/assets/images/icons/female.svg" alt="female.svg"><span>Female</span></label>
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

                <button type="submit" class="submit_profile-btn btn-red">Finish</button>

            </div>

        </form>

    </div>
</body>
</html>
