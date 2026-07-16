<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/marketplace/publish_preview.php
Description: marketplace - preview course details before publish
First Written on: Tuesday, 01-Jul-2026
Edited on: Thursday, 02-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

        $user_id = $_SESSION['user_id'];
        $course_id = $_GET['course_id'];

        // get the course
        $course_sql = "SELECT * FROM COURSE_T WHERE course_id = '$course_id' AND creator_id = '$user_id'";
        $course = mysqli_fetch_assoc(mysqli_query($conn, $course_sql));

        // Step 2: get the creator's username and avatar (it's the logged-in user)
        $user_sql = "SELECT username, avatar_path FROM USER_T WHERE user_id = '$user_id'";
        $creator = mysqli_fetch_assoc(mysqli_query($conn, $user_sql));
        $course['creator_name']   = $creator['username'];
        $course['creator_avatar'] = $creator['avatar_path'];

        // decide show YOURS tag
        $is_owner = true;

        // build quiz list (each level with its questions)
        // quiz_list -> quiz_row -> question
        $quizzes_sql = "SELECT * FROM QUIZ_T WHERE course_id = '$course_id' ORDER BY level_number";
        $quizzes_result = mysqli_query($conn, $quizzes_sql);

        $quiz_list = [];
        $total_questions = 0;
        while ($quiz_row = mysqli_fetch_assoc($quizzes_result)) {
            $quiz_id = $quiz_row['quiz_id'];
            $question_result = mysqli_query($conn, "SELECT * FROM QUESTION_T WHERE quiz_id = '$quiz_id' ORDER BY question_id");

            $questions = [];
            while ($question_row = mysqli_fetch_assoc($question_result)) {
                $questions[] = $question_row;
            }

            $quiz_row['questions'] = $questions;
            $quiz_list[] = $quiz_row;
            $total_questions += count($questions);
        }
        $total_quizzes = count($quiz_list);
    ?>

    <title>Marketplace - Publish Preview</title>
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

            <!-- Left nav -->
            <?php
                $current_page = 'mine';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/nav.php');
            ?>

            <!-- Right -->
            <div class="mkt-right">

                <!-- breadcrumb back btn -->
                <a class="mkt-breadcrumb" href="/Implose.gg-src/pages/user/marketplace/publish_course.php">
                    <img src="/Implose.gg-src/assets/images/icons/chevron-down.svg" alt="">
                    Back to Pick a Course
                </a>

                <!-- course detail banner -->
                <?php
                    include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/course_banner.php');
                ?>

                <!-- quiz list -->
                <?php
                    include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/quiz_list.php');
                ?>

                <!-- publish btn -->
                <form action="/Implose.gg-src/actions/user/marketplace/publish_course.php" method="post">
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">

                    <div class="fork-footer">
                        <a class="btn-pixel" href="/Implose.gg-src/pages/user/marketplace/publish_course.php">
                            Cancel
                        </a>

                        <button type="submit" class="btn-pixel btn-pixel-red">Publish to Marketplace</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</body>
</html>
