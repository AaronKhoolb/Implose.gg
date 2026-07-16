<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/leaderboard.php
Description: Global leaderboard page, top 100 learners by quiz points
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
-->
<?php
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');

$user_id = $_SESSION['user_id'];

// refresh the overall snapshot in LEADERBOARD_T
$old_ranks = array();
$old_scores = array();

$old_sql = "SELECT user_id, rank_position, total_score FROM LEADERBOARD_T WHERE leaderboard_type = 'overall'";
$old_result = mysqli_query($conn, $old_sql);

while ($row = mysqli_fetch_assoc($old_result)) {
    $old_ranks[$row['user_id']] = $row['rank_position'];
    $old_scores[$row['user_id']] = $row['total_score'];
}

mysqli_query($conn, "DELETE FROM LEADERBOARD_T WHERE leaderboard_type = 'overall'");

$overall_sql = "SELECT u.user_id, SUM(r.marks_earned) AS points
                FROM QUIZ_LEARNING_RECORD_T r
                JOIN USER_T u ON u.user_id = r.user_id
                WHERE u.role = 'user' AND u.account_status = 'active'
                GROUP BY u.user_id
                ORDER BY points DESC";
$overall_result = mysqli_query($conn, $overall_sql);

$pos = 0;
while ($row = mysqli_fetch_assoc($overall_result)) {
    $pos++;
    $row_user_id = $row['user_id'];
    $row_points = $row['points'];

    $prev_rank = isset($old_ranks[$row_user_id]) ? $old_ranks[$row_user_id] : 'NULL';
    $score_change = isset($old_scores[$row_user_id]) ? $row_points - $old_scores[$row_user_id] : $row_points;

    $snap_sql = "INSERT INTO LEADERBOARD_T (user_id, leaderboard_type, total_score, rank_position, previous_rank, score_change, last_updated)
                 VALUES ('$row_user_id', 'overall', '$row_points', '$pos', $prev_rank, '$score_change', NOW())";
    mysqli_query($conn, $snap_sql);

    // rank achievements, only for myself so the popup lands in the right session
    if ($row_user_id == $user_id) {
        if ($pos <= 10) {
            award_achievement($conn, $user_id, 'LEADERBOARD_TOP10');
        }
        if ($pos == 1) {
            award_achievement($conn, $user_id, 'LEADERBOARD_TOP1');
        }
    }
}

$range = $_GET['range'] ?? 'weekly';

