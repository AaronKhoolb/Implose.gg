<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/quiz/delete_quiz.php
Description: delete_quiz actions file
            - Remove a quiz after ownership check, questions go with it through FK cascade.
First Written on: Monday, 07-Jul-2026
Edited on: Monday, 07-Jul-2026
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

// Ownership: quiz -> course -> creator
$owner_sql = "SELECT q.quiz_id, q.title, q.course_id
              FROM QUIZ_T q
              JOIN COURSE_T c ON q.course_id = c.course_id
              WHERE q.quiz_id = '$quiz_id'
                AND c.creator_id = '$user_id'";
$owner_result = mysqli_query($conn, $owner_sql);

if (!$owner_result || mysqli_num_rows($owner_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

$quiz = mysqli_fetch_assoc($owner_result);
$course_id = (int)$quiz['course_id'];

$delete_sql = "DELETE FROM QUIZ_T WHERE quiz_id = '$quiz_id'";

if (!mysqli_query($conn, $delete_sql)) {
    $_SESSION['edit_quiz_error'] = 'Could not delete this quiz. Please try again.';
    header('Location: /Implose.gg-src/pages/user/game/quiz/edit_quiz.php?quiz_id=' . $quiz_id);
    exit();
}

add_system_log($conn, $user_id, 'Delete Quiz', "User deleted quiz #$quiz_id ($quiz[title]) from course #$course_id.");

$_SESSION['edit_course_success'] = 'Quiz deleted.';
header('Location: /Implose.gg-src/pages/user/game/manage_course.php?course_id=' . $course_id);
exit();
