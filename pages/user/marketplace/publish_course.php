<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/marketplace/publish_course.php
Description: marketplace - publish page
First Written on: Friday, 27-Jun-2026
Edited on: Tuesday, 01-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

        $user_id = $_SESSION['user_id'];

        // get user own courses
        $own_sql = "SELECT * FROM COURSE_T WHERE creator_id = '$user_id' ORDER BY updated_at DESC";
        $own_result = mysqli_query($conn, $own_sql);
        $own_total = mysqli_num_rows($own_result);
    ?>

    <title>Marketplace - Publish</title>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/marketplace.css">
</head>


<body>
    <?php
        $current_page = 'user_marketplace';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">

        <!-- Top -->
        <div class="mkt-top">
            <span class="mkt-page-title pixel-title">Publish to Marketplace</span>
            <span class="mkt-page-desc">Share a course with the community. Other users can fork it like an open-source project.</span>
            <hr>
        </div>

        <!-- Body -->
        <div class="mkt-body">

            <!-- left nav -->
            <?php
                $current_page = 'mine';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/nav.php');
            ?>

            <!-- right content -->
            <div class="mkt-right">
                <!-- RHS head -->
                <div class="mkt-right-head">
                    <div class="mkt-section-title pixel-title">
                        Pick a course
                        <span class="count-pill"><?php echo $own_total; ?> in library</span>
                    </div>
                </div>


                <!-- RHS head -->
                <?php if ($own_total === 0) { ?>
                    <div class="pixel-panel mkt-empty">
                        <p>You don't have any courses yet.</p>
                    </div>


                <?php
                    } else {
                ?>
                    <div class="mkt-grid">
                        <?php while ($row = mysqli_fetch_assoc($own_result)) {
                            $course_id = $row['course_id'];

                            // count the no. of quizzes in the course
                            $quiz_count_sql = "SELECT COUNT(*) AS total FROM QUIZ_T WHERE course_id = '$course_id'";
                            $quiz_count = mysqli_fetch_assoc(mysqli_query($conn, $quiz_count_sql))['total'];

                            // count the total no. of questions in the course
                            $question_count = 0;
                            $quiz_id_sql = "SELECT quiz_id FROM QUIZ_T WHERE course_id = '$course_id'";
                            $quiz_id_result = mysqli_query($conn, $quiz_id_sql);
                            while ($quiz_id_row = mysqli_fetch_assoc($quiz_id_result)) {
                                $quiz_id = $quiz_id_row['quiz_id'];
                                $question_count_sql = "SELECT COUNT(*) AS total FROM QUESTION_T WHERE quiz_id = '$quiz_id'";
                                $question_count += mysqli_fetch_assoc(mysqli_query($conn, $question_count_sql))['total'];
                            }

                            // check this course publish or not
                            $published_sql = "SELECT COUNT(*) AS total FROM MARKETPLACE_COURSE_T WHERE source_course_id = '$course_id' AND is_deleted = 0";
                            $already_published = mysqli_fetch_assoc(mysqli_query($conn, $published_sql))['total'];

                            $thumb = '/Implose.gg-src/' . $row['thumbnail_path'];
                            $next_url = '/Implose.gg-src/pages/user/marketplace/publish_preview.php?course_id=' . $course_id;
                        ?>

                            <!-- RHS course card -->
                            <div class="pixel-panel mkt-card">
                                <div class="mkt-card-thumb">
                                    <img src="<?php echo $thumb; ?>" alt="thumbnail">

                                    
                                    <?php if ($already_published > 0) { ?>
                                        <span class="badge pub-badge">PUBLISHED</span>
                                    <?php } ?>
                                </div>

                                <div class="mkt-card-body">
                                    <span class="mkt-card-title pixel-title">
                                        <?php echo $row['title']; ?>
                                    </span>

                                    <div class="mkt-card-creator">
                                        <span>Updated <?php echo date('M j, Y', strtotime($row['updated_at'])); ?></span>
                                    </div>

                                    <div class="mkt-card-desc">
                                        <?php echo $row['description']; ?>
                                    </div>

                                    <div class="mkt-card-stats">
                                        <span><strong><?php echo $quiz_count; ?></strong> quizzes</span>
                                        <span><strong><?php echo $question_count; ?></strong> questions</span>
                                    </div>
                                </div>

                                <div class="mkt-card-actions">
                                    <a class="btn-red card-action-fill" href="<?php echo $next_url; ?>">Publish &rarr;</a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>

    </div>
</body>
</html>
