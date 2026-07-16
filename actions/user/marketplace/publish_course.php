<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/user/marketplace/publish_course.php
Description: publish course to marketplace backend - copy user course to marketplace
First Written on: Friday, 27-Jun-2026
Edited on: Friday, 3-Jul-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$user_id = $_SESSION['user_id'];
$course_id = $_POST['course_id'];

// get the source course from the user's library
$source_course_sql = "SELECT title, description, thumbnail_path, course_materials FROM COURSE_T WHERE course_id = '$course_id' AND creator_id = '$user_id'";
$source_course = mysqli_fetch_assoc(mysqli_query($conn, $source_course_sql));

$title = $source_course['title'];
$description = $source_course['description'];
$thumbnail_path = $source_course['thumbnail_path'];
$course_materials = $source_course['course_materials'];

// copy to marketplace course (course_materials & is_deleted are NOT NULL in strict-mode MySQL)
$insert_marketplace_course_sql = "INSERT INTO MARKETPLACE_COURSE_T (creator_id, source_course_id, title, description, thumbnail_path, course_materials, is_deleted, created_at, updated_at) VALUES ('$user_id', '$course_id', '$title', '$description', NULL, '', 0, NOW(), NOW())";
mysqli_query($conn, $insert_marketplace_course_sql);

$new_marketplace_course_id = mysqli_insert_id($conn);

// copy course thumbnail + materials to marketplace course
$doc_root = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/';

// copy thumbnail
$thumbnail_ext = pathinfo($thumbnail_path, PATHINFO_EXTENSION);
$new_thumbnail_path = 'uploads/game/marketplace/thumbnails/' . $new_marketplace_course_id . '.' . $thumbnail_ext;
copy($doc_root . $thumbnail_path, $doc_root . $new_thumbnail_path);

// copy course material (PDF)
$materials_ext = pathinfo($course_materials, PATHINFO_EXTENSION);
$new_course_materials = 'uploads/game/marketplace/materials/' . $new_marketplace_course_id . '.' . $materials_ext;
copy($doc_root . $course_materials, $doc_root . $new_course_materials);

// update file path into db
mysqli_query($conn, "UPDATE MARKETPLACE_COURSE_T SET thumbnail_path = '$new_thumbnail_path', course_materials = '$new_course_materials' WHERE marketplace_course_id = '$new_marketplace_course_id'");

// copy every quiz + questions from user course to marketplace
$quizzes_sql = "SELECT * FROM QUIZ_T WHERE course_id = '$course_id' ORDER BY level_number";
$quizzes_result = mysqli_query($conn, $quizzes_sql);

while ($quiz = mysqli_fetch_assoc($quizzes_result)) {
    $quiz_id = $quiz['quiz_id'];
    $level_number = $quiz['level_number'];
    $quiz_title = $quiz['title'];
    $quiz_description = $quiz['description'];

    $insert_marketplace_quiz_sql = "INSERT INTO MARKETPLACE_QUIZ_T (marketplace_course_id, level_number, title, description, created_at, updated_at) VALUES ('$new_marketplace_course_id', '$level_number', '$quiz_title', '$quiz_description', NOW(), NOW())";
    mysqli_query($conn, $insert_marketplace_quiz_sql);

    $new_marketplace_quiz_id = mysqli_insert_id($conn);

    // copy every question from this quiz
    $questions_sql = "SELECT * FROM QUESTION_T WHERE quiz_id = '$quiz_id' ORDER BY question_id";
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

        $insert_marketplace_question_sql = "INSERT INTO MARKETPLACE_QUESTION_T (marketplace_quiz_id, question_text, topic_tag, question_type, option_a, option_b, option_c, option_d, correct_option, correct_text_answer, marks, time_limit, created_at, updated_at) VALUES ('$new_marketplace_quiz_id', '$question_text', '$topic_tag', '$question_type', '$option_a', '$option_b', '$option_c', '$option_d', '$correct_option', '$correct_text_answer', '$marks', '$time_limit', NOW(), NOW())";
        mysqli_query($conn, $insert_marketplace_question_sql);
    }
}

add_system_log($conn, $user_id, 'Publish Course', "User published course #$course_id ({$source_course['title']}) to marketplace as #$new_marketplace_course_id.");

header('Location: /Implose.gg-src/pages/user/marketplace/course_details.php?id=' . $new_marketplace_course_id);
exit();

?>
