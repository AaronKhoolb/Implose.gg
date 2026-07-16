<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/admin/index.php
Description: Admin Dashboard (Home)
            - Stat cards (Users, Active, Courses, Quizzes, Pending Reports)
            - Role distribution panel
            - Recent activity feed (from SYSTEM_LOG_T)
            - Pending reports preview
            - Quick action shortcuts
First Written on: Thursday, 21-May-2026
Edited on: Saturday, 27-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_feedback.css?v=<?= time() ?>">
    <title>Admin Dashboard — Implose.gg</title>
</head>

<body class="admin-body">
    <?php
        $current_page = 'admin_dashboard';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <?php
        // Rating tier helper (used by the Course Ratings panel)
        include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/course_rating.php');

        // ==========================================
        // 1. HELPER FUNCTIONS
        // ==========================================

        function get_total_count($conn, $sql) {
            $result = mysqli_query($conn, $sql);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                return (int)$row['total'];
            }
            return 0;
        }

        function get_time_ago($date_string) {
            $diff = time() - strtotime($date_string);

            if ($diff < 60) {
                return "Just now";
            } elseif ($diff < 3600) {
                return floor($diff / 60) . "m ago";
            } elseif ($diff < 86400) {
                return floor($diff / 3600) . "h ago";
            } else {
                return floor($diff / 86400) . "d ago";
            }
        }

        function get_log_icon($action_type) {
            // pick a matching icon based on keywords in the action type
            $type = strtolower($action_type);

            if (strpos($type, 'login') !== false) {
                return 'users.svg';
            }
            if (strpos($type, 'registration') !== false) {
                return 'user-shield.svg';
            }
            if (strpos($type, 'profile') !== false) {
                return 'pencil.svg';
            }
            if (strpos($type, 'suspend') !== false) {
                return 'suspend.user.svg';
            }
            if (strpos($type, 'report') !== false) {
                return 'alert.svg';
            }
            if (strpos($type, 'admin') !== false) {
                return 'moderator.svg';
            }
            return 'activity.svg';
        }


        // ==========================================
        // 2. DATA FETCHING (LOGIC)
        // ==========================================

        // Top Stat Cards
        $stat_users   = get_total_count($conn, "SELECT COUNT(*) AS total FROM USER_T");
        $stat_active  = get_total_count($conn, "SELECT COUNT(*) AS total FROM USER_T WHERE account_status = 'active'");
        $stat_courses = get_total_count($conn, "SELECT COUNT(*) AS total FROM MARKETPLACE_COURSE_T WHERE is_deleted = 0");
        $stat_pending = get_total_count($conn, "SELECT COUNT(*) AS total FROM REPORT_T WHERE report_status = 'pending'");

        $new_users_week = get_total_count($conn, "SELECT COUNT(*) AS total FROM USER_T WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

        // Role Distribution Math
        $role_admins = get_total_count($conn, "SELECT COUNT(*) AS total FROM USER_T WHERE role = 'admin'");
        $role_mods   = get_total_count($conn, "SELECT COUNT(*) AS total FROM USER_T WHERE role = 'moderator'");
        $role_users  = get_total_count($conn, "SELECT COUNT(*) AS total FROM USER_T WHERE role = 'user'");
        
        $role_total = $role_admins + $role_mods + $role_users;
        $pct_admins = 0;
        $pct_mods   = 0;
        $pct_users  = 0;

        if ($role_total > 0) {
            $pct_admins = round(($role_admins / $role_total) * 100);
            $pct_mods   = round(($role_mods / $role_total) * 100);
            $pct_users  = round(($role_users / $role_total) * 100);
        }

        // Account Statuses
        $status_suspended = get_total_count($conn, "SELECT COUNT(*) AS total FROM USER_T WHERE account_status = 'suspended'");
        $status_pending   = get_total_count($conn, "SELECT COUNT(*) AS total FROM USER_T WHERE account_status = 'pending'");

        // Recent Activity Feed
        $logs_data = [];
        $log_sql = "SELECT l.action_type, l.description, l.created_at, u.username 
                    FROM SYSTEM_LOG_T l 
                    LEFT JOIN USER_T u ON l.user_id = u.user_id 
                    ORDER BY l.created_at DESC LIMIT 6";
        $log_result = mysqli_query($conn, $log_sql);

        if ($log_result) {
            while ($row = mysqli_fetch_assoc($log_result)) {
                $logs_data[] = $row;
            }
        }

        // Pending Reports Feed
        $pending_reports = [];
        $rep_sql = "SELECT r.report_id, r.reason, r.created_at, u.username AS reporter_name,
                           CASE
                               WHEN r.reported_message_id IS NOT NULL THEN 'Global Chat Report'
                               WHEN r.reported_marketplace_course_id IS NOT NULL THEN 'Course Report'
                               ELSE 'Other'
                           END AS category
                    FROM REPORT_T r
                    LEFT JOIN USER_T u ON r.reporter_id = u.user_id
                    WHERE r.report_status = 'pending'
                    ORDER BY r.created_at DESC LIMIT 4";
        $rep_result = mysqli_query($conn, $rep_sql);

        if ($rep_result) {
            while ($row = mysqli_fetch_assoc($rep_result)) {
                $pending_reports[] = $row;
            }
        }

        // Course Ratings Panel — every marketplace course + its
        // aggregated tier (Very Positive to Very Negative). Show the
        // worst-rated first so problem courses jump out; courses
        // with no ratings go last.
        $course_ratings = array();
        $mkt_sql = "SELECT marketplace_course_id, title FROM MARKETPLACE_COURSE_T
                     WHERE is_deleted = 0 ORDER BY updated_at DESC";
        $mkt_result = mysqli_query($conn, $mkt_sql);

        if ($mkt_result) {
            while ($mkt_row = mysqli_fetch_assoc($mkt_result)) {
                $mid = (int) $mkt_row['marketplace_course_id'];
                $rating_ids = course_feedback_ids_for_marketplace($conn, $mid);
                $summary    = course_rating_summary($conn, $rating_ids);

                $course_ratings[] = array(
                    'id'      => $mid,
                    'title'   => $mkt_row['title'],
                    'summary' => $summary
                );
            }
        }

        // Bubble sort — rated courses (lowest avg first), then unrated
        // at the bottom. Using an easy-to-follow nested loop instead of
        // usort() so the ordering rule is obvious.
        $n = count($course_ratings);
        for ($i = 0; $i < $n - 1; $i++) {
            for ($j = 0; $j < $n - 1 - $i; $j++) {
                $a = $course_ratings[$j];
                $b = $course_ratings[$j + 1];

                $a_count = $a['summary']['count'];
                $b_count = $b['summary']['count'];
                $a_avg   = $a['summary']['avg'];
                $b_avg   = $b['summary']['avg'];

                $swap = false;

                if ($a_count == 0 && $b_count > 0) {
                    // rated should come first (a has none, b has some)
                    $swap = true;
                } else if ($a_count > 0 && $b_count > 0) {
                    if ($a_avg > $b_avg) {
                        // lowest avg first
                        $swap = true;
                    }
                }

                if ($swap) {
                    $course_ratings[$j]     = $b;
                    $course_ratings[$j + 1] = $a;
                }
            }
        }

        // preview is the first 6 (build a fresh array instead of array_slice)
        $course_ratings_preview = array();
        for ($i = 0; $i < count($course_ratings) && $i < 6; $i++) {
            $course_ratings_preview[] = $course_ratings[$i];
        }


        // Header Greeting — pick morning/afternoon/evening from the hour
        $hour = date('G');
        if ($hour < 12) {
            $greeting = 'Good morning';
        } else if ($hour < 18) {
            $greeting = 'Good afternoon';
        } else {
            $greeting = 'Good evening';
        }

        if (isset($admin_username)) {
            $admin_name_display = $admin_username;
        } else {
            $admin_name_display = 'Admin';
        }
    ?>


    <div class="admin-main-content">

        <div class="dashboard-page-header">
            <div class="dashboard-page-header-left">
                <h1><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($admin_name_display) ?>.</h1>
                <p>Here's what's happening across Implose.gg today.</p>
            </div>
            <div class="dashboard-date-chip">
                <span class="dashboard-date-day"><?= date('l') ?></span>
                <span class="dashboard-date-full"><?= date('j M Y') ?></span>
            </div>
        </div>

        <div class="dashboard-stats-row">
            <div class="stat-card">
                <span class="stat-card-label">Total Users</span>
                <span class="stat-card-value"><?= $stat_users ?></span>
                <span class="stat-card-trend up">+<?= $new_users_week ?> this week</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/users2.svg" alt="users">
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-card-label">Active Accounts</span>
                <span class="stat-card-value"><?= $stat_active ?></span>
                <span class="stat-card-trend muted"><?= $status_suspended ?> suspended &middot; <?= $status_pending ?> pending</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/users.svg" alt="active">
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-card-label">Courses</span>
                <span class="stat-card-value"><?= $stat_courses ?></span>
                <span class="stat-card-trend muted">Published library</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/course.svg" alt="courses">
                </div>
            </div>

            <?php
                // decide colour + text for the Pending Reports stat card
                if ($stat_pending > 0) {
                    $pending_class = 'warn';
                    $pending_text  = 'Needs review';
                } else {
                    $pending_class = 'muted';
                    $pending_text  = 'All clear';
                }
            ?>
            <div class="stat-card">
                <span class="stat-card-label">Pending Reports</span>
                <span class="stat-card-value"><?= $stat_pending ?></span>
                <span class="stat-card-trend <?= $pending_class ?>">
                    <?= $pending_text ?>
                </span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/alert.svg" alt="reports">
                </div>
            </div>
        </div>

        <div class="dashboard-main-layout">

            <div class="dashboard-left-column">

                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <div>
                            <span class="dashboard-panel-title">User Role Distribution</span>
                            <span class="dashboard-panel-subtitle">Across <?= $stat_users ?> total accounts</span>
                        </div>
                        <a href="/Implose.gg-src/pages/admin/users.php" class="dashboard-panel-link">View all &rsaquo;</a>
                    </div>

                    <div class="role-dist-list">
                        <div class="role-dist-row">
                            <div class="role-dist-info">
                                <span class="role-dist-dot users"></span>
                                <span class="role-dist-name">Users</span>
                                <span class="role-dist-count"><?= $role_users ?></span>
                            </div>
                            <div class="role-dist-bar">
                                <div class="role-dist-fill users" style="width: <?= $pct_users ?>%;"></div>
                            </div>
                            <span class="role-dist-pct"><?= $pct_users ?>%</span>
                        </div>

                        <div class="role-dist-row">
                            <div class="role-dist-info">
                                <span class="role-dist-dot moderators"></span>
                                <span class="role-dist-name">Moderators</span>
                                <span class="role-dist-count"><?= $role_mods ?></span>
                            </div>
                            <div class="role-dist-bar">
                                <div class="role-dist-fill moderators" style="width: <?= $pct_mods ?>%;"></div>
                            </div>
                            <span class="role-dist-pct"><?= $pct_mods ?>%</span>
                        </div>

                        <div class="role-dist-row">
                            <div class="role-dist-info">
                                <span class="role-dist-dot admins"></span>
                                <span class="role-dist-name">Admins</span>
                                <span class="role-dist-count"><?= $role_admins ?></span>
                            </div>
                            <div class="role-dist-bar">
                                <div class="role-dist-fill admins" style="width: <?= $pct_admins ?>%;"></div>
                            </div>
                            <span class="role-dist-pct"><?= $pct_admins ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <div>
                            <span class="dashboard-panel-title">Recent Activity</span>
                            <span class="dashboard-panel-subtitle">Latest system events</span>
                        </div>
                        <a href="#" class="dashboard-panel-link">All logs &rsaquo;</a>
                    </div>

                    <?php if (count($logs_data) > 0) { ?>
                        <ul class="activity-list">
                            <?php foreach ($logs_data as $log) {
                                $desc = $log['description'];
                                // trim long descriptions so the row stays tidy
                                if (strlen($desc) > 100) {
                                    $desc = substr($desc, 0, 100) . '...';
                                }
                                $icon = get_log_icon($log['action_type']);
                            ?>
                                <li class="activity-row">
                                    <div class="activity-icon">
                                        <img src="/Implose.gg-src/assets/images/icons/<?= $icon ?>" alt="event">
                                    </div>
                                    <div class="activity-body">
                                        <span class="activity-title"><?= htmlspecialchars($log['action_type']) ?></span>
                                        <span class="activity-desc"><?= htmlspecialchars($desc) ?></span>
                                    </div>
                                    <span class="activity-time"><?= get_time_ago($log['created_at']) ?></span>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="dashboard-empty">
                            <img src="/Implose.gg-src/assets/images/icons/activity.svg" alt="empty">
                            <span class="dashboard-empty-title">No recent activity</span>
                            <span class="dashboard-empty-desc">Events will appear here as they happen.</span>
                        </div>
                    <?php } ?>
                </div>

            </div>


            <div class="dashboard-right-column">

                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <div>
                            <span class="dashboard-panel-title">Pending Reports</span>
                            <span class="dashboard-panel-subtitle"><?= $stat_pending ?> awaiting review</span>
                        </div>
                        <a href="/Implose.gg-src/pages/admin/report.php" class="dashboard-panel-link">Review &rsaquo;</a>
                    </div>

                    <?php if (count($pending_reports) > 0) { ?>
                        <ul class="report-preview-list">
                            <?php foreach ($pending_reports as $rep) {
                                $reason = $rep['reason'];
                                if (strlen($reason) > 80) {
                                    $reason = substr($reason, 0, 80) . '...';
                                }

                                if (isset($rep['reporter_name'])) {
                                    $reporter = $rep['reporter_name'];
                                } else {
                                    $reporter = 'Unknown';
                                }
                            ?>
                                <li class="report-preview-row">
                                    <div class="report-preview-head">
                                        <span class="report-preview-tag"><?= htmlspecialchars($rep['category']) ?></span>
                                        <span class="report-preview-time"><?= get_time_ago($rep['created_at']) ?></span>
                                    </div>
                                    <span class="report-preview-reason"><?= htmlspecialchars($reason) ?></span>
                                    <span class="report-preview-meta">Reported by <?= htmlspecialchars($reporter) ?></span>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="dashboard-empty">
                            <img src="/Implose.gg-src/assets/images/icons/done.svg" alt="clear">
                            <span class="dashboard-empty-title">All caught up</span>
                            <span class="dashboard-empty-desc">No reports awaiting review.</span>
                        </div>
                    <?php } ?>
                </div>

                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <div>
                            <span class="dashboard-panel-title">Course Ratings</span>
                            <span class="dashboard-panel-subtitle">Learner sentiment per course</span>
                        </div>
                        <a href="/Implose.gg-src/pages/admin/feedback.php" class="dashboard-panel-link">All feedback &rsaquo;</a>
                    </div>

                    <?php if (count($course_ratings_preview) > 0) { ?>
                        <ul class="course-rating-list">
                            <?php foreach ($course_ratings_preview as $cr) {
                                $s = $cr['summary'];

                                // decide singular/plural for the rating count
                                if ($s['count'] == 1) {
                                    $rating_word = 'rating';
                                } else {
                                    $rating_word = 'ratings';
                                }
                            ?>
                                <li class="course-rating-row">
                                    <div class="course-rating-info">
                                        <span class="course-rating-title" title="<?= htmlspecialchars($cr['title']) ?>"><?= htmlspecialchars($cr['title']) ?></span>
                                        <span class="course-rating-meta">
                                            <?php if ($s['count'] > 0) { ?>
                                                <?= $s['count'] ?> <?= $rating_word ?>
                                                &middot; <?= number_format($s['avg'], 1) ?>/5
                                            <?php } else { ?>
                                                No ratings yet
                                            <?php } ?>
                                        </span>
                                    </div>
                                    <span class="course-rating-pill course-rating-pill--<?= htmlspecialchars($s['tier']) ?>">
                                        <?= htmlspecialchars($s['label']) ?>
                                    </span>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="dashboard-empty">
                            <img src="/Implose.gg-src/assets/images/icons/course.svg" alt="empty">
                            <span class="dashboard-empty-title">No courses yet</span>
                            <span class="dashboard-empty-desc">Ratings will appear once courses are published.</span>
                        </div>
                    <?php } ?>
                </div>

            </div>

        </div>
    </div>
</body>
</html>