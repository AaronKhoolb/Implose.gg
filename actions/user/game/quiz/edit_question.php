<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/quiz/edit_question.php
Description: edit_question actions file
            - Update a question's text, type, options/answer, marks and time limit.
First Written on: Thursday, 2-Jul-2026
Edited on: Thursday, 2-Jul-2026
*/
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

$question_id   = (int)($_POST['question_id'] ?? 0);
$quiz_id       = (int)($_POST['quiz_id'] ?? 0);
$question_text = trim($_POST['question_text'] ?? '');
$question_type = $_POST['question_type'] ?? '';
$marks         = (int)($_POST['marks'] ?? 0);
$time_limit    = (int)($_POST['time_limit'] ?? 0);
$user_id       = $_SESSION['user_id'];

$back_url = '/Implose.gg-src/pages/user/game/quiz/edit_quiz.php?quiz_id=' . $quiz_id;
$edit_url = $back_url . '&edit=' . $question_id . '#q-' . $question_id;

function redirect_with_error($msg) {
    global $edit_url;
    $_SESSION['edit_quiz_error'] = $msg;
    header('Location: ' . $edit_url);
    exit();
}

// question must belong to a quiz under this user's course
$owner_sql = "SELECT qn.question_id
              FROM QUESTION_T qn
              JOIN QUIZ_T q ON qn.quiz_id = q.quiz_id
              JOIN COURSE_T c ON q.course_id = c.course_id
              WHERE qn.question_id = '$question_id'
                AND qn.quiz_id = '$quiz_id'
                AND c.creator_id = '$user_id'";
$owner_result = mysqli_query($conn, $owner_sql);

if (!$owner_result || mysqli_num_rows($owner_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

// Validation
if ($question_text === '' || strlen($question_text) < 2) {
    redirect_with_error('Question text must be at least 2 characters.');
}

if ($question_type !== 'single_choice' && $question_type !== 'text_input') {
    redirect_with_error('Please choose a question type.');
}

if ($marks < 1) {
    redirect_with_error('Points must be at least 1.');
}

if ($time_limit < 1) {
    redirect_with_error('Time limit must be at least 1 second.');
}

$question_text_esc = mysqli_real_escape_string($conn, $question_text);

if ($question_type === 'single_choice') {
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct  = strtolower(trim($_POST['correct_option'] ?? ''));

    if (!in_array($correct, ['a', 'b', 'c', 'd'])) {
        redirect_with_error('Pick which option is the correct answer.');
    }

    $correct_value = '';
    if ($correct === 'a') $correct_value = $option_a;
    if ($correct === 'b') $correct_value = $option_b;
    if ($correct === 'c') $correct_value = $option_c;
    if ($correct === 'd') $correct_value = $option_d;

    if ($correct_value === '') {
        redirect_with_error('The option marked correct cannot be empty.');
    }

    $filled = 0;
    if ($option_a !== '') $filled++;
    if ($option_b !== '') $filled++;
    if ($option_c !== '') $filled++;
    if ($option_d !== '') $filled++;

    if ($filled < 2) {
        redirect_with_error('Fill in at least two options.');
    }

    $opt_a = $option_a === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $option_a) . "'";
    $opt_b = $option_b === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $option_b) . "'";
    $opt_c = $option_c === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $option_c) . "'";
    $opt_d = $option_d === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $option_d) . "'";

    $update_sql = "UPDATE QUESTION_T SET
                        question_text = '$question_text_esc',
                        question_type = 'single_choice',
                        option_a = $opt_a,
                        option_b = $opt_b,
                        option_c = $opt_c,
                        option_d = $opt_d,
                        correct_option = '$correct',
                        correct_text_answer = NULL,
                        marks = '$marks',
                        time_limit = '$time_limit',
                        updated_at = NOW()
                    WHERE question_id = '$question_id'";
} else {
    $answer = trim($_POST['correct_text_answer'] ?? '');
    if ($answer === '') {
        redirect_with_error('Enter the accepted answer for the fill-in-the-blank.');
    }
    $answer_esc = mysqli_real_escape_string($conn, $answer);

    $update_sql = "UPDATE QUESTION_T SET
                        question_text = '$question_text_esc',
                        question_type = 'text_input',
                        option_a = NULL,
                        option_b = NULL,
                        option_c = NULL,
                        option_d = NULL,
                        correct_option = NULL,
                        correct_text_answer = '$answer_esc',
                        marks = '$marks',
                        time_limit = '$time_limit',
                        updated_at = NOW()
                    WHERE question_id = '$question_id'";
}

if (!mysqli_query($conn, $update_sql)) {
    redirect_with_error('Failed to save question. Please try again.');
}

add_system_log($conn, $user_id, 'Edit Question', "User saved question #$question_id in quiz #$quiz_id.");

$_SESSION['edit_quiz_success'] = 'Question saved.';
header('Location: ' . $back_url . '#q-' . $question_id);
exit();
