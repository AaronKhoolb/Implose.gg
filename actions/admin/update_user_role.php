<?php
/*
Programmer Name: Chong Jun Yoong
Program Name: /actions/admin/update_user_role.php
Description: Update user role from admin users page
First Written on: Sunday, 13-Jun-2026
Edited on: Sunday, 14-Jun-2026
*/

session_start();

// buffer db.php so its stray output doesn't pollute the redirect header
ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['action_error'] = 'Invalid request.';
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$role = $_POST['role'] ?? '';

if ($user_id <= 0) {
    $_SESSION['action_error'] = 'Invalid user ID.';
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

$allowed_roles = ['User', 'Moderator', 'Admin'];
if (!in_array($role, $allowed_roles)) {
    $_SESSION['action_error'] = 'Invalid role.';
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

$check_sql = "SELECT username FROM USER_T WHERE user_id = '$user_id'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) != 1) {
    $_SESSION['action_error'] = 'User not found.';
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

$user = mysqli_fetch_assoc($check_result);

$role_lower = strtolower($role);
$update_sql = "UPDATE USER_T SET role = '$role_lower', updated_at = NOW() WHERE user_id = '$user_id'";
$update_result = mysqli_query($conn, $update_sql);

if (!$update_result) {
    $_SESSION['action_error'] = 'Failed to update role.';
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

$admin_id = $_SESSION['user_id'] ?? null;
$username = $user['username'];
add_system_log($conn, $admin_id, 'Admin Update Role', "Admin changed user #$user_id ($username) role to $role.");

$_SESSION['create_user_success'] = "Role updated successfully!";
header('Location: /Implose.gg-src/pages/admin/users.php');
exit();
?>
