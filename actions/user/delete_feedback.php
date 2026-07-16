<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /actions/user/delete_feedback.php
Description: Delete a COURSE_FEEDBACK_T row that belongs to the
            signed-in user. The WHERE clause is scoped to user_id
            so someone can never delete another user's feedback by
            forging the id.
            Replies with plain text "success" or a short error.
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
*/
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

header('Content-Type: text/plain; charset=utf-8');

// 1. Must be signed in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    echo "Not signed in.";
    exit();
}

// 2. Only POST allowed
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "POST required.";
    exit();
}

// 3. Read the feedback id
if (isset($_POST['feedback_id'])) {
    $feedback_id = $_POST['feedback_id'];
} else {
    $feedback_id = 0;
}

if ($feedback_id <= 0) {
    echo "Missing feedback id.";
    exit();
}

// 4. Delete — the AND user_id clause makes sure users can only
//    delete their own rows.
$del_sql = "DELETE FROM COURSE_FEEDBACK_T
             WHERE feedback_id = '$feedback_id' AND user_id = '$user_id'";

$del_result = mysqli_query($conn, $del_sql);
if (!$del_result) {
    echo "Failed to delete feedback.";
    exit();
}

// 5. mysqli_affected_rows tells us whether a row was actually removed
$rows_deleted = mysqli_affected_rows($conn);
if ($rows_deleted == 0) {
    echo "Feedback not found or not yours.";
    exit();
}

// 6. Write a system log entry
if (function_exists('add_system_log')) {
    add_system_log($conn, $user_id, 'Feedback Deleted', "User deleted feedback #$feedback_id.");
}

echo "success";
exit();
?>
