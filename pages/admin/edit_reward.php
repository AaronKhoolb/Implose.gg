<!--
Programmer Name: Mr. Ng Jiunn Chyn
Program Name: /pages/admin/edit_reward.php
Description: Admin Edit Reward Page (pure PHP)
            - Load reward from REWARD_T by ?id= param
            - Redirect to rewards.php if no valid reward id
            - Edit title, description, points, stock, image
            - Form submits to /actions/admin/update_reward.php
            - Follows the same layout/CSS as /pages/admin/edit_user.php
First Written on: Wednesday, 25-Jun-2026
Edited on: Sunday, 05-Jul-2026
-->

<?php
session_start();

// include DB connection
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

// get reward id from URL
$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($edit_id <= 0) {
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}

// fetch reward + creator/updater names
$edit_sql = "SELECT r.reward_id, r.title, r.description, r.points_required,
                    r.stock_quantity, r.created_at, r.updated_at,
                    u1.username AS created_by_name,
                    u2.username AS updated_by_name
             FROM REWARD_T r
             LEFT JOIN USER_T u1 ON r.created_by = u1.user_id
             LEFT JOIN USER_T u2 ON r.updated_by = u2.user_id
             WHERE r.reward_id = '$edit_id'";
$edit_result = mysqli_query($conn, $edit_sql);

if (!$edit_result || mysqli_num_rows($edit_result) !== 1) {
    $_SESSION['reward_error'] = 'Reward not found.';
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}

$reward = mysqli_fetch_assoc($edit_result);

// resolve reward image by convention (with cache-buster from mtime)
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
$image_url = findRewardImage($edit_id);

// count redemptions so far (informational)
$rc_sql    = "SELECT COUNT(*) AS cnt FROM REWARD_REDEMPTION_T WHERE reward_id = '$edit_id'";
$rc_result = mysqli_query($conn, $rc_sql);
$rc_row    = $rc_result ? mysqli_fetch_assoc($rc_result) : ['cnt' => 0];
$redemption_count = (int)($rc_row['cnt'] ?? 0);

// flash messages from update action
$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['edit_reward_success'])) {
    $success_msg = $_SESSION['edit_reward_success'];
    unset($_SESSION['edit_reward_success']);
}

if (isset($_SESSION['edit_reward_error'])) {
    $error_msg = $_SESSION['edit_reward_error'];
    unset($_SESSION['edit_reward_error']);
}

$display_name = $reward['title'] ?? '(Untitled)';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_edit_user.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_rewards.css?v=<?= time() ?>">
    <title>Edit Reward — Implose.gg Admin</title>
    <meta name="description" content="Edit a reward item's title, description, points cost, stock, and image.">
</head>


