<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/delete_course.php
Description: delete_course actions file
            - delete an existing course and remove its thumbnail file.
First Written on: Wednesday, 1-Jul-2026
Edited on: Wednesday, 1-Jul-2026
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

$user_id   = $_SESSION['user_id'];
$course_id = (int) ($_POST['course_id'] ?? 0);

$course_id_esc = mysqli_real_escape_string($conn, (string) $course_id);
$user_id_esc   = mysqli_real_escape_string($conn, (string) $user_id);

// Verify the course exists and belongs to the current user
$course_sql = "SELECT * FROM COURSE_T
                WHERE course_id = '$course_id_esc'
                  AND creator_id = '$user_id_esc'";
$course_result = mysqli_query($conn, $course_sql);

if (!$course_result || mysqli_num_rows($course_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

$course = mysqli_fetch_assoc($course_result);

// Delete the course, quizzes and questions go with it through FK cascade
$delete_sql = "DELETE FROM COURSE_T
                WHERE course_id = '$course_id_esc'
                  AND creator_id = '$user_id_esc'";

if (!mysqli_query($conn, $delete_sql)) {
    $_SESSION['edit_course_error'] = 'Failed to delete course. Please try again.';
    header('Location: /Implose.gg-src/pages/user/game/edit_course.php?course_id=' . $course_id);
    exit();
}

// Remove the thumbnail file from disk if one exists
if (!empty($course['thumbnail_path'])) {
    $thumb_file = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/' . $course['thumbnail_path'];
    if (is_file($thumb_file)) {
        unlink($thumb_file);
    }
}

add_system_log($conn, $user_id, 'Delete Course', "User deleted course #$course_id ($course[title]).");

header('Location: /Implose.gg-src/pages/user/game/view_course.php');
exit();
