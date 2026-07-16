<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/quiz/delete_question.php
Description: delete_question actions file
            - Remove a question from a quiz after ownership check.
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

$question_id = (int)($_POST['question_id'] ?? 0);
$quiz_id     = (int)($_POST['quiz_id'] ?? 0);
$user_id     = $_SESSION['user_id'];

$back_url = '/Implose.gg-src/pages/user/game/quiz/edit_quiz.php?quiz_id=' . $quiz_id;

// Ownership: question -> quiz -> course -> creator
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

$delete_sql = "DELETE FROM QUESTION_T WHERE question_id = '$question_id'";

if (!mysqli_query($conn, $delete_sql)) {
    // probably a report still references this question
    $_SESSION['edit_quiz_error'] = 'Could not delete this question. It may be referenced by an existing report.';
    header('Location: ' . $back_url);
    exit();
}

add_system_log($conn, $user_id, 'Delete Question', "User deleted question #$question_id from quiz #$quiz_id.");

$_SESSION['edit_quiz_success'] = 'Question deleted.';
header('Location: ' . $back_url);
exit();
