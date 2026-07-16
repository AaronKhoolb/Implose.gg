<?php
/*
Programmer Name: Chong Jun Yoong
Program Name: /actions/admin/delete_user.php
Description: Handle user deletion from the admin panel
First Written on: Wednesday, 1-Jul-2026
Edited on: Wednesday, 1-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['action_error'] = 'Invalid request method.';
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    $_SESSION['action_error'] = 'User ID is required.';
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

// admin can't delete their own account
$current_admin_id = $_SESSION['user_id'] ?? null;
if ($current_admin_id == $user_id) {
    $_SESSION['action_error'] = 'You cannot delete your own account.';
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}


// pull username + avatar path so we can log the deletion and cleanup the file after
$user_sql = "SELECT username, avatar_path FROM USER_T WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_sql);

if (mysqli_num_rows($user_result) == 0) {
    $_SESSION['action_error'] = 'User not found. ID received: ' . htmlspecialchars($user_id);
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

$user_data = mysqli_fetch_assoc($user_result);
$username = $user_data['username'];
$avatar_path = $user_data['avatar_path'];


$delete_sql = "DELETE FROM USER_T WHERE user_id = '$user_id'";
$del_result = mysqli_query($conn, $delete_sql);

if (!$del_result) {
    $_SESSION['action_error'] = 'Failed to delete user. They might have associated records that prevent deletion. (Error: ' . mysqli_error($conn) . ')';
    header('Location: /Implose.gg-src/pages/admin/users.php');
    exit();
}

add_system_log($conn, $current_admin_id, 'Admin Delete User', "Admin deleted user #$user_id ($username).");

// skip the shared default avatar so it doesn't get deleted
if (!empty($avatar_path) && strpos($avatar_path, 'avatar_test') === false) {
    $full_avatar_path = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/' . $avatar_path;
    if (file_exists($full_avatar_path)) {
        unlink($full_avatar_path);
    }
}

$_SESSION['create_user_success'] = "User \"$username\" has been deleted successfully.";
header('Location: /Implose.gg-src/pages/admin/users.php');
exit();
?>
