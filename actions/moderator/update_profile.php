<!--
Programmer Name: Mr. Damian Loh Yi Feng
Program Name: /actions/moderator/update_profile.php
Description: Update the signed-in moderator's own profile
            - Handles username, age, gender
            - Handles avatar file upload (falls back to existing avatar)
            - Logs the update to SYSTEM_LOG_T
First Written on: Wednesday, 01-Jul-2026
Edited on: Wednesday, 01-Jul-2026
-->
<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$moderator_id = $_SESSION['user_id'] ?? 0;

if ($moderator_id <= 0) {
    header("Location: /Implose.gg-src/pages/auth/sign_in.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$age      = (int)($_POST['age'] ?? 0);
$gender   = $_POST['gender'] ?? 'male';

/* Get current avatar so we can keep it if no new file is uploaded */
$current_sql    = "SELECT avatar_path FROM USER_T WHERE user_id = '$moderator_id'";
$current_result = mysqli_query($conn, $current_sql);
$current        = mysqli_fetch_assoc($current_result);

$avatar_path = $current['avatar_path'] ?? '';

/* Use uploaded avatar if provided */
if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === 0) {
    $file = $_FILES['avatar_file'];

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $moderator_id . "." . $ext;

    $target_dir  = $_SERVER['DOCUMENT_ROOT'] . "/Implose.gg-src/uploads/avatars/";
    $target_path = $target_dir . $filename;

    move_uploaded_file($file['tmp_name'], $target_path);
    chmod($target_path, 0777);

    $avatar_path = "uploads/avatars/" . $filename;
}

/* Update profile */
$update_sql = "UPDATE USER_T
               SET username = '$username',
                   age = '$age',
                   gender = '$gender',
                   avatar_path = '$avatar_path',
                   updated_at = NOW()
               WHERE user_id = '$moderator_id'";

mysqli_query($conn, $update_sql);

add_system_log($conn, $moderator_id, "Update Profile", "Moderator updated own profile settings.");

header("Location: /Implose.gg-src/pages/moderator/edit_profile.php");
exit();

?>
