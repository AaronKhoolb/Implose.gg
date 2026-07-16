<?php
/*
Programmer Name: Chong Jun Yoong
Program Name: /actions/user/marketplace/report_course.php
Description: Receives a report reason for a marketplace course and stores it in the REPORT_T table.
First Written on: Friday, 3-Jul-2026
Edited on: Friday, 3-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to report.']);
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$course_id = isset($_POST['marketplace_course_id']) ? (int) $_POST['marketplace_course_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if ($course_id <= 0 || empty($reason)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid course or missing reason.']);
    exit();
}

$reason_escaped = mysqli_real_escape_string($conn, $reason);

// prevent duplicate pending reports from the same user
$check_sql = "SELECT report_id FROM REPORT_T WHERE reporter_id = '$user_id' AND reported_marketplace_course_id = '$course_id' AND report_status = 'pending'";
$check_result = mysqli_query($conn, $check_sql);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'You have already reported this course. Please wait for an admin to review it.']);
    exit();
}

$sql = "INSERT INTO REPORT_T (reporter_id, reported_marketplace_course_id, reason, report_status, created_at) VALUES ('$user_id', '$course_id', '$reason_escaped', 'pending', NOW())";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Report submitted successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to submit report. Please try again later.']);
}
?>
