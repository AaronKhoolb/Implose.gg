<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /actions/user/update_feedback.php
Description: Edit an existing COURSE_FEEDBACK_T row that belongs to
            the current signed-in user. Same field rules as the
            original submit: emoji_rating REQUIRED and one of the
            five valid values, description OPTIONAL and <= 500 chars.
            The row is only touched if user_id matches, so a user
            can only edit their own feedback.
            Replies with plain text "success" or a short error.
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
*/
session_start();

// buffer the includes so the header HTML comments inside them don't
// leak into the response body (submit_feedback.php does the same)
ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

header('Content-Type: text/plain; charset=utf-8');

// 1. Check if user is signed in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    echo "Not signed in.";
    exit();
}

// 2. Only POST is allowed
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "POST required.";
    exit();
}

// 3. Read the incoming form fields
if (isset($_POST['feedback_id'])) {
    $feedback_id = $_POST['feedback_id'];
} else {
    $feedback_id = 0;
}

if (isset($_POST['emoji_rating'])) {
    $emoji = trim($_POST['emoji_rating']);
} else {
    $emoji = '';
}

if (isset($_POST['description'])) {
    $description = trim($_POST['description']);
} else {
    $description = '';
}

// 4. Basic validation
if ($feedback_id <= 0) {
    echo "Missing feedback id.";
    exit();
}

$valid_emojis = array('angry', 'sad', 'neutral', 'happy', 'excellent');
if (!in_array($emoji, $valid_emojis)) {
    echo "Invalid emoji rating.";
    exit();
}

if (strlen($description) > 500) {
    echo "Comment is too long (max 500 characters).";
    exit();
}

// 5. Check the row belongs to this user before touching anything
$own_sql = "SELECT feedback_id FROM COURSE_FEEDBACK_T
             WHERE feedback_id = '$feedback_id' AND user_id = '$user_id' LIMIT 1";
$own_res = mysqli_query($conn, $own_sql);

if (!$own_res || mysqli_num_rows($own_res) != 1) {
    echo "Feedback not found or not yours to edit.";
    exit();
}

// 6. Escape strings before insert (protect against SQL injection)
$emoji_esc = mysqli_real_escape_string($conn, $emoji);
$desc_esc  = mysqli_real_escape_string($conn, $description);

$update_sql = "UPDATE COURSE_FEEDBACK_T
                  SET emoji_rating = '$emoji_esc',
                      description  = '$desc_esc'
                WHERE feedback_id = '$feedback_id' AND user_id = '$user_id'";

$update_result = mysqli_query($conn, $update_sql);
if (!$update_result) {
    echo "Failed to update feedback.";
    exit();
}

// 7. Write a system log entry (only if the helper function is loaded)
if (function_exists('add_system_log')) {
    add_system_log($conn, $user_id, 'Feedback Updated', "User edited feedback #$feedback_id.");
}

echo "success";
exit();
?>
