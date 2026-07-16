<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/marketplace/my_publish.php
Description: marketplace - my published courses
First Written on: Wednesday, 25-Jun-2026
Edited on: Monday, 29-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

        $user_id = $_SESSION['user_id'];

        // get the user's published courses
        $mine_sql = "SELECT * FROM MARKETPLACE_COURSE_T WHERE creator_id = '$user_id' AND is_deleted = 0 ORDER BY updated_at DESC";
        $mine_result = mysqli_query($conn, $mine_sql);
        $mine_total = mysqli_num_rows($mine_result);
    ?>

    <title>Marketplace - My Published</title>
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
            <span class="mkt-page-title pixel-title">Marketplace</span>
            <span class="mkt-page-desc">Discover, fork, and publish open-source quiz courses from the community.</span>
            <hr>
        </div>

        <!-- Body -->
        <div class="mkt-body">
            <?php
                $current_page = 'mine';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/nav.php');
            ?>

            <!-- Right -->
            <div class="mkt-right">

                <!-- RHS head -->
                <div class="mkt-right-head">
                    <div class="mkt-section-title pixel-title">
                        My Published
                        <span class="count-pill"><?php echo $mine_total; ?> courses</span>
                    </div>

                    <a class="btn-red" href="/Implose.gg-src/pages/user/marketplace/publish_course.php">+ Publish Course</a>
                </div>

                <!-- RHS body -->
                <?php
                    if ($mine_total == 0) {
                ?>
                    <div class="pixel-panel mkt-empty">
                        <p>You haven't published anything yet.</p>
                    </div>


                <?php
                    } else {
                ?>
                    <div class="mkt-grid">
                        <?php while ($row = mysqli_fetch_assoc($mine_result)) {
                            $marketplace_course_id = $row['marketplace_course_id'];

                            // count no. of quizzes in this course
                            $quiz_count_sql = "SELECT COUNT(*) AS total FROM MARKETPLACE_QUIZ_T WHERE marketplace_course_id = '$marketplace_course_id'";
                            $quiz_count = mysqli_fetch_assoc(mysqli_query($conn, $quiz_count_sql))['total'];

                            // count no. of questions in this course
                            $question_count = 0;
                            $quiz_id_sql = "SELECT marketplace_quiz_id FROM MARKETPLACE_QUIZ_T WHERE marketplace_course_id = '$marketplace_course_id'";
                            $quiz_id_result = mysqli_query($conn, $quiz_id_sql);
                            while ($quiz_id_row = mysqli_fetch_assoc($quiz_id_result)) {
                                $quiz_id = $quiz_id_row['marketplace_quiz_id'];
                                $question_count_sql = "SELECT COUNT(*) AS total FROM MARKETPLACE_QUESTION_T WHERE marketplace_quiz_id = '$quiz_id'";
                                $question_count += mysqli_fetch_assoc(mysqli_query($conn, $question_count_sql))['total'];
                            }

                            // count no. of times this course has been forked
                            $fork_count_sql = "SELECT COUNT(*) AS total FROM COURSE_T WHERE forked_from = '$marketplace_course_id'";
                            $fork_count = mysqli_fetch_assoc(mysqli_query($conn, $fork_count_sql))['total'];

                            $thumb = '/Implose.gg-src/' . $row['thumbnail_path'];
                            $detail_url = '/Implose.gg-src/pages/user/marketplace/course_details.php?id=' . $marketplace_course_id;
                        ?>

                        <!-- RHS course card -->
                        <div class="pixel-panel mkt-card">
                            <div class="mkt-card-thumb">
                                <img src="<?php echo $thumb; ?>" alt="thumbnail">


                                <span class="badge">PUBLISHED</span>
                            </div>

                            <div class="mkt-card-body">
                                <span class="mkt-card-title pixel-title"><?php echo $row['title']; ?></span>

                                <div class="mkt-card-creator">
                                    <span>Updated <?php echo date('M j, Y', strtotime($row['updated_at'])); ?></span>
                                </div>

                                <div class="mkt-card-desc">
                                    <?php echo $row['description']; ?>
                                </div>

                                <div class="mkt-card-stats">
                                    <span><strong><?php echo $quiz_count; ?></strong> quizzes</span>
                                    <span><strong><?php echo $question_count; ?></strong> questions</span>
                                    <span><strong><?php echo $fork_count; ?></strong> forks</span>
                                </div>
                            </div>

                            <div class="mkt-card-actions">
                                <a class="btn-pixel card-action-fill" href="<?php echo $detail_url; ?>">View</a>

                                
                                <form action="/Implose.gg-src/actions/user/marketplace/delete_marketplace_course.php" method="post" onsubmit="return confirm('Unpublish this course?');">
                                    <input type="hidden" name="marketplace_course_id" value="<?php echo $row['marketplace_course_id']; ?>">
                                    
                                    <button type="submit" class="btn-pixel mkt-danger-btn mkt-icon-btn" title="Unpublish">
                                        <img src="/Implose.gg-src/assets/images/icons/trash.svg" alt="Delete">
                                    </button>
                                </form>
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
