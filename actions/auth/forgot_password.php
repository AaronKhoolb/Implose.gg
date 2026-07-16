<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/auth/forgot_password.php
Description: forgot password action
First Written on: Wednesday, 26-May-2026
Edited on: Tuesday, 9-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$email = trim($_POST["email"]);

$check_user_sql = "SELECT * FROM USER_T WHERE email_address = '$email'";
$check_user_result = mysqli_query($conn, $check_user_sql);

if (mysqli_num_rows($check_user_result) != 1) {
    $error = urlencode("Email address not found.");
    header("Location: /Implose.gg-src/pages/auth/forgot_password.php?error=$error");
    exit();
}

$user = mysqli_fetch_assoc($check_user_result);

if ($user['account_status'] == 'suspended') {
    $error = urlencode("Account suspended. Please contact administrator.");
    header("Location: /Implose.gg-src/pages/auth/forgot_password.php?error=$error");
    exit();
}

if ($user['account_status'] == 'banned') {
    $error = urlencode("Account banned. Please contact administrator.");
    header("Location: /Implose.gg-src/pages/auth/forgot_password.php?error=$error");
    exit();
}

if ($user['account_status'] == 'pending') {
    $error = urlencode("Please verify your account first.");
    header("Location: /Implose.gg-src/pages/auth/forgot_password.php?error=$error");
    exit();
}

$user_id = $user['user_id'];
$purpose = 'reset_password';

mysqli_query($conn, "UPDATE OTP_RECORD_T SET is_used = '1' WHERE user_id = '$user_id' AND purpose = '$purpose' AND is_used = '0'");

$otp_code = rand(100000, 999999);

$insert_otp_sql = "INSERT INTO OTP_RECORD_T (user_id, otp_code, purpose, expires_at, is_used, created_at)
                    VALUES ('$user_id', '$otp_code', '$purpose', DATE_ADD(NOW(), INTERVAL 10 MINUTE), '0', NOW())";

mysqli_query($conn, $insert_otp_sql);

send_otp_email($email, $otp_code);

$_SESSION['verify_user_id'] = $user_id;
$_SESSION['verify_purpose'] = $purpose;
$_SESSION['otp_email'] = $email;
$_SESSION['otp_expires_at'] = time() + 600;
$_SESSION['resend_available_at'] = time() + 60;

header("Location: /Implose.gg-src/pages/auth/verify_otp.php");
exit();

?>