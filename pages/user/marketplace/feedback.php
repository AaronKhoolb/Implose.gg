<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/user/marketplace/feedback.php
Description: Marketplace — full feedback / reviews page for a single
            marketplace course. Reachable by clicking the rating pill
            on a marketplace card. Aggregates feedback from the
            source course + every fork of that marketplace course,
            shows the overall tier, an emoji-count breakdown, and the
            full comment list newest-first.
First Written on: Saturday, 04-Jul-2026
Edited on: Saturday, 04-Jul-2026
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/course_rating.php');

        // 1. Get course ID from URL (Basic isset check instead of ??)
        if (isset($_GET['id'])) {
            $marketplace_course_id = $_GET['id'];
        } else {
            $marketplace_course_id = 0;
        }

        // 2. Fetch the course details
        $course_sql = "SELECT * FROM MARKETPLACE_COURSE_T WHERE marketplace_course_id = '$marketplace_course_id' AND is_deleted = 0";
        $course_res = mysqli_query($conn, $course_sql);
        
        // Redirect if course cannot be found
        if (mysqli_num_rows($course_res) > 0) {
            $course = mysqli_fetch_assoc($course_res);
        } else {
            header('Location: /Implose.gg-src/pages/user/marketplace/index.php?sort=newest');
            exit();
        }

        // 3. Get creator info
        $creator_id = $course['creator_id'];
        $creator_sql = "SELECT username, avatar_path FROM USER_T WHERE user_id = '$creator_id'";
        $creator_res = mysqli_query($conn, $creator_sql);
        
        if (mysqli_num_rows($creator_res) > 0) {
            $creator = mysqli_fetch_assoc($creator_res);
        } else {
            $creator = array('username' => 'Unknown', 'avatar_path' => '');
        }

        // 4. Get aggregate rating from source course + every fork
        $feedback_course_ids = course_feedback_ids_for_marketplace($conn, $marketplace_course_id);
        $rating = course_rating_summary($conn, $feedback_course_ids);

        // Current viewer — so we can tell which rows belong to them and
        // therefore need edit/delete controls.
        $current_user_id = $_SESSION['user_id'];

        // 5. Build the feedback rows list (fetch user_id too so we can split
        //    "your feedback" from "everyone else's").
        $rows       = array();
        $my_rows    = array();
        $other_rows = array();

        if (count($feedback_course_ids) > 0) {
            // Build a comma-separated string manually for the SQL IN clause
            $in_list = '';
            for ($i = 0; $i < count($feedback_course_ids); $i++) {
                if ($i > 0) {
                    $in_list = $in_list . ',';
                }
                $in_list = $in_list . $feedback_course_ids[$i];
            }

            $sql = "SELECT f.feedback_id, f.user_id, f.course_id, f.quiz_id,
                           f.emoji_rating, f.description, f.created_at,
                           u.username AS author_name, u.avatar_path AS author_avatar,
                           q.title AS quiz_title
                      FROM COURSE_FEEDBACK_T f
                      LEFT JOIN USER_T u ON u.user_id = f.user_id
                      LEFT JOIN QUIZ_T q ON q.quiz_id = f.quiz_id
                     WHERE f.course_id IN ($in_list)
                     ORDER BY f.created_at DESC";

            $res = mysqli_query($conn, $sql);
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $rows[] = $row;

                    if ($row['user_id'] == $current_user_id) {
                        $my_rows[] = $row;
                    } else {
                        $other_rows[] = $row;
                    }
                }
            }
        }

        // The user's forks of this marketplace course. Also the source
        // course itself if the current user is the one who published it.
        // These are the course_ids a brand new feedback row lives on.
        $my_fork_course_ids = array();

        $fork_sql = "SELECT course_id FROM COURSE_T
                      WHERE forked_from = '$marketplace_course_id'
                        AND creator_id  = '$current_user_id'
                      ORDER BY created_at DESC";
        $fork_res = mysqli_query($conn, $fork_sql);
        if ($fork_res) {
            while ($fork_row = mysqli_fetch_assoc($fork_res)) {
                $my_fork_course_ids[] = $fork_row['course_id'];
            }
        }

        // If the marketplace course was published from the current user's
        // own course, that source course also counts.
        if (isset($course['source_course_id'])) {
            $src_cid = $course['source_course_id'];
        } else {
            $src_cid = 0;
        }

        if ($src_cid > 0) {
            $chk_sql = "SELECT creator_id FROM COURSE_T WHERE course_id = '$src_cid' LIMIT 1";
            $chk_res = mysqli_query($conn, $chk_sql);
            if ($chk_res && mysqli_num_rows($chk_res) > 0) {
                $chk_row = mysqli_fetch_assoc($chk_res);
                if ($chk_row['creator_id'] == $current_user_id) {
                    // put the source course at the front by rebuilding the array
                    $new_ids = array();
                    $new_ids[] = $src_cid;
                    for ($i = 0; $i < count($my_fork_course_ids); $i++) {
                        $new_ids[] = $my_fork_course_ids[$i];
                    }
                    $my_fork_course_ids = $new_ids;
                }
            }
        }

        // Pick the newest fork as the default target for a new feedback row
        if (count($my_fork_course_ids) > 0) {
            $add_target_course_id = $my_fork_course_ids[0];
        } else {
            $add_target_course_id = 0;
        }

        // Setup emojis array
        $emoji_face = array(
            'angry'     => array('😭', 'Big cry'),
            'sad'       => array('😢', 'Small cry'),
            'neutral'   => array('😐', 'Neutral'),
            'happy'     => array('😊', 'Small happy'),
            'excellent' => array('😄', 'Big happy')
        );

        // 6. Calculate breakdown bars
        $bar_rows = array();
        if ($rating['count'] > 0) {
            $emoji_counts = array('excellent'=>0, 'happy'=>0, 'neutral'=>0, 'sad'=>0, 'angry'=>0);
            
            // Loop through all feedback to count emojis
            foreach ($rows as $r) {
                $rating_type = $r['emoji_rating'];
                if (isset($emoji_counts[$rating_type])) {
                    $emoji_counts[$rating_type] = $emoji_counts[$rating_type] + 1;
                }
            }
            
            // Create the bar details
            $keys = array('excellent', 'happy', 'neutral', 'sad', 'angry');
            foreach ($keys as $k) {
                $percentage = 0;
                if ($rating['count'] > 0) {
                    $percentage = round(($emoji_counts[$k] / $rating['count']) * 100);
                }

                $bar_rows[] = array(
                    'key'   => $k,
                    'label' => $emoji_face[$k][1],
                    'face'  => $emoji_face[$k][0],
                    'count' => $emoji_counts[$k],
                    'pct'   => $percentage
                );
            }
        }
    ?>

    <title>Marketplace — <?php echo htmlspecialchars($course['title']); ?> Feedback</title>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/marketplace.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_feedback.css">
    <!-- shared feedback popup tokens (.fb-emoji / .fb-comment / .fb-status) -->
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/components/feedback_popup.css">
    <!-- edit/add modal shell + list card styles are shared with the "My Feedback" page -->
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_feedback_detail.css">
</head>

