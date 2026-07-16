<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/admin/edit_feedback.php
Description: Admin Edit Feedback Page
            - Load a COURSE_FEEDBACK_T row by ?id= param
            - Show author + target (context so admin knows what they're
              editing), the current emoji rating, and the current
              comment
            - Form submits to /actions/admin/update_feedback.php
              which redirects back to /pages/admin/feedback.php with
              a flash message on success
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
-->

<?php
session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

// 1. Get feedback id from the URL
if (isset($_GET['id'])) {
    $edit_id = $_GET['id'];
} else {
    $edit_id = 0;
}

if ($edit_id <= 0) {
    header('Location: /Implose.gg-src/pages/admin/feedback.php');
    exit();
}

// 2. Fetch the feedback row + who wrote it + what it targets
$edit_sql = "SELECT f.feedback_id, f.emoji_rating, f.description, f.created_at,
                    f.course_id, f.quiz_id,
                    c.title AS course_title, q.title AS quiz_title,
                    u.username AS author_name, u.email_address AS author_email
               FROM COURSE_FEEDBACK_T f
               LEFT JOIN COURSE_T c ON f.course_id = c.course_id
               LEFT JOIN QUIZ_T   q ON f.quiz_id   = q.quiz_id
               LEFT JOIN USER_T   u ON f.user_id   = u.user_id
              WHERE f.feedback_id = '$edit_id'";
$edit_result = mysqli_query($conn, $edit_sql);

if (!$edit_result || mysqli_num_rows($edit_result) != 1) {
    $_SESSION['feedback_error'] = 'Feedback not found.';
    header('Location: /Implose.gg-src/pages/admin/feedback.php');
    exit();
}

$target = mysqli_fetch_assoc($edit_result);

// Build display strings we'll show in the page header + subtitle
if ($target['author_name'] != null && $target['author_name'] != '') {
    $author_display = $target['author_name'];
} else {
    $author_display = 'Deleted user';
}

if ($target['quiz_title'] != null && $target['quiz_title'] != '') {
    $target_display = 'Quiz: ' . $target['quiz_title'];
} else if ($target['course_title'] != null && $target['course_title'] != '') {
    $target_display = 'Course: ' . $target['course_title'];
} else {
    $target_display = 'Deleted course';
}

if ($target['description'] != null) {
    $current_desc = $target['description'];
} else {
    $current_desc = '';
}

