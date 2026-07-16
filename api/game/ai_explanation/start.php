<?php
/*
Programmer Name: Mr. Ng Jiunn Chyn
Program Name: /api/game/ai_explanation/start.php
Description: Prime the AI tutor session for a single learning record
            - verifies the record belongs to the signed-in user
            - loads question, quiz, course + course-material PDF text
            - builds the tutor system prompt + attempt message
            - stores conversation state in $_SESSION['ai_explain']
            - if an ai_explanation was already saved for this record, returns it
              directly (cached: true) and seeds it into the conversation so
              follow-up questions still work
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/ai_engine.php');
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');


function fail($msg) {
    echo json_encode(['error' => $msg]);
    exit();
}


$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    fail('Please sign in to use the AI tutor.');
}

$record_id = (int)($_POST['record_id'] ?? 0);
if ($record_id <= 0) {
    fail('Missing record id.');
}


// ── Pull the record + question + quiz + course in one query ──
$sql = "SELECT
            lr.learning_record_id,
            lr.user_id,
            lr.selected_option,
            lr.text_answer,
            lr.is_correct,
            lr.ai_explanation,
            q.question_id,
            q.question_text,
            q.question_type,
            q.option_a, q.option_b, q.option_c, q.option_d,
            q.correct_option,
            q.correct_text_answer,
            qz.quiz_id,
            qz.level_number,
            qz.title       AS quiz_title,
            qz.description AS quiz_description,
            c.course_id,
            c.title        AS course_title,
            c.description  AS course_description,
            c.course_materials
        FROM QUIZ_LEARNING_RECORD_T lr
        JOIN QUESTION_T q  ON lr.question_id = q.question_id
        JOIN QUIZ_T     qz ON q.quiz_id      = qz.quiz_id
        JOIN COURSE_T   c  ON qz.course_id   = c.course_id
        WHERE lr.learning_record_id = '$record_id'
        LIMIT 1";

$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) !== 1) {
    fail('Question record not found.');
}

$row = mysqli_fetch_assoc($result);

// stop other users from priming the tutor with a record that isn't theirs
if ((int)$row['user_id'] !== $user_id) {
    fail('You do not have access to this record.');
}


// ── Build the tutor context ──
$course = [
    'title'       => $row['course_title'],
    'description' => $row['course_description']
];
$quiz = [
    'level_number' => $row['level_number'],
    'title'        => $row['quiz_title'],
    'description'  => $row['quiz_description']
];
$question = [
    'question_text'       => $row['question_text'],
    'question_type'       => $row['question_type'],
    'option_a'            => $row['option_a'],
    'option_b'            => $row['option_b'],
    'option_c'            => $row['option_c'],
    'option_d'            => $row['option_d'],
    'correct_option'      => $row['correct_option'],
    'correct_text_answer' => $row['correct_text_answer']
];

// pull the actual answer the student gave (option or free-text)
if ($row['question_type'] === 'single_choice') {
    $user_answer = $row['selected_option'] ?? '';
} else {
    $user_answer = $row['text_answer'] ?? '';
}

// pull PDF material text if the course has one attached; otherwise leave blank
$material_text = '';
if (!empty($row['course_materials'])) {
    $material_text = ai_extract_pdf_text($row['course_materials']);
}

$system_prompt   = ai_build_system_prompt($course, $quiz, $material_text);
$attempt_message = ai_build_attempt_message($question, $user_answer, (int)$row['is_correct']);


// ── Seed the session so ask.php can take over ──
$_SESSION['ai_explain'] = [
    'record_id' => $record_id,
    'messages'  => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user',   'content' => $attempt_message]
    ]
];


// ── If we've explained this record before, return the cached reply ──
// Also seed it into the conversation so follow-up questions have the full
// context of what the tutor said the first time round.
$cached = trim($row['ai_explanation'] ?? '');
if ($cached !== '') {
    $_SESSION['ai_explain']['messages'][] = [
        'role'    => 'assistant',
        'content' => $cached
    ];

    echo json_encode([
        'cached'     => true,
        'reply_html' => $cached
    ]);
    exit();
}

echo json_encode(['cached' => false]);
