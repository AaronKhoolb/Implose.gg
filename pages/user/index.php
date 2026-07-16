<!--
Programmer Name: Chong Jun Yoong
Program Name: /pages/user/index.php
Description: User dashboard homepage
First Written on: Thursday, 21-May-2026
Edited on: Wednesday, 27-May-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_dashboard.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_chat.css">

    <title>User Dashboard</title>
</head>


<body>

    <?php
        $current_page = 'user_homepage';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <?php
        $me = (int) ($_SESSION['user_id'] ?? 0);

        $me_sql = "SELECT username FROM USER_T WHERE user_id = '$me'";
        $my_name = mysqli_fetch_assoc(mysqli_query($conn, $me_sql))['username'];

        // banner overflows if name too long
        if (strlen($my_name) > 12) $my_name = substr($my_name, 0, 12) . '..';

        $progress_sql = "SELECT quiz.course_id, c.title, COUNT(DISTINCT quiz.quiz_id) AS quizzes_attempted, (SELECT COUNT(*) FROM QUIZ_T WHERE course_id = quiz.course_id) AS total_quizzes, MAX(lr.answered_at) AS last_activity FROM QUIZ_LEARNING_RECORD_T lr JOIN QUESTION_T q ON q.question_id = lr.question_id JOIN QUIZ_T quiz ON quiz.quiz_id = q.quiz_id JOIN COURSE_T c ON c.course_id = quiz.course_id WHERE lr.user_id = '$me' AND c.creator_id = '$me' GROUP BY quiz.course_id, c.title ORDER BY last_activity DESC LIMIT 5";

        $progress_result = mysqli_query($conn, $progress_sql);
        $my_courses = [];

        if ($progress_result) {
            while ($prow = mysqli_fetch_assoc($progress_result)) {
                $cid = (int) $prow['course_id'];
                $total = (int) $prow['total_quizzes'];
                $done = (int) $prow['quizzes_attempted'];
                $pct = ($total > 0) ? min(100, round(($done / $total) * 100)) : 0;

                // find next untouched quiz so Resume continues from there
                $next_sql = "SELECT quiz_id FROM QUIZ_T WHERE course_id = '$cid' AND quiz_id NOT IN (SELECT DISTINCT q2.quiz_id FROM QUESTION_T q2 JOIN QUIZ_LEARNING_RECORD_T lr2 ON lr2.question_id = q2.question_id WHERE lr2.user_id = '$me' AND q2.quiz_id IN (SELECT quiz_id FROM QUIZ_T WHERE course_id = '$cid')) ORDER BY level_number ASC LIMIT 1";
                $next_result = mysqli_query($conn, $next_sql);
                $next_quiz_id = 0;
                if ($next_result && $nrow = mysqli_fetch_assoc($next_result)) {
                    $next_quiz_id = (int) $nrow['quiz_id'];
                }

                // if all quizzes done, fallback to the last one they answered (replayable)
                if ($next_quiz_id == 0) {
                    $last_sql = "SELECT q3.quiz_id FROM QUIZ_LEARNING_RECORD_T lr3 JOIN QUESTION_T q3 ON q3.question_id = lr3.question_id JOIN QUIZ_T qt ON qt.quiz_id = q3.quiz_id WHERE lr3.user_id = '$me' AND qt.course_id = '$cid' ORDER BY lr3.answered_at DESC LIMIT 1";
                    $last_result = mysqli_query($conn, $last_sql);
                    if ($last_result && $lrow = mysqli_fetch_assoc($last_result)) {
                        $next_quiz_id = (int) $lrow['quiz_id'];
                    }
                }

                $my_courses[] = [
                    'course_id' => $cid,
                    'title' => $prow['title'],
                    'done' => $done,
                    'total' => $total,
                    'pct' => $pct,
                    'next_quiz' => $next_quiz_id,
                ];
            }
        }
    ?>


    <div class="main-content">
        <div class="dashboard">

            <div class="dashboard-main">

                <!-- Welcome banner -->
                <div class="welcome-banner">
                    <div class="welcome-text">
                        <h1 class="pixel-title">Welcome Back,<br><?php echo htmlspecialchars($my_name); ?>!</h1>
                    </div>
                </div>

                <!-- Enter Code section -->
                <div class="enter-code-section">
                    <h2 class="pixel-title">Enter A Code To Join</h2>
                    <p class="code-subtitle">Join a course, challenge your friends,<br>and earn awesome rewards!</p>

                    <?php if (!empty($_GET['message'])): ?>
                        <p class="code-error"><?php echo htmlspecialchars($_GET['message']); ?></p>
                    <?php endif; ?>

                    <form class="code-form" action="/Implose.gg-src/actions/user/quiz_room/join_quiz_room.php" method="post">
                        <div class="txt-container"><input type="text" name="room_code" id="course_code" placeholder="Exp: ABC123" maxlength="8" autocomplete="off" required><button type="button" class="clear-btn" data-target="course_code"><img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear"></button></div>

                        <button type="submit" class="btn-red">Join</button>
                    </form>

                </div>

            </div>


            <div class="dashboard-sidebar">

                <!-- Daily Check-in Widget -->
                <div class="checkin-widget pixel-panel">

                    <div class="checkin-header">
                        <div class="checkin-streak">
                            <img src="/Implose.gg-src/assets/images/icons/fire.svg" alt="streak">
                            <span class="streak-num"><?php echo $streak_count; ?></span>
                            <span class="streak-text">day streak</span>
                        </div>
                    </div>

                    <div class="checkin-divider"></div>

                    <span class="checkin-title pixel-title">Check-in daily to earn</span>

                    <!-- 7-day tracker -->
                    <?php
                        $days_done = $streak_count % 7;
                        if ($days_done == 0 && $streak_count > 0) $days_done = 7;
                    ?>

                    <div class="checkin-days">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <?php
                                $is_done = ($i <= $days_done);
                                $absolute_day = ($streak_count - $days_done) + $i;
                                $reward = '+5';
                                $day_label = 'Day ' . $absolute_day;

                                if ($is_done && $i == $days_done) {
                                    $day_label = "Today";
                                }
                            ?>
                            <div class="checkin-day <?php if ($is_done) echo 'completed'; ?> <?php if ($i == $days_done + 1) echo 'today'; ?>"><span class="checkin-reward"><?php echo $reward; ?></span><?php if ($is_done): ?><img class="checkin-check" src="/Implose.gg-src/assets/images/icons/done.svg" alt="done"><?php endif; ?><span class="checkin-label"><?php echo $day_label; ?></span></div>
                        <?php endfor; ?>
                    </div>

                </div>


                <!-- Course Progress panel -->
                <div class="course-progress-panel pixel-panel">
                    <h3 class="pixel-title">Course Progress</h3>

                    <?php if (count($my_courses) > 0): ?>
                        <?php foreach ($my_courses as $mc):
                            $resume_href = $mc['next_quiz'] > 0 ? "/Implose.gg-src/pages/user/game/live_quiz/quiz.php?quiz_id=" . $mc['next_quiz'] : "#";
                        ?>
                            <div class="course-item"><img class="course-item-icon" src="/Implose.gg-src/assets/images/icons/book.svg" alt="<?php echo htmlspecialchars($mc['title']); ?>"><div class="course-item-info"><span class="course-item-name"><?php echo htmlspecialchars($mc['title']); ?></span><div class="progress-bar-track"><div class="progress-bar-fill" style="width: <?php echo $mc['pct']; ?>%;"></div></div><div class="course-item-meta"><a href="<?php echo $resume_href; ?>" class="btn-pixel">Resume</a><span class="course-item-percent"><?php echo $mc['done']; ?>/<?php echo $mc['total']; ?> · <?php echo $mc['pct']; ?>%</span></div></div></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="course-item is-empty">
                            <p>You haven't started any quizzes yet.</p>
                            <a href="/Implose.gg-src/pages/user/marketplace/index.php" class="btn-pixel">Browse Courses</a>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        </div>
    </div>


    <!-- Floating Chat button -->
    <a href="#" class="chat-float-btn btn-pixel" id="chat-open-btn">
        <img src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="chat">
        <span>Chat</span>
    </a>


    <div id="chat-backdrop" class="chat-backdrop"></div>

    <aside id="chat-drawer" class="chat-drawer">

        <div class="chat-drawer-header">
            <h2 class="chat-drawer-title">GLOBAL CHAT</h2>
        </div>

        <button type="button" id="chat-close-btn" class="chat-close-btn">&lt;</button>

        <div id="chat-messages" class="chat-messages">
            <p class="chat-loading">Loading messages...</p>
        </div>

        <form id="chat-form" class="chat-form">
            <input type="text" id="chat-input" class="chat-input" placeholder="Type your message..." maxlength="500" autocomplete="off">
            <button type="submit" class="chat-send-btn">Send</button>
        </form>

    </aside>


    <!-- Chat Report Modal -->
    <div id="chat-report-modal" class="chat-backdrop">
        <div class="pixel-panel chat-report-panel">
            <h3 class="pixel-title">Report Message</h3>
            <p>Why are you reporting this message?</p>

            <form id="chat-report-form">
                <input type="hidden" id="report-message-id" name="message_id" value="">
                <textarea id="report-reason" name="reason" rows="4" placeholder="Enter reason..." required></textarea>

                <div class="chat-report-actions">
                    <button type="button" class="btn-pixel" id="chat-report-cancel">Cancel</button>
                    <button type="submit" class="btn-pixel btn-pixel-red">Submit</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        window.CHAT_CONTEXT = {
            currentUserId: <?php echo (int) ($_SESSION['user_id'] ?? 0); ?>
        };
    </script>
    <script src="/Implose.gg-src/assets/js/user/chat.js"></script>

</body>
</html>
