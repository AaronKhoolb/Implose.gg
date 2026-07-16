<!--
Programmer Name: Mr. Chong Ray Han & Chong Jun Yoong
Program Name: /pages/user/game/manage_course.php
Description: User course management page (hero, stats, levels list)
First Written on: Thursday, 25-Jun-2026
Edited on: Tuesday, 30-Jun-2026
-->
<?php
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['edit_course_success'])) {
    $success_msg = $_SESSION['edit_course_success'];
    unset($_SESSION['edit_course_success']);
}

if (isset($_SESSION['edit_course_error'])) {
    $error_msg = $_SESSION['edit_course_error'];
    unset($_SESSION['edit_course_error']);
}

$course_id = (int)($_GET['course_id'] ?? 0);

$course_sql = "SELECT * FROM COURSE_T WHERE course_id = '$course_id' AND creator_id = '$_SESSION[user_id]'";
$course_result = mysqli_query($conn, $course_sql);

if (!$course_result || mysqli_num_rows($course_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

$course = mysqli_fetch_assoc($course_result);

$quiz_list_sql = "SELECT * FROM QUIZ_T WHERE course_id = '$course_id' ORDER BY level_number ASC";
$quiz_list_result = mysqli_query($conn, $quiz_list_sql);
$level_count = mysqli_num_rows($quiz_list_result);


$my_id = (int) $_SESSION['user_id'];
$quiz_progress = [];
$question_count = 0;

$quiz_id_sql    = "SELECT quiz_id FROM QUIZ_T WHERE course_id = '$course_id'";
$quiz_id_result = mysqli_query($conn, $quiz_id_sql);
while ($quiz_id_row = mysqli_fetch_assoc($quiz_id_result)) {
    $qid = (int) $quiz_id_row['quiz_id'];

    $q_total = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM QUESTION_T WHERE quiz_id = '$qid'"))['c'];
    $question_count += $q_total;

    // distinct questions answered in this quiz
    $q_answered = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT lr.question_id) AS c FROM QUIZ_LEARNING_RECORD_T lr JOIN QUESTION_T que ON que.question_id = lr.question_id WHERE que.quiz_id = '$qid' AND lr.user_id = '$my_id'"))['c'];

    // distinct questions correct at least once
    $q_correct = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT lr.question_id) AS c FROM QUIZ_LEARNING_RECORD_T lr JOIN QUESTION_T que ON que.question_id = lr.question_id WHERE que.quiz_id = '$qid' AND lr.user_id = '$my_id' AND lr.is_correct = 1"))['c'];

    $q_last_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(lr.answered_at) AS t FROM QUIZ_LEARNING_RECORD_T lr JOIN QUESTION_T que ON que.question_id = lr.question_id WHERE que.quiz_id = '$qid' AND lr.user_id = '$my_id'"));
    $q_last = $q_last_row['t'] ?? null;

    $quiz_progress[$qid] = [
        'total'    => $q_total,
        'answered' => $q_answered,
        'correct'  => $q_correct,
        'last'     => $q_last,
    ];
}

