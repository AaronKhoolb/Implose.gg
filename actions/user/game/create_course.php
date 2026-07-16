<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/create_course.php
Description: create_course actions file
            - Create a new course with title, description, and upload thumbnail.
First Written on: Wednesday, 24-Jun-2026
Edited on: Wednesday, 24-Jun-2026
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

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$user_id = $_SESSION['user_id'];


$redirect_url = '/Implose.gg-src/pages/user/game/create_course.php';

function redirect_with_error($msg) {
    global $redirect_url;
    $_SESSION['create_course_error'] = $msg;
    header('Location: ' . $redirect_url);
    exit();
}

// Validation
if ($title === '') {
    redirect_with_error('Course title cannot be empty.');
}

if (strlen($title) < 2) {
    redirect_with_error('Course title must be at least 2 characters.');
}

if (!preg_match('/[a-zA-Z0-9]/', $title)) {
    redirect_with_error('Course title must contain at least one letter or number.');
}

$title_esc       = mysqli_real_escape_string($conn, $title);
$description_esc = mysqli_real_escape_string($conn, $description);

$insert_sql = "INSERT INTO COURSE_T (creator_id, title, description, thumbnail_path, course_materials, forked_from, created_at, updated_at)
                VALUES ('$user_id', '$title_esc', '$description_esc', NULL, NULL, NULL, NOW(), NOW())";

if (!mysqli_query($conn, $insert_sql)) {
    redirect_with_error('Failed to create course. Please try again.');
}

$new_course_id = mysqli_insert_id($conn);

if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
    $file = $_FILES['thumbnail'];
    $allowed_types = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        redirect_with_error('Invalid image type. Please use PNG, JPG, WebP or GIF.');
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $new_course_id . '.' . $ext;
    
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/game/course/thumbnails/';
    $target_path = $target_dir . $filename; 

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    move_uploaded_file($file['tmp_name'], $target_path);

    $thumbnail_path = 'uploads/game/course/thumbnails/' . $filename;

    $update_thumbnail_sql = "UPDATE COURSE_T SET thumbnail_path = '$thumbnail_path' WHERE course_id = '$new_course_id'";
    mysqli_query($conn, $update_thumbnail_sql);

}

add_system_log($conn, $user_id, 'Create Course', "User created course #$new_course_id ($title).");

$_SESSION['create_course_success'] = "Course \"$title\" created successfully.";
header('Location: /Implose.gg-src/pages/user/game/create_course.php');
exit();

