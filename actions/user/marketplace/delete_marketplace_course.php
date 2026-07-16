<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/user/marketplace/delete_marketplace_course.php
Description: unpublish/remove marketplace course backend
First Written on: Friday, 27-Jun-2026
Edited on: Friday, 3-July-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$user_id = $_SESSION['user_id'];
$marketplace_course_id = $_POST['marketplace_course_id'];

$get_course_sql = "SELECT title, thumbnail_path, course_materials FROM MARKETPLACE_COURSE_T WHERE marketplace_course_id = '$marketplace_course_id' AND creator_id = '$user_id'";
$course = mysqli_fetch_assoc(mysqli_query($conn, $get_course_sql));

$delete_questions_sql = "DELETE FROM MARKETPLACE_QUESTION_T WHERE marketplace_quiz_id IN (SELECT marketplace_quiz_id FROM MARKETPLACE_QUIZ_T WHERE marketplace_course_id = '$marketplace_course_id')";
mysqli_query($conn, $delete_questions_sql);

$delete_quizzes_sql = "DELETE FROM MARKETPLACE_QUIZ_T WHERE marketplace_course_id = '$marketplace_course_id'";
mysqli_query($conn, $delete_quizzes_sql);

$delete_course_sql = "DELETE FROM MARKETPLACE_COURSE_T WHERE marketplace_course_id = '$marketplace_course_id' AND creator_id = '$user_id'";
mysqli_query($conn, $delete_course_sql);

// Delete course thumbnail & course materials
$doc_root = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/';
unlink($doc_root . $course['thumbnail_path']);
unlink($doc_root . $course['course_materials']);

add_system_log($conn, $user_id, 'Unpublish Course', "User unpublished marketplace course #$marketplace_course_id ({$course['title']}).");

header('Location: /Implose.gg-src/pages/user/marketplace/my_publish.php');
exit();

?>
