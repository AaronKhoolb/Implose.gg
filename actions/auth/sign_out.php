<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/auth/sign_out.php
Description: sign out action
First Written on: Tuesday, 26-May-2026
Edited on: Tuesday, 9-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
    $user_id = $_SESSION['user_id'];
    $session_token = $_SESSION['session_token'];
    
    $update_session_sql = "UPDATE SESSION_RECORD_T SET is_active = '0', logout_at = NOW() WHERE user_id = '$user_id' AND session_token = '$session_token'";
    
    mysqli_query($conn, $update_session_sql);
}

session_unset();
session_destroy();

// sign out message (add change pswd msg)
if (isset($_GET['message'])) {
    $message = $_GET['message'];
} else {
    $message = "Signed out successfully!";
}

$message = urlencode($message);
header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
exit();
?>