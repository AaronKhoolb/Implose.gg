<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /actions/admin/update_feedback.php
Description: Admin edit for any COURSE_FEEDBACK_T row. Mirrors the
            achievement admin pattern:
              - Validate required fields (feedback_id + emoji_rating)
              - Update COURSE_FEEDBACK_T
              - Write a SYSTEM_LOG_T row
              - Redirect back to /pages/admin/feedback.php with a
                flash message stored in $_SESSION['feedback_success']
                or $_SESSION['feedback_error']. The feedback page
                renders these via an .admin-toast.
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
*/
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

// Always land back on the list page — with a flash message set below
$back_url = '/Implose.gg-src/pages/admin/feedback.php';

// 1. Only POST is allowed
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ' . $back_url);
    exit();
}

// 2. Must be signed in as an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    $_SESSION['feedback_error'] = 'Admin only.';
    header('Location: ' . $back_url);
    exit();
}
$admin_id = $_SESSION['user_id'];

// 3. Read the incoming form fields
if (isset($_POST['feedback_id'])) {
    $feedback_id = $_POST['feedback_id'];
} else {
    $feedback_id = 0;
}

if (isset($_POST['emoji_rating'])) {
    $emoji = trim($_POST['emoji_rating']);
} else {
    $emoji = '';
}

if (isset($_POST['description'])) {
    $description = trim($_POST['description']);
} else {
    $description = '';
}

// 4. Validate
if ($feedback_id <= 0) {
    $_SESSION['feedback_error'] = 'Invalid feedback.';
    header('Location: ' . $back_url);
    exit();
}

$valid_emojis = array('angry', 'sad', 'neutral', 'happy', 'excellent');
if (!in_array($emoji, $valid_emojis)) {
    $_SESSION['feedback_error'] = 'Please choose a valid emoji rating.';
    header('Location: ' . $back_url);
    exit();
}

if (strlen($description) > 500) {
    $_SESSION['feedback_error'] = 'Comment is too long (max 500 characters).';
    header('Location: /Implose.gg-src/pages/admin/edit_feedback.php?id=' . $feedback_id);
    exit();
}

// 5. Make sure the row exists
$find_sql = "SELECT feedback_id FROM COURSE_FEEDBACK_T WHERE feedback_id = '$feedback_id' LIMIT 1";
$find_res = mysqli_query($conn, $find_sql);
if (!$find_res || mysqli_num_rows($find_res) != 1) {
    $_SESSION['feedback_error'] = 'Feedback not found.';
    header('Location: ' . $back_url);
    exit();
}

// 6. Escape strings and run the UPDATE
$emoji_esc = mysqli_real_escape_string($conn, $emoji);
$desc_esc  = mysqli_real_escape_string($conn, $description);

$update_sql = "UPDATE COURSE_FEEDBACK_T
                  SET emoji_rating = '$emoji_esc',
                      description  = '$desc_esc'
                WHERE feedback_id = '$feedback_id'";

$update_result = mysqli_query($conn, $update_sql);
if (!$update_result) {
    $_SESSION['feedback_error'] = 'Failed to update feedback.';
    header('Location: ' . $back_url);
    exit();
}

// 7. Log the moderation action
add_system_log($conn, $admin_id, 'Admin Edit Feedback', "Admin edited feedback #$feedback_id.");

$_SESSION['feedback_success'] = "Feedback #$feedback_id updated.";
header('Location: ' . $back_url);
exit();
?>