<body>
    <?php
        $current_page = 'user_marketplace';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">

        <div class="mkt-top">
            <span class="mkt-page-title pixel-title">Marketplace</span>
            <span class="mkt-page-desc">Learner feedback for <b><?php echo htmlspecialchars($course['title']); ?></b>.</span>
            <hr>
        </div>

        <div class="mkt-body">
            <?php
                $current_page = 'discover';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/marketplace/nav.php');
            ?>

            <div class="mkt-right">

                <a class="mkt-breadcrumb" href="/Implose.gg-src/pages/user/marketplace/course_details.php?id=<?php echo $marketplace_course_id; ?>">
                    <img src="/Implose.gg-src/assets/images/icons/chevron-down.svg" alt="">
                    Back to Course
                </a>

                <div class="mkt-right-head" style="margin-top:10px;">
                    <div class="mkt-section-title pixel-title">
                        Feedback
                        <span class="count-pill"><?php echo $rating['count']; ?> total</span>
                    </div>

                    <span class="mkt-rate <?php echo rating_tier_class($rating['tier']); ?>" style="cursor:default;">
                        <span class="mkt-rate-label"><?php echo htmlspecialchars($rating['label']); ?></span>
                        <?php if ($rating['count'] > 0) { ?>
                            <span class="mkt-rate-count"><?php echo $rating['avg']; ?> / 5</span>
                        <?php } ?>
                    </span>
                </div>

                <?php if ($rating['count'] > 0) { ?>
                    <div class="pixel-panel mkt-fb-summary">
                        <div class="mkt-fb-summary-left">
                            <span class="mkt-fb-avg"><?php echo number_format($rating['avg'], 1); ?><small>/ 5</small></span>
                            <span style="color: var(--text-muted); font-size: 12px;">
                                <?php echo $rating['positive']; ?> positive · <?php echo $rating['neutral']; ?> neutral · <?php echo $rating['negative']; ?> negative
                            </span>
                        </div>

                        <div class="mkt-fb-bars">
                            <?php foreach ($bar_rows as $b) { ?>
                                <div class="mkt-fb-bar-row">
                                    <span><?php echo $b['face']; ?> <?php echo htmlspecialchars($b['label']); ?></span>
                                    <div class="mkt-fb-bar-track">
                                        <div class="mkt-fb-bar-fill" style="width: <?php echo $b['pct']; ?>%;"></div>
                                    </div>
                                    <span style="text-align:right;"><?php echo $b['count']; ?></span>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>

                <!-- ============ YOUR FEEDBACK ============ -->
                <div class="mkt-right-head" style="margin-top:18px;">
                    <div class="mkt-section-title pixel-title">
                        Your Feedback
                        <span class="count-pill"><?php echo count($my_rows); ?></span>
                    </div>
                    <?php if ($add_target_course_id == 0) { ?>
                        <button type="button" class="btn-red" id="fbd-add-open" disabled
                                title="Fork this course first to leave feedback.">
                            + Add Feedback
                        </button>
                    <?php } else { ?>
                        <button type="button" class="btn-red" id="fbd-add-open">
                            + Add Feedback
                        </button>
                    <?php } ?>
                </div>

                <?php if (empty($my_rows)) { ?>
                    <div class="pixel-panel mkt-fb-empty">
                        <?php if ($add_target_course_id == 0) { ?>
                            <p>You haven't forked this course yet. Fork it, play a quiz, then leave a rating.</p>
                        <?php } else { ?>
                            <p>You haven't rated this course yet. Click <b>+ Add Feedback</b> above.</p>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="fbd-list" style="margin-bottom: 20px;">
                        <?php foreach ($my_rows as $r) {
                            // default emoji if the row's rating value isn't in our map
                            $face  = '❔';
                            $label = $r['emoji_rating'];
                            if (isset($emoji_face[$r['emoji_rating']])) {
                                $face  = $emoji_face[$r['emoji_rating']][0];
                                $label = $emoji_face[$r['emoji_rating']][1];
                            }

                            if ($r['description'] != null) {
                                $desc = trim($r['description']);
                            } else {
                                $desc = '';
                            }

                            if ($r['quiz_title'] != null) {
                                $target = 'Rated after: ' . $r['quiz_title'];
                            } else {
                                $target = 'Course rating';
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

                <!-- ============ EVERYONE ELSE'S FEEDBACK ============ -->
                <div class="mkt-right-head" style="margin-top:18px;">
                    <div class="mkt-section-title pixel-title">
                        Community Feedback
                        <span class="count-pill"><?php echo count($other_rows); ?></span>
                    </div>
                </div>

                <?php if (empty($other_rows)) { ?>
                    <div class="pixel-panel mkt-fb-empty">
                        <p>No feedback from other learners yet.</p>
                    </div>
                <?php } else { ?>
                    <div class="mkt-fb-list">
                        <?php foreach ($other_rows as $r) {
                            $face  = '❔';
                            $label = $r['emoji_rating'];
                            if (isset($emoji_face[$r['emoji_rating']])) {
                                $face  = $emoji_face[$r['emoji_rating']][0];
                                $label = $emoji_face[$r['emoji_rating']][1];
                            }

                            if ($r['author_name'] != null) {
                                $author = $r['author_name'];
                            } else {
                                $author = 'Deleted user';
                            }

                            if ($r['description'] != null) {
                                $desc = trim($r['description']);
                            } else {
                                $desc = '';
                            }

                            if ($r['quiz_title'] != null) {
                                $target = 'Rated after: ' . $r['quiz_title'];
                            } else {
                                $target = 'Course rating';
                            }
                        ?>
                            <div class="pixel-panel mkt-fb-item">
                                <div class="mkt-fb-emoji" title="<?php echo htmlspecialchars($label); ?>"><?php echo $face; ?></div>
                                <div class="mkt-fb-item-body">
                                    <div class="mkt-fb-item-head">
                                        <span class="mkt-fb-author"><?php echo htmlspecialchars($author); ?></span>
                                        <span class="mkt-fb-date"><?php echo date('j M Y, g:ia', strtotime($r['created_at'])); ?></span>
                                        <span class="mkt-fb-date">· <?php echo htmlspecialchars($target); ?></span>
                                    </div>
                                    <?php if ($desc != '') { ?>
                                        <div class="mkt-fb-comment"><?php echo htmlspecialchars($desc); ?></div>
                                    <?php } else { ?>
                                        <div class="mkt-fb-comment mkt-fb-comment--empty">No comment left.</div>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>

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


    <!-- ================= ADD MODAL =================
         course_id is fixed for this marketplace page — hidden input so
         the shared feedback_detail.js can read it via #fbd-add-course
         just like it does on the "My Feedback" account page. -->
    <div class="fbd-backdrop" id="fbd-add-modal">
        <div class="pixel-panel fbd-modal">
            <button type="button" class="fbd-close" id="fbd-add-close" aria-label="Close">
                <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="close">
            </button>
            <h2 class="pixel-title fbd-modal-title">Add feedback</h2>
            <p class="fbd-modal-sub">Rate <b><?php echo htmlspecialchars($course['title']); ?></b>.</p>

            <input type="hidden" id="fbd-add-course" value="<?php echo $add_target_course_id; ?>">

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