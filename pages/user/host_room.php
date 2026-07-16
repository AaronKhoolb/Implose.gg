<!--
Programmer Name: Max
Program Name: /pages/user/host_room.php
Description: Host-a-room page. Lets the user pick a course and then a quiz from that course 
             and submit to create a live quiz room. The backend creates the room and redirects 
             the user to the lobby.
First Written on: Tuesday, 30-Jun-2026
Edited on: Wednesday, 2-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_host_room.css">
    <title>Host a Room - Implose.gg</title>
</head>
<body>
    <?php
        $current_page = 'user_homepage';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

        $courses_result = mysqli_query($conn,
            "SELECT course_id, title FROM COURSE_T ORDER BY title ASC");

        $message = isset($_GET['message']) ? $_GET['message'] : '';
    ?>

    <div class="main-content">
        <div class="host-room-wrapper">

            <a href="/Implose.gg-src/pages/user/index.php" class="btn-back">⬅ Back</a>

            <h1 class="pixel-title">Host A Room</h1>
            <p class="host-room-subtitle">
                Pick a course and a quiz. We'll generate a 6-letter code your friends can use to join.
            </p>

            <?php if ($message !== ''): ?>
                <div class="host-room-error"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form class="host-room-form" action="/Implose.gg-src/actions/user/quiz_room/create_quiz_room.php" method="post">

                <label class="host-room-label" for="course_select">Course</label>
                <select id="course_select" class="host-room-select" required>
                    <option value="">-- Select a course --</option>
                    <?php if ($courses_result): ?>
                        <?php while ($c = mysqli_fetch_assoc($courses_result)): ?>
                            <option value="<?php echo (int) $c['course_id']; ?>">
                                <?php echo htmlspecialchars($c['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>

                <label class="host-room-label" for="quiz_select">Quiz</label>
                <select id="quiz_select" name="quiz_id" class="host-room-select" required disabled>
                    <option value="">-- Pick a course first --</option>
                </select>

                <p class="host-room-hint">
                    Once you create the room, the quiz auto-starts in <strong>60 seconds</strong>
                    unless you press <strong>Start</strong> earlier.
                </p>

                <button type="submit" class="btn-red host-room-submit">Create Room</button>
            </form>

        </div>
    </div>

    <script src="/Implose.gg-src/assets/js/user/host_room.js"></script>
</body>
</html>
