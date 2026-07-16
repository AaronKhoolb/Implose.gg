<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/quiz/create_quiz.php
Description: create_quiz actions file
            - Create a new quiz under a course with title and description.
First Written on: Tuesday, 30-Jun-2026
Edited on: Tuesday, 30-Jun-2026
*/
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Implose.gg-src/pages/user/game/create_course.php');
    exit();
}

$course_id   = (int)($_POST['course_id'] ?? 0);
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$user_id     = $_SESSION['user_id'];

$redirect_url = '/Implose.gg-src/pages/user/game/quiz/create_quiz.php?course_id=' . $course_id;

function redirect_with_error($msg) {
    global $redirect_url;
    $_SESSION['create_quiz_error'] = $msg;
    header('Location: ' . $redirect_url);
    exit();
}

$course_check_sql = "SELECT course_id FROM COURSE_T WHERE course_id = '$course_id' AND creator_id = '$user_id'";
$course_check_result = mysqli_query($conn, $course_check_sql);

if (!$course_check_result || mysqli_num_rows($course_check_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/create_course.php');
    exit();
}

// Validation
if ($title === '') {
    redirect_with_error('Quiz title cannot be empty.');
}

if (strlen($title) < 2) {
    redirect_with_error('Quiz title must be at least 2 characters.');
}

if (!preg_match('/[a-zA-Z0-9]/', $title)) {
    redirect_with_error('Quiz title must contain at least one letter or number.');
}

$level_sql = "SELECT COALESCE(MAX(level_number), 0) + 1 AS next_level FROM QUIZ_T WHERE course_id = '$course_id'";
$level_result = mysqli_query($conn, $level_sql);
$level_row = mysqli_fetch_assoc($level_result);
$level_number = (int)$level_row['next_level'];

$title_esc       = mysqli_real_escape_string($conn, $title);
$description_esc = mysqli_real_escape_string($conn, $description);

$insert_sql = "INSERT INTO QUIZ_T (course_id, level_number, title, description, created_at, updated_at)
                VALUES ('$course_id', '$level_number', '$title_esc', '$description_esc', NOW(), NOW())";

if (!mysqli_query($conn, $insert_sql)) {
    redirect_with_error('Failed to create quiz. Please try again.');
}

$new_quiz_id = mysqli_insert_id($conn);

add_system_log($conn, $user_id, 'Create Quiz', "User created quiz #$new_quiz_id ($title) under course #$course_id.");

$_SESSION['create_quiz_success'] = "Quiz \"$title\" created successfully.";
header('Location: ' . $redirect_url);
exit();