$thumb = !empty($course['thumbnail_path']) ? '/Implose.gg-src/' . $course['thumbnail_path'] : '';
$is_forked = !empty($course['forked_from']);
$created_at_fmt = date('M j, Y', strtotime($course['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/marketplace.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_create_course.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_view_course.css">
    <title><?php echo htmlspecialchars($course['title']); ?> — Implose.gg User</title>
    <meta name="description" content="Manage course levels and details.">
</head>
<body>
    <?php
        $current_page = 'user_course';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');

    ?>

    <div class="main-content">
        <div class="vc-container">
            <nav class="cc-breadcrumb">
                <a href="/Implose.gg-src/pages/user/game/view_course.php">Course</a>
                <span class="sep">></span>
                <span class="current"><?php echo htmlspecialchars($course['title']); ?></span>
            </nav>

            <?php if ($error_msg): ?>
                <p class="cc-error"><?php echo htmlspecialchars($error_msg); ?></p>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <p class="cc-success"><?php echo htmlspecialchars($success_msg); ?></p>
            <?php endif; ?>

            <div class="pixel-panel vc-header">
                <div class="vc-thumb" style="<?php echo $thumb ? "background-image:url('" . htmlspecialchars($thumb) . "');" : ''; ?>">
                    <?php if (!$thumb): ?>
                        <div class="vc-thumb-empty">No Thumbnail</div>
                    <?php endif; ?>
                    <?php if ($is_forked): ?>
                        <span class="vc-fork-badge">FORK</span>
                    <?php endif; ?>
                </div>

                <div class="vc-header-body">
                    <h1 class="pixel-title vc-title"><?php echo htmlspecialchars($course['title']); ?></h1>

                    <p class="vc-desc"><?php echo htmlspecialchars($course['description']); ?></p>

                    <div class="vc-stats">
                        <div class="vc-stat">
                            <span class="vc-stat-num"><?php echo $level_count; ?></span>
                            <span class="vc-stat-label">LEVELS</span>
                        </div>
                        <div class="vc-stat">
                            <span class="vc-stat-num"><?php echo $question_count; ?></span>
                            <span class="vc-stat-label">QUESTIONS</span>
                        </div>
                        <div class="vc-stat">
                            <span class="vc-stat-num"><?php echo $created_at_fmt; ?></span>
                            <span class="vc-stat-label">CREATED</span>
                        </div>
                    </div>

                    <div class="vc-header-actions">
                        <a class="btn-pixel btn-pixel-red" href="/Implose.gg-src/pages/user/game/quiz/create_quiz.php?course_id=<?php echo $course['course_id']; ?>">
                            Add Quiz Level
                        </a>
                        <?php if (!empty($course['course_materials'])):
                            $back_url = '/Implose.gg-src/pages/user/game/manage_course.php?course_id=' . $course['course_id'];
                        ?>
                            <a class="btn-pixel" href="/Implose.gg-src/pages/pdf_viewer.php?file=<?php echo urlencode($course['course_materials']); ?>&back=<?php echo urlencode($back_url); ?>">
                                <span>Course Material</span>
                            </a>
                        <?php endif; ?>
                        <a class="btn-pixel" href="/Implose.gg-src/pages/user/game/edit_course.php?course_id=<?php echo $course['course_id']; ?>&return=<?php echo urlencode('/Implose.gg-src/pages/user/game/manage_course.php?course_id=' . $course['course_id']); ?>">
                            <span>Edit Course</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="vc-levels-head">
                <h2 class="pixel-title vc-section-title">Course Levels</h2>
                <span class="vc-count-pill"><?php echo $level_count; ?> total</span>
            </div>

            <?php if ($level_count == 0): ?>
                <div class="pixel-panel vc-empty">
                    <div class="vc-empty-title">No levels yet</div>
                    <div class="vc-empty-sub">Add your first quiz level to get this course started.</div>
                    <a class="btn-pixel btn-pixel-red" href="/Implose.gg-src/pages/user/game/quiz/create_quiz.php?course_id=<?php echo $course['course_id']; ?>">
                        Add Quiz Level
                    </a>
                </div>
            <?php else: ?>
                <div class="vc-levels">
                    <?php while ($quiz = mysqli_fetch_assoc($quiz_list_result)):
                        $qid_now  = (int) $quiz['quiz_id'];
                        $st       = $quiz_progress[$qid_now] ?? ['total' => 0, 'answered' => 0, 'correct' => 0, 'last' => null];
                        $st_total = $st['total'];
                        $st_ans   = $st['answered'];
                        $st_cor   = $st['correct'];
                        $st_last  = $st['last'];

                        if ($st_total > 0 && $st_ans >= $st_total) {
                            $lvl_status = 'done';
                            $btn_label  = 'Replay';
                            $badge_txt  = 'Done';
                        } else if ($st_ans > 0) {
                            $lvl_status = 'in-progress';
                            $btn_label  = 'Continue';
                            $badge_txt  = 'In Progress';
                        } else {
                            $lvl_status = 'not-started';
                            $btn_label  = 'Play';
                            $badge_txt  = '';
                        }

                        if ($st_ans > 0) {
                            $tip  = $st_ans . '/' . $st_total . ' answered · ' . $st_cor . '/' . $st_total . ' correct';
                            if ($st_last) $tip .= ' · Last: ' . date('j M Y g:ia', strtotime($st_last));
                        } else {
                            $tip = 'Not attempted yet';
                        }
                    ?>
                        <div class="pixel-panel vc-level vc-level-<?php echo $lvl_status; ?>" title="<?php echo htmlspecialchars($tip); ?>">
                            <div class="vc-level-badge">
                                <span class="vc-level-num"><?php echo (int)$quiz['level_number']; ?></span>
                                <span class="vc-level-tag">LV</span>
                            </div>

                            <div class="vc-level-body">
                                <span class="vc-level-title">
                                    <?php echo htmlspecialchars($quiz['title']); ?>
                                    <?php if ($badge_txt): ?>
                                        <span class="vc-level-status-pill vc-status-<?php echo $lvl_status; ?>"><?php if ($lvl_status == 'done'): ?><img class="vc-status-tick" src="/Implose.gg-src/assets/images/icons/done.svg" alt=""><?php endif; ?><?php echo $badge_txt; ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="vc-level-desc"><?php echo !empty($quiz['description']) ? htmlspecialchars($quiz['description']) : 'No description.'; ?></span>
                            </div>

                            <div class="vc-level-actions">
                                <a class="btn-pixel card-action-fill mkt-danger-btn" href="/Implose.gg-src/pages/user/game/live_quiz/quiz.php?quiz_id=<?php echo $qid_now; ?>">
                                    <?php echo $btn_label; ?>
                                </a>
                                <a class="btn-pixel vc-mini-btn" href="/Implose.gg-src/pages/user/game/live_quiz/host.php?quiz_id=<?php echo $qid_now; ?>">
                                    Host Live
                                </a>
                                <a class="btn-pixel vc-mini-btn" href="/Implose.gg-src/pages/user/game/quiz/edit_quiz.php?quiz_id=<?php echo $qid_now; ?>">
                                    <span><img src="/Implose.gg-src/assets/images/icons/pencil.svg" alt="edit"></span>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
