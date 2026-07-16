<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/marketplace/my_forks.php
Description: marketplace - my forked courses
First Written on: Wednesday, 25-Jun-2026
Edited on: Monday, 29-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

        $user_id = $_SESSION['user_id'];

        // get the user fork de courses
        $forks_sql = "SELECT * FROM COURSE_T WHERE creator_id = '$user_id' AND forked_from IS NOT NULL ORDER BY updated_at DESC";
        $forks_result = mysqli_query($conn, $forks_sql);
        $forks_total = mysqli_num_rows($forks_result);
    ?>

    <title>Marketplace - My Forks</title>
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
                $current_page = 'my_forks';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/nav.php');
            ?>

            <!-- Right -->
            <div class="mkt-right">
                <div class="mkt-right-head">
                    <div class="mkt-section-title pixel-title">
                        My Forks
                        <span class="count-pill"><?php echo $forks_total; ?> forked</span>
                    </div>

                    <a class="btn-pixel" href="/Implose.gg-src/pages/user/marketplace/index.php?sort=newest">Discover More</a>
                </div>

                <!-- RHS course card -->
                <?php
                    if ($forks_total == 0) {
                ?>
                    <div class="pixel-panel mkt-empty">
                        <p>No forks yet.</p>
                    </div>


                <?php
                    } else {
                ?>
                    <div class="mkt-grid">
                        <?php while ($row = mysqli_fetch_assoc($forks_result)) {
                            $course_id = $row['course_id'];
                            $forked_from = $row['forked_from'];

                            // find the original marketplace course using forked_from id
                            $source_sql = "SELECT title, creator_id FROM MARKETPLACE_COURSE_T WHERE marketplace_course_id = '$forked_from'";
                            $source = mysqli_fetch_assoc(mysqli_query($conn, $source_sql));

                            // find the original creator's username and avatar
                            $source_creator_id = $source['creator_id'];
                            $source_user_sql = "SELECT username, avatar_path FROM USER_T WHERE user_id = '$source_creator_id'";
                            $source_creator = mysqli_fetch_assoc(mysqli_query($conn, $source_user_sql));

                            // count no. of quizzes in this forked course
                            $quiz_count_sql = "SELECT COUNT(*) AS total FROM QUIZ_T WHERE course_id = '$course_id'";
                            $quiz_count = mysqli_fetch_assoc(mysqli_query($conn, $quiz_count_sql))['total'];

                            // count no. of questions in this forked course
                            $question_count = 0;
                            $quiz_id_sql = "SELECT quiz_id FROM QUIZ_T WHERE course_id = '$course_id'";
                            $quiz_id_result = mysqli_query($conn, $quiz_id_sql);
                            while ($quiz_id_row = mysqli_fetch_assoc($quiz_id_result)) {
                                $quiz_id = $quiz_id_row['quiz_id'];
                                $question_count_sql = "SELECT COUNT(*) AS total FROM QUESTION_T WHERE quiz_id = '$quiz_id'";
                                $question_count += mysqli_fetch_assoc(mysqli_query($conn, $question_count_sql))['total'];
                            }

                            $thumb = '/Implose.gg-src/' . $row['thumbnail_path'];
                            $src_avatar = '/Implose.gg-src/' . $source_creator['avatar_path'];

                            $course_url = '/Implose.gg-src/pages/user/game/manage_course.php?course_id=' . $course_id;
                            $source_url = '/Implose.gg-src/pages/user/marketplace/course_details.php?id=' . $forked_from;
                        ?>

                        <!-- course card -->
                        <div class="pixel-panel mkt-card">
                            <div class="mkt-card-thumb">
                                <img src="<?php echo $thumb; ?>" alt="thumbnail">
                                <span class="badge">FORK</span>
                            </div>

                            <div class="mkt-card-body">
                                <span class="mkt-card-title pixel-title"><?php echo $row['title']; ?></span>

                                <div class="mkt-card-creator">
                                    <img src="<?php echo $src_avatar; ?>" alt="">
                                    <span>forked from <a class="mkt-card-source" href="<?php echo $source_url; ?>"><?php echo $source_creator['username']; ?>/<?php echo $source['title']; ?></a></span>
                                </div>

                                <div class="mkt-card-desc">
                                    <?php echo $row['description']; ?>
                                </div>

                                <div class="mkt-card-stats">
                                    <span><strong><?php echo $quiz_count; ?></strong> quizzes</span>
                                    <span><strong><?php echo $question_count; ?></strong> questions</span>
                                    <span>forked <?php echo date('M j', strtotime($row['created_at'])); ?></span>
                                </div>
                            </div>

                            <div class="mkt-card-actions">
                                <a class="btn-red card-action-fill" href="<?php echo $course_url; ?>">Open</a>
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
