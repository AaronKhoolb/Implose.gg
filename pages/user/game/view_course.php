<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/view_course.php
Description: User view and manage own courses page
First Written on: Tuesday, 30-Jun-2026
Edited on: Tuesday, 30-Jun-2026
-->
<?php
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

$user_id = $_SESSION['user_id'];

$count_all_sql = "SELECT COUNT(*) AS total FROM COURSE_T WHERE creator_id = '$user_id'";
$count_all = mysqli_fetch_assoc(mysqli_query($conn, $count_all_sql))['total'];

$count_forked_sql = "SELECT COUNT(*) AS total FROM COURSE_T WHERE creator_id = '$user_id' AND forked_from IS NOT NULL";
$count_forked = mysqli_fetch_assoc(mysqli_query($conn, $count_forked_sql))['total'];

$tab = $_GET['tab'] ?? 'all';

if ($tab == 'forked') {
    $section_title = 'My Forked';
    $list_sql = "SELECT * FROM COURSE_T WHERE creator_id = '$user_id' AND forked_from IS NOT NULL ORDER BY created_at DESC";
} else {
    $tab = 'all';
    $section_title = 'All Courses';
    $list_sql = "SELECT * FROM COURSE_T WHERE creator_id = '$user_id' ORDER BY created_at DESC";
}

$list_result = mysqli_query($conn, $list_sql);
$total = mysqli_num_rows($list_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/marketplace.css">
    <title>My Courses - Implose.gg</title>
</head>
<body>
    <?php
        $current_page = 'user_course';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">

        <div class="mkt-top">
            <span class="mkt-page-title pixel-title">My Courses</span>
            <span class="mkt-page-desc">Manage the courses you've created or forked. Host a quiz, or edit course details.</span>
            <hr>
        </div>

        <div class="mkt-body">

            <div class="mkt-left-nav pixel-panel">
                <ul>
                    <li>
                        <div class="group-label">MY COURSES</div>
                    </li>

                    <li>
                        <a href="?tab=all" class="<?php if ($tab === 'all') echo 'active'; ?>">
                            <span>All</span>
                            <span class="pill"><?php echo $count_all; ?></span>
                        </a>
                    </li>

                    <li>
                        <a href="?tab=forked" class="<?php if ($tab === 'forked') echo 'active'; ?>">
                            <span>My Forked</span>
                            <span class="pill"><?php echo $count_forked; ?></span>
                        </a>
                    </li>

                    <li>
                        <div class="divider"></div>
                    </li>
                </ul>

                <a href="/Implose.gg-src/pages/user/game/create_course.php" class="publish-li btn-red">
                    + Create Course
                </a>
            </div>
            <div class="mkt-right">
                <div class="mkt-right-head">
                    <div class="mkt-section-title pixel-title">
                        <?php echo $section_title; ?>
                        <span class="count-pill"><?php echo $total; ?> results</span>
                    </div>
                </div>

                <?php if ($total == 0): ?>
                    <div class="pixel-panel mkt-empty">
                        <p>No courses here yet.</p>
                    </div>
                <?php else: ?>
                    <div class="mkt-grid">
                        <?php while ($row = mysqli_fetch_assoc($list_result)): ?>
                            <?php
                                $row_id = $row['course_id'];
                                $creator_id = $row['creator_id'];

                                $user_sql = "SELECT username, avatar_path FROM USER_T WHERE user_id = '$creator_id'";
                                $creator = mysqli_fetch_assoc(mysqli_query($conn, $user_sql));

                                $quiz_count_sql = "SELECT COUNT(*) AS total FROM QUIZ_T WHERE course_id = '$row_id'";
                                $quiz_count = mysqli_fetch_assoc(mysqli_query($conn, $quiz_count_sql))['total'];

                                $question_count = 0;
                                $quiz_id_sql = "SELECT quiz_id FROM QUIZ_T WHERE course_id = '$row_id' ORDER BY level_number ASC";
                                $quiz_id_result = mysqli_query($conn, $quiz_id_sql);
                                while ($quiz_id_row = mysqli_fetch_assoc($quiz_id_result)) {
                                    $quiz_id = $quiz_id_row['quiz_id'];
                                    $question_count_sql = "SELECT COUNT(*) AS total FROM QUESTION_T WHERE quiz_id = '$quiz_id'";
                                    $question_count += mysqli_fetch_assoc(mysqli_query($conn, $question_count_sql))['total'];
                                }

                                $thumb = !empty($row['thumbnail_path']) ? '/Implose.gg-src/' . $row['thumbnail_path'] : '';
                                $avatar = '/Implose.gg-src/' . $creator['avatar_path'];
                                $edit_url = '/Implose.gg-src/pages/user/game/manage_course.php?course_id=' . $row_id;
                            ?>

                            <div class="pixel-panel mkt-card">
                                <div class="mkt-card-thumb">
                                    <?php if ($thumb): ?>
                                        <img src="<?php echo $thumb; ?>" alt="thumbnail">
                                    <?php endif; ?>
                                </div>

                                <div class="mkt-card-body">
                                    <span class="mkt-card-title pixel-title">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </span>

                                    <div class="mkt-card-creator">
                                        <img src="<?php echo $avatar; ?>" alt="avatar">
                                        <span><?php echo htmlspecialchars($creator['username']); ?></span>
                                    </div>

                                    <div class="mkt-card-desc">
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </div>

                                    <div class="mkt-card-stats">
                                        <span><strong><?php echo $quiz_count; ?></strong> quizzes</span>
                                        <span><strong><?php echo $question_count; ?></strong> questions</span>
                                    </div>
                                </div>

                                <div class="mkt-card-actions">
                                    <a class="btn-pixel card-action-fill mkt-danger-btn" href="/Implose.gg-src/pages/user/game/manage_course.php?course_id=<?php echo $row_id; ?>">
                                        View Course
                                    </a>

                                    <a class="btn-pixel mkt-icon-btn" href="<?php echo $edit_url; ?>">
                                        <img src="/Implose.gg-src/assets/images/icons/pencil.svg" alt="edit">
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
