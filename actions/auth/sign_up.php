<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/auth/sign_up.php
Description: sign up action
First Written on: Thursday, 18-May-2026
Edited on: Tuesday, 9-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');


$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Check password match
if($password != $confirm_password) {
    $error = urlencode("Password does not match");
    header("location: /Implose.gg-src/pages/auth/sign_up.php?error=$error");
    exit();
}

// Check email registered?
$email = mysqli_real_escape_string($conn, $email);

$check_email_sql = "SELECT * FROM USER_T WHERE email_address = '$email'";
$check_email_result = mysqli_query($conn, $check_email_sql);

if(mysqli_num_rows($check_email_result) > 0) {
    $error = urlencode("Email already registered");
    header("location: /Implose.gg-src/pages/auth/sign_up.php?error=$error");
    exit();
}

// Password hash
$password_hash = password_hash($password, PASSWORD_DEFAULT);


// throw data to user tbl
$insert_user_sql = "INSERT INTO USER_T (role, email_address, password_hash, total_points, streak_count, account_status, created_at, updated_at)
                    VALUES ('user', '$email', '$password_hash', 0, 0, 'pending', NOW(), NOW())";

$insert_user_result = mysqli_query($conn, $insert_user_sql);

// Generate otp
$user_id = mysqli_insert_id($conn);

add_system_log($conn, $user_id, "User Registration", "User registered account and waiting for OTP verification.");

$otp_code = rand(100000, 999999);

$insert_otp_sql = "INSERT INTO OTP_RECORD_T (user_id, otp_code, purpose, expires_at, is_used, created_at) 
                    VALUES ('$user_id', '$otp_code', 'register', DATE_ADD(NOW(), INTERVAL 10 MINUTE), '0', NOW())";

mysqli_query($conn, $insert_otp_sql);

send_otp_email($email, $otp_code);

$_SESSION['verify_user_id'] = $user_id;
$_SESSION['verify_purpose'] = 'register';
$_SESSION['otp_email'] = $email;
$_SESSION['otp_expires_at'] = time() + 600;
$_SESSION['resend_available_at'] = time() + 60;


header("Location: /Implose.gg-src/pages/auth/verify_otp.php");
exit();
?>