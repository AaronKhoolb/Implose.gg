<?php
/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/auth/verify_otp.php
Description: verify otp action
First Written on: Thursday, 18-May-2026
Edited on: Sunday, 05-Jul-2026
*/

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');

// bounce back if user landed here without a valid OTP flow
if (!isset($_SESSION['verify_user_id']) || !isset($_SESSION['verify_purpose']) || !isset($_POST['otp_code'])) {
    $message = urlencode("Session expired. Please try again.");
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

$user_id = $_SESSION['verify_user_id'];
$purpose = $_SESSION['verify_purpose'];

$otp_code = trim($_POST['otp_code']);

$check_otp_sql = "SELECT * FROM OTP_RECORD_T WHERE user_id = '$user_id' AND otp_code = '$otp_code' AND purpose = '$purpose' AND is_used = '0' AND expires_at > NOW()";

$check_otp_result = mysqli_query($conn, $check_otp_sql);

if (mysqli_num_rows($check_otp_result) != 1) {
    $error = urlencode("Invalid or expired verification code");
    header("Location: /Implose.gg-src/pages/auth/verify_otp.php?error=$error");
    exit();
}

$otp = mysqli_fetch_assoc($check_otp_result);
$otp_id = $otp['otp_id'];

mysqli_query($conn, "UPDATE OTP_RECORD_T SET is_used = '1' WHERE otp_id = '$otp_id'");

if ($purpose == 'register') {
    mysqli_query($conn, "UPDATE USER_T SET account_status = 'active', updated_at = NOW() WHERE user_id = '$user_id'");
    
    unset($_SESSION['verify_user_id']);
    unset($_SESSION['verify_purpose']);
    unset($_SESSION['otp_email']);
    unset($_SESSION['otp_expires_at']);
    unset($_SESSION['resend_available_at']);

    $message = urlencode("Account verified. You can login now.");
    
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

if ($purpose == 'login') {
    $user_sql = "SELECT * FROM USER_T WHERE user_id = '$user_id'";
    $user_result = mysqli_query($conn, $user_sql);
    $user = mysqli_fetch_assoc($user_result);

    // streak
    $last_login_at = $user['last_login_at'];
    $streak_count = $user['streak_count'];
    $add_points = 0;
    
    if ($last_login_at == null) {
        $streak_count = 1;
        $add_points = 5;
    } else {
        $last_login_date = date('Y-m-d', strtotime($last_login_at));
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

         if ($last_login_date == $yesterday) {
            $streak_count++;
            $add_points = 5;
        } else if ($last_login_date != $today) {
            $streak_count = 1;
            $add_points = 5;
        }
    }

    // update USER_T tbl
    $update_user_sql = "UPDATE USER_T SET streak_count = '$streak_count', total_points = total_points + $add_points, last_login_at = NOW(), updated_at = NOW() WHERE user_id = '$user_id'";

    mysqli_query($conn, $update_user_sql);

    // ── Award achievements based on login event ──
    // first ever login → FIRST_LOGIN trigger
    if ($last_login_at == null) {
        award_achievement($conn, $user_id, 'FIRST_LOGIN');
    }
    // streak milestones (STREAK_3 / STREAK_7 / STREAK_30)
    check_streak_milestones($conn, $user_id, $streak_count);
    // points milestones (POINTS_100 / POINTS_500 / POINTS_1000) — uses fresh total_points
    check_points_milestones($conn, $user_id);

    // create SESSION_RECORD_T
    $session_token = bin2hex(random_bytes(32));

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    
    $insert_session_sql = "INSERT INTO SESSION_RECORD_T (user_id, session_token, ip_address, user_agent, login_at, expires_at, is_active) VALUES ('$user_id', '$session_token', '$ip_address', '$user_agent', NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), '1')";
    
    mysqli_query($conn, $insert_session_sql);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = $user['role'];
    $_SESSION['session_token'] = $session_token;

    // unset unnecessary session
    unset($_SESSION['verify_user_id']);
    unset($_SESSION['verify_purpose']);
    unset($_SESSION['otp_email']);
    unset($_SESSION['otp_expires_at']);
    unset($_SESSION['resend_available_at']);

    // redirect
    if ($user['username'] == null || $user['avatar_path'] == null || $user['age'] == null || $user['gender'] == null) {
        header("Location: /Implose.gg-src/pages/auth/complete_profile.php");
        exit();
    }
    
    if ($user['role'] == 'admin') {
        header("Location: /Implose.gg-src/pages/admin/index.php");
        exit();
    } else if ($user['role'] == 'moderator') {
        header("Location: /Implose.gg-src/pages/moderator/index.php");
        exit();
    } else if ($user['role'] == 'user') {
        header("Location: /Implose.gg-src/pages/user/index.php");
        exit();
    }
}

if ($purpose == 'reset_password') {
    $_SESSION['reset_user_id'] = $user_id;

    unset($_SESSION['verify_user_id']);
    unset($_SESSION['verify_purpose']);
    unset($_SESSION['otp_email']);
    unset($_SESSION['otp_expires_at']);
    unset($_SESSION['resend_available_at']);

    header("Location: /Implose.gg-src/pages/auth/reset_password.php");
    exit();
}

?>