if ($range == 'monthly') {
    $date_filter = "AND r.answered_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} else if ($range == 'alltime') {
    $date_filter = "";
} else {
    $range = 'weekly';
    $date_filter = "AND r.answered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

$board_sql = "SELECT u.user_id, u.username, u.avatar_path, SUM(r.marks_earned) AS points
              FROM QUIZ_LEARNING_RECORD_T r
              JOIN USER_T u ON u.user_id = r.user_id
              WHERE u.role = 'user' AND u.account_status = 'active' $date_filter
              GROUP BY u.user_id
              ORDER BY points DESC";
$board_result = mysqli_query($conn, $board_sql);

// walk the whole ranking so the dock still knows my rank outside the top 100
$players = array();
$my_rank = 0;
$my_points = 0;
$rank = 0;

while ($row = mysqli_fetch_assoc($board_result)) {
    $rank++;

    if ($row['user_id'] == $user_id) {
        $my_rank = $rank;
        $my_points = $row['points'];
    }

    if ($rank <= 100) {
        $row['rank'] = $rank;
        $players[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_global_leaderboard.css">
    <title>Leaderboard - Implose.gg</title>
</head>
<body>
    <?php
        $current_page = 'user_leaderboard';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">
        <div class="board-container">

            <div class="board-header">
                <div>
                    <h1 class="pixel-title">Leaderboard</h1>
                    <p class="board-subtitle">The top 100 learners on Implose.gg. Climb the ranks!</p>
                </div>

                <div class="mascot-box">
                    <div class="mascot-glow"></div>
                    <img src="/Implose.gg-src/assets/images/ui/auth/welcome.png" alt="mascot">
                </div>
            </div>

            <div class="board-tabs">
                <a href="?range=weekly" class="tab-btn <?php echo $range == 'weekly' ? 'btn-pixel btn-pixel-red' : 'btn-pixel'; ?>">Weekly</a>
                <a href="?range=monthly" class="tab-btn <?php echo $range == 'monthly' ? 'btn-pixel btn-pixel-red' : 'btn-pixel'; ?>">Monthly</a>
                <a href="?range=alltime" class="tab-btn <?php echo $range == 'alltime' ? 'btn-pixel btn-pixel-red' : 'btn-pixel'; ?>">All Time</a>
            </div>

            <div class="board pixel-panel">
                <div class="board-head">
                    <span>RANK</span>
                    <span>PLAYER</span>
                    <span>POINTS</span>
                </div>

                <?php if (count($players) == 0): ?>
                    <div class="board-empty">No points earned in this period yet.</div>
                <?php endif; ?>

                <?php foreach ($players as $p): ?>
                    <?php
                        $is_me = ($p['user_id'] == $user_id);
                        $row_avatar = !empty($p['avatar_path']) ? '/Implose.gg-src/' . $p['avatar_path'] : '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';
                    ?>

                    <?php if ($p['rank'] <= 3): ?>
                        <div class="board-row champ rank-<?php echo $p['rank']; ?><?php if ($is_me) echo ' you'; ?>" <?php if ($is_me) echo 'data-you="true"'; ?>>
                            <span class="medal-bar"></span>
                            <span class="champ-rank"><?php echo $p['rank']; ?></span>
                            <span class="board-player">
                                <img class="champ-avatar" src="<?php echo $row_avatar; ?>" alt="avatar">
                                <span class="champ-name-col">
                                    <span class="champ-name"><?php echo htmlspecialchars($p['username']); ?></span>
                                    <?php if ($p['rank'] == 1): ?>
                                        <span class="champ-tag">CHAMPION</span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($is_me): ?>
                                    <span class="you-chip">YOU</span>
                                <?php endif; ?>
                            </span>
                            <span class="board-points"><?php echo number_format($p['points']); ?><span class="pts">PTS</span></span>
                        </div>
                    <?php else: ?>
                        <div class="board-row<?php if ($is_me) echo ' you'; ?>" <?php if ($is_me) echo 'data-you="true"'; ?>>
                            <span class="board-rank"><?php echo $p['rank']; ?></span>
                            <span class="board-player">
                                <img class="board-avatar" src="<?php echo $row_avatar; ?>" alt="avatar">
                                <span class="board-name"><?php echo htmlspecialchars($p['username']); ?></span>
                                <?php if ($is_me): ?>
                                    <span class="you-chip">YOU</span>
                                <?php endif; ?>
                            </span>
                            <span class="board-points"><?php echo number_format($p['points']); ?><span class="pts">PTS</span></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- $username and $user_avatar already loaded by nav.php -->
    <div class="you-dock pixel-panel">
        <span class="dock-label">YOUR RANK</span>
        <span class="dock-rank">#<?php echo $my_rank > 0 ? $my_rank : '-'; ?></span>
        <img class="dock-avatar" src="<?php echo $user_avatar; ?>" alt="avatar">
        <span class="dock-name"><?php echo htmlspecialchars($username); ?></span>
        <span class="dock-spacer"></span>
        <span class="dock-points"><?php echo number_format($my_points); ?><span class="pts">PTS</span></span>
        <button id="find-me-btn" class="btn-red dock-find">Find Me</button>
    </div>

    <script>
        document.getElementById('find-me-btn').addEventListener('click', function () {
            var row = document.querySelector('[data-you="true"]');
            if (!row) return;
            var y = row.getBoundingClientRect().top + window.scrollY - window.innerHeight / 2;
            window.scrollTo(0, Math.max(0, y));
        });
    </script>

</body>
</html>
