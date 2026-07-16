<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /actions/user/submit_feedback.php
Description: Save the user's emoji feedback after a quiz/course.
            POST fields:
                emoji_rating  REQUIRED — angry|sad|neutral|happy|excellent
                description   OPTIONAL — free text, max 500 chars
                quiz_id       OPTIONAL — int, if rating a quiz
                course_id     OPTIONAL — int, if rating a course directly
            At least one of (quiz_id, course_id) is required.
            If only quiz_id is given, the parent course_id is looked up.
            Replies with plain text "success" or an error message.
First Written on: Saturday, 27-Jun-2026
Edited on: Saturday, 27-Jun-2026
*/
session_start();

// 1. Include standard required files.
//    Wrapped in ob_start/ob_end_clean because these files each start with
//    an HTML `<!-- ... -->` header comment that would otherwise be emitted
//    into the response body — the popup JS matches on "success" exactly,
//    so any extra bytes break the check.
ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

header('Content-Type: text/plain; charset=utf-8');

// 2. Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    echo "Not signed in.";
    exit();
}

// 3. Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "POST required.";
    exit();
}

// 4. Retrieve form data using isset() to avoid undefined index warnings
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

if (isset($_POST['quiz_id'])) {
    $quiz_id = $_POST['quiz_id'];
} else {
    $quiz_id = 0;
}

if (isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
} else {
    $course_id = 0;
}

// 5. Validate the emoji rating
$valid_emojis = array('angry', 'sad', 'neutral', 'happy', 'excellent');

if (!in_array($emoji, $valid_emojis)) {
    echo "Invalid emoji rating.";
    exit();
}

// 6. Validate description length
if (strlen($description) > 500) {
    echo "Comment is too long (max 500 characters).";
    exit();
}

// 7. If course_id is missing but we have quiz_id, find the course_id from DB
if ($course_id == 0 && $quiz_id > 0) {
    $lookup_query = "SELECT course_id FROM QUIZ_T WHERE quiz_id = '$quiz_id'";
    $lookup_result = mysqli_query($conn, $lookup_query);

    if ($lookup_result && mysqli_num_rows($lookup_result) > 0) {
        $row = mysqli_fetch_assoc($lookup_result);
        $course_id = $row['course_id'];
    }
}

// Final check to make sure we have a course_id to link the feedback to
if ($course_id == 0) {
    echo "Missing course context.";
    exit();
}

// 8. Security: Escape strings before inserting to prevent SQL Injection
$emoji_esc = mysqli_real_escape_string($conn, $emoji);
$desc_esc  = mysqli_real_escape_string($conn, $description);

// Prepare the quiz_id value for the SQL statement (handle NULL)
if ($quiz_id > 0) {
    $quiz_sql_value = "'$quiz_id'";
} else {
    $quiz_sql_value = "NULL";
}

// 9. Insert feedback into the database using standard MySQLi
$insert_sql = "INSERT INTO COURSE_FEEDBACK_T (course_id, quiz_id, user_id, emoji_rating, description, created_at)
               VALUES ('$course_id', $quiz_sql_value, '$user_id', '$emoji_esc', '$desc_esc', NOW())";

$insert_result = mysqli_query($conn, $insert_sql);

if (!$insert_result) {
    echo "Failed to save feedback. Please try again.";
    exit();
}

// 10. Award achievement for the user's first feedback
if (function_exists('award_achievement')) {
    award_achievement($conn, $user_id, 'FIRST_COURSE_RATED');
    
    if (function_exists('check_points_milestones')) {
        check_points_milestones($conn, $user_id);
    }
}

// Send success response back to the frontend AJAX
echo "success";
exit();
?>
