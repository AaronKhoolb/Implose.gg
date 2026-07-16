<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/live_quiz/quiz.php
Description: Live Quiz page
First Written on: Wednesday, 1-July-2026
Edited on: Wednesday, 1-July-2026
-->
<?php
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

$quiz_id = (int)($_GET['quiz_id'] ?? 0);

$quiz_sql = "SELECT q.quiz_id, q.title, q.description, q.level_number, c.title AS course_title, c.course_id
            FROM QUIZ_T q JOIN COURSE_T c ON q.course_id = c.course_id
            WHERE q.quiz_id = '$quiz_id'";

$quiz_result = mysqli_query($conn, $quiz_sql);

if (!$quiz_result || mysqli_num_rows($quiz_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

$quiz = mysqli_fetch_assoc($quiz_result);

$questions_sql = "SELECT * FROM QUESTION_T WHERE quiz_id = '$quiz_id' ORDER BY question_id";
$questions_result = mysqli_query($conn, $questions_sql);

$questions = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $questions[] = $row;
}

if (count($questions) === 0) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

// Boss-battle detection: a quiz is the course's boss battle when its
// level_number equals MAX(level_number) for the same course_id. This
// matches the rule used by learning_analytics.php. Boss battles play
// on their own page with hearts and boss hp.
$is_boss_battle = false;
$boss_sql = "SELECT MAX(level_number) AS max_level
             FROM QUIZ_T WHERE course_id = '" . (int)$quiz['course_id'] . "'";
if ($boss_res = mysqli_query($conn, $boss_sql)) {
    if ($boss_row = mysqli_fetch_assoc($boss_res)) {
        $is_boss_battle = ((int)$quiz['level_number'] === (int)$boss_row['max_level']);
    }
}

if ($is_boss_battle) {
    $target = '/Implose.gg-src/pages/user/game/live_quiz/boss_battle.php?quiz_id=' . $quiz_id;
    if (trim($_GET['room_code'] ?? '') !== '') {
        $target .= '&room_code=' . urlencode($_GET['room_code']);
    }
    header('Location: ' . $target);
    exit();
}

// live mode: the room must be running and this player registered in it
$room_code = strtoupper(trim($_GET['room_code'] ?? ''));

if ($room_code !== '') {
    $safe_code = mysqli_real_escape_string($conn, $room_code);
    $room_sql = "SELECT room_id, quiz_id, status FROM QUIZ_ROOM_T WHERE room_code = '$safe_code' LIMIT 1";
    $room_result = mysqli_query($conn, $room_sql);

    if (!$room_result || mysqli_num_rows($room_result) !== 1) {
        header('Location: /Implose.gg-src/pages/user/game/live_quiz/join.php');
        exit();
    }

    $room = mysqli_fetch_assoc($room_result);

    if ((int)$room['quiz_id'] !== $quiz_id) {
        header('Location: /Implose.gg-src/pages/user/game/live_quiz/join.php');
        exit();
    }

    if ($room['status'] === 'waiting') {
        header('Location: /Implose.gg-src/pages/user/game/live_quiz/player_lobby.php?room_code=' . urlencode($room_code));
        exit();
    }

    if ($room['status'] !== 'in_progress') {
        header('Location: /Implose.gg-src/pages/user/game/live_quiz/leaderboard.php?room_code=' . urlencode($room_code));
        exit();
    }

    $room_id = (int)$room['room_id'];
    $participant_sql = "SELECT participant_id FROM QUIZ_ROOM_PARTICIPANT_T
                        WHERE room_id = '$room_id' AND user_id = '$_SESSION[user_id]' LIMIT 1";
    $participant_result = mysqli_query($conn, $participant_sql);

    if (!$participant_result || mysqli_num_rows($participant_result) !== 1) {
        header('Location: /Implose.gg-src/pages/user/game/live_quiz/player_lobby.php?room_code=' . urlencode($room_code));
        exit();
    }
}

$question_data = [];

foreach ($questions as $q) {
    $question_data[] = [
        'question_id' => (int)$q['question_id'],
        'type' => $q['question_type'],
        'topic' => $q['topic_tag'],
        'text' => $q['question_text'],
        'options' => [
            'a' => $q['option_a'],
            'b' => $q['option_b'],
            'c' => $q['option_c'],
            'd' => $q['option_d'],
        ],
        'correct' => $q['correct_option'],
        'answer' => $q['correct_text_answer'],
        'placeholder' => 'type the answer...',
        'marks' =>(int)$q['marks'],
        'time' => (int)$q['time_limit'],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_quiz.css">
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <title>Quiz — Implose.gg User</title>
    <meta name="description" content="Live quiz session.">
</head>
<body>
    <div id="quiz-screen" class="play-shell">
        <header class="play-top">
            <div class="play-brand"><img src="/Implose.gg-src/assets/images/logo/logo.png" alt="Implose.gg"></div>
            <div class="play-progress">
                <span class="q-counter">Question <b id="q-now">1</b> / <span id="q-total">5</span></span>
                <div class="seg-track" id="seg-track"></div>
            </div>
            <span class="streak-tag" id="streak-tag"><span class="flame"></span>STREAK x<b id="streak-n">2</b></span>
            <div class="score-box"><span class="lbl">PTS</span><span class="val" id="score-val">0</span></div>
        </header>

        <div class="timer-row">
            <div class="timer-num" id="timer-num"><span id="timer-secs">20</span></div>
            <div class="timer-bar-wrap">
                <div class="timer-meta">
                    <span class="topic" id="q-topic">LOOPS &amp; INTERATION</span>
                    <span class="marks">Worth <b id="q-marks">10</b> pts</span>
                </div>
                <div class="timer-bar">
                    <div class="timer-fill" id="timer-fill"></div>
                </div>
            </div>
        </div>
        
        <section class="pixel-panel q-panel">
            <span class="q-kicker" id="q-kicker">SINGLE CHOICE - PICK ONE</span>
            <h1 class="q-text" id="q-text">Question goes here</h1>
        </section>

        <div class="answers" id="answers"></div>

        <div class="play-actions" id="play-actions">
            <div class="fb" id="fb">
                <div class="fb-icon" id="fb-icon"></div>
                <div class="fb-text">
                    <span class="fb-head" id="fb-head">CORRECT!</span>
                    <span class="fb-sub" id="fb-sub"></span>
                </div>
                <span class="fb-pts" id="fb-pts"></span>
            </div>
            <div class="act-spacer" id="act-spacer"></div>
            <button class="btn-red is-disabled" id="next-btn" type="button">Next</button>
        </div>
    </div>
    
    <div class="results-screen" id="results-screen" data-screen-label="Quiz · Final results">
        <img class="res-mascot" src="/Implose.gg-src/assets/images/ui/auth/welcome.png" alt="">
        <span class="res-eyebrow">QUIZ COMPLETE</span>
        <h2 class="res-title" id="res-title">Loops &amp; Iteration <br>Cleared!</h2>

        <div class="pixel-panel res-card">
            <div class="res-grade">
                <div class="grade-badge"><span id="grade-letter">A</span></div>
                <div class="res-score">
                    <div class="big"><span id="final-score">0</span><small> pts</small></div>
                    <div class="cap" id="score-cap">Nice run — you're getting the hang of it.</div>
                </div>
            </div>

            <div class="res-stats">
                <div class="res-stat"><div class="n green" id="stat-correct">0</div><div class="k">CORRECT</div></div>
                <div class="res-stat"><div class="n" id="stat-acc">0%</div><div class="k">ACCURACY</div></div>
                <div class="res-stat"><div class="n" id="stat-best">x0</div><div class="k">BEST STREAK</div></div>
            </div>

            <div class="res-dots" id="res-dots"></div>
        </div>

        <div class="res-buttons">
            <button class="btn-pixel" id="exit-btn" type="button">
                <img src="/Implose.gg-src/assets/images/icons/arrow.clockwise.svg" alt="">Back to Course
            </button>
            <button class="btn-pixel btn-pixel-red" id="again-btn" type="button">Play Again</button>
        </div>
    </div>

 <script>
    window.QUIZ_DATA = <?php echo json_encode([
        'quiz_id' => (int)$quiz['quiz_id'],
        'course_id' => (int)$quiz['course_id'],
        'questions' => $question_data,
        'title' => $quiz['title'],
        'room_code' => $room_code !== '' ? $room_code : null
    ], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
 </script>
 <script src="/Implose.gg-src/assets/js/user/game/quiz_complete.js"></script>
 <script src="/Implose.gg-src/assets/js/user/game/quiz.js"></script>
</body>