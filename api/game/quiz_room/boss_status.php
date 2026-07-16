<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /api/game/quiz_room/boss_status.php
Description: boss_status api file
            - Polled by the boss battle player + host pages every 2 seconds.
            - Replays each player's answers in order to work out damage dealt,
              hearts left and KO state (answers after a KO deal no damage).
            - Shared boss HP pool for the whole party, plus the battle outcome.
            GET: room_code
First Written on: Monday, 07-Jul-2026
Edited on: Monday, 07-Jul-2026
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

$room_sql = "SELECT room_id, quiz_id, host_id, status,
                    TIMESTAMPDIFF(SECOND, started_at, NOW()) AS elapsed
             FROM QUIZ_ROOM_T WHERE room_code = '$safe_code' LIMIT 1";
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

$hearts_max = 3;

$players = [];
$player_count = 0;
$total_damage = 0;
$all_ko = true;
$all_done = true;

$list_sql = "SELECT u.user_id, u.username, u.avatar_path
             FROM QUIZ_ROOM_PARTICIPANT_T p
             JOIN USER_T u ON u.user_id = p.user_id
             WHERE p.room_id = '$room_id' AND p.is_host = 0
             ORDER BY p.joined_at ASC";
$list_result = mysqli_query($conn, $list_sql);

while ($u = mysqli_fetch_assoc($list_result)) {
    $player_count++;
    $pid = (int)$u['user_id'];

    // replay this player's answers in the order they happened
    $records_sql = "SELECT r.is_correct, r.response_time, q.marks, q.time_limit
                    FROM QUIZ_LEARNING_RECORD_T r
                    JOIN QUESTION_T q ON q.question_id = r.question_id
                    WHERE r.room_id = '$room_id' AND r.user_id = '$pid'
                    ORDER BY r.answered_at ASC, r.learning_record_id ASC";
    $records_result = mysqli_query($conn, $records_sql);

    $hearts = $hearts_max;
    $answered = 0;
    $correct = 0;
    $dmg = 0;
    $score = 0;
    $streak = 0;

    while ($r = mysqli_fetch_assoc($records_result)) {
        $answered++;

        if ((int)$r['is_correct'] === 1) {
            $correct++;

            // knocked out players keep answering but deal no damage
            if ($hearts > 0) {
                $dmg++;
                $streak++;

                // boss battles run on 3/4 time and double points
                $boss_time = max(round((int)$r['time_limit'] * 0.75), 5);
                $score += round((int)$r['marks'] * (2 - (float)$r['response_time'] / $boss_time) * 10);
            }
        } else {
            $streak = 0;
            if ($hearts > 0) {
                $hearts--;
            }
        }
    }

    $ko = $hearts <= 0;
    $total_damage += $dmg;

    if (!$ko) $all_ko = false;
    if (!$ko && $answered < $total_questions) $all_done = false;

    $players[] = [
        'user_id' => $pid,
        'name' => $u['username'],
        'avatar_path' => $u['avatar_path'],
        'answered' => $answered,
        'correct' => $correct,
        'dmg' => $dmg,
        'hearts' => $hearts,
        'ko' => $ko,
        'streak' => $streak,
        'score' => $score,
    ];
}

// shared HP pool, tuned so an average party can bring it down
$hp_max = max((int)ceil($player_count * $total_questions * 0.3), 1);
$boss_hp = max($hp_max - $total_damage, 0);

if ($boss_hp <= 0) {
    $outcome = 'victory';
} else if ($player_count > 0 && ($all_ko || $all_done)) {
    $outcome = 'defeat';
} else if ($room['status'] === 'finished') {
    $outcome = 'defeat';
} else {
    $outcome = 'ongoing';
}

$elapsed = $room['elapsed'] !== null ? max((int)$room['elapsed'], 0) : 0;

out([
    'status' => $room['status'],
    'is_host' => (int)$room['host_id'] === $user_id,
    'total_questions' => $total_questions,
    'hearts_max' => $hearts_max,
    'hp_max' => $hp_max,
    'damage' => $total_damage,
    'boss_hp' => $boss_hp,
    'outcome' => $outcome,
    'elapsed' => $elapsed,
    'players' => $players,
]);
