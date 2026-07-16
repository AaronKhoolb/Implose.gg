<!--
Programmer Name: Mr. Ng Jiun Chyn
Program Name: /pages/admin/rewards.php
Description: Admin Rewards Management Page
            - Stat cards (total rewards, pending approvals, total redemptions, total stock)
            - Rewards table with image, title, description, points, stock, actions
            - Edit links to edit_reward.php
            - Delete confirmation modal (PHP form POST)
            - Redemption approval queue with Approve / Reject actions
              (status tracked via SYSTEM_LOG_T; no schema change)
            - Image thumbnails
            - Tracks created_by and updated_by in REWARD_T
First Written on: Wednesday, 25-Jun-2026
Edited on: Sunday, 05-Jul-2026
-->

<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <!-- Admin Rewards CSS -->
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_rewards.css">

    <title>Rewards Management — Implose.gg Admin</title>
    <meta name="description" content="Manage reward items, upload images, set point costs, and track redemptions.">
</head>


<body class="admin-body">
    <?php
        $current_page = 'admin_rewards';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <?php
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

            // ── Flash messages ──
            $success_msg = $_SESSION['reward_success'] ?? '';
            $error_msg   = $_SESSION['reward_error']   ?? '';
            unset($_SESSION['reward_success'], $_SESSION['reward_error']);

            // ── Fetch all rewards ──
            $rewards_sql = "SELECT r.reward_id, r.title, r.description, r.points_required,
                                   r.stock_quantity, r.created_at, r.updated_at,
                                   u1.username AS created_by_name,
                                   u2.username AS updated_by_name,
                                   (SELECT COUNT(*) FROM REWARD_REDEMPTION_T rr
                                     WHERE rr.reward_id = r.reward_id) AS redemption_count
                            FROM REWARD_T r
                            LEFT JOIN USER_T u1 ON r.created_by = u1.user_id
                            LEFT JOIN USER_T u2 ON r.updated_by = u2.user_id
                            ORDER BY r.created_at DESC";
            $rewards_result = mysqli_query($conn, $rewards_sql);
            $rewards = array();
            if ($rewards_result) {
                while ($row = mysqli_fetch_assoc($rewards_result)) {
                    $row['image_url'] = findRewardImage($row['reward_id']);
                    $rewards[] = $row;
                }
            }

            // ── Stats ──
            $stat_total = count($rewards);

            $redemptions_sql    = "SELECT COUNT(*) AS cnt FROM REWARD_REDEMPTION_T";
            $redemptions_result = mysqli_query($conn, $redemptions_sql);
            $stat_redemptions   = 0;
            if ($redemptions_result) {
                $r = mysqli_fetch_assoc($redemptions_result);
                if ($r) {
                    $stat_redemptions = $r['cnt'];
                }
            }

            $stat_total_stock = 0;
            foreach ($rewards as $rw) {
                $stat_total_stock += $rw['stock_quantity'];
            }

            // ── Fetch approval decisions from SYSTEM_LOG_T ──
            // Description format written by approve/reject actions:
            //   "redemption_id=<id>|<message>"
            // Latest log per redemption wins.
            $status_map = array();
            $status_sql = "SELECT sl.log_id, sl.user_id AS admin_id, sl.action_type,
                                  sl.description, sl.created_at,
                                  u.username AS admin_name
                           FROM SYSTEM_LOG_T sl
                           LEFT JOIN USER_T u ON sl.user_id = u.user_id
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


            // ── Fetch all redemptions ──
            $redemptions_full_sql = "SELECT rr.redemption_id, rr.user_id, rr.reward_id, rr.redeemed_at,
                                            r.title AS reward_title, r.points_required,
                                            u.username, u.email_address
                                     FROM REWARD_REDEMPTION_T rr
                                     LEFT JOIN REWARD_T r ON rr.reward_id = r.reward_id
                                     LEFT JOIN USER_T   u ON rr.user_id   = u.user_id
                                     ORDER BY rr.redeemed_at DESC";
            $redemptions_full_result = mysqli_query($conn, $redemptions_full_sql);
            $redemptions_full = array();
            $pending_count    = 0;
            $approved_count   = 0;
            $rejected_count   = 0;

            if ($redemptions_full_result) {
                while ($row = mysqli_fetch_assoc($redemptions_full_result)) {
                    $rid = (int) $row['redemption_id'];
                    if (isset($status_map[$rid])) {
                        $log = $status_map[$rid];
                        if ($log['action_type'] === 'Reward Approve') {
                            $row['status']         = 'approved';
                            $approved_count++;
                        } else {
                            $row['status']         = 'rejected';
                            $rejected_count++;
                        }
                        $row['reviewed_at']   = $log['created_at'];
                        $row['reviewed_by']   = $log['admin_name'];
                    } else {
                        $row['status']       = 'pending';
                        $row['reviewed_at']  = null;
                        $row['reviewed_by']  = null;
                        $pending_count++;
                    }
                    $redemptions_full[] = $row;
                }
            }


            // ── Claim token helper (matches redeem_reward.php) ──
            function buildClaimTokenAdmin($redemption_id, $user_id, $reward_id, $redeemed_at) {
                $year      = date('Y', strtotime($redeemed_at));
                $id_padded = str_pad($redemption_id, 6, '0', STR_PAD_LEFT);
                $hash      = strtoupper(substr(
                    hash('sha256', $redemption_id . '|implose_gg|' . $user_id . '|' . $reward_id),
                    0, 4
                ));
                return "RWD-{$year}-{$id_padded}-{$hash}";
            }
        ?>


        <!-- ── Page Header ── -->
        <div class="rw-page-header">
            <div class="rw-page-header-left">
                <h1>Rewards Management</h1>
                <p>Create, edit, and manage reward items for the reward shop.</p>
            </div>
            <a class="btn-create-reward" href="/Implose.gg-src/pages/admin/create_reward.php">
                + Create Reward
            </a>
        </div>


        <!-- ── Flash Messages ── -->
        <?php if ($success_msg): ?>
            <div class="rw-flash success">&#10003; <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="rw-flash error">&#10007; <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>


        <!-- Stat Cards -->
        <div class="rw-stats-row">
            <div class="rw-stat-card" style="display: flex !important; flex-direction: column !important; align-items: flex-start !important; justify-content: center !important; padding-left: 24px !important; position: relative;">
                <img src="/Implose.gg-src/assets/images/icons/reward.svg" alt="" style="position: absolute; top: 50%; right: 28px; transform: translateY(-50%); width: 64px; height: 64px; opacity: 0.5; filter: invert(1); image-rendering: pixelated;">
                <span class="rw-stat-label">Total Rewards</span>
                <span class="rw-stat-value"><?php echo $stat_total; ?></span>
            </div>
            <div class="rw-stat-card"style="display: flex !important; flex-direction: column !important; align-items: flex-start !important; justify-content: center !important; padding-left: 24px !important; position: relative;">
                <img src="/Implose.gg-src/assets/images/icons/pedingstock.svg" alt="" style="position: absolute; top: 50%; right: 28px; transform: translateY(-50%); width: 64px; height: 64px; opacity: 0.5; filter: invert(1); image-rendering: pixelated;">
                <span class="rw-stat-label">Pending Stock</span>
                <span class="rw-stat-value"><?php echo number_format($pending_count); ?></span>
            </div>
            <div class="rw-stat-card"style="display: flex !important; flex-direction: column !important; align-items: flex-start !important; justify-content: center !important; padding-left: 24px !important; position: relative;">
                <img src="/Implose.gg-src/assets/images/icons/stock.svg" alt="" style="position: absolute; top: 50%; right: 28px; transform: translateY(-50%); width: 64px; height: 64px; opacity: 0.5; filter: invert(1); image-rendering: pixelated;">
                <span class="rw-stat-label">Total Stock</span>
                <span class="rw-stat-value"><?php echo number_format($stat_total_stock); ?></span>
            </div>
        </div>


        <!-- Rewards Table -->
        <div class="rw-table-card">
            <div class="rw-toolbar">
                <h3>All Rewards</h3>
            </div>

            <div class="rw-table-wrap">
                <?php if (empty($rewards)): ?>
                    <div class="rw-table-empty">
                        <span class="rw-table-empty-icon"></span>
                        <p>No rewards created yet. Click "Create Reward" to add your first item.</p>
                    </div>
                <?php else: ?>
                    <table class="rw-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Points</th>
                                <th>Stock</th>
                                <th>Created By</th>
                                <th>Updated By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rewards as $rw): ?>
                                <tr>
                                    <td>
                                        <div class="rw-thumb">
                                            <?php if ($rw['image_url']): ?>
                                                <img src="<?php echo htmlspecialchars($rw['image_url']); ?>" alt="">
                                            <?php else: ?>
                                                <span class="rw-thumb-placeholder"></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($rw['title']); ?></strong></td>
                                    <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($rw['description'] ?? '—'); ?>
                                    </td>
                                    <td>
                                        <span class="rw-points-badge"><?php echo number_format($rw['points_required']); ?> pts</span>
                                    </td>
                                    <td>
                                        <?php
                                            $stock = $rw['stock_quantity'];
                                            if ($stock <= 0) {
                                                $stock_class = 'out-stock';
                                            } elseif ($stock <= 5) {
                                                $stock_class = 'low-stock';
                                            } else {
                                                $stock_class = 'in-stock';
                                            }
                                        ?>
                                        <span class="rw-stock-badge <?php echo $stock_class; ?>">
                                            <?php echo $stock; ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 12px; color: var(--admin-text-muted);">
                                        <?php echo htmlspecialchars($rw['created_by_name'] ?? '—'); ?>
                                    </td>
                                    <td style="font-size: 12px; color: var(--admin-text-muted);">
                                        <?php echo htmlspecialchars($rw['updated_by_name'] ?? '—'); ?>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="row-action-btn" href="/Implose.gg-src/pages/admin/edit_reward.php?id=<?php echo $rw['reward_id']; ?>" title="Edit">
                                                <img src="/Implose.gg-src/assets/images/icons/pencil.svg" alt="Edit">
                                            </a>
                                            <button type="button" class="row-action-btn delete" title="Delete"
                                                    onclick="requestDeleteReward(<?= (int)$rw['reward_id'] ?>, <?= htmlspecialchars(json_encode($rw['title']), ENT_QUOTES) ?>, <?= (int)$rw['redemption_count'] ?>)">
                                                <img src="/Implose.gg-src/assets/images/icons/trash.svg" alt="Delete">
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>


        <!-- Reward Redemptions (Approval Queue) -->
        <?php $filter = $_GET['filter'] ?? 'pending'; ?>
        <div class="rw-table-card">
            <div class="rw-toolbar">
                <h3>Reward Redemptions</h3>
                <a href="?filter=pending"  class="rw-btn-edit"   style="text-decoration:none; <?php echo $filter === 'pending'  ? 'background: rgba(34, 211, 238, 0.2); border-color: rgba(34, 211, 238, 0.5);' : ''; ?>">Pending (<?php echo $pending_count; ?>)</a>
                <a href="?filter=approved" class="rw-btn-edit"   style="text-decoration:none; <?php echo $filter === 'approved' ? 'background: rgba(34, 211, 238, 0.2); border-color: rgba(34, 211, 238, 0.5);' : ''; ?>">Approved (<?php echo $approved_count; ?>)</a>
                <a href="?filter=rejected" class="rw-btn-edit"   style="text-decoration:none; <?php echo $filter === 'rejected' ? 'background: rgba(34, 211, 238, 0.2); border-color: rgba(34, 211, 238, 0.5);' : ''; ?>">Rejected (<?php echo $rejected_count; ?>)</a>
                <a href="?filter=all"      class="rw-btn-edit"   style="text-decoration:none; <?php echo $filter === 'all'      ? 'background: rgba(34, 211, 238, 0.2); border-color: rgba(34, 211, 238, 0.5);' : ''; ?>">All</a>
            </div>

            <div class="rw-table-wrap">
                <?php
                    $filtered = array();
                    foreach ($redemptions_full as $rr) {
                        if ($filter === 'all' || $rr['status'] === $filter) {
                            $filtered[] = $rr;
                        }
                    }
                ?>

                <?php if (empty($filtered)): ?>
                    <div class="rw-table-empty">
                        <span class="rw-table-empty-icon">📋</span>
                        <p>No <?php echo htmlspecialchars($filter); ?> redemptions.</p>
                    </div>
                <?php else: ?>
                    <table class="rw-table">
                        <thead>
                            <tr>
                                <th>Claim Token</th>
                                <th>User</th>
                                <th>Reward</th>
                                <th>Points</th>
                                <th>Redeemed At</th>
                                <th>Status</th>
                                <th>Reviewed By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered as $rr):
                                $token = buildClaimTokenAdmin(
                                    $rr['redemption_id'],
                                    $rr['user_id'],
                                    $rr['reward_id'],
                                    $rr['redeemed_at']
                                );

                                // Reuse existing stock-badge palette:
                                //   in-stock  → approved (green)
                                //   low-stock → pending  (orange)
                                //   out-stock → rejected (red)
                                if ($rr['status'] === 'approved') {
                                    $badge_class = 'in-stock';
                                } elseif ($rr['status'] === 'rejected') {
                                    $badge_class = 'out-stock';
                                } else {
                                    $badge_class = 'low-stock';
                                }
                            ?>
                                <tr>
                                    <td style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($token); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rr['username'] ?? '—'); ?></strong><br>
                                        <span style="font-size: 12px; color: var(--admin-text-muted);"><?php echo htmlspecialchars($rr['email_address'] ?? ''); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($rr['reward_title'] ?? '—'); ?></td>
                                    <td>
                                        <span class="rw-points-badge"><?php echo number_format($rr['points_required'] ?? 0); ?> pts</span>
                                    </td>
                                    <td style="font-size: 12px;"><?php echo date('j M Y, g:ia', strtotime($rr['redeemed_at'])); ?></td>
                                    <td>
                                        <span class="rw-stock-badge <?php echo $badge_class; ?>" style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em;">
                                            <?php echo ucfirst($rr['status']); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 12px; color: var(--admin-text-muted);">
                                        <?php if ($rr['reviewed_by']): ?>
                                            <?php echo htmlspecialchars($rr['reviewed_by']); ?><br>
                                            <span style="font-size: 11px;"><?php echo date('j M, g:ia', strtotime($rr['reviewed_at'])); ?></span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($rr['status'] === 'pending'): ?>
                                            <div class="rw-actions">
                                                <form method="POST" action="/Implose.gg-src/actions/admin/approve_redemption.php" style="display:inline;"
                                                      onsubmit="return confirm('Approve this redemption?');">
                                                    <input type="hidden" name="redemption_id" value="<?php echo $rr['redemption_id']; ?>">
                                                    <button type="submit" class="rw-btn-edit">Approve</button>
                                                </form>
                                                <form method="POST" action="/Implose.gg-src/actions/admin/reject_redemption.php" style="display:inline;"
                                                      onsubmit="return confirm('Reject this redemption? Points will be refunded and stock returned.');">
                                                    <input type="hidden" name="redemption_id" value="<?php echo $rr['redemption_id']; ?>">
                                                    <button type="submit" class="rw-btn-delete">Reject</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--admin-text-muted); font-size: 12px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>


    <script>
    // Confirm + submit a hidden form to the delete action
    function requestDeleteReward(id, title, redemption_count) {
        // extra warning when the reward already has redemptions
        var warn = '';
        if (redemption_count > 0) {
            var userVerb;
            if (redemption_count == 1) {
                userVerb = 'user has';
            } else {
                userVerb = 'users have';
            }
            warn = '\n\nWARNING: ' + redemption_count + ' ' + userVerb
                 + ' already redeemed this reward.';
        }

        var ok = confirm('Delete reward "' + title + '"? This cannot be undone.' + warn);
        if (!ok) {
            return;
        }

        // build a hidden POST form and submit it
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/Implose.gg-src/actions/admin/delete_reward.php';

        var input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = 'reward_id';
        input.value = id;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
    </script>

</body>
</html>
