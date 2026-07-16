<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /actions/user/game/quiz/complete_quiz.php
Description: End-of-quiz hook, called from quiz.js the moment the
            results screen renders. It:
              1. Queues the emoji-feedback popup for the next page
                 (via $_SESSION['feedback_prompt']).
              2. Awards the achievement chain the run just satisfied:
                   FIRST_QUIZ_COMPLETE   — every question of THIS quiz
                                           has now been answered.
                   FIRST_BOSS_WIN        — the finished quiz was a
                                           "Boss Battle" level.
                   FIRST_COURSE_COMPLETE — every quiz in the parent
                                           course is now answered
                                           through, i.e. the user has
                                           at least one attempt per
                                           question across the whole
                                           course.
            All checks are idempotent — award_achievement() already
            skips achievements the user has already unlocked.
            Replies with JSON so quiz.js can react if it ever needs to.
First Written on: Saturday, 04-Jul-2026
Edited on: Sunday, 05-Jul-2026
*/
session_start();

// Wrap the includes with ob_start / ob_end_clean because db.php /
// achievement.php / system_log.php each start with an HTML `<!-- -->`
// header comment. Without the buffer, those bytes get prepended to
// the JSON body and JSON.parse on the client side blows up.
ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/system_log.php');
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

// Standard response object we fill in as we go
$response = array(
    'success'          => false,
    'error'            => '',
    'quiz_completed'   => false,
    'course_completed' => false
);

// 1. Must be signed in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $response['error'] = 'Not signed in.';
    echo json_encode($response);
    exit();
}

// 2. Only POST is allowed
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $response['error'] = 'POST required.';
    echo json_encode($response);
    exit();
}

// 3. Read the quiz_id
if (isset($_POST['quiz_id']) && $_POST['quiz_id'] > 0) {
    $quiz_id = $_POST['quiz_id'];
} else {
    $response['error'] = 'Missing quiz id.';
    echo json_encode($response);
    exit();
}

// 4. Look up the quiz + its parent course
$quiz_sql = "SELECT quiz_id, course_id, title FROM QUIZ_T WHERE quiz_id = '$quiz_id'";
$quiz_res = mysqli_query($conn, $quiz_sql);

if ($quiz_res && mysqli_num_rows($quiz_res) > 0) {
    $quiz       = mysqli_fetch_assoc($quiz_res);
    $course_id  = $quiz['course_id'];
    $quiz_title = $quiz['title'];
} else {
    $response['error'] = 'Quiz not found.';
    echo json_encode($response);
    exit();
}

// Save data in session so the next page render shows the feedback popup
$_SESSION['feedback_prompt'] = array(
    'quiz_id'    => $quiz_id,
    'course_id'  => $course_id,
    'quiz_title' => $quiz_title
);

// ===============================================================
// SECTION 1: FIRST_QUIZ_COMPLETE (did the user answer every
//            question in this quiz?)
// ===============================================================
$q_total        = 0;
$q_done         = 0;
$quiz_completed = false;

// Total questions in this quiz
$q_total_sql = "SELECT COUNT(*) AS total FROM QUESTION_T WHERE quiz_id = '$quiz_id'";
$q_total_res = mysqli_query($conn, $q_total_sql);
if ($q_total_res && mysqli_num_rows($q_total_res) > 0) {
    $row     = mysqli_fetch_assoc($q_total_res);
    $q_total = $row['total'];
}

// Distinct questions the user has answered in this quiz
// (subquery instead of a JOIN to keep it easy to read)
$q_done_sql = "SELECT COUNT(DISTINCT question_id) AS done
                 FROM QUIZ_LEARNING_RECORD_T
                WHERE user_id = '$user_id'
                  AND question_id IN (SELECT question_id FROM QUESTION_T WHERE quiz_id = '$quiz_id')";
$q_done_res = mysqli_query($conn, $q_done_sql);
if ($q_done_res && mysqli_num_rows($q_done_res) > 0) {
    $row    = mysqli_fetch_assoc($q_done_res);
    $q_done = $row['done'];
}

if ($q_total > 0 && $q_done >= $q_total) {
    $quiz_completed = true;

    if (function_exists('award_achievement')) {
        award_achievement($conn, $user_id, 'FIRST_QUIZ_COMPLETE');

        // Boss Battle bonus — check if quiz title contains "boss"
        // stripos() is case-insensitive
        if (stripos($quiz_title, 'boss') !== false) {
            award_achievement($conn, $user_id, 'FIRST_BOSS_WIN');
        }
    }
}

// ===============================================================
// SECTION 2: FIRST_COURSE_COMPLETE (is every quiz in the parent
//            course now answered through?)
// ===============================================================
$course_done = false;

// Only worth checking the course if the current quiz is already done
if ($quiz_completed == true && $course_id > 0) {
    $c_total = 0;
    $c_done  = 0;

    // Total questions across every quiz in this course
    $c_total_sql = "SELECT COUNT(*) AS total
                      FROM QUESTION_T
                     WHERE quiz_id IN (SELECT quiz_id FROM QUIZ_T WHERE course_id = '$course_id')";
    $c_total_res = mysqli_query($conn, $c_total_sql);
    if ($c_total_res && mysqli_num_rows($c_total_res) > 0) {
        $row     = mysqli_fetch_assoc($c_total_res);
        $c_total = $row['total'];
    }

    // Distinct questions the user answered across the whole course
    $c_done_sql = "SELECT COUNT(DISTINCT question_id) AS done
                     FROM QUIZ_LEARNING_RECORD_T
                    WHERE user_id = '$user_id'
                      AND question_id IN (
                          SELECT question_id FROM QUESTION_T WHERE quiz_id IN (
                              SELECT quiz_id FROM QUIZ_T WHERE course_id = '$course_id'
                          )
                      )";
    $c_done_res = mysqli_query($conn, $c_done_sql);
    if ($c_done_res && mysqli_num_rows($c_done_res) > 0) {
        $row    = mysqli_fetch_assoc($c_done_res);
        $c_done = $row['done'];
    }

    if ($c_total > 0 && $c_done >= $c_total) {
        $course_done = true;
        if (function_exists('award_achievement')) {
            award_achievement($conn, $user_id, 'FIRST_COURSE_COMPLETE');
        }
    }
}

// 5. Update points milestones if any of the awards above added coins
if (function_exists('check_points_milestones')) {
    check_points_milestones($conn, $user_id);
}

// 6. Return the JSON response for the frontend AJAX
$response['success']          = true;
$response['quiz_completed']   = $quiz_completed;
$response['course_completed'] = $course_done;

echo json_encode($response);
exit();
?>
