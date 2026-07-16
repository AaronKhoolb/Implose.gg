<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/moderator/achievement.php
Description: Moderator Achievement Management Page
            - Stat cards (Total, Total Unlocks, Avg Unlock Rate, Most Popular)
            - Search + filter toolbar
            - Achievements table with 50x50 badge, title, points, trigger, unlock %
            - Create / Edit actions (no delete — moderators cannot delete)
First Written on: Saturday, 27-Jun-2026
Edited on: Saturday, 27-Jun-2026
-->

<?php
session_start();

// flash messages
$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['achievement_success'])) {
    $success_msg = $_SESSION['achievement_success'];
    unset($_SESSION['achievement_success']);
}

if (isset($_SESSION['achievement_error'])) {
    $error_msg = $_SESSION['achievement_error'];
    unset($_SESSION['achievement_error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_achievement.css?v=<?= time() ?>">
    <title>Achievement Management — Implose.gg Moderator</title>
    <meta name="description" content="View achievements, unlock statistics, create and edit achievements.">
</head>


<body class="admin-body">
    <?php
        $current_page = 'moderator_achievements';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/moderator/nav.php');
    ?>

    <?php
        include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');
        $trigger_options = achievement_trigger_options();

        // Simple count helper — runs the SQL and returns c or 0
        function mod_ach_count($conn, $sql) {
            $r = mysqli_query($conn, $sql);
            if ($r && mysqli_num_rows($r) > 0) {
                $row = mysqli_fetch_assoc($r);
                return $row['c'];
            }
            return 0;
        }

        // Denominator for the unlock percentage — regular users only
        $total_users = mod_ach_count($conn, "SELECT COUNT(*) AS c FROM USER_T WHERE role = 'user'");

        // Load every achievement with its unlock count
        $ach_sql = "SELECT a.achievement_id, a.title, a.description, a.badge_icon_path,
                           a.points_reward, a.trigger_code, a.created_at, a.updated_at,
                           COUNT(DISTINCT ua.user_id) AS unlock_count
                      FROM ACHIEVEMENT_T a
                      LEFT JOIN USER_ACHIEVEMENT_T ua ON a.achievement_id = ua.achievement_id
                     GROUP BY a.achievement_id
                     ORDER BY a.created_at DESC";

        $ach_result = mysqli_query($conn, $ach_sql);
        $ach_data   = array();

        // Running totals for the stat cards
        $stat_total   = 0;
        $stat_unlocks = 0;
        $sum_pct      = 0;

        // Track the most popular achievement as we loop
        $most_pop_title = '—';
        $most_pop_count = 0;

        if ($ach_result) {
            while ($row = mysqli_fetch_assoc($ach_result)) {
                $unlock_count = $row['unlock_count'];

                // percentage of registered users who unlocked this one
                if ($total_users > 0) {
                    $pct = round(($unlock_count / $total_users) * 100);
                } else {
                    $pct = 0;
                }
                if ($pct > 100) {
                    $pct = 100;
                }

                // trigger code — fall back to MANUAL if unknown
                if (isset($row['trigger_code']) && $row['trigger_code'] != null) {
                    $trig_code = $row['trigger_code'];
                } else {
                    $trig_code = 'MANUAL';
                }
                if (!array_key_exists($trig_code, $trigger_options)) {
                    $trig_code = 'MANUAL';
                }
                $trig_label = $trigger_options[$trig_code];

                // Description can be NULL in the DB
                if (isset($row['description']) && $row['description'] != null) {
                    $description = $row['description'];
                } else {
                    $description = '';
                }

                // Badge is a relative path — prefix web root
                if ($row['badge_icon_path'] != null && $row['badge_icon_path'] != '') {
                    $badge_url = '/Implose.gg-src/' . $row['badge_icon_path'];
                } else {
                    $badge_url = '';
                }

                $ach_data[] = array(
                    'id'            => $row['achievement_id'],
                    'title'         => $row['title'],
                    'description'   => $description,
                    'badge'         => $badge_url,
                    'points'        => $row['points_reward'],
                    'trigger_code'  => $trig_code,
                    'trigger_label' => $trig_label,
                    'unlock_count'  => $unlock_count,
                    'unlock_pct'    => $pct,
                    'created'       => date('j M Y', strtotime($row['created_at']))
                );

                $stat_total   = $stat_total + 1;
                $stat_unlocks = $stat_unlocks + $unlock_count;
                $sum_pct      = $sum_pct + $pct;

                if ($unlock_count > $most_pop_count) {
                    $most_pop_count = $unlock_count;
                    $most_pop_title = $row['title'];
                }
            }
        }

        if ($stat_total > 0) {
            $stat_avg_pct = round($sum_pct / $stat_total);
        } else {
            $stat_avg_pct = 0;
        }
    ?>

    <div class="admin-main-content">

        <?php
            // singular / plural for "user" / "users"
            if ($total_users == 1) {
                $user_word = 'user';
            } else {
                $user_word = 'users';
            }
        ?>
        <div class="ach-page-header">
            <div class="ach-page-header-left">
                <h1>Achievement Management</h1>
                <p>Manage badges and track unlock rates across <?= $total_users ?> registered <?= $user_word ?>.</p>
            </div>
            <a href="/Implose.gg-src/pages/moderator/create_achievement.php" class="btn-create-achievement">+ Create Achievement</a>
        </div>


        <?php
            // pick which flash message (if any) to render
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


        <div class="ach-stats-row">

            <div class="stat-card">
                <span class="stat-card-label">Total Achievements</span>
                <span class="stat-card-value"><?= $stat_total ?></span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/nav_achievement.svg" alt="achievements">
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-card-label">Total Unlocks</span>
                <span class="stat-card-value"><?= $stat_unlocks ?></span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/done.svg" alt="unlocks">
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-card-label">Avg Unlock Rate</span>
                <span class="stat-card-value"><?= $stat_avg_pct ?>%</span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/stats.svg" alt="rate">
                </div>
            </div>

            <?php
                // shorten the title if it's too long for the card
                if (strlen($most_pop_title) > 22) {
                    $most_pop_display = substr($most_pop_title, 0, 22) . '...';
                } else {
                    $most_pop_display = $most_pop_title;
                }

                // singular / plural for "unlock" / "unlocks"
                if ($most_pop_count == 1) {
                    $unlock_word = 'unlock';
                } else {
                    $unlock_word = 'unlocks';
                }
            ?>
            <div class="stat-card">
                <span class="stat-card-label">Most Popular</span>
                <span class="stat-card-popular"><?= htmlspecialchars($most_pop_display) ?></span>
                <span class="stat-card-popular-meta"><?= $most_pop_count ?> <?= $unlock_word ?></span>
                <div class="stat-card-icon">
                    <img src="/Implose.gg-src/assets/images/icons/fire.svg" alt="popular">
                </div>
            </div>

        </div>


        <div class="ach-table-card">

            <div class="ach-toolbar">
                <div class="ach-search-wrap">
                    <input
                        type="text"
                        id="ach-search"
                        class="ach-search"
                        placeholder="Search achievements..."
                        oninput="filterAchievements()">
                    <img class="search-icon" src="/Implose.gg-src/assets/images/icons/search.svg" alt="search">
                </div>

                <select class="ach-filter-select" id="filter-popularity" onchange="filterAchievements()">
                    <option value="">All Popularity</option>
                    <option value="high">High (&ge; 50%)</option>
                    <option value="mid">Medium (10% &ndash; 49%)</option>
                    <option value="low">Low (&lt; 10%)</option>
                </select>
            </div>

            <table class="ach-table" id="ach-table">
                <thead>
                    <tr>
                        <th>Badge</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Points</th>
                        <th>Trigger</th>
                        <th>Unlock Rate</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="ach-tbody">
                    <!-- Rows injected by JS -->
                </tbody>
            </table>

        </div>

    </div>


    <script>
    // Achievement data — encoded from PHP into a JS array so the page
    // doesn't need to re-query the server when the user searches / filters
    var ACHIEVEMENTS = <?php echo json_encode($ach_data); ?>;

    // Render the first time after the page's DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        renderTable(ACHIEVEMENTS);
    });

    // Simple HTML escape so titles/descriptions never break markup or run JS
    function escapeHtml(str) {
        var s = String(str);
        s = s.replace(/&/g, '&amp;');
        s = s.replace(/</g, '&lt;');
        s = s.replace(/>/g, '&gt;');
        s = s.replace(/"/g, '&quot;');
        s = s.replace(/'/g, '&#39;');
        return s;
    }

    // Build every table row for the given achievement list
    function renderTable(items) {
        var tbody = document.getElementById('ach-tbody');
        tbody.innerHTML = '';

        // Show a friendly "empty" row if there's nothing to render
        if (items.length == 0) {
            var emptyRow = '<tr>'
                + '<td colspan="8" style="text-align:center;padding:48px 20px;color:var(--admin-text-muted);">'
                + 'No achievements found. Click "Create Achievement" to add one.'
                + '</td>'
                + '</tr>';
            tbody.innerHTML = emptyRow;
            return;
        }

        for (var i = 0; i < items.length; i++) {
            var a = items[i];

            var escTitle = escapeHtml(a.title);
            var escDesc  = '';
            if (a.description) {
                escDesc = escapeHtml(a.description);
            }

            // Trim long descriptions so the row stays tidy
            var descPreview;
            if (escDesc.length > 70) {
                descPreview = escDesc.substring(0, 70) + '...';
            } else if (escDesc != '') {
                descPreview = escDesc;
            } else {
                descPreview = '<span style="opacity:0.6;">No description</span>';
            }

            // Badge cell — image if there is one, otherwise fallback
            var badgeCell = '';
            var badgeFallback = 'ach-badge--fallback';
            if (a.badge) {
                badgeCell = '<img src="' + a.badge + '" alt="' + escTitle
                          + '" onerror="this.style.display=\'none\'; this.parentElement.classList.add(\'ach-badge--fallback\');">';
                badgeFallback = '';
            }

            // Popularity colour — low / mid / high
            var popTone = 'low';
            if (a.unlock_pct >= 50) {
                popTone = 'high';
            } else if (a.unlock_pct >= 10) {
                popTone = 'mid';
            }

            // singular / plural for the tooltip
            var userWord;
            if (a.unlock_count == 1) {
                userWord = 'user';
            } else {
                userWord = 'users';
            }

            var tr = document.createElement('tr');
            tr.dataset.achievementId = a.id;

            var html = '';
            html += '<td>';
            html +=   '<div class="ach-badge-cell ' + badgeFallback + '">';
            html +=     badgeCell;
            html +=   '</div>';
            html += '</td>';
            html += '<td><span class="ach-title">' + escTitle + '</span></td>';
            html += '<td><span class="ach-desc">' + descPreview + '</span></td>';
            html += '<td><span class="ach-points">' + a.points + ' coins</span></td>';
            html += '<td>';
            html +=   '<span class="ach-trigger trigger-' + a.trigger_code.toLowerCase() + '"';
            html +=       ' title="' + escapeHtml(a.trigger_label) + '">';
            html +=     escapeHtml(a.trigger_code);
            html +=   '</span>';
            html += '</td>';
            html += '<td>';
            html +=   '<div class="ach-unlock-wrap" title="' + a.unlock_count + ' ' + userWord + ' unlocked">';
            html +=     '<div class="ach-unlock-bar">';
            html +=       '<div class="ach-unlock-fill ' + popTone + '" style="width: ' + a.unlock_pct + '%;"></div>';
            html +=     '</div>';
            html +=     '<span class="ach-unlock-pct ' + popTone + '">' + a.unlock_pct + '%</span>';
            html +=   '</div>';
            html += '</td>';
            html += '<td><span class="ach-created">' + a.created + '</span></td>';
            html += '<td>';
            html +=   '<div class="row-actions">';
            html +=     '<button class="row-action-btn" title="Edit"';
            html +=         ' onclick="window.location.href=\'/Implose.gg-src/pages/moderator/edit_achievement.php?id=' + a.id + '\'">';
            html +=       '<img src="/Implose.gg-src/assets/images/icons/pencil.svg" alt="Edit">';
            html +=     '</button>';
            html +=   '</div>';
            html += '</td>';

            tr.innerHTML = html;
            tbody.appendChild(tr);
        }
    }

    // Search + popularity filter — build a new list then render it
    function filterAchievements() {
        var q      = document.getElementById('ach-search').value.toLowerCase();
        var popKey = document.getElementById('filter-popularity').value;

        var filtered = [];
        for (var i = 0; i < ACHIEVEMENTS.length; i++) {
            var a = ACHIEVEMENTS[i];

            // does the title or description contain the search text?
            var matchQ = false;
            if (a.title.toLowerCase().indexOf(q) !== -1) {
                matchQ = true;
            } else if (a.description && a.description.toLowerCase().indexOf(q) !== -1) {
                matchQ = true;
            }

            // popularity band
            var matchPop = true;
            if (popKey == 'high') {
                matchPop = (a.unlock_pct >= 50);
            } else if (popKey == 'mid') {
                matchPop = (a.unlock_pct >= 10 && a.unlock_pct < 50);
            } else if (popKey == 'low') {
                matchPop = (a.unlock_pct < 10);
            }

            if (matchQ && matchPop) {
                filtered.push(a);
            }
        }

        renderTable(filtered);
    }
    </script>

</body>
</html>
