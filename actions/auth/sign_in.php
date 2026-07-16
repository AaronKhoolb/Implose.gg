<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/auth/sign_in.php
Description: sign in action
First Written on: Thursday, 18-May-2026
Edited on: Tuesday, 9-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

function validateTurnstile($token, $secret, $remoteip = null) {
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $data = [
        'secret' => $secret,
        'response' => $token
    ];

    if ($remoteip) {
        $data['remoteip'] = $remoteip;
    }

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === FALSE) {
        return ['success' => false, 'error-codes' => ['internal-error']];
    }

    return json_decode($response, true);

}



$env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/Implose.gg-src/.env");

$turnstile_token = $_POST['cf-turnstile-response'] ?? '';
$turnstile_secret_key = $env['TURNSTILE_SECRET_KEY'];

$remoteip = $_SERVER['HTTP_CF_CONNECTING_IP'] ??
            $_SERVER['HTTP_X_FORWARDED_FOR'] ??
            $_SERVER['REMOTE_ADDR'];

$validation = validateTurnstile($turnstile_token, $turnstile_secret_key, $remoteip);

if (!$validation['success']) {
    // Invalid token - show error
    $message = urlencode("Human verification failed. Please try again. ");
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

$email = trim($_POST['email']);
$password = $_POST['password'];

$check_user_sql = "SELECT * FROM USER_T WHERE email_address = '$email'";
$check_user_result = mysqli_query($conn, $check_user_sql);

if(mysqli_num_rows($check_user_result) != 1) {
    $message = urlencode("Invalid email address or password");
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

$user = mysqli_fetch_assoc($check_user_result);

if(!password_verify($password, $user['password_hash'])) {
    $message = urlencode("Invalid email address or password");
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

if ($user['account_status'] == 'suspended') {
    $message = urlencode("Account suspended. Please contact administrator.");
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

if ($user['account_status'] == 'banned') {
    $message = urlencode("Account banned. Please contact administrator.");
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

if ($user['account_status'] == 'pending') {
    $purpose = 'register';
} else {
    $purpose = 'login';
}

// Generate otp_code
$user_id = $user['user_id'];

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