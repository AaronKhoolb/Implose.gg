<?php
/*
Programmer Name: Chong Jun Yoong
Program Name: /actions/user/chat/report_message.php
Description: Receives a report reason for a specific chat message and stores it in the REPORT_T table.
First Written on: Thursday, 18-Jun-2026
Edited on: Thursday, 18-Jun-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "You must be logged in.";
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$message_id = isset($_POST['message_id']) ? (int) $_POST['message_id'] : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if ($message_id <= 0 || empty($reason)) {
    echo "Invalid input.";
    exit();
}

$reason_escaped = mysqli_real_escape_string($conn, $reason);

$sql = "INSERT INTO REPORT_T (reporter_id, reported_message_id, reason, report_status, created_at) VALUES ('$user_id', '$message_id', '$reason_escaped', 'pending', NOW())";
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "ok";
} else {
    echo "Failed to report: " . mysqli_error($conn);
}
?>
