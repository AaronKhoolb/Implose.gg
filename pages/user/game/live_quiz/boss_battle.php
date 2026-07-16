<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/live_quiz/boss_battle.php
Description: Boss Battle page for the final level of a course
            (solo duel vs the boss, or live party mode in a room)
First Written on: Monday, 07-Jul-2026
Edited on: Monday, 07-Jul-2026
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

// only the highest level of a course fights here, everything else is a normal quiz
$max_sql = "SELECT MAX(level_number) AS max_level FROM QUIZ_T WHERE course_id = '" . (int)$quiz['course_id'] . "'";
$max_result = mysqli_query($conn, $max_sql);
$max_row = mysqli_fetch_assoc($max_result);

if ((int)$quiz['level_number'] !== (int)$max_row['max_level']) {
    $target = '/Implose.gg-src/pages/user/game/live_quiz/quiz.php?quiz_id=' . $quiz_id;
    if (trim($_GET['room_code'] ?? '') !== '') {
        $target .= '&room_code=' . urlencode($_GET['room_code']);
    }
    header('Location: ' . $target);
    exit();
}

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

// player name and avatar for the vs panel
$my_name = 'You';
$my_avatar = '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';
$my_id = (int)($_SESSION['user_id'] ?? 0);

$me_sql = "SELECT username, avatar_path FROM USER_T WHERE user_id = '$my_id'";
$me_result = mysqli_query($conn, $me_sql);

if ($me_result && mysqli_num_rows($me_result) === 1) {
    $me = mysqli_fetch_assoc($me_result);
    if (!empty($me['username'])) {
        $my_name = $me['username'];
    }
    if (!empty($me['avatar_path'])) {
        $my_avatar = '/Implose.gg-src/' . $me['avatar_path'];
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
        'marks' => (int)$q['marks'],
        // boss battles run on faster timers
        'time' => max((int)round($q['time_limit'] * 0.75), 5),
    ];
}

$boss_img = '/Implose.gg-src/assets/images/ui/boss/glitch_king.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_quiz.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_boss_battle.css">
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <title>Boss Battle — Implose.gg User</title>
    <meta name="description" content="Boss battle quiz session.">
