<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/marketplace/index.php
Description: marketplace - discover all open-source quiz courses
First Written on: Wednesday, 25-Jun-2026
Edited on: Monday, 29-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/course_rating.php');

        if (isset($_GET['search'])) {
            $search = trim($_GET['search']);
        } else {
            $search = '';
        }

        $sort_by = $_GET['sort'];

        if ($sort_by == 'oldest') {
            $order = "created_at ASC";
        } else {
            $order = "created_at DESC";
        }

        // get list of courses (filter by title/description if searching)
        if ($search != '') {
            $list_sql = "SELECT * FROM MARKETPLACE_COURSE_T WHERE (title LIKE '%$search%' OR description LIKE '%$search%') AND is_deleted = 0 ORDER BY $order";
        } else {
            $list_sql = "SELECT * FROM MARKETPLACE_COURSE_T WHERE is_deleted = 0 ORDER BY $order";
        }

        $list_result = mysqli_query($conn, $list_sql);
        $total_filtered = mysqli_num_rows($list_result);
    ?>

    <title>Marketplace - Discover</title>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/marketplace.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_feedback.css">
</head>


<body>
    <?php
        $current_page = 'user_marketplace';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">

        <!-- Top -->
        <div class="mkt-top">
            <span class="mkt-page-title pixel-title">Marketplace</span>
            <span class="mkt-page-desc">Discover, fork, and publish open-source quiz courses from the community.</span>
            <hr>
        </div>

        <!-- Body -->
        <div class="mkt-body">

            <!-- Left nav -->
            <?php
                $current_page = 'discover';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/nav.php');
            ?>

            <!-- Right -->
            <div class="mkt-right">
                <div class="mkt-right-head">
                    <!-- RHS head -->
                    <div class="mkt-section-title pixel-title">
                        Discover
                        <span class="count-pill"><?php echo $total_filtered; ?> results</span>
                    </div>

                    <!-- RHS center toolbar -->
                    <form method="get" class="toolbar">
                        <div class="txt-container <?php if ($search != '') echo 'has-text'; ?>">
                            <input type="text" name="search" id="search" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">

                            <button type="button" class="clear-btn" data-target="search">
                                <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear">
                            </button>
                        </div>

                        <div class="pixel-select">
                            <select name="sort" onchange="this.form.submit()">
                                <option value="newest" <?php if ($sort_by == 'newest') echo 'selected'; ?>>Newest</option>
                                <option value="oldest" <?php if ($sort_by == 'oldest') echo 'selected'; ?>>Oldest</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-red search-btn">Search</button>
                    </form>
                </div>

                <!-- RHS course card -->
                <?php
                    if (mysqli_num_rows($list_result) == 0) {
                ?>
                    <div class="pixel-panel mkt-empty">
                        <p>No courses found.</p>
                    </div>


                <?php
                    } else {
                ?>
                    <div class="mkt-grid">
                    <?php 
                        while ($row = mysqli_fetch_assoc($list_result)) {
                            $marketplace_course_id = $row['marketplace_course_id'];
                            $creator_id = $row['creator_id'];

                            // get the creator's username and avatar
                            $user_sql = "SELECT username, avatar_path FROM USER_T WHERE user_id = '$creator_id'";
                            $user = mysqli_fetch_assoc(mysqli_query($conn, $user_sql));

                            // count how many quizzes in this course
                            $quiz_count_sql = "SELECT COUNT(*) AS total FROM MARKETPLACE_QUIZ_T WHERE marketplace_course_id = '$marketplace_course_id'";
                            $quiz_count = mysqli_fetch_assoc(mysqli_query($conn, $quiz_count_sql))['total'];

                            // count how many questions in this course
                            $question_count = 0;
                            $quiz_id_sql = "SELECT marketplace_quiz_id FROM MARKETPLACE_QUIZ_T WHERE marketplace_course_id = '$marketplace_course_id'";
                            $quiz_id_result = mysqli_query($conn, $quiz_id_sql);

                            while ($quiz_id_row = mysqli_fetch_assoc($quiz_id_result)) {
                                $quiz_id = $quiz_id_row['marketplace_quiz_id'];
                                $question_count_sql = "SELECT COUNT(*) AS total FROM MARKETPLACE_QUESTION_T WHERE marketplace_quiz_id = '$quiz_id'";
                                $question_count += mysqli_fetch_assoc(mysqli_query($conn, $question_count_sql))['total'];
                            }

                            // count how many times this course has been forked
                            $fork_count_sql = "SELECT COUNT(*) AS total FROM COURSE_T WHERE forked_from = '$marketplace_course_id'";
                            $fork_count = mysqli_fetch_assoc(mysqli_query($conn, $fork_count_sql))['total'];

                            // aggregate rating across the source course + every fork
                            $feedback_course_ids = course_feedback_ids_for_marketplace($conn, $marketplace_course_id);
                            $rating = course_rating_summary($conn, $feedback_course_ids);

                            $thumb = '/Implose.gg-src/' . $row['thumbnail_path'];
                            $avatar = '/Implose.gg-src/' . $user['avatar_path'];

                            $detail_url   = '/Implose.gg-src/pages/user/marketplace/course_details.php?id=' . $marketplace_course_id;
                            $feedback_url = '/Implose.gg-src/pages/user/marketplace/feedback.php?id=' . $marketplace_course_id;
                            $is_self = $creator_id == $_SESSION['user_id'];
                    ?>

                        <div class="pixel-panel mkt-card">
                            <div class="mkt-card-thumb">
                                <img src="<?php echo $thumb; ?>" alt="thumbnail">


                                <?php
                                    if ($is_self) {
                                ?>
                                    <span class="badge">YOURS</span>
                                <?php
                                    }
                                ?>
                            </div>

                            <div class="mkt-card-body">
                                <span class="mkt-card-title pixel-title">
                                    <?php echo $row['title']; ?>
                                </span>

                                <div class="mkt-card-creator">
                                    <img src="<?php echo $avatar; ?>" alt="avatar">
                                    <span><?php echo $user['username']; ?></span>
                                </div>

                                <div class="mkt-card-desc">
                                    <?php echo $row['description']; ?>
                                </div>

                                <div class="mkt-card-stats">
                                    <span>
                                        <strong><?php echo $quiz_count; ?></strong>
                                        quizzes
                                    </span>

                                    <span>
                                        <strong><?php echo $question_count; ?></strong>
                                        questions
                                    </span>

                                    <span>
                                        <strong><?php echo $fork_count; ?></strong>
                                        forks
                                    </span>
                                </div>

                                <a href="<?php echo $feedback_url; ?>"
                                   class="mkt-rate <?php echo rating_tier_class($rating['tier']); ?>"
                                   title="See all learner feedback">
                                    <span class="mkt-rate-label"><?php echo htmlspecialchars($rating['label']); ?></span>
                                    <span class="mkt-rate-count">
                                        <?php if ($rating['count'] > 0): ?>
                                            <?php echo (int) $rating['count']; ?> rating<?php echo $rating['count'] === 1 ? '' : 's'; ?>
                                        <?php else: ?>
                                            Be the first
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </div>

                            <div class="mkt-card-actions">
                                <a class="btn-pixel card-action-fill" href="<?php echo $detail_url; ?>">
                                    View
                                </a>
                            </div>
                        </div>


                    <?php } ?>
                    </div>
                <?php
                    }
                ?>
            </div>
        </div>

    </div>
</body>
</html>
