<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /api/game/quiz_room/room_leaderboard.php
Description: room_leaderboard api file
            - Returns ranked players for a room with points, correct count and progress.
            - Points recomputed from the saved records, same formula as the player screen.
            GET: room_code
First Written on: Thursday, 02-Jul-2026
Edited on: Thursday, 02-Jul-2026
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

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) out(['error' => 'not_signed_in'], 401);

$room_code = strtoupper(trim($_GET['room_code'] ?? ''));
if (!preg_match('/^[A-Z0-9]{4,8}$/', $room_code)) out(['error' => 'bad_code'], 400);

$safe_code = mysqli_real_escape_string($conn, $room_code);

$room_sql = "SELECT room_id, quiz_id, host_id, status FROM QUIZ_ROOM_T WHERE room_code = '$safe_code' LIMIT 1";
$room_result = mysqli_query($conn, $room_sql);
if (!$room_result || mysqli_num_rows($room_result) !== 1) out(['error' => 'not_found'], 404);

$room = mysqli_fetch_assoc($room_result);
$room_id = (int)$room['room_id'];
$quiz_id = (int)$room['quiz_id'];

// host or participant only
if ((int)$room['host_id'] !== $user_id) {
    $participant_sql = "SELECT participant_id FROM QUIZ_ROOM_PARTICIPANT_T
                        WHERE room_id = '$room_id' AND user_id = '$user_id' LIMIT 1";
    $participant_result = mysqli_query($conn, $participant_sql);
    if (!$participant_result || mysqli_num_rows($participant_result) !== 1) {
        out(['error' => 'not_allowed'], 403);
    }
}

$total_sql = "SELECT COUNT(*) AS total FROM QUESTION_T WHERE quiz_id = '$quiz_id'";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_questions = (int)$total_row['total'];

// one row per player: answered count, correct count, arcade points
$players = [];
$players_sql = "SELECT u.user_id, u.username, u.avatar_path,
                       COUNT(r.learning_record_id) AS answered,
                       COALESCE(SUM(r.is_correct), 0) AS correct,
                       COALESCE(SUM(CASE WHEN r.is_correct = 1
                            THEN ROUND(q.marks * (2 - r.response_time / q.time_limit) * 5)
                            ELSE 0 END), 0) AS points
                FROM QUIZ_ROOM_PARTICIPANT_T p
                JOIN USER_T u ON u.user_id = p.user_id
                LEFT JOIN QUIZ_LEARNING_RECORD_T r ON r.user_id = p.user_id AND r.room_id = '$room_id'
                LEFT JOIN QUESTION_T q ON q.question_id = r.question_id
                WHERE p.room_id = '$room_id' AND p.is_host = 0
                GROUP BY u.user_id, u.username, u.avatar_path
                ORDER BY points DESC, correct DESC, u.username ASC";
$players_result = mysqli_query($conn, $players_sql);

if ($players_result) {
    while ($row = mysqli_fetch_assoc($players_result)) {
        $players[] = [
            'user_id' => (int)$row['user_id'],
            'name' => $row['username'],
            'avatar_path' => $row['avatar_path'],
            'answered' => (int)$row['answered'],
            'correct' => (int)$row['correct'],
            'points' => (int)$row['points'],
        ];
    }
}

out([
    'status' => $room['status'],
    'is_host' => (int)$room['host_id'] === $user_id,
    'total_questions' => $total_questions,
    'players' => $players,
]);
