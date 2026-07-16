<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/quiz/create_quiz.php
Description: User Create quiz page
First Written on: Tuesday, 30-Jun-2026
Edited on: Tuesday, 30-Jun-2026
-->
<?php
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['create_quiz_success'])) {
    $success_msg = $_SESSION['create_quiz_success'];
    unset($_SESSION['create_quiz_success']);
}

if (isset($_SESSION['create_quiz_error'])) {
    $error_msg = $_SESSION['create_quiz_error'];
    unset($_SESSION['create_quiz_error']);
}

$course_id = (int)($_GET['course_id'] ?? 0);

$course_sql = "SELECT * FROM COURSE_T WHERE course_id = '$course_id' AND creator_id = '$_SESSION[user_id]'";
$course_result = mysqli_query($conn, $course_sql);

if (!$course_result || mysqli_num_rows($course_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/manage_course.php?course_id=' . $course_id);
    exit();
}

$course = mysqli_fetch_assoc($course_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_create_quiz.css">
    <title>Create Quiz — Implose.gg User</title>
    <meta name="description" content="Create a new Quiz.">
</head>
<body>
    <?php
        $current_page = 'course';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">
        <section>
            <div class="cq-container">
            <nav class="cq-breadcrumb">
                <a href="/Implose.gg-src/pages/user/game/view_course.php">Course</a>
                <span class="sep">></span>
                <a href="/Implose.gg-src/pages/user/game/manage_course.php?course_id=<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['title']); ?></a>
                <span class="sep">></span>
                <span class="current">Create Quiz</span>
            </nav>

            <div class="cq-main-title">
                <h1 class="pixel-title">Create a quiz</h1>
                <p class="page-subtitle">Set quiz Name, add description.</p>
            </div>

            <?php if ($error_msg): ?>
                <p class="cq-error"><?php echo htmlspecialchars($error_msg); ?></p>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <p class="cq-success"><?php echo htmlspecialchars($success_msg); ?></p>
            <?php endif; ?>

            <form action="/Implose.gg-src/actions/user/game/quiz/create_quiz.php" method="post">
                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                <div class="pixel-panel cq-panel">
                    <h2 class="pixel-title">Quiz Details</h2>

                    <div class="cq-field">
                        <label>Quiz Title</label>
                        <div class="txt-container">
                            <input type="text" name="title" placeholder="e.g. Lesson 1">
                        </div>
                    </div>

                    <div class="cq-field">
                        <label>Description</label>
                        <div class="txt-container">
                            <textarea name="description" placeholder="e.g. This quiz covers the basics of Python."></textarea>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-red">Create Quiz</button>
            </form>
            </div>
        </section>
    </div>

</body>
</html>
