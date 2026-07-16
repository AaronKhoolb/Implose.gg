<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/quiz/edit_quiz.php
Description: edit_quiz actions file
            - update an existing quiz's title, description, and level_number.
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

$quiz_id      = (int)($_POST['quiz_id'] ?? 0);
$title        = trim($_POST['title'] ?? '');
$description  = trim($_POST['description'] ?? '');
$level_number = (int)($_POST['level_number'] ?? 0);
$user_id      = $_SESSION['user_id'];

$redirect_url = '/Implose.gg-src/pages/user/game/quiz/edit_quiz.php?quiz_id=' . $quiz_id;

function redirect_with_error($msg) {
    global $redirect_url;
    $_SESSION['edit_quiz_error'] = $msg;
    header('Location: ' . $redirect_url);
    exit();
}

// quiz must exist and belong to this user
$quiz_check_sql = "SELECT q.quiz_id, q.course_id
                   FROM QUIZ_T q
                   JOIN COURSE_T c ON q.course_id = c.course_id
                   WHERE q.quiz_id = '$quiz_id'
                     AND c.creator_id = '$user_id'";
$quiz_check_result = mysqli_query($conn, $quiz_check_sql);

if (!$quiz_check_result || mysqli_num_rows($quiz_check_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

$quiz_row  = mysqli_fetch_assoc($quiz_check_result);
$course_id = (int)$quiz_row['course_id'];

// Validation
if ($title === '') {
    redirect_with_error('Level title cannot be empty.');
}

if (strlen($title) < 2) {
    redirect_with_error('Level title must be at least 2 characters.');
}

if ($level_number < 1) {
    redirect_with_error('Level number must be 1 or higher.');
}

// level number cannot clash with another quiz in this course
$level_clash_sql = "SELECT quiz_id FROM QUIZ_T
                    WHERE course_id = '$course_id'
                      AND level_number = '$level_number'
                      AND quiz_id <> '$quiz_id'";
$level_clash_result = mysqli_query($conn, $level_clash_sql);
if ($level_clash_result && mysqli_num_rows($level_clash_result) > 0) {
    redirect_with_error("Level number $level_number is already used in this course.");
}

$title_esc       = mysqli_real_escape_string($conn, $title);
$description_esc = mysqli_real_escape_string($conn, $description);

$update_sql = "UPDATE QUIZ_T
               SET title = '$title_esc',
                   description = '$description_esc',
                   level_number = '$level_number',
                   updated_at = NOW()
               WHERE quiz_id = '$quiz_id'";

if (!mysqli_query($conn, $update_sql)) {
    redirect_with_error('Failed to update quiz. Please try again.');
}

add_system_log($conn, $user_id, 'Edit Quiz', "User edited quiz #$quiz_id ($title).");

$_SESSION['edit_quiz_success'] = "Quiz details saved.";
header('Location: ' . $redirect_url);
exit();
