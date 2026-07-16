<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/auth/profile_setup.php
Description: profile setup action (upload avatar, store info to users tbl)
First Written on: Monday, 25-May-2026
Edited on: Tuesday, 9-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');

$user_id = $_SESSION['user_id'];

$username = trim($_POST['username']);
$age = $_POST['age'];
$gender = $_POST['gender'];


$check_username_sql = "SELECT * FROM USER_T WHERE username = '$username' AND user_id != '$user_id'";
$check_username_result = mysqli_query($conn, $check_username_sql);

if (mysqli_num_rows($check_username_result) > 0) {
    $error = urlencode("Username already taken");

    header("location: /Implose.gg-src/pages/auth/complete_profile.php?error=" . $error);
    exit();
}

$avatar_path = "";

if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] == 0) {
    $file = $_FILES['avatar_file'];

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $user_id . "." . $ext;

    $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/Implose.gg-src/uploads/avatars/";
    $target_path = $target_dir . $filename;

    move_uploaded_file($file['tmp_name'], $target_path);
    chmod($target_path, 0777);
    
    $avatar_path = "uploads/avatars/" . $filename;
}else{
    $avatar_path = $_POST['avatar_choice'];
}

$update_user_sql = "UPDATE USER_T SET username = '$username', age = '$age', gender = '$gender', avatar_path = '$avatar_path' WHERE user_id = '$user_id'";

mysqli_query($conn, $update_user_sql);

add_system_log($conn, $user_id, "Profile Setup", "User completed profile setup.");

// ── Award the PROFILE_COMPLETE achievement (and any points milestone it triggers) ──
award_achievement($conn, $user_id, 'PROFILE_COMPLETE');
check_points_milestones($conn, $user_id);

header("location: /Implose.gg-src/pages/user/index.php");
exit();

?>