<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/quiz/save_attempt.php
Description: save_attempt actions file
            - Saves every answer of a finished quiz run into QUIZ_LEARNING_RECORD_T.
            - Correctness and marks are recomputed here so the browser cannot fake them.
First Written on: Thursday, 02-Jul-2026
Edited on: Thursday, 02-Jul-2026
*/
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

header('Content-Type: application/json');

function fail($msg) {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    fail('Not signed in.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Invalid request.');
}

$quiz_id = (int)($_POST['quiz_id'] ?? 0);
$answers = json_decode($_POST['answers'] ?? '', true);

if ($quiz_id <= 0 || !is_array($answers) || count($answers) === 0) {
    fail('Nothing to save.');
}

// live mode: tie the records to the room so the leaderboard can find them
$room_code = strtoupper(trim($_POST['room_code'] ?? ''));
$room_id = 0;

if ($room_code !== '') {
    $safe_code = mysqli_real_escape_string($conn, $room_code);
    $room_sql = "SELECT room_id, quiz_id FROM QUIZ_ROOM_T WHERE room_code = '$safe_code' LIMIT 1";
    $room_result = mysqli_query($conn, $room_sql);

    if (!$room_result || mysqli_num_rows($room_result) !== 1) {
        fail('Room not found.');
    }

    $room = mysqli_fetch_assoc($room_result);
    if ((int)$room['quiz_id'] !== $quiz_id) {
        fail('Room and quiz do not match.');
    }

    $room_id = (int)$room['room_id'];

    $participant_sql = "SELECT participant_id FROM QUIZ_ROOM_PARTICIPANT_T
                        WHERE room_id = '$room_id' AND user_id = '$user_id' LIMIT 1";
    $participant_result = mysqli_query($conn, $participant_sql);

    if (!$participant_result || mysqli_num_rows($participant_result) !== 1) {
        fail('You are not in this room.');
    }
}

$saved = 0;

foreach ($answers as $a) {
    $question_id   = (int)($a['question_id'] ?? 0);
    $selected      = $a['selected_option'] ?? null;
    $text_answer   = $a['text_answer'] ?? null;
    $response_time = (float)($a['response_time'] ?? 0);

    // only a-d counts as a real option
    if ($selected !== null && !in_array($selected, ['a', 'b', 'c', 'd'], true)) {
        $selected = null;
    }

    // the question must belong to the quiz being saved
    $q_sql = "SELECT question_type, correct_option, correct_text_answer, marks
                FROM QUESTION_T WHERE question_id = '$question_id' AND quiz_id = '$quiz_id'";
    $q_result = mysqli_query($conn, $q_sql);

    if (!$q_result || mysqli_num_rows($q_result) !== 1) {
        continue;
    }

    $q = mysqli_fetch_assoc($q_result);

    // recompute correctness server side, dont trust the browser
    if ($q['question_type'] === 'single_choice') {
        $is_correct = ($selected !== null && $selected === $q['correct_option']) ? 1 : 0;
    } else {
        $is_correct = ($text_answer !== null
            && strtolower(trim($text_answer)) === strtolower(trim($q['correct_text_answer']))) ? 1 : 0;
    }

    $marks_earned = $is_correct ? (int)$q['marks'] : 0;

    // one answer per question per room, so replays cannot double score
    if ($room_id > 0) {
        $dup_sql = "SELECT learning_record_id FROM QUIZ_LEARNING_RECORD_T
                    WHERE room_id = '$room_id' AND user_id = '$user_id' AND question_id = '$question_id' LIMIT 1";
        $dup_result = mysqli_query($conn, $dup_sql);
        if ($dup_result && mysqli_num_rows($dup_result) > 0) {
            continue;
        }
    }

    $selected_esc = $selected === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $selected) . "'";
    $text_esc     = $text_answer === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $text_answer) . "'";
    $room_esc     = $room_id > 0 ? "'$room_id'" : 'NULL';

    $insert_sql = "INSERT INTO QUIZ_LEARNING_RECORD_T
                    (user_id, room_id, question_id, selected_option, text_answer, is_correct, marks_earned, response_time, answered_at)
                    VALUES ('$user_id', $room_esc, '$question_id', $selected_esc, $text_esc, '$is_correct', '$marks_earned', '$response_time', NOW())";

    if (mysqli_query($conn, $insert_sql)) {
        $saved++;
    }
}

if ($saved === 0) {
    fail('Failed to save the attempt.');
}

// A row landed in QUIZ_LEARNING_RECORD_T — the user just answered their
// very first quiz question if this achievement is still locked.
if (function_exists('award_achievement')) {
    award_achievement($conn, $user_id, 'FIRST_QUESTION_ANSWERED');
    if (function_exists('check_points_milestones')) {
        check_points_milestones($conn, $user_id);
    }
}

echo json_encode(['success' => true, 'saved' => $saved]);
