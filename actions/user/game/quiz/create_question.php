<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/quiz/create_question.php
Description: create_question actions file
            - Insert a blank MCQ row under a quiz and open it in the inline editor.
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

$quiz_id = (int)($_POST['quiz_id'] ?? 0);
$user_id = $_SESSION['user_id'];

$back_url = '/Implose.gg-src/pages/user/game/quiz/edit_quiz.php?quiz_id=' . $quiz_id;

// make sure the quiz belongs to this user
$owner_sql = "SELECT q.quiz_id
              FROM QUIZ_T q
              JOIN COURSE_T c ON q.course_id = c.course_id
              WHERE q.quiz_id = '$quiz_id'
                AND c.creator_id = '$user_id'";
$owner_result = mysqli_query($conn, $owner_sql);

if (!$owner_result || mysqli_num_rows($owner_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

// insert a blank mcq first, user fills it in on the edit page
$insert_sql = "INSERT INTO QUESTION_T (quiz_id, question_text, question_type, correct_option, marks, time_limit, created_at, updated_at)
                VALUES ('$quiz_id', 'New question', 'single_choice', 'a', 10, 20, NOW(), NOW())";

if (!mysqli_query($conn, $insert_sql)) {
    $_SESSION['edit_quiz_error'] = 'Failed to add question. Please try again.';
    header('Location: ' . $back_url);
    exit();
}

$new_question_id = mysqli_insert_id($conn);

add_system_log($conn, $user_id, 'Create Question', "User added question #$new_question_id to quiz #$quiz_id.");

// jump straight into editing the new question
header('Location: ' . $back_url . '&edit=' . $new_question_id . '#q-' . $new_question_id);
exit();
