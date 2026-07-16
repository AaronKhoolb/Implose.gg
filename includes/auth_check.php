<?php
/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /includes/auth_check.php
Description:    - check login session and restrict pages by role
                - block logged in session to access auth pages
                - check session token lifetime (is_active)
First Written on: Monday, 18-May-2026
Edited on: Tuesday, 9-Jun-2026
*/

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$path = $_SERVER['SCRIPT_FILENAME'];

$need_role = null;
$is_auth_page = false;


if (str_contains($path, '/pages/user/')) {
    $need_role = 'user';
} else if (str_contains($path, '/pages/admin/')) {
    $need_role = 'admin';
} else if (str_contains($path, '/pages/moderator/')) {
    $need_role = 'moderator';
} else if (str_contains($path, '/pages/auth/complete_profile.php')) {
    $need_role = 'user';
}

if (str_contains($path,'/pages/auth/sign_in.php') || str_contains($path,'/pages/auth/sign_up.php')) {
    $is_auth_page = true;
}


if ($need_role == null && $is_auth_page == false) {
    return;
}

// redirect to lgn page if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['session_token'])) {
    if ($need_role != null) {
        $message = urlencode("Please sign in to access this page.");
        header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
        exit();
    }

    return;
}

// check session
$user_id = $_SESSION['user_id'];
$session_token = $_SESSION['session_token'];


$check_session_sql = "SELECT * FROM SESSION_RECORD_T WHERE user_id = '$user_id' AND session_token = '$session_token'";

$check_session_result = mysqli_query($conn, $check_session_sql);

if (mysqli_num_rows($check_session_result) != 1) {
    session_destroy();
    $message = urlencode('Invalid session. Please sign in again.');
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

$session = mysqli_fetch_assoc($check_session_result);

if ($session['is_active'] != 1) {
    session_destroy();
    $message = urlencode('Your session has been terminated. Please sign in again.');
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

if ($session['expires_at'] < date('Y-m-d H:i:s')) {
    session_destroy();
    $message = urlencode('Your session has expired. Please sign in again.');
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}


// check user account status
$check_user_sql = "SELECT * FROM USER_T WHERE user_id = '$user_id'";

$check_user_result = mysqli_query($conn, $check_user_sql);

if (mysqli_num_rows($check_user_result) != 1) {
    session_destroy();
    $message = urlencode('Account not found.');
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

$user = mysqli_fetch_assoc($check_user_result);
if ($user["account_status"] == "suspended") {
    session_destroy();
    $message = urlencode('Your account has been suspended. Please contact the administrator.');
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

if ($user["account_status"] == "banned") {
    session_destroy();
    $message = urlencode('Your account has been banned. Please contact the administrator.');
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

if ($user["account_status"] == "pending") {
    session_destroy();
    $message = urlencode('Please verify your account first.');
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}


// redirect by role
$_SESSION["role"] = $user["role"];

if ($_SESSION['role'] == 'admin') {
    $dashboard = "/Implose.gg-src/pages/admin/index.php";
} else if ($_SESSION['role'] == 'moderator') {
    $dashboard = "/Implose.gg-src/pages/moderator/index.php";
} else if ($_SESSION['role'] == 'user') {
    $dashboard = "/Implose.gg-src/pages/user/index.php";
} else {
    session_destroy();
    $message = urlencode('Invalid role.');
    header("Location: /Implose.gg-src/pages/auth/sign_in.php?message=$message");
    exit();
}

if ($is_auth_page) {
    header("Location: " . $dashboard);
    exit();
}

if ($need_role != null && $_SESSION['role'] != $need_role) {
    header("Location: " . $dashboard);
    exit();
}

?>