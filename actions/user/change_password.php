<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/user/change_password.php
Description: change user password action
First Written on: Monday, 22-Jun-2026
Edited on: Monday, 22-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$user_id = $_SESSION['user_id'];

$old_password = $_POST['old_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];


/* Check confirm password */
if ($new_password != $confirm_password) {
    $error = urlencode("New password and confirm password do not match.");
    header("Location: /Implose.gg-src/pages/user/account/account_security.php?error=$error");
    exit();
}

/* Get current password */
$user_sql = "SELECT password_hash FROM USER_T WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);
$user = mysqli_fetch_assoc($user_result);


/* Check old password */
if (!password_verify($old_password, $user['password_hash'])) {
    $error = urlencode("Old password is incorrect.");
    header("Location: /Implose.gg-src/pages/user/account/account_security.php?error=$error");
    exit();
}

/* Change password */
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

$update_password_sql = "UPDATE USER_T SET password_hash = '$new_password_hash', updated_at = NOW() WHERE user_id = '$user_id'";

mysqli_query($conn, $update_password_sql);

$message = urlencode("Password changed successfully. Please sign in again.");
header("Location: /Implose.gg-src/actions/auth/sign_out.php?message=$message");
exit();

?>