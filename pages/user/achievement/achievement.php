<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/user/achievement/achievement.php
Description: User Achievement Page
            - View all achievements in a pixel-panel grid
            - Unlocked: full colour, shows unlock date
            - Locked: greyscale + lock overlay, shows points reward
            - Filter tabs: All / Unlocked / Locked
            - Stat header showing progress 
            - Note: Implemented using standalone queries and PHP array mapping (No SQL JOINs).
First Written on: Saturday, 27-Jun-2026
Edited on: Sunday, 28-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_achievement.css?v=<?= time() ?>">
    <title>My Achievements — Implose.gg</title>
</head>

<body>
    <?php
        $current_page = 'user_achievement';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <?php
        // ==========================================
        // Load achievements & user unlock state
        // (Using foundational PHP logic, no JOINs)
        // ==========================================

        $current_user_id = (int) ($_SESSION['user_id'] ?? 0);

        // 1. Fetch all achievements from the database
        $achievements_sql = "SELECT achievement_id, title, description, badge_icon_path, points_reward FROM ACHIEVEMENT_T ORDER BY points_reward DESC, created_at ASC";
        $achievements_result = mysqli_query($conn, $achievements_sql);

        // 2. Fetch the current user's unlocked achievements
        $unlocks_sql = "SELECT achievement_id, unlocked_at FROM USER_ACHIEVEMENT_T WHERE user_id = '$current_user_id'";
        $unlocks_result = mysqli_query($conn, $unlocks_sql);

        // 3. Store user unlocks in an associative array for easy lookup
        $user_unlocks = [];
        if ($unlocks_result) {
            while ($unlock_row = mysqli_fetch_assoc($unlocks_result)) {
                $user_unlocks[$unlock_row['achievement_id']] = $unlock_row['unlocked_at'];
            }
        }

        // 4. Process and categorize the achievements
        $unlocked_list = [];
        $locked_list   = [];
        $points_earned = 0;

        if ($achievements_result) {
            while ($row = mysqli_fetch_assoc($achievements_result)) {
                $ach_id = $row['achievement_id'];
                
                // Check if this achievement exists in the user's unlock array
                $is_unlocked = array_key_exists($ach_id, $user_unlocks);
                
                $entry = [
                    'id'       => (int) $ach_id,
                    'title'    => $row['title'],
                    'desc'     => $row['description'] ?? '',
                    'badge'    => $row['badge_icon_path'] ? '/Implose.gg-src/' . $row['badge_icon_path'] : '',
                    'points'   => (int) $row['points_reward'],
                    'unlocked' => $is_unlocked,
                    'unlock_at'=> $is_unlocked ? date('j M Y', strtotime($user_unlocks[$ach_id])) : null,
                ];

                if ($is_unlocked) {
                    $unlocked_list[] = $entry;
                    $points_earned  += $entry['points'];
                } else {
                    $locked_list[] = $entry;
                }
            }
        }

        // Calculate progress statistics
        $total_count    = count($unlocked_list) + count($locked_list);
        $unlocked_count = count($unlocked_list);
        $locked_count   = count($locked_list);
        $progress_pct   = $total_count > 0 ? round(($unlocked_count / $total_count) * 100) : 0;
    ?>

    <div class="main-content">
        <div class="ach-page">

            <!-- ── Header Section ── -->
            <div class="ach-page-head">
                <h1 class="pixel-title ach-page-title">My Achievements</h1>
                <p class="ach-page-subtitle">Collect badges, earn coins, level up your profile.</p>

                <!-- Progress Strip -->
                <div class="ach-progress pixel-panel">
                    <div class="ach-progress-stats">
                        <div class="ach-progress-stat">
                            <span class="ach-stat-num"><?= $unlocked_count ?> / <?= $total_count ?></span>
                            <span class="ach-stat-label">Unlocked</span>
                        </div>
                        <div class="ach-progress-stat">
                            <span class="ach-stat-num"><?= $points_earned ?></span>
                            <span class="ach-stat-label">Coins Earned</span>
                        </div>
                        <div class="ach-progress-stat">
                            <span class="ach-stat-num"><?= $progress_pct ?>%</span>
                            <span class="ach-stat-label">Completion</span>
                        </div>
                    </div>
                    <div class="ach-progress-track">
                        <div class="ach-progress-fill" style="width: <?= $progress_pct ?>%;"></div>
                    </div>
                </div>
            </div>

            <!-- ── Filter Tabs ── -->
            <div class="ach-tabs" id="ach-tabs">
                <button type="button" class="ach-tab btn-pixel active" data-filter="all">All (<?= $total_count ?>)</button>
                <button type="button" class="ach-tab btn-pixel" data-filter="unlocked">Unlocked (<?= $unlocked_count ?>)</button>
                <button type="button" class="ach-tab btn-pixel" data-filter="locked">Locked (<?= $locked_count ?>)</button>
            </div>

            <!-- ── Achievement Grid ── -->
            <div class="ach-grid" id="ach-grid">

                <?php
                    // Helper function to render each achievement card smoothly
                    $render_card = function ($entry) {
                        $is_unlocked = $entry['unlocked'];
                        $class       = $is_unlocked ? 'ach-card--unlocked' : 'ach-card--locked';
                        $filter_val  = $is_unlocked ? 'unlocked' : 'locked';
                ?>
                    <div class="ach-card pixel-panel <?= $class ?>" data-filter="<?= $filter_val ?>">
                        <div class="ach-card-badge-wrap">
                            <div class="ach-card-badge <?= $entry['badge'] ? '' : 'ach-card-badge--fallback' ?>">
                                <?php if ($entry['badge']): ?>
                                    <img src="<?= htmlspecialchars($entry['badge']) ?>"
                                         alt="<?= htmlspecialchars($entry['title']) ?>"
                                         onerror="this.style.display='none'; this.parentElement.classList.add('ach-card-badge--fallback');">
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!$is_unlocked): ?>
                                <div class="ach-card-lock" aria-hidden="true">
                                    <img src="/Implose.gg-src/assets/images/icons/ban.svg" alt="Locked Icon">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="ach-card-body">
                            <span class="ach-card-title"><?= htmlspecialchars($entry['title']) ?></span>
                            <span class="ach-card-desc"><?= htmlspecialchars($entry['desc'] ?: 'No description provided.') ?></span>

                            <div class="ach-card-meta">
                                <?php if ($is_unlocked): ?>
                                    <span class="ach-card-tag ach-card-tag--unlocked">Unlocked <?= htmlspecialchars($entry['unlock_at']) ?></span>
                                <?php else: ?>
                                    <span class="ach-card-tag ach-card-tag--locked">Locked</span>
                                <?php endif; ?>
                                <span class="ach-card-points">+<?= $entry['points'] ?> coins</span>
                            </div>
                        </div>
                    </div>
                <?php
                    };

                    // Render unlocked first, then locked to keep the UI sorted
                    foreach ($unlocked_list as $entry) { $render_card($entry); }
                    foreach ($locked_list   as $entry) { $render_card($entry); }
                ?>

                <!-- Empty State Fallback -->
                <?php if ($total_count === 0): ?>
                    <div class="ach-empty pixel-panel">
                        <span class="pixel-title ach-empty-title">No achievements yet</span>
                        <p class="ach-empty-desc">Check back soon — new badges are on the way.</p>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>

    <!-- ── Interactive Scripts ── -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const tabs = document.querySelectorAll('.ach-tab');
            const cards = document.querySelectorAll('.ach-card');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Update active tab styling
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    // Filter logic
                    const filter = tab.dataset.filter;
                    cards.forEach(card => {
                        const shouldShow = (filter === 'all') || (card.dataset.filter === filter);
                        card.style.display = shouldShow ? '' : 'none';
                    });
                });
            });
        });
    </script>

</body>
</html>