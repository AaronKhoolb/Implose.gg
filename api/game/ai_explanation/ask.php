<?php
/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /api/game/ai_explanation/ask.php
Description: Ask the AI tutor for a reply and return it as JSON
            - mode=explain: first explanation
            - mode=followup: student follow up question
            - returns: output HTML response
First Written on: Thursday, 02-Jul-2026
Edited on: Saturday, 04-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/ai_engine.php');
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');


// check got prompt message?
if (!isset($_SESSION['ai_explain']['messages'])) {
    echo json_encode(['error' => 'No question loaded. Please go back and open the explanation page again.']);
    exit();
}

$mode = $_POST['mode'];

// a follow-up question becomes part of the conversation
if ($mode === 'followup') {
    $_SESSION['ai_explain']['messages'][] = ['role' => 'user', 'content' => $_POST['message']];
}

$settings_sql = "SELECT * FROM AI_SETTING_T WHERE ai_setting_id = 1";
$settings_result = mysqli_query($conn, $settings_sql);
$settings = mysqli_fetch_assoc($settings_result);


// use ai_chat to send the full conversation and wait for the whole reply
$response = ai_chat($settings, $_SESSION['ai_explain']['messages']);
if (isset($response['choices'][0]['message']['content'])) {
    $reply = trim($response['choices'][0]['message']['content']);
} else {
    $reply = '';
}

if ($reply === '') {
    echo json_encode(['error' => 'The AI tutor is unavailable right now. Please try again.']);
    exit();
}

// remember the reply so follow-up questions know the whole conversation
$_SESSION['ai_explain']['messages'][] = ['role' => 'assistant', 'content' => $reply];

// save the main explanation to db
$record_id = $_SESSION['ai_explain']['record_id'];
if ($record_id && $mode === 'explain') {
    $escaped_reply = mysqli_real_escape_string($conn, $reply);
    $update_explanation_sql = "UPDATE QUIZ_LEARNING_RECORD_T SET ai_explanation = '$escaped_reply' WHERE learning_record_id = '$record_id'";
    mysqli_query($conn, $update_explanation_sql);
}

echo json_encode(['reply_html' => $reply]);
