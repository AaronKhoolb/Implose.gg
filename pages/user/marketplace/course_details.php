<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/marketplace/course_details.php
Description: marketplace - discover course details
First Written on: Wednesday, 25-Jun-2026
Edited on: Sunday, 05-Jul-2026
-->

<?php
    // load session + db first - prepared for seo
    include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/auth_check.php');
    include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

    $course_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // get the course
    $course_sql = "SELECT * FROM MARKETPLACE_COURSE_T WHERE marketplace_course_id = '$course_id'";
    $course = mysqli_fetch_assoc(mysqli_query($conn, $course_sql));

    $creator_id = $course['creator_id'];
    $is_owner = $creator_id == $user_id;

    // get the creator username and avatar
    $user_sql = "SELECT username, avatar_path FROM USER_T WHERE user_id = '$creator_id'";
    $creator = mysqli_fetch_assoc(mysqli_query($conn, $user_sql));
    $course['creator_name'] = $creator['username'];
    $course['creator_avatar'] = $creator['avatar_path'];

    // count no. of this course forked
    $fork_count_sql = "SELECT COUNT(*) AS total FROM COURSE_T WHERE forked_from = '$course_id'";
    $course['fork_count'] = mysqli_fetch_assoc(mysqli_query($conn, $fork_count_sql))['total'];

    // get all quizzes in this course
    $quiz_sql = "SELECT * FROM MARKETPLACE_QUIZ_T WHERE marketplace_course_id = '$course_id' ORDER BY level_number ASC, created_at ASC";
    $quiz_result = mysqli_query($conn, $quiz_sql);

    // build quiz list
    // quiz_list -> quiz_row -> question
    $quiz_list = [];
    $total_questions = 0;

    while ($quiz_row = mysqli_fetch_assoc($quiz_result)) {
        $marketplace_quiz_id = $quiz_row['marketplace_quiz_id'];
        $question_result = mysqli_query($conn, "SELECT * FROM MARKETPLACE_QUESTION_T WHERE marketplace_quiz_id = '$marketplace_quiz_id' ORDER BY marketplace_question_id ASC");

        $questions = [];
        while ($question_row = mysqli_fetch_assoc($question_result)) {
            $questions[] = $question_row;
        }

        $quiz_row['questions'] = $questions;
        $quiz_list[] = $quiz_row;

        $total_questions += count($questions);
    }

    $total_quizzes = count($quiz_list);

    // SEO / OG
    $og_title = 'Marketplace - ' . $course['title'];
    $og_description = $course['description'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

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
                $current_page = 'discover';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/nav.php');
            ?>

            <!-- Right -->
            <div class="mkt-right">

                <!-- breadcrumb back btn -->
                <a class="mkt-breadcrumb" href="/Implose.gg-src/pages/user/marketplace/index.php?sort=newest">
                    <img src="/Implose.gg-src/assets/images/icons/chevron-down.svg" alt="">
                    Back to Marketplace
                </a>

                <!-- course detail banner -->
                <?php
                    $show_actions = true;
                    include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/course_banner.php');
                ?>

                <!-- Lessons -->
                <div class="mkt-right-head mkt-lessons-head">
                    <div class="mkt-section-title pixel-title">
                        Lessons
                        <span class="count-pill"><?php echo $total_quizzes; ?> total</span>
                    </div>
                </div>

                <!-- quiz list -->
                <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/quiz_list.php'); ?>
            </div>
        </div>

    </div>

    <!-- Course Report Modal -->
    <div id="course-report-modal">
        <div class="pixel-panel mkt-report-panel">
            <h3 class="pixel-title">Report Course</h3>
            <p>Why are you reporting this course?</p>

            <form id="course-report-form">
                <input type="hidden" id="report-course-id" name="marketplace_course_id" value="<?php echo $course['marketplace_course_id']; ?>">
                <textarea id="course-report-reason" name="reason" rows="4" placeholder="Enter reason..." required></textarea>

                <div class="mkt-report-actions">
                    <button type="button" class="btn-pixel" id="course-report-cancel">Cancel</button>
                    <button type="submit" class="btn-pixel mkt-danger-btn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── report course modal open/close + submit ── -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const reportBtn = document.getElementById('course-report-btn');
            const reportModal = document.getElementById('course-report-modal');
            const reportCancelBtn = document.getElementById('course-report-cancel');
            const reportForm = document.getElementById('course-report-form');

            if (!reportBtn || !reportModal) return;

            // open modal when Report btn is clicked
            reportBtn.addEventListener('click', function (e) {
                e.preventDefault();
                reportModal.style.display = 'flex';
            });

            // cancel btn closes modal
            reportCancelBtn.addEventListener('click', function () {
                reportModal.style.display = 'none';
                reportForm.reset();
            });

            // click outside the panel (on the dark backdrop) also closes
            reportModal.addEventListener('click', function (e) {
                if (e.target === reportModal) {
                    reportModal.style.display = 'none';
                    reportForm.reset();
                }
            });

            // submit report via fetch (need to stay on same page)
            reportForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(reportForm);

                fetch('/Implose.gg-src/actions/user/marketplace/report_course.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.status === 'success') {
                        alert(data.message);
                        reportModal.style.display = 'none';
                        reportForm.reset();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(function (err) {
                    console.error('Error reporting course:', err);
                    alert('An error occurred. Please try again later.');
                });
            });
        });
    </script>
</body>
</html>
