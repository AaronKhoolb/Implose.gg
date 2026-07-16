<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/auth/resend_otp.php
Description: resend otp action
First Written on: Thursday, 18-May-2026
Edited on: Tuesday, 9-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');


if (!isset($_SESSION['verify_user_id']) || !isset($_SESSION['verify_purpose']) || !isset($_SESSION['otp_email'])) {
    header("location: /Implose.gg-src/pages/auth/sign_in.php");
    exit();
}

$user_id = $_SESSION['verify_user_id'];
$purpose = $_SESSION['verify_purpose'];

if (isset($_SESSION['resend_available_at']) && time() < $_SESSION['resend_available_at']) {
    header("location: /Implose.gg-src/pages/auth/verify_otp.php");
    exit();
}


mysqli_query($conn, "UPDATE OTP_RECORD_T SET is_used = '1' WHERE user_id = '$user_id' AND purpose = '$purpose' AND is_used = '0'");

// Generate otp
$otp_code = rand(100000, 999999);

$insert_otp_sql = "INSERT INTO OTP_RECORD_T (user_id, otp_code, purpose, expires_at, is_used, created_at) VALUES ('$user_id', '$otp_code', '$purpose', DATE_ADD(NOW(), INTERVAL 10 MINUTE), '0', NOW())";

mysqli_query($conn, $insert_otp_sql);

$email = $_SESSION['otp_email'];
send_otp_email($email, $otp_code);

$_SESSION['otp_expires_at'] = time() + 600;
$_SESSION['resend_available_at'] = time() + 60;


header("Location: /Implose.gg-src/pages/auth/verify_otp.php");
exit();
?>