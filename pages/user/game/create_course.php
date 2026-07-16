<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/create_course.php
Description: User Create course page
First Written on: Wednesday, 24-Jun-2026
Edited on: Wednesday, 24-Jun-2026
-->
<?php
session_start();

$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['create_course_success'])) {
    $success_msg = $_SESSION['create_course_success'];
    unset($_SESSION['create_course_success']);
}

if (isset($_SESSION['create_course_error'])) {
    $error_msg = $_SESSION['create_course_error'];
    unset($_SESSION['create_course_error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_create_course.css">
    <title>Create Course — Implose.gg User</title>
    <meta name="description" content="Create a new Course.">
</head>
<body>
    <?php
        $current_page = 'course';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">
        <section>
            <div class="cc-container">
            <nav class="cc-breadcrumb">
                <a href="/Implose.gg-src/pages/user/game/view_course.php">Course</a>
                <span class="sep">></span>
                <span class="current">Create</span>
            </nav>

            <div class="cc-main-title">
                <h1 class="pixel-title">Create a course</h1>
                <p class="page-subtitle">Set course Name, add thumbnail, add description.</p>
            </div>

            <?php if ($error_msg): ?>
                <p class="cc-error"><?php echo htmlspecialchars($error_msg); ?></p>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <p class="cc-success"><?php echo htmlspecialchars($success_msg); ?></p>
            <?php endif; ?>

            <form action="/Implose.gg-src/actions/user/game/create_course.php" method="post" enctype="multipart/form-data">
                <div class="pixel-panel cc-panel">
                    <h2 class="pixel-title">Course Details</h2>

                    <div class="cc-field">
                        <label>Course Title</label>
                        <div class="txt-container">
                            <input type="text" name="title" placeholder="e.g. Python Basics">
                        </div>
                    </div>

                    <div class="cc-field">
                        <label>Description</label>
                        <div class="txt-container">
                            <textarea name="description" placeholder="e.g. This course is about Python Basics."></textarea>
                        </div>
                    </div>

                    <div class="cc-field">
                        <label>Thumbnail</label>
                        <label class="upload-box pixel-panel" for="thumbnail">
                            
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/png, image/jpeg">
                                <img src="/Implose.gg-src/assets/images/icons/upload.svg" alt="upload">
                                <span>Click to upload thumbnail</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-red">Create Course</button>
            </form>
            </div>
        </section>
    </div>

</body>
</html>
