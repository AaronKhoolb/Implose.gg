<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/live_quiz/boss_host.php
Description: Boss battle host stage (big boss, party row, live leaderboard,
            victory / defeat cinematics). Host watches while players fight.
First Written on: Monday, 07-Jul-2026
Edited on: Monday, 07-Jul-2026
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
                    q.title AS quiz_title, q.level_number, c.title AS course_title, c.course_id
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
$quiz_id = (int)$room['quiz_id'];

// the stage is the host's view, players fight on their own battle page
if ((int)$room['host_id'] !== (int)$_SESSION['user_id']) {
    if ($room['status'] === 'in_progress') {
        header('Location: /Implose.gg-src/pages/user/game/live_quiz/boss_battle.php?quiz_id=' . $quiz_id . '&room_code=' . urlencode($room_code));
    } else {
        header('Location: /Implose.gg-src/pages/user/game/live_quiz/leaderboard.php?room_code=' . urlencode($room_code));
    }
    exit();
}

if ($room['status'] === 'waiting') {
    header('Location: /Implose.gg-src/pages/user/game/live_quiz/host.php?quiz_id=' . $quiz_id);
    exit();
}

// only the highest level of a course is a boss battle
$max_sql = "SELECT MAX(level_number) AS max_level FROM QUIZ_T WHERE course_id = '" . (int)$room['course_id'] . "'";
$max_result = mysqli_query($conn, $max_sql);
$max_row = mysqli_fetch_assoc($max_result);

if ((int)$room['level_number'] !== (int)$max_row['max_level']) {
    header('Location: /Implose.gg-src/pages/user/game/live_quiz/leaderboard.php?room_code=' . urlencode($room_code));
    exit();
}

$boss_img = '/Implose.gg-src/assets/images/ui/boss/glitch_king.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_boss_host.css">
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <title>Boss Battle Host — Implose.gg User</title>
    <meta name="description" content="Boss battle host stage.">
</head>
<body>
    <div id="fx-vignette"></div>

    <div class="host-shell">
        <header class="host-top">
            <div class="brand"><img src="/Implose.gg-src/assets/images/logo/logo.png" alt="Implose.gg"></div>
            <div class="host-title">
                <span class="eyebrow">FINAL BOSS — <?php echo htmlspecialchars($room['course_title']); ?></span>
                <span class="name">Boss Battle</span>
            </div>
            <div class="session-timer"><span class="lbl">TIME</span><span class="val" id="session-time">00:00</span></div>
            <button class="btn-pixel" id="end-btn" type="button">End Session</button>
        </header>

        <div class="host-main">
            <section class="pixel-panel stage">
                <div class="boss-zone">
                    <div class="boss-wrap" id="boss-wrap">
                        <div class="boss-art"><img src="<?php echo $boss_img; ?>" alt="The Glitch King" onerror="this.style.display='none'"></div>
                    </div>
                    <span class="boss-name">The Glitch King</span>
                    <div class="boss-hp-row">
                        <div class="boss-hp-bar"><div class="boss-hp-fill" id="boss-hp-fill"></div></div>
                        <span class="boss-hp-num" id="boss-hp-num">HP --</span>
                    </div>
                </div>
                <div class="party-row" id="party-row"></div>
            </section>

            <aside class="pixel-panel sidebar">
                <div class="sb-head"><span class="t">LEADERBOARD</span><span class="sub" id="sb-alive"></span></div>
                <div class="lb-list" id="lb-list"></div>
            </aside>
        </div>
    </div>

    <div class="cinematic" id="victory-cine">
        <div class="cine-inner">
            <img class="cine-mascot" src="/Implose.gg-src/assets/images/ui/auth/welcome.png" alt="">
            <h1 class="cine-title">VICTORY!</h1>
            <p class="cine-sub">The Glitch King is down — <b><?php echo htmlspecialchars($room['course_title']); ?> complete!</b></p>
            <div class="pixel-panel mvp-card">
                <img id="mvp-img" src="/Implose.gg-src/assets/images/avatar_test/avatar_robot.png" alt="">
                <div class="col">
                    <span class="k">★ MVP</span>
                    <span class="nm" id="mvp-name"></span>
                    <span class="sc"><b id="mvp-score">0</b> pts · dealt <b id="mvp-dmg">0</b> dmg</span>
                </div>
            </div>
            <div class="cine-btns">
                <button class="btn-pixel" id="v-standings-btn" type="button">See Standings</button>
                <button class="btn-red" id="v-reset-btn" type="button">Play Again</button>
            </div>
        </div>
    </div>

    <div class="cinematic" id="defeat-cine">
        <div class="cine-inner">
            <h1 class="cine-title">PARTY WIPED</h1>
            <p class="cine-sub">The Glitch King survives with <b id="d-boss-hp">0</b> HP. Rally and try again!</p>
            <div class="pixel-panel mvp-card">
                <img id="dmvp-img" src="/Implose.gg-src/assets/images/avatar_test/avatar_robot.png" alt="">
                <div class="col">
                    <span class="k">TOP DAMAGE</span>
                    <span class="nm" id="dmvp-name"></span>
                    <span class="sc"><b id="dmvp-dmg">0</b> dmg · <b id="dmvp-score">0</b> pts</span>
                </div>
            </div>
            <div class="cine-btns">
                <button class="btn-pixel" id="d-standings-btn" type="button">See Standings</button>
                <button class="btn-red" id="d-reset-btn" type="button">Try Again</button>
            </div>
        </div>
    </div>

 <script>
    window.ROOM_DATA = <?php echo json_encode([
        'room_code' => $room_code,
        'quiz_id' => $quiz_id,
        'course_id' => (int)$room['course_id'],
    ]); ?>;
 </script>
 <script src="/Implose.gg-src/assets/js/user/game/boss_host.js"></script>
</body>
</html>
