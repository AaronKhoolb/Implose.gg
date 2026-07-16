<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/user/update_profile.php
Description: update user profile action
First Written on: Sunday, 21-Jun-2026
Edited on: Monday, 22-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$user_id = $_SESSION['user_id'];

$username = $_POST['username'];
$age = $_POST['age'];
$gender = $_POST['gender'];

/* Get current avatar */
$current_avatar_sql = "SELECT avatar_path FROM USER_T WHERE user_id = '$user_id'";
$current_avatar_result = mysqli_query($conn, $current_avatar_sql);
$current_user = mysqli_fetch_assoc($current_avatar_result);

$avatar_path = $current_user['avatar_path'];

/* Use selected default avatar */
if (isset($_POST['avatar_choice'])) {
    $avatar_path = $_POST['avatar_choice'];
}

/* Use uploaded avatar */
if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] == 0) {
    $file = $_FILES['avatar_file'];

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $user_id . "." . $ext;

    $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/Implose.gg-src/uploads/avatars/";
    $target_path = $target_dir . $filename;

    move_uploaded_file($file['tmp_name'], $target_path);
    chmod($target_path, 0777);

    $avatar_path = "uploads/avatars/" . $filename;
}

/* Update profile */
$update_profile_sql = "UPDATE USER_T SET username = '$username', age = '$age', gender = '$gender', avatar_path = '$avatar_path', updated_at = NOW() WHERE user_id = '$user_id'";

mysqli_query($conn, $update_profile_sql);

add_system_log($conn, $user_id, "Update Profile", "User updated profile settings.");

header("Location: /Implose.gg-src/pages/user/account/index.php");
exit();

?>