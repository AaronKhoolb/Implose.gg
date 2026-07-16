<!--
Programmer Name: Mr. Ng Jiunn Chyn
Program Name: /pages/user/rewards.php
Description: User Rewards page
            - Points balance indicator (reads USER_T.total_points)
            - Reward catalog grid (REWARD_T): image, title, description, points, stock
            - Redeem button submits a PHP form POST to /actions/user/redeem_reward.php
              (stock check + balance check + row-locked transaction on the backend)
            - Success and error messages shown via PHP session flashes
            - Claim token displayed after successful redemption
            - Redemption history (REWARD_REDEMPTION_T) with per-row claim token
First Written on: Tuesday, 24-Jun-2026
Edited on: Wednesday, 02-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <!-- Rewards CSS -->
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_rewards.css">

    <title>Rewards — Implose.gg</title>
    <meta name="description" content="Redeem your earned points for exclusive rewards and track your redemption history.">
</head>


<body>
    <?php
        $current_page = 'user_rewards';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <?php
        $uid = ($_SESSION['user_id'] ?? 0);

        // ── Helper: find reward image by convention (with cache-buster) ──
        function findRewardImage($reward_id) {
            $extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'avif');
            $base_dir   = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/rewards/';
            foreach ($extensions as $ext) {
                $file = $base_dir . $reward_id . '.' . $ext;
                if (file_exists($file)) {
                    return '/Implose.gg-src/uploads/rewards/' . $reward_id . '.' . $ext . '?v=' . filemtime($file);
                }
            }
            return null;
        }

        // ── Fetch user points ──
        $points_sql    = "SELECT total_points FROM USER_T WHERE user_id = $uid";
        $points_result = mysqli_query($conn, $points_sql);
        $user_points   = 0;
        if ($points_result) {
            $row = mysqli_fetch_assoc($points_result);
            if ($row) {
                $user_points = $row['total_points'];
            }
        }

        // ── Fetch available rewards ──
        $rewards_sql = "SELECT r.reward_id, r.title, r.description, r.points_required, r.stock_quantity
                        FROM REWARD_T r
                        ORDER BY r.points_required ASC";
        $rewards_result = mysqli_query($conn, $rewards_sql);
        $rewards = array();
        if ($rewards_result) {
            while ($row = mysqli_fetch_assoc($rewards_result)) {
                $rewards[] = $row;
            }
        }

        // ── Fetch redemption history ──
        $history_sql = "SELECT rr.redemption_id, rr.reward_id, rr.redeemed_at,
                               r.title, r.points_required
                        FROM REWARD_REDEMPTION_T rr
                        JOIN REWARD_T r ON rr.reward_id = r.reward_id
                        WHERE rr.user_id = $uid
                        ORDER BY rr.redeemed_at DESC";
        $history_result = mysqli_query($conn, $history_sql);
        $history = array();
        if ($history_result) {
            while ($row = mysqli_fetch_assoc($history_result)) {
                $history[] = $row;
            }
        }

        // ── Fetch approval / rejection decisions from SYSTEM_LOG_T 
        $status_map = array();
        $status_sql = "SELECT sl.log_id, sl.action_type, sl.description, sl.created_at
                       FROM SYSTEM_LOG_T sl
                       WHERE sl.action_type IN ('Reward Approve', 'Reward Reject')
                       ORDER BY sl.log_id DESC";
        $status_result = mysqli_query($conn, $status_sql);
        if ($status_result) {
            while ($srow = mysqli_fetch_assoc($status_result)) {
                if (preg_match('/^redemption_id=(\d+)\|/', $srow['description'], $m)) {
                    $rid = (int) $m[1];
                    if (!isset($status_map[$rid])) {
                        $status_map[$rid] = $srow;
                    }
                }
            }
        }

        foreach ($history as &$h) {
            $rid = (int) $h['redemption_id'];
            if (isset($status_map[$rid])) {
                $log = $status_map[$rid];
                $h['status']       = ($log['action_type'] === 'Reward Approve') ? 'approved' : 'rejected';
                $h['reviewed_at']  = $log['created_at'];
            } else {
                $h['status']      = 'pending';
                $h['reviewed_at'] = null;
            }
        }
        unset($h);

        // ── Claim token helper (must match /actions/user/redeem_reward.php)
        function buildClaimToken($redemption_id, $user_id, $reward_id, $redeemed_at) {
            $year      = date('Y', strtotime($redeemed_at));
            $id_padded = str_pad($redemption_id, 6, '0', STR_PAD_LEFT);
            $hash      = strtoupper(substr(
                hash('sha256', $redemption_id . '|implose_gg|' . $user_id . '|' . $reward_id),
                0, 4
            ));
            return "RWD-{$year}-{$id_padded}-{$hash}";
        }

        // ── Read result from URL params (set by redeem_reward.php redirect) ──
        $redeem_success     = '';
        $redeem_error       = '';
        $redeem_claim_token = '';
        $redeem_title       = '';

        if (isset($_GET['success'])) {
            $redeem_success     = 'Reward redeemed successfully!';
            $redeem_claim_token = $_GET['token'] ?? '';
            $redeem_title       = $_GET['title'] ?? '';
        }

        if (isset($_GET['error'])) {
            $redeem_error = $_GET['error'];
        }
    ?>

    <div class="main-content">
        <div class="rewards-page">

            <!-- PAGE HEADER + POINTS BALANCE -->
            <div class="rewards-page-header">
                <div class="rewards-page-header-left">
                    <h1 class="pixel-title">Rewards</h1>
                    <p>Spend your hard-earned points on exclusive rewards!</p>
                </div>

            </div>


            <!-- ── Flash Messages ── -->
            <?php if ($redeem_success): ?>
                <div class="reward-flash-success pixel-panel" style="margin-bottom: 24px; padding: 16px 20px;">
                    <strong>&#10003; <?php echo htmlspecialchars($redeem_success); ?></strong><br>
                    <?php if ($redeem_title): ?>
                        <span style="color: var(--text-muted);">Reward: <?php echo htmlspecialchars($redeem_title); ?></span><br>
                    <?php endif; ?>
                    <?php if ($redeem_claim_token): ?>
                        <span style="color: var(--text-muted);">Your claim token: <strong><?php echo htmlspecialchars($redeem_claim_token); ?></strong></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($redeem_error): ?>
                <div class="reward-flash-error pixel-panel" style="margin-bottom: 24px; padding: 16px 20px; background: rgba(239,68,68,0.1); border-color: #ef4444;">
                    <strong>&#10007; <?php echo htmlspecialchars($redeem_error); ?></strong>
                </div>
            <?php endif; ?>


            <!-- REWARD CATALOG -->
            <div class="rewards-catalog">
                <h2 class="rewards-section-title pixel-title">&#128722; Reward Catalog</h2>

                <?php if (empty($rewards)): ?>
                    <div class="rewards-empty pixel-panel">
                        <span class="rewards-empty-icon">🎁</span>
                        <p class="rewards-empty-text">No rewards available at the moment.<br>Check back soon for new items!</p>
                    </div>
                <?php else: ?>
                    <div class="rewards-grid">
                        <?php foreach ($rewards as $r):
                            $rid      = $r['reward_id'];
                            $stock    = $r['stock_quantity'];
                            $cost     = $r['points_required'];
                            $can_afford  = $user_points >= $cost;
                            $in_stock    = $stock > 0;
                            $can_redeem  = $can_afford && $in_stock;
                        ?>
                            <div class="reward-card pixel-panel"
                                 id="reward-card-<?php echo $rid; ?>">

                                <!-- Card Image -->
                                <?php $img_url = findRewardImage($rid); ?>
                                <div class="reward-card-image">
                                    <?php if ($img_url): ?>
                                        <img src="<?php echo htmlspecialchars($img_url); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>">
                                    <?php else: ?>
                                        <span class="reward-placeholder-icon">🎁</span>
                                    <?php endif; ?>
                                    <?php if (!$in_stock): ?>
                                        <div class="out-of-stock-overlay">
                                            <span>Out of Stock</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Card Body -->
                                <div class="reward-card-body">
                                    <span class="reward-card-title"><?php echo htmlspecialchars($r['title']); ?></span>

                                    <?php if (!empty($r['description'])): ?>
                                        <span class="reward-card-description"><?php echo htmlspecialchars($r['description']); ?></span>
                                    <?php endif; ?>

                                    <div class="reward-card-meta">
                                        <span class="reward-card-points">
                                            <img src="/Implose.gg-src/assets/images/icons/nav_coin.svg" alt="pts">
                                            <?php echo number_format($cost); ?>
                                        </span>
                                        <?php if ($in_stock): ?>
                                            <span class="reward-card-stock <?php echo ($stock <= 3) ? 'low' : ''; ?>">
                                                <?php echo $stock; ?> left
                                            </span>
                                        <?php else: ?>
                                            <span class="reward-card-stock low">Sold out</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Card Footer -->
                                <div class="reward-card-footer">
                                    <?php if ($can_redeem): ?>
                                        <form method="POST" action="/Implose.gg-src/actions/user/redeem_reward.php"
                                              onsubmit="return confirm('Redeem <?php echo htmlspecialchars(addslashes($r['title'])); ?> for <?php echo number_format($cost); ?> points?');">
                                            <input type="hidden" name="reward_id" value="<?php echo $rid; ?>">
                                            <button type="submit" class="btn-red btn-redeem">Redeem</button>
                                        </form>
                                    <?php elseif (!$in_stock): ?>
                                        <button class="btn-pixel btn-redeem disabled" disabled>Out of Stock</button>
                                    <?php else: ?>
                                        <button class="btn-pixel btn-redeem disabled" disabled>Not Enough Points</button>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>


            <!-- REDEMPTION HISTORY -->
            <div class="rewards-history">
                <h2 class="rewards-section-title pixel-title">&#128220; Redemption History</h2>

                <div class="rewards-history-panel pixel-panel">
                    <?php if (empty($history)): ?>
                        <div class="rewards-empty">
                            <span class="rewards-empty-icon">📋</span>
                            <p class="rewards-empty-text">You haven't redeemed any rewards yet.<br>Start spending your points!</p>
                        </div>
                    <?php else: ?>
                        <div class="history-list">
                            <?php foreach ($history as $h):
                                $claim_token = buildClaimToken(
                                    $h['redemption_id'],
                                    $uid,
                                    $h['reward_id'],
                                    $h['redeemed_at']
                                );
                                $status       = $h['status'];
                                $is_rejected  = ($status === 'rejected');
                                $is_approved  = ($status === 'approved');
                                $status_label = ucfirst($status);
                            ?>
                                <div class="history-item">
                                    <div class="history-item-left">
                                        <span class="history-item-title">
                                            <?php echo htmlspecialchars($h['title']); ?>
                                            <span class="history-status-badge history-status-<?php echo $status; ?>">
                                                <?php echo $status_label; ?>
                                            </span>
                                        </span>
                                        <span class="history-item-date">
                                            Redeemed on <?php echo date('j M Y, g:ia', strtotime($h['redeemed_at'])); ?>
                                        </span>
                                        <?php if ($is_rejected): ?>
                                            <span class="history-item-date">
                                                Rejected on <?php echo date('j M Y, g:ia', strtotime($h['reviewed_at'])); ?> — points refunded
                                            </span>
                                        <?php else: ?>
                                            <span class="history-item-date">
                                                Claim token: <?php echo htmlspecialchars($claim_token); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="history-item-right">
                                        <img src="/Implose.gg-src/assets/images/icons/nav_coin.svg" style="width: 22px !important; height: 22px !important; display: block !important; flex-shrink: 0 !important; image-rendering: pixelated !important; "alt="pts">
                                        <?php if ($is_rejected): ?>
                                            <span class="history-item-points history-item-points-refund">+<?php echo number_format($h['points_required']); ?></span>
                                        <?php else: ?>
                                            <span class="history-item-points">-<?php echo number_format($h['points_required']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
