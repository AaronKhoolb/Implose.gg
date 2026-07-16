<?php
/*
Programmer Name: Max
Program Name: /actions/user/quiz_room/join_quiz_room.php
Description: User joins an existing quiz room by entering a room code from the dashboard. 
             Validates the code, checks that the room is still open, and adds the user to 
             QUIZ_ROOM_PARTICIPANT_T. Redirects to the lobby on success.
First Written on: Tuesday, 30-Jun-2026
Edited on: Wednesday, 2-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

function back_with($msg) {
    header('Location: /Implose.gg-src/pages/user/index.php?message=' . urlencode($msg));
    exit();
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: /Implose.gg-src/pages/auth/sign_in.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back_with('Invalid request.');
}

$room_code = strtoupper(trim($_POST['room_code'] ?? ''));
if ($room_code === '' || !preg_match('/^[A-Z0-9]{4,8}$/', $room_code)) {
    back_with('Enter a valid room code.');
}

$safe_code = mysqli_real_escape_string($conn, $room_code);

$room_q = mysqli_query($conn,
    "SELECT room_id, status FROM QUIZ_ROOM_T WHERE room_code = '$safe_code' LIMIT 1");
if (!$room_q || mysqli_num_rows($room_q) !== 1) {
    back_with('No room found for that code.');
}

$room = mysqli_fetch_assoc($room_q);
$room_id = (int) $room['room_id'];

if ($room['status'] === 'finished' || $room['status'] === 'cancelled') {
    back_with('That room is no longer open.');
}

// INSERT IGNORE so re-joining after a refresh does not error
mysqli_query($conn,
    "INSERT IGNORE INTO QUIZ_ROOM_PARTICIPANT_T (room_id, user_id, is_host, joined_at)
     VALUES ('$room_id', '$user_id', 0, NOW())");

header('Location: /Implose.gg-src/pages/user/room_lobby.php?room_code=' . urlencode($room_code));
exit();
?>