// Flash messages set by update_feedback.php on validation failure
$error_msg = '';
if (isset($_SESSION['feedback_error'])) {
    $error_msg = $_SESSION['feedback_error'];
    unset($_SESSION['feedback_error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_edit_user.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_feedback.css?v=<?= time() ?>">
    <!-- emoji picker + comment textarea tokens -->
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/components/feedback_popup.css">
    <title>Edit Feedback — Implose.gg Admin</title>
</head>


<body class="admin-body">
    <?php
        $current_page = 'admin_feedback';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <!-- ── Page Header ── -->
        <div class="eu-page-header">
            <div class="eu-page-header-left">
                <nav class="eu-breadcrumb" aria-label="Breadcrumb">
                    <a href="/Implose.gg-src/pages/admin/feedback.php">Feedback Management</a>
                    <span class="sep">/</span>
                    <span class="current">Edit Feedback #<?= $target['feedback_id'] ?></span>
                </nav>
                <h1>Edit Feedback #<?= $target['feedback_id'] ?></h1>
                <p>Left by <b><?= htmlspecialchars($author_display) ?></b> &middot; <?= htmlspecialchars($target_display) ?></p>
            </div>
        </div>


        <!-- ── Toast (only shown when update_feedback.php bounced back with an error) ── -->
        <?php if ($error_msg != '') { ?>
            <div class="admin-toast admin-toast--error" id="admin-toast">
                <?= htmlspecialchars($error_msg) ?>
            </div>
            <script>
                // fade the toast out after 15 seconds
                setTimeout(function () {
                    var t = document.getElementById('admin-toast');
                    if (t) {
                        t.classList.add('admin-toast--hide');
                    }
                }, 15000);
            </script>
        <?php } ?>


        <!-- ── Feedback Edit Card ── -->
        <div class="eu-card">

            <div class="eu-card-header">
                <img class="eu-card-header-icon" src="/Implose.gg-src/assets/images/icons/chat-error.svg" alt="Feedback">
                <span class="eu-card-header-title">Feedback Details</span>
            </div>

            <div class="eu-card-divider"></div>

            <div class="eu-card-body">

                <form method="POST" action="/Implose.gg-src/actions/admin/update_feedback.php">

                    <input type="hidden" name="feedback_id" value="<?= $target['feedback_id'] ?>">

                    <div class="eu-form-grid">

                        <!-- Emoji Rating -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label">
                                Rating <span class="req">*</span>
                            </label>

                            <div class="fb-emoji-row" id="edit-emoji-row" style="margin-bottom: 6px;">
                                <button type="button" class="fb-emoji" data-rating="angry"><span class="fb-emoji-face">😭</span><span class="fb-emoji-label">Big cry</span></button>
                                <button type="button" class="fb-emoji" data-rating="sad"><span class="fb-emoji-face">😢</span><span class="fb-emoji-label">Small cry</span></button>
                                <button type="button" class="fb-emoji" data-rating="neutral"><span class="fb-emoji-face">😐</span><span class="fb-emoji-label">Neutral</span></button>
                                <button type="button" class="fb-emoji" data-rating="happy"><span class="fb-emoji-face">😊</span><span class="fb-emoji-label">Small happy</span></button>
                                <button type="button" class="fb-emoji" data-rating="excellent"><span class="fb-emoji-face">😄</span><span class="fb-emoji-label">Big happy</span></button>
                            </div>

                            <!-- hidden field the form actually submits -->
                            <input type="hidden" name="emoji_rating" id="emoji-rating-value" value="<?= htmlspecialchars($target['emoji_rating']) ?>" required>

                            <p class="eu-avatar-hint" style="margin-top:6px;">
                                Change the rating only if the admin is moderating obviously spam/troll feedback.
                            </p>
                        </div>

                        <!-- Comment -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label" for="field-description">Comment</label>
                            <textarea
                                id="field-description"
                                name="description"
                                class="eu-input"
                                rows="4"
                                maxlength="500"
                                placeholder="Comment left by the learner..."><?= htmlspecialchars($current_desc) ?></textarea>
                        </div>

                    </div><!-- /.eu-form-grid -->


                    <!-- ── Action Footer ── -->
                    <div class="eu-footer-actions">
                        <a href="/Implose.gg-src/pages/admin/feedback.php" class="btn-cancel">← Back to Feedback</a>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>

                </form>

            </div>

        </div>

    </div>


    <script>
    // Highlight whichever emoji tile matches the current rating.
    // Clicking a tile updates the hidden #emoji-rating-value input so
    // the form submits the new choice.
    (function () {
        var row  = document.getElementById('edit-emoji-row');
        var hidden = document.getElementById('emoji-rating-value');

        function paint(rating) {
            var tiles = row.querySelectorAll('.fb-emoji');
            for (var i = 0; i < tiles.length; i++) {
                if (tiles[i].dataset.rating == rating) {
                    tiles[i].classList.add('selected');
                } else {
                    tiles[i].classList.remove('selected');
                }
            }
        }

        // seed selection from the pre-loaded rating value
        paint(hidden.value);

        var tiles = row.querySelectorAll('.fb-emoji');
        for (var i = 0; i < tiles.length; i++) {
            (function (btn) {
                btn.addEventListener('click', function () {
                    hidden.value = btn.dataset.rating;
                    paint(hidden.value);
                });
            })(tiles[i]);
        }
    })();
    </script>

</body>
</html>
