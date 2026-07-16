<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /actions/moderator/update_achievement.php
Description: Update an existing achievement from moderator panel
            - Validate required fields (title, points_reward)
            - Optional badge image replacement
            - Update ACHIEVEMENT_T
            - Log action to SYSTEM_LOG_T
            - Redirect back with flash message
First Written on: Saturday, 27-Jun-2026
Edited on: Saturday, 27-Jun-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');
ob_end_clean();

// only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Implose.gg-src/pages/moderator/achievement.php');
    exit();
}

$id           = isset($_POST['achievement_id']) ? (int)$_POST['achievement_id'] : 0;
$title        = trim($_POST['title']           ?? '');
$description  = trim($_POST['description']     ?? '');
$points       = isset($_POST['points_reward']) ? (int)$_POST['points_reward'] : 0;
$trigger_code = trim($_POST['trigger_code']    ?? 'MANUAL');

if ($id <= 0) {
    $_SESSION['achievement_error'] = 'Invalid achievement.';
    header('Location: /Implose.gg-src/pages/moderator/achievement.php');
    exit();
}

$redirect_url = '/Implose.gg-src/pages/moderator/edit_achievement.php?id=' . $id;

function ach_update_redirect_with_error($msg) {
    global $redirect_url;
    $_SESSION['edit_achievement_error'] = $msg;
    header('Location: ' . $redirect_url);
    exit();
}

// ensure achievement exists and grab the current badge path
$current_sql = "SELECT badge_icon_path FROM ACHIEVEMENT_T WHERE achievement_id = '$id' LIMIT 1";
$current_res = mysqli_query($conn, $current_sql);
if (!$current_res || mysqli_num_rows($current_res) !== 1) {
    $_SESSION['achievement_error'] = 'Achievement not found.';
    header('Location: /Implose.gg-src/pages/moderator/achievement.php');
    exit();
}
$current_row  = mysqli_fetch_assoc($current_res);
$current_badge = $current_row['badge_icon_path'];

// validate title
if (strlen($title) < 2 || strlen($title) > 255) {
    ach_update_redirect_with_error('Title must be 2-255 characters.');
}

// validate description
if (strlen($description) > 1000) {
    ach_update_redirect_with_error('Description must be 1000 characters or fewer.');
}

// validate points
if ($points < 0 || $points > 100000) {
    ach_update_redirect_with_error('Points reward must be between 0 and 100,000.');
}

// validate trigger_code against the canonical dropdown list
$valid_triggers = array_keys(achievement_trigger_options());
if (!in_array($trigger_code, $valid_triggers, true)) {
    ach_update_redirect_with_error('Invalid unlock trigger selected.');
}

// validate badge upload (optional)
$has_badge = false;
$file = null;

if (isset($_FILES['badge_icon']) && $_FILES['badge_icon']['error'] === UPLOAD_ERR_OK) {
    $has_badge = true;
    $file = $_FILES['badge_icon'];

    $allowed_types = ['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/svg+xml'];
    if (!in_array($file['type'], $allowed_types)) {
        ach_update_redirect_with_error('Invalid image type. Please use PNG, JPG, WebP, GIF or SVG.');
    }
} elseif (isset($_FILES['badge_icon']) && $_FILES['badge_icon']['error'] !== UPLOAD_ERR_NO_FILE) {
    ach_update_redirect_with_error('Error uploading badge image.');
}

// check title uniqueness against OTHER rows
$title_escaped = mysqli_real_escape_string($conn, $title);
$check_title_sql = "SELECT achievement_id FROM ACHIEVEMENT_T WHERE title = '$title_escaped' AND achievement_id <> '$id' LIMIT 1";
$check_title_result = mysqli_query($conn, $check_title_sql);

if ($check_title_result && mysqli_num_rows($check_title_result) > 0) {
    ach_update_redirect_with_error('Another achievement already uses this title.');
}

// move file first (if any) so we know the new path
$new_badge_path = $current_badge;

if ($has_badge) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $id . '.' . $ext;

    $target_dir  = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/achievements/';
    $target_path = $target_dir . $filename;

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // remove the old file if it was a DIFFERENT extension to avoid orphan files
        if (!empty($current_badge) && $current_badge !== 'uploads/achievements/' . $filename) {
            $old_full = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/' . $current_badge;
            if (is_file($old_full)) {
                @unlink($old_full);
            }
        }
        $new_badge_path = 'uploads/achievements/' . $filename;
    } else {
        ach_update_redirect_with_error('Failed to save uploaded image.');
    }
}

// update row
$desc_escaped    = mysqli_real_escape_string($conn, $description);
$trigger_escaped = mysqli_real_escape_string($conn, $trigger_code);
$badge_sql_val = ($new_badge_path === null || $new_badge_path === '')
    ? "NULL"
    : "'" . mysqli_real_escape_string($conn, $new_badge_path) . "'";

$update_sql = "UPDATE ACHIEVEMENT_T
               SET title = '$title_escaped',
                   description = '$desc_escaped',
                   badge_icon_path = $badge_sql_val,
                   points_reward = '$points',
                   trigger_code = '$trigger_escaped',
                   updated_at = NOW()
               WHERE achievement_id = '$id'";

$update_result = mysqli_query($conn, $update_sql);

if (!$update_result) {
    ach_update_redirect_with_error('Failed to update achievement. Please try again.');
}

// log
$admin_id  = $_SESSION['user_id'] ?? null;
$log_title = mysqli_real_escape_string($conn, $title);
add_system_log($conn, $admin_id, 'Moderator Update Achievement', "Moderator updated achievement #$id ($log_title).");

$_SESSION['achievement_success'] = "Achievement \"$title\" updated successfully.";
header('Location: /Implose.gg-src/pages/moderator/achievement.php');
exit();
?>