<body class="admin-body">
    <?php
        $current_page = 'admin_rewards';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <!-- ── Page Header ── -->
        <div class="eu-page-header">
            <div class="eu-page-header-left">
                <nav class="eu-breadcrumb" aria-label="Breadcrumb">
                    <a href="/Implose.gg-src/pages/admin/rewards.php">Rewards Management</a>
                    <span class="sep">/</span>
                    <span class="current">Edit — <?= htmlspecialchars($display_name) ?></span>
                </nav>
                <h1>Edit — <?= htmlspecialchars($display_name) ?></h1>
                <p>Update this reward's details. <?= number_format($redemption_count) ?> redemption<?= $redemption_count === 1 ? '' : 's' ?> so far.</p>
            </div>
        </div>


        <!-- ── Toast Notification ── -->
        <?php
            $toast = null;
            if ($success_msg) {
                $toast = ['type' => 'success', 'text' => $success_msg];
            } elseif ($error_msg) {
                $toast = ['type' => 'error', 'text' => $error_msg];
            }
        ?>
        <?php if ($toast): ?>
            <div class="admin-toast admin-toast--<?= $toast['type'] ?>" id="admin-toast">
                <?= htmlspecialchars($toast['text']) ?>
            </div>
            <script>
                setTimeout(() => {
                    const t = document.getElementById('admin-toast');
                    if (t) t.classList.add('admin-toast--hide');
                }, 15000);
            </script>
        <?php endif; ?>


        <!-- ── Reward Info Card ── -->
        <div class="eu-card">

            <div class="eu-card-header">
                <img class="eu-card-header-icon" src="/Implose.gg-src/assets/images/icons/nav_coin.svg" alt="Reward">
                <span class="eu-card-header-title">Reward Information</span>
            </div>

            <div class="eu-card-divider"></div>

            <div class="eu-card-body">

                <form method="POST" action="/Implose.gg-src/actions/admin/update_reward.php" enctype="multipart/form-data">

                    <input type="hidden" name="reward_id" value="<?= (int)$reward['reward_id'] ?>">

                    <!-- ── 2-col grid ── -->
                    <div class="eu-form-grid">

                        <!-- Title (full width) -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label" for="field-title">
                                Reward Title <span class="req">*</span>
                            </label>
                            <input
                                type="text"
                                id="field-title"
                                name="title"
                                class="eu-input"
                                placeholder="e.g., Gaming Mouse"
                                maxlength="255"
                                minlength="2"
                                required
                                autocomplete="off"
                                value="<?= htmlspecialchars($reward['title'] ?? '') ?>"
                            >
                        </div>

                        <!-- Points Required -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-points">
                                Points Required <span class="req">*</span>
                            </label>
                            <input
                                type="number"
                                id="field-points"
                                name="points_required"
                                class="eu-input"
                                value="<?= (int)($reward['points_required'] ?? 0) ?>"
                                min="1"
                                max="100000"
                                required
                            >
                        </div>

                        <!-- Stock Quantity -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-stock">
                                Stock Quantity <span class="req">*</span>
                            </label>
                            <input
                                type="number"
                                id="field-stock"
                                name="stock_quantity"
                                class="eu-input"
                                value="<?= (int)($reward['stock_quantity'] ?? 0) ?>"
                                min="0"
                                required
                            >
                        </div>

                        <!-- Description (full width) -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label" for="field-description">
                                Description
                            </label>
                            <textarea
                                id="field-description"
                                name="description"
                                class="eu-input"
                                rows="4"
                                maxlength="1000"
                                placeholder="Describe what the user will receive..."><?= htmlspecialchars($reward['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Created By (read-only) -->
                        <div class="eu-form-field">
                            <label class="eu-label">Created By</label>
                            <input
                                type="text"
                                class="eu-input"
                                disabled
                                value="<?= htmlspecialchars(($reward['created_by_name'] ?? '—') . ' • ' . date('j M Y', strtotime($reward['created_at']))) ?>"
                            >
                        </div>

                        <!-- Total Redemptions (read-only) -->
                        <div class="eu-form-field">
                            <label class="eu-label">Total Redemptions</label>
                            <input
                                type="text"
                                class="eu-input"
                                disabled
                                value="<?= number_format($redemption_count) ?> redeemed"
                            >
                        </div>

                        <!-- Reward Image -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label">Reward Image</label>

                            <div class="eu-avatar-row">
                                <div class="eu-avatar-preview" id="reward-preview">
                                    <img id="reward-preview-img"
                                         src="<?= htmlspecialchars($image_url ?: '/Implose.gg-src/assets/images/icons/nav_coin.svg') ?>"
                                         alt="Reward preview"
                                         class="<?= $image_url ? '' : 'placeholder' ?>">
                                </div>

                                <div class="eu-avatar-controls">
                                    <label class="eu-file-btn" for="reward-file-input">
                                        <?= $image_url ? 'Replace File' : 'Choose File' ?>
                                        <input type="file" name="reward_image" id="reward-file-input" accept="image/png,image/jpeg,image/webp,image/gif,image/avif" style="display:none;">
                                    </label>
                                    <p class="eu-avatar-hint">PNG, JPG, WEBP, GIF, AVIF</p>
                                </div>
                            </div>
                        </div>

                        <script>
                            const rewardFile = document.getElementById('reward-file-input');
                            const rewardImg  = document.getElementById('reward-preview-img');

                            rewardFile.onchange = function () {
                                if (rewardFile.files.length > 0) {
                                    rewardImg.src = URL.createObjectURL(rewardFile.files[0]);
                                    rewardImg.classList.remove('placeholder');
                                }
                            };
                        </script>

                    </div><!-- /.eu-form-grid -->


                    <!-- ── Action Footer ── -->
                    <div class="eu-footer-actions">
                        <a href="/Implose.gg-src/pages/admin/rewards.php" class="btn-cancel">← Back to Rewards</a>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>

                </form>

            </div><!-- /.eu-card-body -->

        </div><!-- /.eu-card -->

    </div><!-- /.admin-main-content -->

</body>
</html>
