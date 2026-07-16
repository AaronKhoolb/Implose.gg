<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/user/marketplace/fork_course.php
Description: fork marketplace course to user library
First Written on: Friday, 27-Jun-2026
Edited on: Saturday, 28-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$user_id = $_SESSION['user_id'];
$marketplace_course_id = $_POST['marketplace_course_id'];

// get the source marketplace course
$source_course_sql = "SELECT title, description, thumbnail_path, course_materials FROM MARKETPLACE_COURSE_T WHERE marketplace_course_id = '$marketplace_course_id'";
$source_course = mysqli_fetch_assoc(mysqli_query($conn, $source_course_sql));

$title = $source_course['title'];
$description = $source_course['description'];
$thumbnail_path = $source_course['thumbnail_path'];
$course_materials = $source_course['course_materials'];

// insert a new course into the user library
$insert_course_sql = "INSERT INTO COURSE_T (creator_id, title, description, thumbnail_path, course_materials, forked_from, created_at, updated_at) VALUES ('$user_id', '$title', '$description', NULL, NULL, '$marketplace_course_id', NOW(), NOW())";
mysqli_query($conn, $insert_course_sql);

$new_course_id = mysqli_insert_id($conn);

// copy course thumbnail & course materials
$doc_root = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/';

// copy thumbnail
$thumbnail_ext = pathinfo($thumbnail_path, PATHINFO_EXTENSION);
$new_thumbnail_path = 'uploads/game/course/thumbnails/' . $new_course_id . '.' . $thumbnail_ext;
copy($doc_root . $thumbnail_path, $doc_root . $new_thumbnail_path);

// copy course material (PDF)
$materials_ext = pathinfo($course_materials, PATHINFO_EXTENSION);
$new_course_materials = 'uploads/game/course/materials/' . $new_course_id . '.' . $materials_ext;
copy($doc_root . $course_materials, $doc_root . $new_course_materials);

// update file path into db
mysqli_query($conn, "UPDATE COURSE_T SET thumbnail_path = '$new_thumbnail_path', course_materials = '$new_course_materials' WHERE course_id = '$new_course_id'");

// copy every quiz + questions from marketplace to user library
$quizzes_sql = "SELECT * FROM MARKETPLACE_QUIZ_T WHERE marketplace_course_id = '$marketplace_course_id' ORDER BY level_number";
$quizzes_result = mysqli_query($conn, $quizzes_sql);

while ($quiz = mysqli_fetch_assoc($quizzes_result)) {
    $marketplace_quiz_id = $quiz['marketplace_quiz_id'];
    $level_number = $quiz['level_number'];
    $quiz_title = $quiz['title'];
    $quiz_description = $quiz['description'];

    $insert_quiz_sql = "INSERT INTO QUIZ_T (course_id, level_number, title, description, created_at, updated_at) VALUES ('$new_course_id', '$level_number', '$quiz_title', '$quiz_description', NOW(), NOW())";
    mysqli_query($conn, $insert_quiz_sql);

    $new_quiz_id = mysqli_insert_id($conn);

    // copy every question from this marketplace quiz
    $questions_sql = "SELECT * FROM MARKETPLACE_QUESTION_T WHERE marketplace_quiz_id = '$marketplace_quiz_id' ORDER BY marketplace_question_id";
    $questions_result = mysqli_query($conn, $questions_sql);

    while ($question = mysqli_fetch_assoc($questions_result)) {
        $question_text = $question['question_text'];
        $topic_tag = $question['topic_tag'];
        $question_type = $question['question_type'];
        $option_a = $question['option_a'];
        $option_b = $question['option_b'];
        $option_c = $question['option_c'];
        $option_d = $question['option_d'];
        $correct_option = $question['correct_option'];
        $correct_text_answer = $question['correct_text_answer'];
        $marks = $question['marks'];
        $time_limit = $question['time_limit'];

        $insert_question_sql = "INSERT INTO QUESTION_T (quiz_id, question_text, topic_tag, question_type, option_a, option_b, option_c, option_d, correct_option, correct_text_answer, marks, time_limit, created_at, updated_at) VALUES ('$new_quiz_id', '$question_text', '$topic_tag', '$question_type', '$option_a', '$option_b', '$option_c', '$option_d', '$correct_option', '$correct_text_answer', '$marks', '$time_limit', NOW(), NOW())";
        mysqli_query($conn, $insert_question_sql);
    }
}

add_system_log($conn, $user_id, 'Fork Course', "User forked marketplace course #$marketplace_course_id ({$source_course['title']}) as course #$new_course_id.");

header('Location: /Implose.gg-src/pages/user/marketplace/my_forks.php');
exit();

?>
