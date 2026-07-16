<?php
/*
Programmer Name: Chong Jun Yoong
Program Name: /actions/admin/report_action.php
Description: Form POST handler for Admin Report Actions (Suspend, Delete, Dismiss)
First Written on: Friday, 20-Jun-2026
Edited on: Friday, 20-Jun-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: /Implose.gg-src/pages/admin/report.php');
    exit();
}

$action = $_POST['action'] ?? '';
$report_id = intval($_POST['report_id'] ?? 0);
$admin_id = intval($_SESSION['user_id'] ?? 0);

if ($report_id <= 0 || !$action) {
    $_SESSION['error_msg'] = 'Missing parameters.';
    header('Location: /Implose.gg-src/pages/admin/report.php');
    exit();
}

$report_sql = "SELECT * FROM REPORT_T WHERE report_id = $report_id";
$report_result = mysqli_query($conn, $report_sql);

if (!$report_result) {
    $_SESSION['error_msg'] = 'Database error.';
    header('Location: /Implose.gg-src/pages/admin/report.php');
    exit();
}

$report = mysqli_fetch_assoc($report_result);

if (!$report) {
    $_SESSION['error_msg'] = 'Report not found.';
    header('Location: /Implose.gg-src/pages/admin/report.php');
    exit();
}


// ACTION: dismiss - mark report as rejected
if ($action == 'dismiss') {
    $dismiss_sql = "UPDATE REPORT_T SET report_status = 'rejected', reviewed_at = NOW(), reviewed_by = $admin_id WHERE report_id = $report_id";
    $ok = mysqli_query($conn, $dismiss_sql);

    if ($ok) {
        $_SESSION['success_msg'] = 'Report dismissed.';
    } else {
        $_SESSION['error_msg'] = 'Failed to dismiss report.';
    }
    header('Location: /Implose.gg-src/pages/admin/report.php');
    exit();
}


// ACTION: suspend - suspend user + delete message + resolve report
if ($action == 'suspend') {
    $msg_id = intval($report['reported_message_id']);

    if ($msg_id <= 0) {
        $_SESSION['error_msg'] = 'Message not found for suspension.';
        header('Location: /Implose.gg-src/pages/admin/report.php');
        exit();
    }

    $msg_sql = "SELECT sender_id FROM CHAT_MESSAGE_T WHERE message_id = $msg_id";
    $msg_result = mysqli_query($conn, $msg_sql);

    if (!$msg_result) {
        $_SESSION['error_msg'] = 'Database error.';
        header('Location: /Implose.gg-src/pages/admin/report.php');
        exit();
    }

    $msg = mysqli_fetch_assoc($msg_result);

    if (!$msg) {
        $_SESSION['error_msg'] = 'Message not found.';
        header('Location: /Implose.gg-src/pages/admin/report.php');
        exit();
    }

    $sender_id = intval($msg['sender_id']);

    $flag_sql = "UPDATE CHAT_MESSAGE_T SET is_deleted = 1 WHERE message_id = $msg_id";
    $flag_ok = mysqli_query($conn, $flag_sql);

    $sus_sql = "UPDATE USER_T SET account_status = 'suspended', updated_at = NOW() WHERE user_id = $sender_id";
    $sus_ok = mysqli_query($conn, $sus_sql);

    if ($flag_ok && $sus_ok) {
        $resolve_sql = "UPDATE REPORT_T SET report_status = 'resolved', reviewed_at = NOW(), reviewed_by = $admin_id WHERE report_id = $report_id";
        mysqli_query($conn, $resolve_sql);
        $_SESSION['success_msg'] = 'User suspended and message deleted. Report resolved.';
    } else {
        $_SESSION['error_msg'] = 'Failed to suspend user.';
    }
    header('Location: /Implose.gg-src/pages/admin/report.php');
    exit();
}


// ACTION: delete - flag course as deleted + resolve report
if ($action == 'delete') {
    $course_id = intval($report['reported_marketplace_course_id']);

    if ($course_id <= 0) {
        $_SESSION['error_msg'] = 'No content found to delete.';
        header('Location: /Implose.gg-src/pages/admin/report.php');
        exit();
    }

    $del_sql = "UPDATE MARKETPLACE_COURSE_T SET is_deleted = 1 WHERE marketplace_course_id = $course_id";
    $content_ok = mysqli_query($conn, $del_sql);

    if ($content_ok) {
        $resolve_sql = "UPDATE REPORT_T SET report_status = 'resolved', reviewed_at = NOW(), reviewed_by = $admin_id WHERE report_id = $report_id";
        mysqli_query($conn, $resolve_sql);
        $_SESSION['success_msg'] = 'Content deleted. Report resolved.';
    } else {
        $_SESSION['error_msg'] = 'Failed to delete content.';
    }
    header('Location: /Implose.gg-src/pages/admin/report.php');
    exit();
}


$_SESSION['error_msg'] = 'Unknown action.';
header('Location: /Implose.gg-src/pages/admin/report.php');
exit();
?>
