<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /actions/admin/create_achievement.php
Description: Create new achievement from admin panel
            - Validate required fields (title, points_reward)
            - Optional badge image upload (saved to uploads/achievements/)
            - Insert into ACHIEVEMENT_T
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
    header('Location: /Implose.gg-src/pages/admin/achievement.php');
    exit();
}

$title        = trim($_POST['title']         ?? '');
$description  = trim($_POST['description']   ?? '');
$points       = isset($_POST['points_reward']) ? (int)$_POST['points_reward'] : 0;
$trigger_code = trim($_POST['trigger_code']  ?? 'MANUAL');

$redirect_url = '/Implose.gg-src/pages/admin/create_achievement.php';

function ach_redirect_with_error($msg) {
    global $redirect_url, $title, $description, $points, $trigger_code;
    $_SESSION['create_achievement_error'] = $msg;
    $_SESSION['create_achievement_old']   = [
        'title'        => $title,
        'description'  => $description,
        'points'       => $points,
        'trigger_code' => $trigger_code,
    ];
    header('Location: ' . $redirect_url);
    exit();
}

// validate trigger_code against the canonical dropdown list
$valid_triggers = array_keys(achievement_trigger_options());
if (!in_array($trigger_code, $valid_triggers, true)) {
    ach_redirect_with_error('Invalid unlock trigger selected.');
}

// validate title
if (strlen($title) < 2 || strlen($title) > 255) {
    ach_redirect_with_error('Title must be 2-255 characters.');
}

// validate description
if (strlen($description) > 1000) {
    ach_redirect_with_error('Description must be 1000 characters or fewer.');
}

// validate points
if ($points < 0 || $points > 100000) {
    ach_redirect_with_error('Points reward must be between 0 and 100,000.');
}

// validate badge upload (optional)
$has_badge = false;
$file = null;

if (isset($_FILES['badge_icon']) && $_FILES['badge_icon']['error'] === UPLOAD_ERR_OK) {
    $has_badge = true;
    $file = $_FILES['badge_icon'];

    $allowed_types = ['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/svg+xml'];
    if (!in_array($file['type'], $allowed_types)) {
        ach_redirect_with_error('Invalid image type. Please use PNG, JPG, WebP, GIF or SVG.');
    }
} elseif (isset($_FILES['badge_icon']) && $_FILES['badge_icon']['error'] !== UPLOAD_ERR_NO_FILE) {
    ach_redirect_with_error('Error uploading badge image.');
}

// check title uniqueness
$title_escaped = mysqli_real_escape_string($conn, $title);
$check_title_sql = "SELECT achievement_id FROM ACHIEVEMENT_T WHERE title = '$title_escaped' LIMIT 1";
$check_title_result = mysqli_query($conn, $check_title_sql);

if ($check_title_result && mysqli_num_rows($check_title_result) > 0) {
    ach_redirect_with_error('An achievement with this title already exists.');
}

// insert (badge path empty for now, fill after upload)
$desc_escaped    = mysqli_real_escape_string($conn, $description);
$trigger_escaped = mysqli_real_escape_string($conn, $trigger_code);

$insert_sql = "INSERT INTO ACHIEVEMENT_T (title, description, badge_icon_path, points_reward, trigger_code, created_at, updated_at)
               VALUES ('$title_escaped', '$desc_escaped', NULL, '$points', '$trigger_escaped', NOW(), NOW())";

$insert_result = mysqli_query($conn, $insert_sql);

if (!$insert_result) {
    ach_redirect_with_error('Failed to create achievement. Please try again.');
}

$new_id = mysqli_insert_id($conn);

// upload badge if provided
if ($has_badge) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $new_id . '.' . $ext;

    $target_dir  = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/achievements/';
    $target_path = $target_dir . $filename;

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $badge_path = 'uploads/achievements/' . $filename;
        $badge_escaped = mysqli_real_escape_string($conn, $badge_path);
        $update_sql = "UPDATE ACHIEVEMENT_T SET badge_icon_path = '$badge_escaped', updated_at = NOW() WHERE achievement_id = '$new_id'";
        mysqli_query($conn, $update_sql);
    }
}

// log
$admin_id = $_SESSION['user_id'] ?? null;
$log_title = mysqli_real_escape_string($conn, $title);
add_system_log($conn, $admin_id, 'Admin Create Achievement', "Admin created achievement #$new_id ($log_title).");

$_SESSION['achievement_success'] = "Achievement \"$title\" created successfully.";
header('Location: /Implose.gg-src/pages/admin/achievement.php');
exit();
?>
