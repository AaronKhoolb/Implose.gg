<?php
/*
Programmer Name: Max
Program Name: /actions/user/quiz_room/start_quiz_room.php
Description: When the host clicks Start Quiz Now, this endpoint flips the room status 
             in QUIZ_ROOM_T from waiting to in_progress so all clients redirect to the 
             live quiz page on their next poll. Only the host of the room is allowed 
             to call this endpoint.
First Written on: Tuesday, 30-Jun-2026
Edited on: Wednesday, 2-Jul-2026
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

$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) reply_err('Not signed in.', 401);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply_err('POST required.', 405);

$room_code = strtoupper(trim($_POST['room_code'] ?? ''));
if (!preg_match('/^[A-Z0-9]{4,8}$/', $room_code)) reply_err('Invalid room code.');

$safe_code = mysqli_real_escape_string($conn, $room_code);

$room_q = mysqli_query($conn,
    "SELECT room_id, host_id, status FROM QUIZ_ROOM_T WHERE room_code = '$safe_code' LIMIT 1");
if (!$room_q || mysqli_num_rows($room_q) !== 1) reply_err('Room not found.', 404);

$room = mysqli_fetch_assoc($room_q);
if ((int) $room['host_id'] !== $user_id) reply_err('Only the host can start the room.', 403);

if ($room['status'] !== 'waiting') {
    echo 'ok';
    exit();
}

$room_id = (int) $room['room_id'];
mysqli_query($conn,
    "UPDATE QUIZ_ROOM_T
        SET status = 'in_progress',
            started_at = NOW()
      WHERE room_id = '$room_id' AND status = 'waiting'");

echo 'ok';
exit();
?>