</head>
<body data-layout="<?php echo $room_code !== '' ? 'arena' : 'duel'; ?>">
    <div id="fx-vignette"></div>

    <div class="intro-screen" id="intro-screen">
        <span class="intro-eyebrow">FINAL BOSS — <?php echo htmlspecialchars($quiz['course_title']); ?></span>
        <h1 class="intro-title pixel-title">Boss Battle</h1>
        <p class="intro-sub">Beat <b>The Glitch King</b> to clear the course!</p>

        <div class="pixel-panel vs-panel">
            <div class="vs-side">
                <div class="portrait"><img src="<?php echo $my_avatar; ?>" alt="Your avatar"></div>
                <span class="vs-name"><?php echo htmlspecialchars($my_name); ?></span>
                <div class="hearts intro-hearts" id="intro-hearts"></div>
                <span class="vs-tag you">CHALLENGER</span>
            </div>
            <div class="vs-mid"><span>VS</span></div>
            <div class="vs-side">
                <div class="boss-art"><img src="<?php echo $boss_img; ?>" alt="The Glitch King" onerror="this.style.display='none'"></div>
                <span class="vs-name">The Glitch King</span>
                <div class="intro-hp" id="intro-hp"></div>
                <span class="vs-tag boss">FINAL BOSS</span>
            </div>
        </div>

        <div class="rules-row">
            <span class="rule-chip"><b id="rule-q"><?php echo count($questions); ?></b>Questions</span>
            <span class="rule-chip"><b>3</b>Hearts</span>
            <span class="rule-chip"><b>2x</b>Points</span>
            <span class="rule-chip"><b>FAST</b>Timers</span>
        </div>

        <div class="intro-buttons">
            <button class="btn-pixel" id="intro-back-btn" type="button"><img src="/Implose.gg-src/assets/images/icons/arrow.clockwise.svg" alt="">Back to Course</button>
            <button class="btn-red" id="fight-btn" type="button">Fight!</button>
        </div>
    </div>

    <div class="play-shell" id="battle-screen" style="display: none;">
        <header class="play-top">
            <div class="play-brand"><img src="/Implose.gg-src/assets/images/logo/logo.png" alt="Implose.gg"></div>
            <div class="play-progress">
                <span class="q-counter boss-counter">Q <b id="q-now">1</b> / <span id="q-total">8</span></span>
                <div class="seg-track" id="seg-track"></div>
            </div>
            <span class="streak-tag" id="streak-tag"><span class="flame"></span>STREAK x<b id="streak-n">2</b></span>
            <div class="hearts" id="top-hearts"></div>
            <span class="spec-tag"><span class="ghost"></span>SPECTATOR</span>
            <div class="score-box"><span class="lbl">PTS</span><span class="val" id="score-val">0</span></div>
        </header>

        <div class="battle-hud">
            <div class="hud-player">
                <img class="avatar" src="<?php echo $my_avatar; ?>" alt="Your avatar">
                <div class="hud-col">
                    <span class="hud-name">YOU</span>
                    <div class="hearts" id="hud-hearts"></div>
                    <span class="spec-tag"><span class="ghost"></span>SPECTATOR</span>
                </div>
            </div>
            <div class="hud-vs"><span>VS</span></div>
            <div class="hud-boss">
                <div class="boss-sprite" id="boss-sprite">
                    <div class="boss-art"><img src="<?php echo $boss_img; ?>" alt="The Glitch King" onerror="this.style.display='none'"></div>
                    <span class="dmg-pop" id="dmg-pop">HIT!</span>
                </div>
                <div class="hud-col">
                    <span class="hud-name">THE GLITCH KING</span>
                    <div class="boss-hp" id="boss-hp"></div>
                    <span class="boss-hp-label" id="boss-hp-label">HP 6/6</span>
                </div>
            </div>
        </div>

        <div class="timer-row">
            <div class="timer-num" id="timer-num"><span id="timer-secs">12</span></div>
            <div class="timer-bar-wrap">
                <div class="timer-meta">
                    <span class="topic" id="q-topic">VARIABLES</span>
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

    <div class="results-screen" id="victory-screen">
        <img class="res-mascot" src="/Implose.gg-src/assets/images/ui/auth/welcome.png" alt="">
        <span class="res-eyebrow">BOSS DEFEATED</span>
        <h2 class="res-title">Course Cleared!</h2>

        <div class="pixel-panel res-card">
            <span class="clear-ribbon">★ <?php echo htmlspecialchars(strtoupper($quiz['course_title'])); ?> COMPLETE</span>
            <div class="res-grade">
                <div class="trophy-badge"><span>★</span></div>
                <div class="res-score">
                    <div class="big"><span id="v-score">0</span><small> pts</small></div>
                    <div class="cap" id="v-cap">Includes a <b>+500</b> course clear bonus.</div>
                </div>
            </div>
            <div class="res-stats">
                <div class="res-stat"><div class="n green" id="v-dmg">6/6</div><div class="k">DMG DEALT</div></div>
                <div class="res-stat"><div class="n red" id="v-hearts">♥ 3</div><div class="k">HEARTS LEFT</div></div>
                <div class="res-stat"><div class="n" id="v-best">x0</div><div class="k">BEST STREAK</div></div>
            </div>
            <div class="res-dots" id="v-dots"></div>
        </div>

        <div class="res-buttons">
            <button class="btn-pixel" id="v-exit-btn" type="button"><img src="/Implose.gg-src/assets/images/icons/arrow.clockwise.svg" alt="">Back to Course</button>
            <button class="btn-red" id="v-again-btn" type="button">Rematch</button>
            <button class="btn-pixel btn-pixel-red" id="v-analytics-btn" type="button">Next</button>
        </div>
    </div>

    <div class="results-screen" id="defeat-screen">
        <div class="defeat-boss"><div class="boss-art"><img src="<?php echo $boss_img; ?>" alt="The Glitch King" onerror="this.style.display='none'"></div></div>
        <span class="res-eyebrow red" id="d-eyebrow">KNOCKED OUT</span>
        <h2 class="res-title">The Glitch King Wins</h2>

        <div class="pixel-panel res-card">
            <div class="res-grade">
                <div class="skull-badge"><span>KO</span></div>
                <div class="res-score">
                    <div class="big"><span id="d-score">0</span><small> pts</small></div>
                    <div class="cap" id="d-cap">So close! Run it back and finish him.</div>
                </div>
            </div>
            <div class="res-stats">
                <div class="res-stat"><div class="n gold" id="d-dmg">4/6</div><div class="k">DMG DEALT</div></div>
                <div class="res-stat"><div class="n" id="d-answered">8/8</div><div class="k">ANSWERED</div></div>
                <div class="res-stat"><div class="n" id="d-best">x0</div><div class="k">BEST STREAK</div></div>
            </div>
            <div class="res-dots" id="d-dots"></div>
        </div>

        <div class="res-buttons">
            <button class="btn-pixel" id="d-exit-btn" type="button"><img src="/Implose.gg-src/assets/images/icons/arrow.clockwise.svg" alt="">Back to Course</button>
            <button class="btn-red" id="d-again-btn" type="button">Try Again</button>
            <button class="btn-pixel btn-pixel-red" id="d-analytics-btn" type="button">Next</button>
        </div>
    </div>

 <script>
    window.BOSS_DATA = <?php echo json_encode([
        'quiz_id' => (int)$quiz['quiz_id'],
        'course_id' => (int)$quiz['course_id'],
        'questions' => $question_data,
        'title' => $quiz['title'],
        'room_code' => $room_code !== '' ? $room_code : null,
    ], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
 </script>
 <script src="/Implose.gg-src/assets/js/user/game/quiz_complete.js"></script>
 <script src="/Implose.gg-src/assets/js/user/game/boss_battle.js"></script>
</body>
</html>
