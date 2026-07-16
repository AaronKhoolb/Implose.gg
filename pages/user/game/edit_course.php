<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/edit_course.php
Description: User edit course details form page
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

// Where to send the user back to after saving. Only accept safe local paths.
$return_to = $_GET['return'] ?? '';
if (!is_string($return_to) || strpos($return_to, '/Implose.gg-src/') !== 0 || strpbrk($return_to, "\r\n") !== false) {
    $return_to = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_create_course.css">
    <title>Edit Course — Implose.gg User</title>
    <meta name="description" content="Edit course details.">
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
                <a href="/Implose.gg-src/pages/user/game/manage_course.php?course_id=<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['title']); ?></a>
                <span class="sep">></span>
                <span class="current">Edit</span>
            </nav>

            <div class="cc-main-title">
                <h1 class="pixel-title">Edit a course</h1>
                <p class="page-subtitle">Update course details.</p>
            </div>

            <?php if ($error_msg): ?>
                <p class="cc-error"><?php echo htmlspecialchars($error_msg); ?></p>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <p class="cc-success"><?php echo htmlspecialchars($success_msg); ?></p>
            <?php endif; ?>

            <form id="edit-course-form" action="/Implose.gg-src/actions/user/game/edit_course.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($return_to); ?>">
                <div class="pixel-panel cc-panel">
                    <h2 class="pixel-title">Course Details</h2>

                    <div class="cc-field">
                        <label>Course Title</label>
                        <div class="txt-container">
                            <input type="text" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" placeholder="e.g. Python Basics">
                        </div>
                    </div>

                    <div class="cc-field">
                        <label>Description</label>
                        <div class="txt-container">
                            <textarea name="description" placeholder="e.g. This course is about Python Basics."><?php echo htmlspecialchars($course['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="cc-field">
                        <label>Thumbnail</label>

                        <img id="thumb-preview" src="<?php echo $course['thumbnail_path'] ? '/Implose.gg-src/' . htmlspecialchars($course['thumbnail_path']) : ''; ?>" alt="" style="<?php echo $course['thumbnail_path'] ? '' : 'display:none;'; ?>">

                        <label class="upload-box pixel-panel" for="thumbnail">
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/png, image/jpeg">
                                <img src="/Implose.gg-src/assets/images/icons/upload.svg" alt="upload">
                                <span id="thumb-filename">Click to upload thumbnail</span>
                        </label>
                    </div>

                    <div class="cc-field">
                        <label>Course Material</label>

                        <?php
                            $current_material = $course['course_materials'] ?? '';
                            $material_name    = $current_material ? basename($current_material) : '';
                        ?>

                        <?php if ($current_material): ?>
                            <p class="cc-current-material">
                                Current: <?php echo htmlspecialchars($material_name); ?>
                            </p>
                        <?php endif; ?>

                        <label class="upload-box pixel-panel" for="course_material">
                                <input type="file" name="course_material" id="course_material" accept="application/pdf">
                                <img src="/Implose.gg-src/assets/images/icons/upload.svg" alt="upload">
                                <span>Click to upload a PDF</span>
                        </label>
                    </div>
                </div>

            </form>

            <div class="cc-form-actions">
                <button type="submit" form="edit-course-form" class="btn-red">Save Changes</button>

                <form action="/Implose.gg-src/actions/user/game/delete_course.php" method="post" onsubmit="return confirm('Delete this course and its thumbnail? This cannot be undone.');">
                    <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                    <button type="submit" class="btn-pixel">
                        <span><img src="/Implose.gg-src/assets/images/icons/trash.svg" alt=""></span>
                        <span>Delete Course</span>
                    </button>
                </form>
            </div>
            </div>
        </section>
    </div>

    <script>
        document.getElementById('thumbnail').addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
                alert('Thumbnail is too large. Please choose an image under 2 MB.');
                this.value = '';
                document.getElementById('thumb-filename').textContent = 'Click to upload thumbnail';
                return;
            }
            document.getElementById('thumb-filename').textContent = file.name;
            var preview = document.getElementById('thumb-preview');
            preview.src = URL.createObjectURL(file);
            preview.style.display = '';
        });

    </script>

</body>
</html>
