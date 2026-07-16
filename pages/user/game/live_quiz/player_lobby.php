<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/live_quiz/player_lobby.php
Description: Player waiting room for a live quiz (polls until the host starts)
First Written on: Thursday, 02-Jul-2026
Edited on: Thursday, 02-Jul-2026
-->
<?php
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

$room_code = strtoupper(trim($_GET['room_code'] ?? ''));

if (!preg_match('/^[A-Z0-9]{4,8}$/', $room_code)) {
    header('Location: /Implose.gg-src/pages/user/game/live_quiz/join.php');
    exit();
}

$safe_code = mysqli_real_escape_string($conn, $room_code);

$room_sql = "SELECT r.room_id, r.quiz_id, r.host_id, r.status,
                    q.title AS quiz_title, c.title AS course_title
             FROM QUIZ_ROOM_T r
             JOIN QUIZ_T q ON q.quiz_id = r.quiz_id
             JOIN COURSE_T c ON c.course_id = q.course_id
             WHERE r.room_code = '$safe_code'";
$room_result = mysqli_query($conn, $room_sql);

if (!$room_result || mysqli_num_rows($room_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/live_quiz/join.php');
    exit();
}

$room = mysqli_fetch_assoc($room_result);

if ($room['status'] === 'finished' || $room['status'] === 'cancelled') {
    header('Location: /Implose.gg-src/pages/user/game/live_quiz/join.php');
    exit();
}

// register this user as a participant, INSERT IGNORE so a refresh does not error
$room_id = (int)$room['room_id'];
$is_host = (int)$room['host_id'] === (int)$_SESSION['user_id'] ? 1 : 0;

$join_sql = "INSERT IGNORE INTO QUIZ_ROOM_PARTICIPANT_T (room_id, user_id, is_host, joined_at)
             VALUES ('$room_id', '$_SESSION[user_id]', '$is_host', NOW())";
mysqli_query($conn, $join_sql);

// game already running, jump straight in
if ($room['status'] === 'in_progress') {
    header('Location: /Implose.gg-src/pages/user/game/live_quiz/quiz.php?quiz_id=' . $room['quiz_id'] . '&room_code=' . urlencode($room_code));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_player_lobby.css">
    <title>Waiting Room — Implose.gg User</title>
    <meta name="description" content="Waiting for the live quiz to start.">
</head>
<body>
    <div class="pl-shell">
        <img class="pl-logo" src="/Implose.gg-src/assets/images/logo/logo.png" alt="Implose.gg">

        <span class="pl-eyebrow">YOU'RE IN — GET READY</span>
        <p class="pl-course"><?php echo htmlspecialchars($room['course_title']); ?></p>
        <h1 class="pixel-title pl-title"><?php echo htmlspecialchars($room['quiz_title']); ?></h1>

        <div class="pl-code-row">
            <span class="pl-code-label">ROOM</span>
            <span class="pl-code"><?php echo htmlspecialchars($room_code); ?></span>
        </div>

        <div class="pixel-panel pl-status-panel">
            <p class="pl-status" id="pl-status">Waiting for the host to start...</p>
            <p class="pl-countdown">Auto-starts in <b id="pl-timer">--</b></p>
        </div>

        <div class="pl-players-head">
            <span>Players</span>
            <span class="pl-count" id="pl-count">1</span>
        </div>
        <div class="pl-players" id="pl-players"></div>
    </div>

    <script>
        window.ROOM_DATA = <?php echo json_encode([
            'room_code' => $room_code,
            'quiz_id' => (int)$room['quiz_id'],
        ]); ?>;
    </script>
    <script src="/Implose.gg-src/assets/js/user/game/player_lobby.js"></script>
</body>
</html>
