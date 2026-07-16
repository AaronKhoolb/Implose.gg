<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/live_quiz/host.php
Description: User host live quiz lobby page
First Written on: Tuesday, 30-Jun-2026
Edited on: Tuesday, 30-Jun-2026
-->
<?php
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

$quiz_id = (int)($_GET['quiz_id'] ?? 0);

$quiz_sql = "SELECT q.quiz_id, q.title, q.description, q.level_number,
                    c.title AS course_title, c.course_id
             FROM QUIZ_T q
             JOIN COURSE_T c ON q.course_id = c.course_id
             WHERE q.quiz_id = '$quiz_id'
               AND c.creator_id = '$_SESSION[user_id]'";
$quiz_result = mysqli_query($conn, $quiz_sql);

if (!$quiz_result || mysqli_num_rows($quiz_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

$quiz = mysqli_fetch_assoc($quiz_result);
$course_id = $quiz['course_id'];

// boss battle rooms send the host to the battle stage instead of the leaderboard
$max_sql = "SELECT MAX(level_number) AS max_level FROM QUIZ_T WHERE course_id = '$course_id'";
$max_result = mysqli_query($conn, $max_sql);
$max_row = mysqli_fetch_assoc($max_result);
$is_boss = (int)$quiz['level_number'] === (int)$max_row['max_level'];

$total_sql = "SELECT COUNT(*) AS total FROM QUIZ_T WHERE course_id = '$course_id'";
$total_result = mysqli_query($conn, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$course_quiz_total = $total_row['total'];

$stats_sql = "SELECT COUNT(*) AS total, AVG(time_limit) AS avg_time FROM QUESTION_T WHERE quiz_id = '$quiz_id'";
$stats_result = mysqli_query($conn, $stats_sql);
$question_stats = mysqli_fetch_assoc($stats_result);
$question_count = (int)$question_stats['total'];
$avg_time = $question_stats['avg_time'] !== null ? (int)round($question_stats['avg_time']) : 0;

// reuse the open room for this quiz so a refresh wont create a second one
$room_sql = "SELECT room_id, room_code FROM QUIZ_ROOM_T
             WHERE quiz_id = '$quiz_id' AND host_id = '$_SESSION[user_id]' AND status = 'waiting'
             ORDER BY room_id DESC LIMIT 1";
$room_result = mysqli_query($conn, $room_sql);

if ($room_result && mysqli_num_rows($room_result) === 1) {
    $room = mysqli_fetch_assoc($room_result);
    $room_id = (int)$room['room_id'];
    $room_code = $room['room_code'];
} else {
    // 6 char code, no confusing letters like O/0 and I/1, retry if taken
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $room_code = '';

    for ($try = 0; $try < 10; $try++) {
        $candidate = '';
        for ($i = 0; $i < 6; $i++) {
            $candidate .= $alphabet[rand(0, strlen($alphabet) - 1)];
        }

        $taken_sql = "SELECT room_id FROM QUIZ_ROOM_T WHERE room_code = '$candidate' LIMIT 1";
        $taken_result = mysqli_query($conn, $taken_sql);
        if ($taken_result && mysqli_num_rows($taken_result) === 0) {
            $room_code = $candidate;
            break;
        }
    }

    if ($room_code === '') {
        header('Location: /Implose.gg-src/pages/user/game/view_course.php');
        exit();
    }

    $insert_room_sql = "INSERT INTO QUIZ_ROOM_T (room_code, quiz_id, host_id, status, auto_start_seconds, starts_at, created_at)
                    VALUES ('$room_code', '$quiz_id', '$_SESSION[user_id]', 'waiting',
                            300, DATE_ADD(NOW(), INTERVAL 300 SECOND), NOW())";
    mysqli_query($conn, $insert_room_sql);
    $room_id = (int)mysqli_insert_id($conn);

    $insert_host_sql = "INSERT INTO QUIZ_ROOM_PARTICIPANT_T (room_id, user_id, is_host, joined_at)
                        VALUES ('$room_id', '$_SESSION[user_id]', 1, NOW())";
    mysqli_query($conn, $insert_host_sql);
}

$join_host = $_SERVER['HTTP_HOST'] . '/Implose.gg-src/pages/user/game/live_quiz/join.php';
$join_url  = 'http://' . $join_host . '?code=' . urlencode($room_code);
$qr_src    = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . urlencode($join_url);

// players already in the room, host_lobby.js keeps this list fresh after load
$players = [];
$players_sql = "SELECT u.username, u.avatar_path
                FROM QUIZ_ROOM_PARTICIPANT_T p
                JOIN USER_T u ON u.user_id = p.user_id
                WHERE p.room_id = '$room_id' AND p.is_host = 0
                ORDER BY p.joined_at ASC";
$players_result = mysqli_query($conn, $players_sql);

if ($players_result) {
    while ($row = mysqli_fetch_assoc($players_result)) {
        $avatar = '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';
        if ($row['avatar_path']) {
            $avatar = '/Implose.gg-src/' . $row['avatar_path'];
        }
        $players[] = ['name' => $row['username'], 'avatar' => $avatar];
    }
}
$player_count = count($players);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_host_quiz.css">
    <title>Host Quiz — Implose.gg User</title>
    <meta name="description" content="Host a live quiz session.">
</head>
<body class="host-body-bg">
    <div class="host-page">
        <header class="host-topbar">
            <div class="topbar-left">
                <img class="topbar-mascot" src="/Implose.gg-src/assets/images/logo/logo.png" alt="">
            </div>
            <div class="topbar-right">
                <a class="end-session-btn" href="/Implose.gg-src/pages/user/game/view_course.php">
                    <span class="x">×</span>
                    <span>End Session</span>
                </a>
            </div>
        </header>

        <div class="host-body">
            <section class="pixel-panel host-left">
                <span class="now-hosting">NOW HOSTING</span>
                <p class="course-name"><?php echo htmlspecialchars($quiz['course_title']); ?></p>
                <h1 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h1>

                <p class="join-prompt">Players, head to</p>
                <p class="join-url"><?php echo htmlspecialchars($join_host); ?></p>

                <span class="code-label">ENTER THIS CODE</span>
                <div class="pin-box">
                    <span class="pin-code"><?php echo htmlspecialchars($room_code); ?></span>
                </div>

                <div class="qr-row">
                    <div class="qr-frame">
                        <img src="<?php echo htmlspecialchars($qr_src); ?>" alt="Scan to join">
                    </div>
                    <p class="qr-text">Or <strong>scan to join</strong> instantly<br>from any phone.</p>
                </div>
            </section>

            <section class="pixel-panel host-right">
                <div class="right-header">
                    <span class="players-heading">
                        <img src="/Implose.gg-src/assets/images/icons/nav_achievement.svg" alt="" class="players-icon">
                        Players Joined
                    </span>
                    <span class="waiting-pill">
                        <strong id="waiting-count"><?php echo $player_count; ?></strong>
                        <span>WAITING</span>
                    </span>
                </div>

                <div class="players-grid" id="players-grid">
                    <?php if ($player_count === 0): ?>
                        <div class="players-empty">
                            <p>No players yet.</p>
                            <p class="hint">Share the code or QR to bring them in.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($players as $p): ?>
                            <div class="pixel-panel player-card">
                                <img class="player-avatar" src="<?php echo htmlspecialchars($p['avatar']); ?>" alt="">
                                <span class="player-name"><?php echo htmlspecialchars($p['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <footer class="host-actions-bar">
            <div class="meta-left">
                <span>Quiz <strong><?php echo $quiz['level_number']; ?> of <?php echo $course_quiz_total; ?></strong></span>
                <span><strong><?php echo $question_count; ?></strong> questions</span>
                <span><strong><?php echo $avg_time; ?>s</strong> per question</span>
            </div>
            <div class="meta-right">
                <button class="btn-red start-btn" id="start-btn" type="button">Start Game</button>
            </div>
        </footer>
    </div>

    <script>
        window.ROOM_DATA = <?php echo json_encode(['room_code' => $room_code, 'is_boss' => $is_boss]); ?>;
    </script>
    <script src="/Implose.gg-src/assets/js/user/game/host_lobby.js"></script>
</body>
</html>
