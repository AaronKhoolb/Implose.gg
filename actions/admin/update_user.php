<?php
/*
Programmer Name: Chong Jun Yoong
Program Name: /actions/admin/update_user.php
Description: Update user profile from admin edit user page
First Written on: Friday, 12-Jun-2026
Edited on: Thursday, 18-Jun-2026
*/

session_start();

// buffer db.php so its stray output doesn't pollute the redirect header
ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'user');
$status_active = isset($_POST['status_active']); // checkbox: present = active
$status = $status_active ? 'active' : 'suspended';


if ($user_id <= 0) {
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

if (strlen($username) < 2 || strlen($username) > 32) {
    $_SESSION['edit_user_error'] = 'Username must be 2-32 characters.';
    header("Location: /Implose.gg-src/pages/admin/edit_user.php?id=$user_id");
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
    $_SESSION['edit_user_error'] = 'Username can only contain letters, numbers, _ or -.';
    header("Location: /Implose.gg-src/pages/admin/edit_user.php?id=$user_id");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['edit_user_error'] = 'Please enter a valid email address.';
    header("Location: /Implose.gg-src/pages/admin/edit_user.php?id=$user_id");
    exit();
}

$allowed_roles = ['user', 'moderator', 'admin'];
if (!in_array($role, $allowed_roles)) {
    $_SESSION['edit_user_error'] = 'Invalid role selected.';
    header("Location: /Implose.gg-src/pages/admin/edit_user.php?id=$user_id");
    exit();
}


$check_sql = "SELECT * FROM USER_T WHERE user_id = '$user_id'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) != 1) {
    $_SESSION['edit_user_error'] = 'User not found.';
    header("Location: /Implose.gg-src/pages/admin/edit_user.php?id=$user_id");
    exit();
}

$current_user = mysqli_fetch_assoc($check_result);

// exclude self so admin can save without changing the username
$check_username_sql = "SELECT * FROM USER_T WHERE username = '$username' AND user_id != '$user_id'";
$check_username_result = mysqli_query($conn, $check_username_sql);

if (mysqli_num_rows($check_username_result) > 0) {
    $_SESSION['edit_user_error'] = 'Username already taken.';
    header("Location: /Implose.gg-src/pages/admin/edit_user.php?id=$user_id");
    exit();
}

$check_email_sql = "SELECT * FROM USER_T WHERE email_address = '$email' AND user_id != '$user_id'";
$check_email_result = mysqli_query($conn, $check_email_sql);

if (mysqli_num_rows($check_email_result) > 0) {
    $_SESSION['edit_user_error'] = 'Email address already taken.';
    header("Location: /Implose.gg-src/pages/admin/edit_user.php?id=$user_id");
    exit();
}


// keep current avatar unless a new one is uploaded
$avatar_path = $current_user['avatar_path'];

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
    $file = $_FILES['avatar'];

    $allowed_types = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['edit_user_error'] = 'Invalid image type. Please use PNG, JPG, WebP or GIF.';
        header("Location: /Implose.gg-src/pages/admin/edit_user.php?id=$user_id");
        exit();
    }

    // skip the shared default avatar so it doesn't get deleted
    if ($avatar_path != '' && $avatar_path != null && strpos($avatar_path, 'avatar_test') === false) {
        $old_file = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/' . $avatar_path;
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $user_id . '.' . $ext;

    $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/avatars/';
    $target_path = $target_dir . $filename;

    move_uploaded_file($file['tmp_name'], $target_path);

    $avatar_path = 'uploads/avatars/' . $filename;
}


$update_sql = "UPDATE USER_T SET username = '$username', email_address = '$email', role = '$role', account_status = '$status', avatar_path = '$avatar_path', updated_at = NOW() WHERE user_id = '$user_id'";
$update_result = mysqli_query($conn, $update_sql);

if (!$update_result) {
    $_SESSION['edit_user_error'] = 'Failed to update user. Please try again.';
    header("Location: /Implose.gg-src/pages/admin/edit_user.php?id=$user_id");
    exit();
}

$admin_id = $_SESSION['user_id'] ?? null;
add_system_log($conn, $admin_id, 'Admin Edit User', "Admin updated user #$user_id ($username).");

$_SESSION['create_user_success'] = 'User "' . $username . '" updated successfully.';
header('Location: /Implose.gg-src/pages/admin/users.php');
exit();
?>
