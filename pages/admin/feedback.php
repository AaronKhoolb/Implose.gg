<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/admin/feedback.php
Description: Admin Feedback Management Page
            - Stat cards (Total, Positive, Neutral, Negative)
            - Filter by emoji + search
            - Table of feedback with user, target (quiz/course), emoji,
              comment, date + Edit / Delete actions
            - Edit button links to /pages/admin/edit_feedback.php?id=X
            - Delete button submits a hidden POST form to
              /actions/admin/delete_feedback.php
            - .admin-toast renders any flash message set by those actions
First Written on: Saturday, 27-Jun-2026
Edited on: Sunday, 05-Jul-2026
-->

<?php
session_start();

// Flash messages set by update_feedback.php / delete_feedback.php
$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['feedback_success'])) {
    $success_msg = $_SESSION['feedback_success'];
    unset($_SESSION['feedback_success']);
}

if (isset($_SESSION['feedback_error'])) {
    $error_msg = $_SESSION['feedback_error'];
    unset($_SESSION['feedback_error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_feedback.css?v=<?= time() ?>">
    <title>Feedback Management — Implose.gg Admin</title>
</head>


<body class="admin-body">
    <?php
        $current_page = 'admin_feedback';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <?php
        // Simple SELECT COUNT() helper — used a lot on this page
        function fb_count($conn, $sql) {
            $r = mysqli_query($conn, $sql);
            if ($r && mysqli_num_rows($r) > 0) {
                $row = mysqli_fetch_assoc($r);
                return $row['c'];
            }
            return 0;
        }

        // Totals + per-emoji breakdown
        $fb_total     = fb_count($conn, "SELECT COUNT(*) AS c FROM COURSE_FEEDBACK_T");
        $fb_angry     = fb_count($conn, "SELECT COUNT(*) AS c FROM COURSE_FEEDBACK_T WHERE emoji_rating = 'angry'");
        $fb_sad       = fb_count($conn, "SELECT COUNT(*) AS c FROM COURSE_FEEDBACK_T WHERE emoji_rating = 'sad'");
        $fb_neutral   = fb_count($conn, "SELECT COUNT(*) AS c FROM COURSE_FEEDBACK_T WHERE emoji_rating = 'neutral'");
        $fb_happy     = fb_count($conn, "SELECT COUNT(*) AS c FROM COURSE_FEEDBACK_T WHERE emoji_rating = 'happy'");
        $fb_excellent = fb_count($conn, "SELECT COUNT(*) AS c FROM COURSE_FEEDBACK_T WHERE emoji_rating = 'excellent'");

        $fb_positive  = $fb_happy + $fb_excellent;
        $fb_negative  = $fb_angry + $fb_sad;

        // Satisfaction % — guard against divide-by-zero when there's no feedback
        if ($fb_total > 0) {
            $satisfaction_pct = round(($fb_positive / $fb_total) * 100);
        } else {
            $satisfaction_pct = 0;
        }

        // All feedback rows, newest first, joined to the target course/quiz + author
        $sql = "SELECT f.feedback_id, f.emoji_rating, f.description, f.created_at,
                       c.title AS course_title, q.title AS quiz_title,
                       u.username AS author_name, u.avatar_path AS author_avatar,
                       u.email_address AS author_email
                  FROM COURSE_FEEDBACK_T f
                  LEFT JOIN COURSE_T c ON f.course_id = c.course_id
                  LEFT JOIN QUIZ_T   q ON f.quiz_id   = q.quiz_id
                  LEFT JOIN USER_T   u ON f.user_id   = u.user_id
                 ORDER BY f.created_at DESC";
        $result = mysqli_query($conn, $sql);

        $fb_rows = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $fb_rows[] = $row;
            }
        }

        // Emoji character + human-readable label for each rating value
        $emoji_face = array(
            'angry'     => array('😭', 'Big cry'),
            'sad'       => array('😢', 'Small cry'),
            'neutral'   => array('😐', 'Neutral'),
            'happy'     => array('😊', 'Small happy'),
            'excellent' => array('😄', 'Big happy')
        );
    ?>

    <div class="admin-main-content">

        <div class="fb-page-header">
            <div class="fb-page-header-left">
                <h1>Feedback Management</h1>
                <p>Review quiz and course feedback from your learners.</p>
            </div>
        </div>


        <!-- ── Toast Notification (flash message from update/delete actions) ── -->
        <?php
            // Pick which flash message (if any) to render
            $toast_type = '';
            $toast_text = '';

            if ($success_msg != '') {
                $toast_type = 'success';
                $toast_text = $success_msg;
            } else if ($error_msg != '') {
                $toast_type = 'error';
                $toast_text = $error_msg;
            }
        ?>
        <?php if ($toast_type != '') { ?>
            <div class="admin-toast admin-toast--<?= $toast_type ?>" id="admin-toast">
                <?= htmlspecialchars($toast_text) ?>
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


        <div class="fb-stats-row">

            <div class="stat-card">
                <span class="stat-card-label">Total Feedback</span>
                <span class="stat-card-value"><?= $fb_total ?></span>
                <span class="stat-card-trend muted">All-time count</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/chat-error.svg" alt="feedback">
                </div>
            </div>

            <?php
                // trend colour depends on how good the satisfaction rate is
                if ($satisfaction_pct >= 60) {
                    $positive_class = 'up';
                } else {
                    $positive_class = 'muted';
                }
            ?>
            <div class="stat-card">
                <span class="stat-card-label">Positive (😊 + 😄)</span>
                <span class="stat-card-value"><?= $fb_positive ?></span>
                <span class="stat-card-trend <?= $positive_class ?>">
                    <?= $satisfaction_pct ?>% satisfaction
                </span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/done.svg" alt="positive">
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-card-label">Neutral (😐)</span>
                <span class="stat-card-value"><?= $fb_neutral ?></span>
                <span class="stat-card-trend muted">Mid-pack ratings</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="neutral">
                </div>
            </div>

            <?php
                // negative counter — warn colour if we've had any bad feedback
                if ($fb_negative > 0) {
                    $negative_class = 'warn';
                    $negative_text  = 'Worth investigating';
                } else {
                    $negative_class = 'muted';
                    $negative_text  = 'None so far';
                }
            ?>
            <div class="stat-card">
                <span class="stat-card-label">Negative (😢 + 😭)</span>
                <span class="stat-card-value"><?= $fb_negative ?></span>
                <span class="stat-card-trend <?= $negative_class ?>">
                    <?= $negative_text ?>
                </span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/alert.svg" alt="negative">
                </div>
            </div>

        </div>

        <div class="fb-table-card">

            <div class="fb-toolbar">
                <div class="fb-search-wrap">
                    <input
                        type="text"
                        id="fb-search-input"
                        class="fb-search"
                        placeholder="Search comments / users / targets..."
                        oninput="filterFeedback()">
                    <img class="search-icon" src="/Implose.gg-src/assets/images/icons/search.svg" alt="search">
                </div>

                <select id="fb-filter-rating" class="fb-filter-select" onchange="filterFeedback()">
                    <option value="">All ratings</option>
                    <option value="excellent">😄 Big happy</option>
                    <option value="happy">😊 Small happy</option>
                    <option value="neutral">😐 Neutral</option>
                    <option value="sad">😢 Small cry</option>
                    <option value="angry">😭 Big cry</option>
                </select>
            </div>

            <table class="fb-table" id="fb-table">
                <thead>
                    <tr>
                        <th>Rating</th>
                        <th>From</th>
                        <th>Target</th>
                        <th>Comment</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="fb-tbody">
                    <?php if (count($fb_rows) == 0) { ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:48px 20px;color:var(--admin-text-muted);">
                                No feedback yet. Once learners finish quizzes and rate them, their feedback will appear here.
                            </td>
                        </tr>
                    <?php } else { ?>
                        <?php foreach ($fb_rows as $row) {
                            // Pick the emoji character + label. If the rating isn't
                            // in our map, fall back to a question mark.
                            if (isset($emoji_face[$row['emoji_rating']])) {
                                $face  = $emoji_face[$row['emoji_rating']][0];
                                $label = $emoji_face[$row['emoji_rating']][1];
                            } else {
                                $face  = '❔';
                                $label = $row['emoji_rating'];
                            }

                            // Target — prefer the quiz name, fall back to course
                            if ($row['quiz_title'] != null && $row['quiz_title'] != '') {
                                $target = 'Quiz: ' . $row['quiz_title'];
                            } else if ($row['course_title'] != null && $row['course_title'] != '') {
                                $target = 'Course: ' . $row['course_title'];
                            } else {
                                $target = 'Course: Unknown';
                            }

                            // Author name
                            if ($row['author_name'] != null && $row['author_name'] != '') {
                                $author = $row['author_name'];
                            } else {
                                $author = 'Deleted user';
                            }

                            // Avatar URL
                            if ($row['author_avatar'] != null && $row['author_avatar'] != '') {
                                $avatar = '/Implose.gg-src/' . $row['author_avatar'];
                            } else {
                                $avatar = '';
                            }

                            // Email (may be NULL when the user is gone)
                            if (isset($row['author_email']) && $row['author_email'] != null) {
                                $email = $row['author_email'];
                            } else {
                                $email = '';
                            }

                            // Comment
                            if ($row['description'] != null) {
                                $desc = $row['description'];
                            } else {
                                $desc = '';
                            }

                            // What the search input matches against
                            $search = strtolower($desc . ' ' . $author . ' ' . $target);
                        ?>
                            <tr data-rating="<?= htmlspecialchars($row['emoji_rating']) ?>"
                                data-search="<?= htmlspecialchars($search) ?>">
                                <td>
                                    <span class="fb-rating fb-rating--<?= htmlspecialchars($row['emoji_rating']) ?>">
                                        <span class="fb-rating-face"><?= $face ?></span>
                                        <span class="fb-rating-label"><?= $label ?></span>
                                    </span>
                                </td>
                                <td>
                                    <div class="fb-author">
                                        <div class="fb-author-avatar">
                                            <?php if ($avatar != '') { ?>
                                                <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($author) ?>"
                                                    onerror="this.style.display='none';">
                                            <?php } ?>
                                        </div>
                                        <div class="fb-author-info">
                                            <span class="fb-author-name"><?= htmlspecialchars($author) ?></span>
                                            <span class="fb-author-email"><?= htmlspecialchars($email) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="fb-target"><?= htmlspecialchars($target) ?></span></td>
                                <td>
                                    <?php if ($desc != '') { ?>
                                        <span class="fb-comment"><?= htmlspecialchars($desc) ?></span>
                                    <?php } else { ?>
                                        <span class="fb-comment fb-comment--empty">No comment</span>
                                    <?php } ?>
                                </td>
                                <td><span class="fb-date"><?= date('j M Y, g:ia', strtotime($row['created_at'])) ?></span></td>
                                <td>
                                    <div class="row-actions">
                                        <button type="button" class="row-action-btn" title="Edit"
                                                onclick="window.location.href='/Implose.gg-src/pages/admin/edit_feedback.php?id=<?= $row['feedback_id'] ?>'">
                                            <img src="/Implose.gg-src/assets/images/icons/pencil.svg" alt="Edit">
                                        </button>
                                        <button type="button" class="row-action-btn delete" title="Delete"
                                                onclick="requestDeleteFeedback(<?= $row['feedback_id'] ?>)">
                                            <img src="/Implose.gg-src/assets/images/icons/trash.svg" alt="Delete">
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </tbody>
            </table>

        </div>

    </div>

    <script>
    // Client-side search + emoji filter. Show/hide table rows based on
    // whether they match the search text AND the picked rating.
    function filterFeedback() {
        var q   = document.getElementById('fb-search-input').value.toLowerCase();
        var rat = document.getElementById('fb-filter-rating').value;

        var rows = document.querySelectorAll('#fb-tbody tr');

        for (var i = 0; i < rows.length; i++) {
            var tr = rows[i];

            // skip the "empty state" row (it has no dataset)
            if (!tr.dataset.search) {
                continue;
            }

            // search match — empty query matches everything
            var matchSearch = true;
            if (q != '') {
                if (tr.dataset.search.indexOf(q) === -1) {
                    matchSearch = false;
                }
            }

            // rating match — empty select matches everything
            var matchRating = true;
            if (rat != '') {
                if (tr.dataset.rating != rat) {
                    matchRating = false;
                }
            }

            if (matchSearch && matchRating) {
                tr.style.display = '';
            } else {
                tr.style.display = 'none';
            }
        }
    }

    // Confirm delete, then submit a hidden POST form to the admin delete
    // action. Same pattern as admin/achievement.php's requestDeleteAchievement.
    function requestDeleteFeedback(id) {
        var ok = confirm('Delete feedback #' + id + '? This cannot be undone.');
        if (!ok) {
            return;
        }

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/Implose.gg-src/actions/admin/delete_feedback.php';

        var input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = 'feedback_id';
        input.value = id;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
    </script>

</body>
</html>
