<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/quiz_room/end_quiz_room.php
Description: Host ends the session. Flips status -> finished.
            POST: room_code (string, required)
            Responds plain text "ok" or an error message.
First Written on: Thursday, 02-Jul-2026
Edited on: Thursday, 02-Jul-2026
*/
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

header('Content-Type: text/plain; charset=utf-8');

function reply_err($msg, $code = 400) {
    http_response_code($code);
    echo $msg;
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) reply_err('Not signed in.', 401);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply_err('POST required.', 405);

$room_code = strtoupper(trim($_POST['room_code'] ?? ''));
if (!preg_match('/^[A-Z0-9]{4,8}$/', $room_code)) reply_err('Invalid room code.');

$safe_code = mysqli_real_escape_string($conn, $room_code);

$room_sql = "SELECT room_id, host_id, status FROM QUIZ_ROOM_T WHERE room_code = '$safe_code' LIMIT 1";
$room_result = mysqli_query($conn, $room_sql);
if (!$room_result || mysqli_num_rows($room_result) !== 1) reply_err('Room not found.', 404);

$room = mysqli_fetch_assoc($room_result);
if ((int)$room['host_id'] !== $user_id) reply_err('Only the host can end the room.', 403);

if ($room['status'] === 'finished') {
    echo 'ok';
    exit();
}

$room_id = (int)$room['room_id'];
$end_sql = "UPDATE QUIZ_ROOM_T SET status = 'finished' WHERE room_id = '$room_id'";
mysqli_query($conn, $end_sql);

echo 'ok';
exit();
