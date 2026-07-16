<?php
/*
Programmer Name: Mr. Chong Ray Han
Program Name: /actions/user/game/edit_course.php
Description: edit_course actions file
            - update an existing course's title, description, and thumbnail.
First Written on: Tuesday, 25-Jun-2026
Edited on: Tuesday, 30-Jun-2026
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

// If the upload exceeds PHP's post_max_size, PHP discards the whole POST body,
// leaving $_POST/$_FILES empty even though this is a POST. Send the user back to
// the form with a clear message instead of a confusing "course not found" bounce.
if (empty($_POST) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $_SESSION['edit_course_error'] = 'Upload is too large. Keep thumbnails under 2 MB and course materials under 10 MB.';
    $back = $_SERVER['HTTP_REFERER'] ?? '';
    if (!is_string($back) || strpos($back, '/Implose.gg-src/') === false) {
        $back = '/Implose.gg-src/pages/user/game/view_course.php';
    }
    header('Location: ' . $back);
    exit();
}

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$user_id     = $_SESSION['user_id'];
$course_id   = (int) ($_POST['course_id'] ?? 0);

// Default landing page after a successful save.
$success_url = '/Implose.gg-src/pages/user/game/manage_course.php?course_id=' . $course_id;

// Honor an explicit return target if it is a safe local path.
$return_to = $_POST['return_to'] ?? '';
if (is_string($return_to) && strpos($return_to, '/Implose.gg-src/') === 0 && strpbrk($return_to, "\r\n") === false) {
    $success_url = $return_to;
} else {
    $return_to = '';
}

// On validation errors, send the user back to the form (preserving the return target).
$redirect_url = '/Implose.gg-src/pages/user/game/edit_course.php?course_id=' . $course_id;
if ($return_to !== '') {
    $redirect_url .= '&return=' . urlencode($return_to);
}

function redirect_with_error($msg) {
    global $redirect_url;
    $_SESSION['edit_course_error'] = $msg;
    header('Location: ' . $redirect_url);
    exit();
}

// Verify the course exists and belongs to the current user
$course_id_esc = mysqli_real_escape_string($conn, (string) $course_id);
$user_id_esc   = mysqli_real_escape_string($conn, (string) $user_id);

$course_sql = "SELECT * FROM COURSE_T
                WHERE course_id = '$course_id_esc'
                  AND creator_id = '$user_id_esc'";
$course_result = mysqli_query($conn, $course_sql);

if (!$course_result || mysqli_num_rows($course_result) !== 1) {
    redirect_with_error('Course not found.');
}

$course = mysqli_fetch_assoc($course_result);

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

// Handle optional thumbnail upload before saving
$thumbnail_path = $course['thumbnail_path'];

if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['thumbnail'];

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > 2 * 1024 * 1024) {
        redirect_with_error('Thumbnail is too large. Please choose an image under 2 MB.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        redirect_with_error('Failed to upload thumbnail. Please try again.');
    }

    $allowed_types = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        redirect_with_error('Invalid image type. Please use PNG, JPG, WebP or GIF.');
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $course_id . '.' . $ext;

    $target_dir  = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/game/course/thumbnails/';
    $target_path = $target_dir . $filename;

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        redirect_with_error('Failed to upload thumbnail. Please try again.');
    }

    $thumbnail_path = 'uploads/game/course/thumbnails/' . $filename;
}

// Handle optional course material (PDF) upload
$course_materials = $course['course_materials'];

if (isset($_FILES['course_material']) && $_FILES['course_material']['error'] !== UPLOAD_ERR_NO_FILE) {
    $mat = $_FILES['course_material'];

    if ($mat['error'] === UPLOAD_ERR_INI_SIZE || $mat['error'] === UPLOAD_ERR_FORM_SIZE || $mat['size'] > 10 * 1024 * 1024) {
        redirect_with_error('Course material is too large. Please choose a PDF under 10 MB.');
    }
    if ($mat['error'] !== UPLOAD_ERR_OK) {
        redirect_with_error('Failed to upload course material. Please try again.');
    }

    if ($mat['type'] !== 'application/pdf') {
        redirect_with_error('Course material must be a PDF file.');
    }

    $mat_target_dir  = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/game/course/materials/';
    $mat_target_path = $mat_target_dir . $course_id . '.pdf';

    if (!is_dir($mat_target_dir)) {
        mkdir($mat_target_dir, 0777, true);
    }

    if (!move_uploaded_file($mat['tmp_name'], $mat_target_path)) {
        redirect_with_error('Failed to upload course material. Please try again.');
    }

    $course_materials = 'uploads/game/course/materials/' . $course_id . '.pdf';
}

$thumbnail_path_esc = $thumbnail_path !== null
    ? "'" . mysqli_real_escape_string($conn, $thumbnail_path) . "'"
    : 'NULL';

$course_materials_esc = $course_materials !== null
    ? "'" . mysqli_real_escape_string($conn, $course_materials) . "'"
    : 'NULL';

$update_sql = "UPDATE COURSE_T
                SET title = '$title_esc',
                    description = '$description_esc',
                    thumbnail_path = $thumbnail_path_esc,
                    course_materials = $course_materials_esc,
                    updated_at = NOW()
                WHERE course_id = '$course_id_esc'
                  AND creator_id = '$user_id_esc'";

if (!mysqli_query($conn, $update_sql)) {
    redirect_with_error('Failed to update course. Please try again.');
}

add_system_log($conn, $user_id, 'Edit Course', "User edited course #$course_id ($title).");

$_SESSION['edit_course_success'] = "Course \"$title\" updated successfully.";
header('Location: ' . $success_url);
exit();
