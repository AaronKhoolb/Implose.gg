<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/live_quiz/join.php
Description: Player enters a room code to join a live quiz
First Written on: Thursday, 02-Jul-2026
Edited on: Thursday, 02-Jul-2026
-->
<?php
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

$code = strtoupper(trim($_GET['code'] ?? ''));
$error_msg = '';

// a code arrived (typed or from the QR), check it and go straight to the lobby
if ($code !== '') {
    if (!preg_match('/^[A-Z0-9]{4,8}$/', $code)) {
        $error_msg = 'That code does not look right.';
    } else {
        $safe_code = mysqli_real_escape_string($conn, $code);
        $room_sql = "SELECT status FROM QUIZ_ROOM_T WHERE room_code = '$safe_code' LIMIT 1";
        $room_result = mysqli_query($conn, $room_sql);

        if (!$room_result || mysqli_num_rows($room_result) !== 1) {
            $error_msg = 'No room found for that code.';
        } else {
            $room = mysqli_fetch_assoc($room_result);

            if ($room['status'] === 'finished' || $room['status'] === 'cancelled') {
                $error_msg = 'That room is no longer open.';
            } else {
                header('Location: /Implose.gg-src/pages/user/game/live_quiz/player_lobby.php?room_code=' . urlencode($code));
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_join_room.css">
    <title>Join Quiz — Implose.gg User</title>
    <meta name="description" content="Join a live quiz with a room code.">
</head>
<body>
    <div class="join-shell">
        <img class="join-logo" src="/Implose.gg-src/assets/images/logo/logo.png" alt="Implose.gg">

        <div class="pixel-panel join-panel">
            <span class="join-eyebrow">LIVE QUIZ</span>
            <h1 class="pixel-title join-title">Join a Room</h1>
            <p class="join-sub">Enter the code on the host's screen.</p>

            <?php if ($error_msg): ?>
                <p class="join-error"><?php echo htmlspecialchars($error_msg); ?></p>
            <?php endif; ?>

            <form method="get" action="/Implose.gg-src/pages/user/game/live_quiz/join.php">
                <div class="join-code-box">
                    <input type="text" name="code" maxlength="8" autocomplete="off" spellcheck="false"
                           placeholder="ROOM CODE" value="<?php echo htmlspecialchars($code); ?>">
                </div>
                <button type="submit" class="btn-red join-btn">Join Room</button>
            </form>
        </div>

        <a class="join-back" href="/Implose.gg-src/pages/user/index.php">Back to dashboard</a>
    </div>
</body>
</html>
