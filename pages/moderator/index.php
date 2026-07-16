<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/moderator/index.php
Description: Moderator Dashboard (Home)
            - Stat cards (Users, Active, Pending Reports, Achievements, Total Unlocks)
            - Pending reports preview (top 4)
            - Recent activity feed (from SYSTEM_LOG_T)
            - Top achievements (most unlocked)
            - Quick action shortcuts
First Written on: Saturday, 27-Jun-2026
Edited on: Saturday, 27-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/moderator_dashboard.css?v=<?= time() ?>">
    <title>Moderator Dashboard — Implose.gg</title>
</head>

<body class="admin-body">
    <?php
        $current_page = 'moderator_dashboard';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/moderator/nav.php');
    ?>

    <?php
        // ==========================================
        // 1. HELPER FUNCTIONS
        // ==========================================

        // Simple SELECT COUNT() helper — returns 0 when there are no rows
        function mod_count($conn, $sql) {
            $r = mysqli_query($conn, $sql);
            if ($r && mysqli_num_rows($r) > 0) {
                $row = mysqli_fetch_assoc($r);
                return $row['c'];
            }
            return 0;
        }

        // Pick an icon filename based on keywords in the action type
        function mod_log_icon($action_type) {
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
            if (strpos($type, 'achievement') !== false) {
                return 'nav_achievement.svg';
            }
            if (strpos($type, 'admin') !== false) {
                return 'moderator.svg';
            }
            return 'activity.svg';
        }

        // Turn a datetime string into a "5m ago" / "2h ago" / "3d ago" label
        function mod_time_ago($date_string) {
            $diff = time() - strtotime($date_string);
            if ($diff < 60) {
                return '1m ago';
            }
            if ($diff < 3600) {
                return floor($diff / 60) . 'm ago';
            }
            if ($diff < 86400) {
                return floor($diff / 3600) . 'h ago';
            }
            return floor($diff / 86400) . 'd ago';
        }

        // Shorten a string to $limit chars, adding "..." if trimmed
        function mod_short($text, $limit) {
            if (strlen($text) > $limit) {
                return substr($text, 0, $limit) . '...';
            }
            return $text;
        }


        // ==========================================
        // 2. DATA FETCHING
        // ==========================================

        // Top stats
        $stat_users        = mod_count($conn, "SELECT COUNT(*) AS c FROM USER_T");
        $stat_active       = mod_count($conn, "SELECT COUNT(*) AS c FROM USER_T WHERE account_status = 'active'");
        $stat_pending      = mod_count($conn, "SELECT COUNT(*) AS c FROM REPORT_T WHERE report_status = 'pending'");
        $stat_achievements = mod_count($conn, "SELECT COUNT(*) AS c FROM ACHIEVEMENT_T");
        $stat_unlocks      = mod_count($conn, "SELECT COUNT(*) AS c FROM USER_ACHIEVEMENT_T");

        $stat_suspended    = mod_count($conn, "SELECT COUNT(*) AS c FROM USER_T WHERE account_status = 'suspended'");
        $stat_resolved     = mod_count($conn, "SELECT COUNT(*) AS c FROM REPORT_T WHERE report_status IN ('resolved','reviewed','rejected')");
        $stat_total_reports = mod_count($conn, "SELECT COUNT(*) AS c FROM REPORT_T");

        // Pending reports list (latest 4)
        $pending_reports = [];
        $rep_sql = "
            SELECT r.report_id, r.reason, r.created_at, u.username AS reporter_name,
                CASE
                    WHEN r.reported_message_id IS NOT NULL THEN 'Global Chat Report'
                    WHEN r.reported_marketplace_course_id  IS NOT NULL THEN 'Course Report'
                    ELSE 'Other'
                END AS category
            FROM REPORT_T r
            LEFT JOIN USER_T u ON r.reporter_id = u.user_id
            WHERE r.report_status = 'pending'
            ORDER BY r.created_at DESC LIMIT 4
        ";
        $rep_res = mysqli_query($conn, $rep_sql);
        if ($rep_res) {
            while ($row = mysqli_fetch_assoc($rep_res)) {
                $pending_reports[] = $row;
            }
        }

        // Recent activity (latest 6)
        $logs_data = [];
        $log_sql = "
            SELECT l.action_type, l.description, l.created_at, u.username
            FROM SYSTEM_LOG_T l
            LEFT JOIN USER_T u ON l.user_id = u.user_id
            ORDER BY l.created_at DESC LIMIT 6
        ";
        $log_res = mysqli_query($conn, $log_sql);
        if ($log_res) {
            while ($row = mysqli_fetch_assoc($log_res)) {
                $logs_data[] = $row;
            }
        }

        // Top achievements by unlock count (top 5)
        $top_ach = array();
        $top_sql = "SELECT a.achievement_id, a.title, a.badge_icon_path, a.points_reward,
                           COUNT(DISTINCT ua.user_id) AS unlock_count
                      FROM ACHIEVEMENT_T a
                      LEFT JOIN USER_ACHIEVEMENT_T ua ON a.achievement_id = ua.achievement_id
                     GROUP BY a.achievement_id
                     ORDER BY unlock_count DESC, a.created_at DESC
                     LIMIT 5";
        $top_res = mysqli_query($conn, $top_sql);

        // Denominator for the unlock percentage — regular users. Guard
        // against 0 so we don't divide by zero later.
        $user_count = mod_count($conn, "SELECT COUNT(*) AS c FROM USER_T WHERE role = 'user'");
        if ($user_count < 1) {
            $denominator = 1;
        } else {
            $denominator = $user_count;
        }

        if ($top_res) {
            while ($row = mysqli_fetch_assoc($top_res)) {
                $unlock = $row['unlock_count'];

                // percentage — clamp to 100 in case of dirty data
                $pct = round(($unlock / $denominator) * 100);
                if ($pct > 100) {
                    $pct = 100;
                }

                // badge URL — the DB stores a relative path
                if ($row['badge_icon_path'] != null && $row['badge_icon_path'] != '') {
                    $badge = '/Implose.gg-src/' . $row['badge_icon_path'];
                } else {
                    $badge = '';
                }

                $top_ach[] = array(
                    'id'     => $row['achievement_id'],
                    'title'  => $row['title'],
                    'badge'  => $badge,
                    'points' => $row['points_reward'],
                    'unlock' => $unlock,
                    'pct'    => $pct
                );
            }
        }

        // Greeting — morning / afternoon / evening based on the hour
        $hour = date('G');
        if ($hour < 12) {
            $greeting = 'Good morning';
        } else if ($hour < 18) {
            $greeting = 'Good afternoon';
        } else {
            $greeting = 'Good evening';
        }

        if (isset($moderator_username) && $moderator_username != '') {
            $name_display = $moderator_username;
        } else {
            $name_display = 'Moderator';
        }
    ?>


    <div class="admin-main-content">

        <!-- ── Page Header ── -->
        <div class="dashboard-page-header">
            <div class="dashboard-page-header-left">
                <h1><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($name_display) ?>.</h1>
                <p>Keep an eye on reports, user activity, and achievement progress.</p>
            </div>
            <div class="dashboard-date-chip">
                <span class="dashboard-date-day"><?= date('l') ?></span>
                <span class="dashboard-date-full"><?= date('j M Y') ?></span>
            </div>
        </div>

        <!-- ── Stat Cards ── -->
        <div class="dashboard-stats-row">

            <div class="stat-card">
                <span class="stat-card-label">Total Users</span>
                <span class="stat-card-value"><?= $stat_users ?></span>
                <span class="stat-card-trend muted"><?= $stat_active ?> active &middot; <?= $stat_suspended ?> suspended</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/users2.svg" alt="users">
                </div>
            </div>

            <?php
                // pick colour + text for the Pending Reports card
                if ($stat_pending > 0) {
                    $pending_class = 'warn';
                    $pending_text  = 'Needs review';
                } else {
                    $pending_class = 'up';
                    $pending_text  = 'All caught up';
                }
            ?>
            <div class="stat-card">
                <span class="stat-card-label">Pending Reports</span>
                <span class="stat-card-value"><?= $stat_pending ?></span>
                <span class="stat-card-trend <?= $pending_class ?>">
                    <?= $pending_text ?>
                </span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/alert.svg" alt="pending">
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-card-label">Resolved Reports</span>
                <span class="stat-card-value"><?= $stat_resolved ?></span>
                <span class="stat-card-trend muted">of <?= $stat_total_reports ?> total</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/done.svg" alt="resolved">
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-card-label">Achievements</span>
                <span class="stat-card-value"><?= $stat_achievements ?></span>
                <span class="stat-card-trend muted">Across all categories</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/nav_achievement.svg" alt="achievements">
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-card-label">Total Unlocks</span>
                <span class="stat-card-value"><?= $stat_unlocks ?></span>
                <span class="stat-card-trend muted">By all users combined</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/stats.svg" alt="unlocks">
                </div>
            </div>

        </div>

        <!-- ── Main Layout ── -->
        <div class="dashboard-main-layout">

            <!-- ── Left Column ── -->
            <div class="dashboard-left-column">

                <!-- Pending Reports -->
                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <div>
                            <span class="dashboard-panel-title">Pending Reports</span>
                            <span class="dashboard-panel-subtitle"><?= $stat_pending ?> awaiting review</span>
                        </div>
                        <a href="/Implose.gg-src/pages/moderator/report.php" class="dashboard-panel-link">Review &rsaquo;</a>
                    </div>

                    <?php if (count($pending_reports) > 0) { ?>
                        <ul class="report-preview-list">
                            <?php foreach ($pending_reports as $rep) {
                                $r_ago  = mod_time_ago($rep['created_at']);
                                $reason = mod_short($rep['reason'], 100);

                                if (isset($rep['reporter_name']) && $rep['reporter_name'] != '') {
                                    $reporter = $rep['reporter_name'];
                                } else {
                                    $reporter = 'Unknown';
                                }
                            ?>
                                <li class="report-preview-row">
                                    <div class="report-preview-head">
                                        <span class="report-preview-tag"><?= htmlspecialchars($rep['category']) ?></span>
                                        <span class="report-preview-time"><?= $r_ago ?></span>
                                    </div>
                                    <span class="report-preview-reason">
                                        <?= htmlspecialchars($reason) ?>
                                    </span>
                                    <span class="report-preview-meta">
                                        Reported by <?= htmlspecialchars($reporter) ?>
                                    </span>
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

                <!-- Recent Activity -->
                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <div>
                            <span class="dashboard-panel-title">Recent Activity</span>
                            <span class="dashboard-panel-subtitle">Latest system events</span>
                        </div>
                    </div>

                    <?php if (count($logs_data) > 0) { ?>
                        <ul class="activity-list">
                            <?php foreach ($logs_data as $log) {
                                $log_ago  = mod_time_ago($log['created_at']);
                                $log_desc = mod_short($log['description'], 100);
                                $log_icon = mod_log_icon($log['action_type']);
                            ?>
                                <li class="activity-row">
                                    <div class="activity-icon">
                                        <img src="/Implose.gg-src/assets/images/icons/<?= $log_icon ?>" alt="event">
                                    </div>
                                    <div class="activity-body">
                                        <span class="activity-title"><?= htmlspecialchars($log['action_type']) ?></span>
                                        <span class="activity-desc"><?= htmlspecialchars($log_desc) ?></span>
                                    </div>
                                    <span class="activity-time"><?= $log_ago ?></span>
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

            <!-- ── Right Column ── -->
            <div class="dashboard-right-column">

                <!-- Top Achievements -->
                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <div>
                            <span class="dashboard-panel-title">Top Achievements</span>
                            <span class="dashboard-panel-subtitle">Most unlocked across users</span>
                        </div>
                        <a href="/Implose.gg-src/pages/moderator/achievement.php" class="dashboard-panel-link">View all &rsaquo;</a>
                    </div>

                    <?php if (count($top_ach) > 0) { ?>
                        <ul class="top-ach-list">
                            <?php foreach ($top_ach as $a) {
                                // fallback class if the achievement has no badge
                                if ($a['badge'] != '') {
                                    $badge_class = '';
                                } else {
                                    $badge_class = 'top-ach-badge--fallback';
                                }

                                // singular / plural for the meta line
                                if ($a['unlock'] == 1) {
                                    $unlock_word = 'unlock';
                                } else {
                                    $unlock_word = 'unlocks';
                                }
                            ?>
                                <li class="top-ach-row">
                                    <div class="top-ach-badge <?= $badge_class ?>">
                                        <?php if ($a['badge'] != '') { ?>
                                            <img src="<?= htmlspecialchars($a['badge']) ?>" alt="<?= htmlspecialchars($a['title']) ?>"
                                                 onerror="this.style.display='none'; this.parentElement.classList.add('top-ach-badge--fallback');">
                                        <?php } ?>
                                    </div>
                                    <div class="top-ach-body">
                                        <span class="top-ach-title"><?= htmlspecialchars($a['title']) ?></span>
                                        <span class="top-ach-meta"><?= $a['unlock'] ?> <?= $unlock_word ?> &middot; <?= $a['points'] ?> pts</span>
                                    </div>
                                    <span class="top-ach-pct"><?= $a['pct'] ?>%</span>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <div class="dashboard-empty">
                            <img src="/Implose.gg-src/assets/images/icons/nav_achievement.svg" alt="empty">
                            <span class="dashboard-empty-title">No achievements yet</span>
                            <span class="dashboard-empty-desc">Achievements will appear here once created.</span>
                        </div>
                    <?php } ?>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-panel">
                    <div class="dashboard-panel-header">
                        <div>
                            <span class="dashboard-panel-title">Quick Actions</span>
                            <span class="dashboard-panel-subtitle">Shortcuts to common tasks</span>
                        </div>
                    </div>

                    <div class="quick-actions-grid">
                        <a href="/Implose.gg-src/pages/moderator/report.php" class="quick-action-card">
                            <div class="quick-action-icon accent">
                                <img src="/Implose.gg-src/assets/images/icons/alert.svg" alt="review reports">
                            </div>
                            <span class="quick-action-label">Review Reports</span>
                        </a>

                        <a href="/Implose.gg-src/pages/moderator/users.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <img src="/Implose.gg-src/assets/images/icons/users.svg" alt="users">
                            </div>
                            <span class="quick-action-label">View Users</span>
                        </a>

                        <a href="/Implose.gg-src/pages/moderator/achievement.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <img src="/Implose.gg-src/assets/images/icons/nav_achievement.svg" alt="achievements">
                            </div>
                            <span class="quick-action-label">Achievements</span>
                        </a>

                        <a href="/Implose.gg-src/actions/auth/sign_out.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <img src="/Implose.gg-src/assets/images/icons/logout.svg" alt="sign out">
                            </div>
                            <span class="quick-action-label">Sign Out</span>
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

</body>
</html>
