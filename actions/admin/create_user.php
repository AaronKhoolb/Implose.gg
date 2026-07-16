<?php
/*
Programmer Name: Chong Jun Yoong
Program Name: /actions/admin/create_user.php
Description: Handle new user creation from the admin panel
First Written on: Tuesday, 16-Jun-2026
Edited on: Thursday, 18-Jun-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = trim($_POST['role'] ?? 'user');


if (strlen($username) < 2 || strlen($username) > 32) {
    $_SESSION['create_user_error'] = 'Username must be 2-32 characters.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
    $_SESSION['create_user_error'] = 'Username can only contain letters, numbers, _ or -.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['create_user_error'] = 'Please enter a valid email address.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['create_user_error'] = 'Password must be at least 6 characters.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}

if ($password != $confirm_password) {
    $_SESSION['create_user_error'] = 'Passwords do not match.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}

$allowed_roles = ['user', 'moderator', 'admin'];
if (!in_array($role, $allowed_roles)) {
    $_SESSION['create_user_error'] = 'Invalid role selected.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}


$has_avatar = false;
$file = null;

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $has_avatar = true;
    $file = $_FILES['avatar'];

    $allowed_types = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['create_user_error'] = 'Invalid image type. Please use PNG, JPG, WebP or GIF.';
        header('Location: /Implose.gg-src/pages/admin/create_user.php');
        exit();
    }
} elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $_SESSION['create_user_error'] = 'Error uploading profile picture.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}


$check_username_sql = "SELECT * FROM USER_T WHERE username = '$username'";
$check_username_result = mysqli_query($conn, $check_username_sql);

if (mysqli_num_rows($check_username_result) > 0) {
    $_SESSION['create_user_error'] = 'Username already taken.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}

$check_email_sql = "SELECT * FROM USER_T WHERE email_address = '$email'";
$check_email_result = mysqli_query($conn, $check_email_sql);

if (mysqli_num_rows($check_email_result) > 0) {
    $_SESSION['create_user_error'] = 'Email address already taken.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}


$password_hash = password_hash($password, PASSWORD_DEFAULT);

// filled in after insert once we have the user_id for the filename
$avatar_path = '';

$insert_sql = "INSERT INTO USER_T (role, email_address, password_hash, username, avatar_path, total_points, streak_count, account_status, created_at, updated_at) VALUES ('$role', '$email', '$password_hash', '$username', '$avatar_path', 0, 0, 'active', NOW(), NOW())";
$insert_result = mysqli_query($conn, $insert_sql);

if (!$insert_result) {
    $_SESSION['create_user_error'] = 'Failed to create user. Please try again.';
    header('Location: /Implose.gg-src/pages/admin/create_user.php');
    exit();
}

$new_user_id = mysqli_insert_id($conn);


$target_dir = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/avatars/';

if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if ($has_avatar) {
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $new_user_id . '.' . $ext;
    $target_path = $target_dir . $filename;
    move_uploaded_file($file['tmp_name'], $target_path);
} else {
    // copy the default avatar rather than reference it, so deleting the user
    // won't wipe the shared system default
    $filename = $new_user_id . '.png';
    $target_path = $target_dir . $filename;
    $default_avatar = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';
    copy($default_avatar, $target_path);
}

$uploaded_avatar_path = 'uploads/avatars/' . $filename;

$update_avatar_sql = "UPDATE USER_T SET avatar_path = '$uploaded_avatar_path' WHERE user_id = '$new_user_id'";
mysqli_query($conn, $update_avatar_sql);


$admin_id = $_SESSION['user_id'] ?? null;
add_system_log($conn, $admin_id, 'Admin Create User', "Admin created user #$new_user_id ($username).");

$display_role = ucfirst($role);
$_SESSION['create_user_success'] = "$display_role \"$username\" created successfully.";
header('Location: /Implose.gg-src/pages/admin/users.php');
exit();
?>
