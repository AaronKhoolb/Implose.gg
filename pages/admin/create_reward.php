<!--
Programmer Name: Mr. Ng Jiunn Chyn
Program Name: /pages/admin/create_reward.php
Description: Admin Create Reward Page
            - Create a new reward item (title, description, points, stock, image)
            - Follows the same layout/CSS as /pages/admin/create_user.php
              (uses the eu-* classes from admin_edit_user.css)
            - Form submits to /actions/admin/create_reward.php
            - Writes to REWARD_T with created_by / updated_by
First Written on: Wednesday, 25-Jun-2026
Edited on: Wednesday, 02-Jul-2026
-->

<?php
session_start();

// flash messages from the create action
$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['create_reward_success'])) {
    $success_msg = $_SESSION['create_reward_success'];
    unset($_SESSION['create_reward_success']);
}

if (isset($_SESSION['create_reward_error'])) {
    $error_msg = $_SESSION['create_reward_error'];
    unset($_SESSION['create_reward_error']);
}

// re-populate form on validation error
$prev = $_SESSION['create_reward_form'] ?? array();
unset($_SESSION['create_reward_form']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_edit_user.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_rewards.css?v=<?= time() ?>">
    <title>Create Reward — Implose.gg Admin</title>
    <meta name="description" content="Create a new reward item with title, description, points cost, stock, and image.">
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
                    <span class="current">Create Reward</span>
                </nav>
                <h1>Create Reward</h1>
                <p>Fill in the fields below to add a new item to the reward shop.</p>
            </div>
        </div>


        <!-- ── Flash Messages ── -->
        <?php if ($success_msg): ?>
            <div class="admin-toast admin-toast--success" id="admin-toast">
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="admin-toast admin-toast--error" id="admin-toast">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>


        <!-- ── Reward Info Card ── -->
        <div class="eu-card">

            <div class="eu-card-header">
                <img class="eu-card-header-icon" src="/Implose.gg-src/assets/images/icons/nav_coin.svg" alt="Reward">
                <span class="eu-card-header-title">New Reward Information</span>
            </div>

            <div class="eu-card-divider"></div>

            <div class="eu-card-body">

                <p class="eu-card-desc">Set the title, cost, and stock. Users can redeem this item from the reward shop once created.</p>

                <form method="POST" action="/Implose.gg-src/actions/admin/create_reward.php" enctype="multipart/form-data">

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
                                value="<?php echo htmlspecialchars($prev['title'] ?? ''); ?>"
                                required
                                autocomplete="off"
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
                                placeholder="e.g., 100"
                                min="1"
                                value="<?php echo htmlspecialchars($prev['points_required'] ?? ''); ?>"
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
                                placeholder="e.g., 10"
                                min="0"
                                value="<?php echo htmlspecialchars($prev['stock_quantity'] ?? ''); ?>"
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
                                placeholder="Describe what the user will receive..."
                                rows="4"
                            ><?php echo htmlspecialchars($prev['description'] ?? ''); ?></textarea>
                        </div>

                        <!-- Reward Image -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label">
                                Reward Image
                            </label>

                            <div class="eu-avatar-row">
                                <!-- Image preview -->
                                <div class="eu-avatar-preview" id="reward-preview" style="background: transparent;">
                                    <span id="reward-placeholder"></span>
                                    <img id="reward-preview-img" src="" alt="Reward preview" style="display:none;">
                                </div>

                                <!-- Upload controls -->
                                <div class="eu-avatar-controls">
                                    <label class="eu-file-btn" for="reward-file-input">
                                        Choose File
                                        <input type="file" name="reward_image" id="reward-file-input" accept="image/png,image/jpeg,image/webp,image/gif,image/avif" style="display:none;">
                                    </label>
                                    <p class="eu-avatar-hint">PNG, JPG, WEBP, GIF, AVIF</p>
                                </div>
                            </div>
                        </div>

                    </div>


                    <!-- ── Action Footer ── -->
                    <div class="eu-footer-actions">
                        <a href="/Implose.gg-src/pages/admin/rewards.php" class="btn-cancel">← Back to Rewards</a>
                        <button type="submit" class="btn-save">Create Reward</button>
                    </div>

                </form>

            </div>

        </div>

    </div>

    <script>
        // Image preview on file select
        var rewardFile        = document.getElementById('reward-file-input');
        var rewardImg         = document.getElementById('reward-preview-img');
        var rewardPlaceholder = document.getElementById('reward-placeholder');

        rewardFile.onchange = function() {
            if (rewardFile.files.length > 0) {
                if (rewardPlaceholder) {
                    rewardPlaceholder.style.display = 'none';
                }
                rewardImg.style.display = '';
                rewardImg.src = URL.createObjectURL(rewardFile.files[0]);
            }
        };
    </script>

</body>
</html>
