<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/live_quiz/leaderboard.php
Description: Live leaderboard for a quiz room (host view, players can watch too)
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
                    q.title AS quiz_title, c.title AS course_title, c.course_id
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
$room_id = (int)$room['room_id'];
$is_host = (int)$room['host_id'] === (int)$_SESSION['user_id'];

// host or participant only
if (!$is_host) {
    $participant_sql = "SELECT participant_id FROM QUIZ_ROOM_PARTICIPANT_T
                        WHERE room_id = '$room_id' AND user_id = '$_SESSION[user_id]' LIMIT 1";
    $participant_result = mysqli_query($conn, $participant_sql);

    if (!$participant_result || mysqli_num_rows($participant_result) !== 1) {
        header('Location: /Implose.gg-src/pages/user/game/live_quiz/join.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_leaderboard.css">
    <title>Leaderboard — Implose.gg User</title>
    <meta name="description" content="Live quiz leaderboard.">
</head>
<body>
    <div class="lb-shell">
        <header class="lb-top">
            <img class="lb-logo" src="/Implose.gg-src/assets/images/logo/logo.png" alt="Implose.gg">
            <div class="lb-code-row">
                <span class="lb-code-label">ROOM</span>
                <span class="lb-code"><?php echo htmlspecialchars($room_code); ?></span>
            </div>
        </header>

        <span class="lb-eyebrow">LIVE LEADERBOARD</span>
        <p class="lb-course"><?php echo htmlspecialchars($room['course_title']); ?></p>
        <h1 class="pixel-title lb-title"><?php echo htmlspecialchars($room['quiz_title']); ?></h1>
        <p class="lb-status" id="lb-status">Scores update as players lock in answers.</p>

        <div class="lb-list" id="lb-list">
            <div class="pixel-panel lb-empty">Waiting for the first answers to come in...</div>
        </div>

        <footer class="lb-actions">
            <?php if ($is_host): ?>
                <button class="btn-red lb-end-btn" id="end-btn" type="button">End Session</button>
            <?php else: ?>
                <a class="lb-back" href="/Implose.gg-src/pages/user/index.php">Back to dashboard</a>
            <?php endif; ?>
        </footer>
    </div>

    <script>
        window.ROOM_DATA = <?php echo json_encode([
            'room_code' => $room_code,
            'is_host' => $is_host,
            'course_id' => (int)$room['course_id'],
        ]); ?>;
    </script>
    <script src="/Implose.gg-src/assets/js/user/game/leaderboard.js"></script>
</body>
</html>
