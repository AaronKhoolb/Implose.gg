<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /actions/admin/delete_achievement.php
Description: Delete an achievement
            - Remove related USER_ACHIEVEMENT_T rows first
            - Remove badge file from disk if present
            - Remove ACHIEVEMENT_T row
            - Log to SYSTEM_LOG_T
            - Redirect back with flash message
First Written on: Saturday, 27-Jun-2026
Edited on: Saturday, 27-Jun-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

// only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Implose.gg-src/pages/admin/achievement.php');
    exit();
}

$id = isset($_POST['achievement_id']) ? (int)$_POST['achievement_id'] : 0;

if ($id <= 0) {
    $_SESSION['achievement_error'] = 'Invalid achievement.';
    header('Location: /Implose.gg-src/pages/admin/achievement.php');
    exit();
}

// load achievement to capture title + badge path
$load_sql = "SELECT title, badge_icon_path FROM ACHIEVEMENT_T WHERE achievement_id = '$id' LIMIT 1";
$load_res = mysqli_query($conn, $load_sql);

if (!$load_res || mysqli_num_rows($load_res) !== 1) {
    $_SESSION['achievement_error'] = 'Achievement not found.';
    header('Location: /Implose.gg-src/pages/admin/achievement.php');
    exit();
}

$row         = mysqli_fetch_assoc($load_res);
$title       = $row['title'] ?? '(Untitled)';
$badge_path  = $row['badge_icon_path'] ?? '';

// remove related unlock records first
$unlink_sql = "DELETE FROM USER_ACHIEVEMENT_T WHERE achievement_id = '$id'";
mysqli_query($conn, $unlink_sql);

// remove the achievement
$delete_sql    = "DELETE FROM ACHIEVEMENT_T WHERE achievement_id = '$id'";
$delete_result = mysqli_query($conn, $delete_sql);

if (!$delete_result) {
    $_SESSION['achievement_error'] = 'Failed to delete achievement.';
    header('Location: /Implose.gg-src/pages/admin/achievement.php');
    exit();
}

// remove badge file from disk if present
if (!empty($badge_path)) {
    $full = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/' . $badge_path;
    if (is_file($full)) {
        @unlink($full);
    }
}

// log
$admin_id  = $_SESSION['user_id'] ?? null;
$log_title = mysqli_real_escape_string($conn, $title);
add_system_log($conn, $admin_id, 'Admin Delete Achievement', "Admin deleted achievement #$id ($log_title).");

$_SESSION['achievement_success'] = "Achievement \"$title\" deleted.";
header('Location: /Implose.gg-src/pages/admin/achievement.php');
exit();
?>
