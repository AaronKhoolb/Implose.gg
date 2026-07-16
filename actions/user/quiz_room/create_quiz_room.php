<?php
/*
Programmer Name: Max
Program Name: /actions/user/quiz_room/create_quiz_room.php
Description: Host creates a new quiz room. 
             Validates the quiz, generates a unique 6-character room code, 
             inserts a row into QUIZ_ROOM_T, and adds the host to QUIZ_ROOM_PARTICIPANT_T. 
             Redirects to the lobby on success.
First Written on: Tuesday, 30-Jun-2026
Edited on: Wednesday, 2-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

function back_with($url, $msg) {
    $sep = strpos($url, '?') === false ? '?' : '&';
    header('Location: ' . $url . $sep . 'message=' . urlencode($msg));
    exit();
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: /Implose.gg-src/pages/auth/sign_in.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Implose.gg-src/pages/user/host_room.php');
    exit();
}

$quiz_id = (int) ($_POST['quiz_id'] ?? 0);
if ($quiz_id <= 0) {
    back_with('/Implose.gg-src/pages/user/host_room.php', 'Pick a quiz first.');
}

// confirm quiz exists
$q_check = mysqli_query($conn, "SELECT quiz_id, course_id FROM QUIZ_T WHERE quiz_id = '$quiz_id' LIMIT 1");
if (!$q_check || mysqli_num_rows($q_check) !== 1) {
    back_with('/Implose.gg-src/pages/user/host_room.php', 'That quiz does not exist.');
}

// generate a unique 6-char code (A-Z0-9, no confusing chars)
function generate_room_code() {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $code;
}

$room_code = '';
$last_error = '';
for ($try = 0; $try < 10; $try++) {
    $candidate = generate_room_code();
    $safe = mysqli_real_escape_string($conn, $candidate);
    $exists = mysqli_query($conn, "SELECT room_id FROM QUIZ_ROOM_T WHERE room_code = '$safe' LIMIT 1");
    if (!$exists) {
        $last_error = mysqli_error($conn);
        break;
    }
    if (mysqli_num_rows($exists) === 0) {
        $room_code = $candidate;
        break;
    }
}
if ($room_code === '') {
    $detail = $last_error !== '' ? ' (' . $last_error . ')' : '';
    back_with('/Implose.gg-src/pages/user/host_room.php', 'Could not generate a room code. Try again.' . $detail);
}

$auto_start = 60;
$now_sql       = "NOW()";
$starts_at_sql = "DATE_ADD(NOW(), INTERVAL $auto_start SECOND)";

$insert_room = "INSERT INTO QUIZ_ROOM_T
                (room_code, quiz_id, host_id, status, auto_start_seconds, starts_at, created_at)
                VALUES ('$room_code', '$quiz_id', '$user_id', 'waiting',
                        '$auto_start', $starts_at_sql, $now_sql)";

if (!mysqli_query($conn, $insert_room)) {
    back_with('/Implose.gg-src/pages/user/host_room.php',
              'Could not create the room: ' . mysqli_error($conn));
}

$room_id = (int) mysqli_insert_id($conn);

// host joins their own room
mysqli_query($conn,
    "INSERT INTO QUIZ_ROOM_PARTICIPANT_T (room_id, user_id, is_host, joined_at)
     VALUES ('$room_id', '$user_id', 1, NOW())");

header('Location: /Implose.gg-src/pages/user/room_lobby.php?room_code=' . urlencode($room_code));
exit();
?>
