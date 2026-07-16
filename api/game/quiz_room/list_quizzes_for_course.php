<?php
/*
Programmer Name: Max
Program Name: /api/game/quiz_room/list_quizzes_for_course.php
Description: Returns the quizzes for a given course as JSON. 
              Used by the Host A Room page to populate the quiz dropdown when a course is picked.
First Written on: Tuesday, 30-Jun-2026
Edited on: Wednesday, 2-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

if ((int) ($_SESSION['user_id'] ?? 0) <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'not_signed_in']);
    exit();
}

$course_id = (int) ($_GET['course_id'] ?? 0);
if ($course_id <= 0) {
    echo json_encode(['quizzes' => []]);
    exit();
}

$quizzes = [];
$q = mysqli_query($conn,
    "SELECT quiz_id, title, level_number
       FROM QUIZ_T
      WHERE course_id = '$course_id'
      ORDER BY level_number ASC, quiz_id ASC");

if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $quizzes[] = [
            'quiz_id'      => (int) $row['quiz_id'],
            'title'        => $row['title'],
            'level_number' => isset($row['level_number']) ? (int) $row['level_number'] : null,
        ];
    }
}

echo json_encode(['quizzes' => $quizzes]);
?>
