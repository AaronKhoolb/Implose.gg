<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/auth/reset_password.php
Description: reset password action
First Written on: Wednesday, 26-May-2026
Edited on: Tuesday, 9-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

if (!isset($_SESSION['reset_user_id'])) {
    $error = urlencode("Invalid session.");
    header("Location: /Implose.gg-src/pages/auth/forgot_password.php?error=$error");
    exit();
}

$user_id = $_SESSION['reset_user_id'];

$password = $_POST["password"];
$confirm_password = $_POST["confirm_password"];

if ($password != $confirm_password) {
    $error = urlencode("Passwords do not match.");
    header("Location: /Implose.gg-src/pages/auth/reset_password.php?error=$error");
    exit();
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$update_password_sql = "UPDATE USER_T SET password_hash = '$password_hash', updated_at = NOW() WHERE user_id = '$user_id'";

mysqli_query($conn, $update_password_sql);

add_system_log($conn, $user_id, "Password Reset", "User reset account password successfully.");


unset($_SESSION['reset_user_id']);

$message = urlencode("Password reset successfully. You can sign in now.");
header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
exit();

?>