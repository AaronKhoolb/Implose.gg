<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /actions/admin/delete_feedback.php
Description: Admin delete for any COURSE_FEEDBACK_T row. Mirrors the
            achievement admin pattern:
              - Delete the row
              - Write a SYSTEM_LOG_T entry
              - Redirect back to /pages/admin/feedback.php with a
                flash message set in the session. That page renders
                the message as an .admin-toast.
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
*/
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

$back_url = '/Implose.gg-src/pages/admin/feedback.php';

// 1. Only POST allowed
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

// 3. Read the feedback id
if (isset($_POST['feedback_id'])) {
    $feedback_id = $_POST['feedback_id'];
} else {
    $feedback_id = 0;
}

if ($feedback_id <= 0) {
    $_SESSION['feedback_error'] = 'Invalid feedback.';
    header('Location: ' . $back_url);
    exit();
}

// 4. Delete — no user_id filter (admins can remove any feedback)
$del_sql = "DELETE FROM COURSE_FEEDBACK_T WHERE feedback_id = '$feedback_id'";
$del_result = mysqli_query($conn, $del_sql);
if (!$del_result) {
    $_SESSION['feedback_error'] = 'Failed to delete feedback.';
    header('Location: ' . $back_url);
    exit();
}

// mysqli_affected_rows confirms a row was actually removed
$rows_deleted = mysqli_affected_rows($conn);
if ($rows_deleted == 0) {
    $_SESSION['feedback_error'] = 'Feedback not found.';
    header('Location: ' . $back_url);
    exit();
}

// 5. Log the moderation action
add_system_log($conn, $admin_id, 'Admin Delete Feedback', "Admin deleted feedback #$feedback_id.");

$_SESSION['feedback_success'] = "Feedback #$feedback_id deleted.";
header('Location: ' . $back_url);
exit();
?>
