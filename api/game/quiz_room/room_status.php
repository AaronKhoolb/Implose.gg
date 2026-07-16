<?php
/*
Programmer Name: Max
Program Name: /api/game/quiz_room/room_status.php
Description: JSON endpoint polled by the room lobby page every 2 seconds. 
             Returns the room status, auto-start countdown, and the current 
             participant list. Also performs auto-start: if the status is still 
             waiting but the timer has passed, it flips to in_progress so the 
             quiz starts even if the host left.
First Written on: Tuesday, 30-Jun-2026
Edited on: Wednesday, 2-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

function out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) out(['error' => 'not_signed_in'], 401);

$room_code = strtoupper(trim($_GET['room_code'] ?? ''));
if (!preg_match('/^[A-Z0-9]{4,8}$/', $room_code)) {
    out(['error' => 'bad_code'], 400);
}

$safe_code = mysqli_real_escape_string($conn, $room_code);

$room_q = mysqli_query($conn,
    "SELECT room_id, room_code, quiz_id, host_id, status,
            auto_start_seconds, starts_at, started_at,
            UNIX_TIMESTAMP(starts_at) - UNIX_TIMESTAMP(NOW()) AS seconds_remaining
       FROM QUIZ_ROOM_T
      WHERE room_code = '$safe_code'
      LIMIT 1");

if (!$room_q || mysqli_num_rows($room_q) !== 1) out(['error' => 'not_found'], 404);

$room = mysqli_fetch_assoc($room_q);
$room_id = (int) $room['room_id'];

// auto-start: any client whose poll hits us past starts_at flips the room.
// First poll past the deadline does the update; later ones see in_progress already.
if ($room['status'] === 'waiting' && (int) $room['seconds_remaining'] <= 0) {
    mysqli_query($conn,
        "UPDATE QUIZ_ROOM_T
            SET status = 'in_progress', started_at = NOW()
          WHERE room_id = '$room_id' AND status = 'waiting'");
    $room['status'] = 'in_progress';
    $room['started_at'] = date('Y-m-d H:i:s');
}

// participant list (with names)
$participants = [];
$plist_q = mysqli_query($conn,
    "SELECT p.user_id, p.is_host, p.joined_at, u.avatar_path,
            COALESCE(NULLIF(u.username, ''), CONCAT('User #', u.user_id)) AS display_name
       FROM QUIZ_ROOM_PARTICIPANT_T p
       JOIN USER_T u ON u.user_id = p.user_id
      WHERE p.room_id = '$room_id'
      ORDER BY p.is_host DESC, p.joined_at ASC");

if ($plist_q) {
    while ($row = mysqli_fetch_assoc($plist_q)) {
        $participants[] = [
            'user_id'      => (int) $row['user_id'],
            'is_host'      => (int) $row['is_host'] === 1,
            'display_name' => $row['display_name'],
            'avatar_path'  => $row['avatar_path'],
        ];
    }
}

$seconds_remaining = (int) $room['seconds_remaining'];
if ($seconds_remaining < 0) $seconds_remaining = 0;

out([
    'room_code'         => $room['room_code'],
    'quiz_id'           => (int) $room['quiz_id'],
    'host_id'           => (int) $room['host_id'],
    'is_host'           => ((int) $room['host_id'] === $user_id),
    'status'            => $room['status'],
    'seconds_remaining' => $seconds_remaining,
    'participants'      => $participants,
]);
?>
