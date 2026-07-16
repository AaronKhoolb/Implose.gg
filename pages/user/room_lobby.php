<!--
Programmer Name: Max
Program Name: /pages/user/room_lobby.php
Description: Waiting-room page for a quiz room. Shows the 6-character room code, 
             lists who has joined (polled every 2 seconds), and shows a countdown 
             timer for auto-start. Host sees a Start button; other users see a waiting message. 
             When the room status flips to in_progress 
             (either by host pressing Start or by the auto-start timer), 
             all clients redirect to the live quiz page.
First Written on: Tuesday, 30-Jun-2026
Edited on: Wednesday, 2-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_room_lobby.css">
    <title>Room Lobby - Implose.gg</title>
</head>
<body>
    <?php
        $current_page = 'user_homepage';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

        $room_code = isset($_GET['room_code']) ? strtoupper(trim($_GET['room_code'])) : '';
        if (!preg_match('/^[A-Z0-9]{4,8}$/', $room_code)) {
            header('Location: /Implose.gg-src/pages/user/index.php?message=' . urlencode('Invalid room code.'));
            exit();
        }

        $safe_code = mysqli_real_escape_string($conn, $room_code);

        $room_q = mysqli_query($conn,
            "SELECT r.room_id, r.quiz_id, r.host_id, r.status,
                    q.title AS quiz_title, c.title AS course_title
               FROM QUIZ_ROOM_T r
               JOIN QUIZ_T   q ON q.quiz_id   = r.quiz_id
               JOIN COURSE_T c ON c.course_id = q.course_id
              WHERE r.room_code = '$safe_code'
              LIMIT 1");

        if (!$room_q || mysqli_num_rows($room_q) !== 1) {
            header('Location: /Implose.gg-src/pages/user/index.php?message=' . urlencode('Room not found.'));
            exit();
        }

        $room = mysqli_fetch_assoc($room_q);
        $is_host = ((int) $room['host_id'] === (int) ($_SESSION['user_id'] ?? 0));

        // Make sure the current user is registered as a participant (idempotent).
        $room_id_int = (int) $room['room_id'];
        $uid_int     = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid_int > 0) {
            mysqli_query($conn,
                "INSERT IGNORE INTO QUIZ_ROOM_PARTICIPANT_T (room_id, user_id, is_host, joined_at)
                 VALUES ('$room_id_int', '$uid_int', " . ($is_host ? 1 : 0) . ", NOW())");
        }
    ?>

    <div class="main-content">
        <div class="lobby-wrapper">

            <a href="/Implose.gg-src/pages/user/index.php" class="btn-back">⬅ Leave Lobby</a>

            <div class="lobby-header">
                <span class="lobby-eyebrow">Quiz Room</span>
                <h1 class="pixel-title lobby-quiz-title">
                    <?php echo htmlspecialchars($room['quiz_title']); ?>
                </h1>
                <p class="lobby-course-label">
                    Course: <strong><?php echo htmlspecialchars($room['course_title']); ?></strong>
                </p>
            </div>

            <div class="lobby-code-card pixel-panel">
                <span class="lobby-code-label">ROOM CODE</span>
                <span class="lobby-code-value" id="lobby-code-value">
                    <?php echo htmlspecialchars($room_code); ?>
                </span>
                <button type="button" class="btn-pixel lobby-copy-btn" id="lobby-copy-btn">
                    Copy Code
                </button>
                <p class="lobby-code-hint">Share this code so others can join from their dashboard.</p>
            </div>

            <div class="lobby-timer-card pixel-panel">
                <span class="lobby-timer-label">Auto-starts in</span>
                <span class="lobby-timer-value pixel-giant-timer" id="lobby-countdown">--</span>
                <span class="lobby-timer-sub" id="lobby-timer-sub">seconds</span>
            </div>

            <div class="lobby-action-row">
                <?php if ($is_host): ?>
                    <button type="button" id="lobby-start-btn" class="btn-red lobby-start-btn">
                        Start Quiz Now
                    </button>
                <?php else: ?>
                    <span class="lobby-waiting-text">Waiting for host to start...</span>
                <?php endif; ?>
            </div>

            <div class="lobby-participants pixel-panel">
                <h3 class="pixel-title">
                    Players <span class="lobby-count" id="lobby-count">(0)</span>
                </h3>
                <ul class="lobby-participant-list" id="lobby-participant-list">
                    <li class="lobby-empty">Loading...</li>
                </ul>
            </div>

        </div>
    </div>

    <script>
        window.ROOM_CONTEXT = {
            roomCode: <?php echo json_encode($room_code); ?>,
            quizId  : <?php echo (int) $room['quiz_id']; ?>,
            isHost  : <?php echo $is_host ? 'true' : 'false'; ?>
        };
    </script>
    <script src="/Implose.gg-src/assets/js/user/room_lobby.js"></script>
</body>
</html>
