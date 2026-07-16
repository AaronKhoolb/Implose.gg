<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/user/account/feedback_detail.php
Description: Account Center — My Feedback page. Lets the signed-in
            user see every rating they've submitted with the target
            (quiz / course), emoji, and comment, then edit, delete,
            or add a brand new rating for any course they've played.
            Edit + delete talk to /actions/user/update_feedback.php
            and /actions/user/delete_feedback.php over fetch. Add
            reuses the existing /actions/user/submit_feedback.php.
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/complete_profile.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_account.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_feedback.css">
    <!-- reuse the emoji-row / comment box / .selected styles from the popup -->
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/components/feedback_popup.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_feedback_detail.css?v=<?= time() ?>">
    <title>Account Center — My Feedback</title>
</head>

<body>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');

        $user_id = $_SESSION['user_id'];

        // 1. every feedback this user has submitted, newest first
        $fb_sql = "SELECT f.feedback_id, f.emoji_rating, f.description, f.created_at,
                          f.course_id, f.quiz_id,
                          c.title AS course_title,
                          q.title AS quiz_title
                     FROM COURSE_FEEDBACK_T f
                     LEFT JOIN COURSE_T c ON c.course_id = f.course_id
                     LEFT JOIN QUIZ_T   q ON q.quiz_id   = f.quiz_id
                    WHERE f.user_id = '$user_id'
                    ORDER BY f.created_at DESC";

        $fb_res = mysqli_query($conn, $fb_sql);
        $my_feedback = array();
        if ($fb_res) {
            while ($row = mysqli_fetch_assoc($fb_res)) {
                $my_feedback[] = $row;
            }
        }

        // 2. courses the user has actually played (has an attempt in), so
        //    the "Add feedback" form only offers targets that make sense.
        $course_sql = "SELECT DISTINCT c.course_id, c.title
                         FROM COURSE_T c
                         JOIN QUIZ_T q ON q.course_id = c.course_id
                         JOIN QUESTION_T que ON que.quiz_id = q.quiz_id
                         JOIN QUIZ_LEARNING_RECORD_T lr ON lr.question_id = que.question_id
                        WHERE lr.user_id = '$user_id'
                        ORDER BY c.title ASC";

        $course_res = mysqli_query($conn, $course_sql);
        $playable_courses = array();
        if ($course_res) {
            while ($row = mysqli_fetch_assoc($course_res)) {
                $playable_courses[] = $row;
            }
        }

        $emoji_face = array(
            'angry'     => array('😭', 'Big cry'),
            'sad'       => array('😢', 'Small cry'),
            'neutral'   => array('😐', 'Neutral'),
            'happy'     => array('😊', 'Small happy'),
            'excellent' => array('😄', 'Big happy')
        );
    ?>

    <div class="main-content">

        <div class="account-top">
            <span class="account-title pixel-title">Account Center</span>
            <span class="account-description">Manage your account settings</span>
            <hr>
        </div>

        <div class="account-body">
            <?php
                $current_page = 'feedback_detail';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/account/nav.php');
            ?>

            <div class="account-right">
                <div class="profile_setup-container pixel-panel">
                    <div class="profile_setup-header">
                        <span class="title pixel-title">My Feedback</span>
                        <span class="subtitle">Every rating you've left. You can edit, delete, or add a new one.</span>
                    </div>

                    <div class="fbd-actions-row">
                        <span class="fbd-count"><?php echo count($my_feedback); ?> total</span>
                        <button type="button" class="btn-red" id="fbd-add-open">+ Add Feedback</button>
                    </div>

                    <!-- FEEDBACK LIST -->
                    <?php if (count($my_feedback) == 0) { ?>
                        <div class="mkt-fb-empty pixel-panel">
                            <p>You haven't left any feedback yet.</p>
                            <p>Play a quiz to unlock the emoji popup, or click <b>+ Add Feedback</b> to rate a course you've played.</p>
                        </div>
                    <?php } else { ?>
                        <div class="fbd-list">
                            <?php foreach ($my_feedback as $r) {
                                // default emoji if the rating value isn't in our map
                                $face  = '❔';
                                $label = $r['emoji_rating'];
                                if (isset($emoji_face[$r['emoji_rating']])) {
                                    $face  = $emoji_face[$r['emoji_rating']][0];
                                    $label = $emoji_face[$r['emoji_rating']][1];
                                }

                                // target label: quiz name is preferred over course name
                                if ($r['quiz_title'] != null && $r['quiz_title'] != '') {
                                    $target = 'Quiz: ' . $r['quiz_title'];
                                } else if ($r['course_title'] != null && $r['course_title'] != '') {
                                    $target = 'Course: ' . $r['course_title'];
                                } else {
                                    $target = 'Deleted course';
                                }

                                if ($r['description'] != null) {
                                    $desc = trim($r['description']);
                                } else {
                                    $desc = '';
                                }
                            ?>
                                <div class="pixel-panel fbd-item"
                                     data-id="<?php echo $r['feedback_id']; ?>"
                                     data-emoji="<?php echo htmlspecialchars($r['emoji_rating']); ?>"
                                     data-desc="<?php echo htmlspecialchars($desc); ?>">
                                    <div class="fbd-item-emoji" title="<?php echo htmlspecialchars($label); ?>"><?php echo $face; ?></div>
                                    <div class="fbd-item-body">
                                        <div class="fbd-item-head">
                                            <span class="fbd-item-target"><?php echo htmlspecialchars($target); ?></span>
                                            <span class="fbd-item-date"><?php echo date('j M Y, g:ia', strtotime($r['created_at'])); ?></span>
                                        </div>
                                        <?php if ($desc != '') { ?>
                                            <div class="fbd-item-comment"><?php echo htmlspecialchars($desc); ?></div>
                                        <?php } else { ?>
                                            <div class="fbd-item-comment fbd-item-comment--empty">No comment left.</div>
                                        <?php } ?>
                                    </div>
                                    <div class="fbd-item-actions">
                                        <button type="button" class="btn-pixel fbd-edit-btn">Edit</button>
                                        <button type="button" class="btn-pixel mkt-danger-btn fbd-delete-btn">Delete</button>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>


    <!-- ================= EDIT MODAL ================= -->
    <div class="fbd-backdrop" id="fbd-edit-modal">
        <div class="pixel-panel fbd-modal">
            <button type="button" class="fbd-close" id="fbd-edit-close" aria-label="Close">
                <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="close">
            </button>
            <h2 class="pixel-title fbd-modal-title">Edit your feedback</h2>
            <p class="fbd-modal-sub">Change how you feel or update the comment.</p>

            <div class="fb-emoji-row" id="fbd-edit-emojis">
                <button type="button" class="fb-emoji" data-rating="angry"><span class="fb-emoji-face">😭</span><span class="fb-emoji-label">Big cry</span></button>
                <button type="button" class="fb-emoji" data-rating="sad"><span class="fb-emoji-face">😢</span><span class="fb-emoji-label">Small cry</span></button>
                <button type="button" class="fb-emoji" data-rating="neutral"><span class="fb-emoji-face">😐</span><span class="fb-emoji-label">Neutral</span></button>
                <button type="button" class="fb-emoji" data-rating="happy"><span class="fb-emoji-face">😊</span><span class="fb-emoji-label">Small happy</span></button>
                <button type="button" class="fb-emoji" data-rating="excellent"><span class="fb-emoji-face">😄</span><span class="fb-emoji-label">Big happy</span></button>
            </div>

            <label class="fb-comment-label" for="fbd-edit-comment">
                Comment <span class="fb-comment-optional">(optional)</span>
            </label>
            <textarea id="fbd-edit-comment" class="fb-comment" maxlength="500" rows="3" placeholder="Tell us what you thought..."></textarea>

            <div class="fb-actions">
                <button type="button" class="btn-pixel" id="fbd-edit-cancel">Cancel</button>
                <button type="button" class="btn-red"   id="fbd-edit-save">Save</button>
            </div>
            <div class="fb-status" id="fbd-edit-status" aria-live="polite"></div>
        </div>
    </div>


    <!-- ================= ADD MODAL ================= -->
    <div class="fbd-backdrop" id="fbd-add-modal">
        <div class="pixel-panel fbd-modal">
            <button type="button" class="fbd-close" id="fbd-add-close" aria-label="Close">
                <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="close">
            </button>
            <h2 class="pixel-title fbd-modal-title">Add feedback</h2>
            <p class="fbd-modal-sub">Pick a course you've played and rate it.</p>

            <label class="fb-comment-label" for="fbd-add-course">Course</label>
            <div class="pixel-select fbd-select-wrap">
                <select id="fbd-add-course">
                    <?php if (count($playable_courses) == 0) { ?>
                        <option value="">— No played courses yet —</option>
                    <?php } else { ?>
                        <?php foreach ($playable_courses as $c) { ?>
                            <option value="<?php echo $c['course_id']; ?>">
                                <?php echo htmlspecialchars($c['title']); ?>
                            </option>
                        <?php } ?>
                    <?php } ?>
                </select>
            </div>

            <div class="fb-emoji-row" id="fbd-add-emojis">
                <button type="button" class="fb-emoji" data-rating="angry"><span class="fb-emoji-face">😭</span><span class="fb-emoji-label">Big cry</span></button>
                <button type="button" class="fb-emoji" data-rating="sad"><span class="fb-emoji-face">😢</span><span class="fb-emoji-label">Small cry</span></button>
                <button type="button" class="fb-emoji" data-rating="neutral"><span class="fb-emoji-face">😐</span><span class="fb-emoji-label">Neutral</span></button>
                <button type="button" class="fb-emoji" data-rating="happy"><span class="fb-emoji-face">😊</span><span class="fb-emoji-label">Small happy</span></button>
                <button type="button" class="fb-emoji" data-rating="excellent"><span class="fb-emoji-face">😄</span><span class="fb-emoji-label">Big happy</span></button>
            </div>

            <label class="fb-comment-label" for="fbd-add-comment">
                Comment <span class="fb-comment-optional">(optional)</span>
            </label>
            <textarea id="fbd-add-comment" class="fb-comment" maxlength="500" rows="3" placeholder="Tell us what you thought..."></textarea>

            <div class="fb-actions">
                <button type="button" class="btn-pixel" id="fbd-add-cancel">Cancel</button>
                <button type="button" class="btn-red"   id="fbd-add-save" disabled>Submit</button>
            </div>
            <div class="fb-status" id="fbd-add-status" aria-live="polite"></div>
        </div>
    </div>


    <script src="/Implose.gg-src/assets/js/user/account/feedback_detail.js"></script>
</body>
</html>
