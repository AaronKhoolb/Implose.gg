<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/admin/create_achievement.php
Description: Admin Create Achievement Page
            - Create a new achievement with title, description, badge image, points
            - Form submits to /actions/admin/create_achievement.php
First Written on: Saturday, 27-Jun-2026
Edited on: Saturday, 27-Jun-2026
-->

<?php
session_start();

// trigger options
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');
$trigger_options = achievement_trigger_options();

// flash messages
$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['create_achievement_success'])) {
    $success_msg = $_SESSION['create_achievement_success'];
    unset($_SESSION['create_achievement_success']);
}

if (isset($_SESSION['create_achievement_error'])) {
    $error_msg = $_SESSION['create_achievement_error'];
    unset($_SESSION['create_achievement_error']);
}

// preserve typed-in values on validation error
$old = $_SESSION['create_achievement_old'] ?? [];
unset($_SESSION['create_achievement_old']);
$old_title   = htmlspecialchars($old['title']        ?? '');
$old_desc    = htmlspecialchars($old['description']  ?? '');
$old_points  = htmlspecialchars($old['points']       ?? '');
$old_trigger = htmlspecialchars($old['trigger_code'] ?? 'MANUAL');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_edit_user.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_achievement.css?v=<?= time() ?>">
    <title>Create Achievement — Implose.gg Admin</title>
    <meta name="description" content="Create a new achievement with badge image, title, description and points reward.">
</head>


<body class="admin-body">
    <?php
        $current_page = 'admin_achievements';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <!-- ── Page Header ── -->
        <div class="eu-page-header">
            <div class="eu-page-header-left">
                <nav class="eu-breadcrumb" aria-label="Breadcrumb">
                    <a href="/Implose.gg-src/pages/admin/achievement.php">Achievement Management</a>
                    <span class="sep">/</span>
                    <span class="current">Create Achievement</span>
                </nav>
                <h1>Create Achievement</h1>
                <p>Fill in the details below to add a new achievement badge.</p>
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


        <!-- ── Achievement Info Card ── -->
        <div class="eu-card">

            <div class="eu-card-header">
                <img class="eu-card-header-icon" src="/Implose.gg-src/assets/images/icons/nav_achievement.svg" alt="Achievement">
                <span class="eu-card-header-title">New Achievement</span>
            </div>

            <div class="eu-card-divider"></div>

            <div class="eu-card-body">

                <p class="eu-card-desc">Title and points are required. Description and badge image are optional.</p>

                <form method="POST" action="/Implose.gg-src/actions/admin/create_achievement.php" enctype="multipart/form-data">

                    <div class="eu-form-grid">

                        <!-- Title -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-title">
                                Title <span class="req">*</span>
                            </label>
                            <input
                                type="text"
                                id="field-title"
                                name="title"
                                class="eu-input"
                                placeholder="e.g., First Steps"
                                maxlength="255"
                                minlength="2"
                                value="<?= $old_title ?>"
                                required
                                autocomplete="off"
                            >
                        </div>

                        <!-- Points Reward -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-points">
                                Points Reward <span class="req">*</span>
                            </label>
                            <input
                                type="number"
                                id="field-points"
                                name="points_reward"
                                class="eu-input"
                                placeholder="e.g., 50"
                                min="0"
                                max="100000"
                                value="<?= $old_points !== '' ? $old_points : '10' ?>"
                                required
                            >
                        </div>

                        <!-- Unlock Trigger -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label" for="field-trigger">
                                Unlock Trigger <span class="req">*</span>
                            </label>
                            <select id="field-trigger" name="trigger_code" class="eu-select" required>
                                <?php foreach ($trigger_options as $code => $label): ?>
                                    <option value="<?= htmlspecialchars($code) ?>" <?= $old_trigger === $code ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="eu-avatar-hint" style="margin-top:6px;">
                                Choose when this achievement should unlock automatically. Pick "Manual" to award it only by hand.
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label" for="field-description">
                                Description
                            </label>
                            <textarea
                                id="field-description"
                                name="description"
                                class="eu-input"
                                placeholder="Describe what unlocks this achievement..."
                                rows="3"
                                maxlength="1000"><?= $old_desc ?></textarea>
                        </div>

                        <!-- Badge Image -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label">Badge Image (50&times;50 preview)</label>

                            <div class="eu-avatar-row">
                                <div class="ach-badge-preview" id="badge-preview">
                                    <img id="badge-preview-img" src="/Implose.gg-src/assets/images/icons/nav_achievement.svg" alt="Badge preview" class="placeholder">
                                </div>

                                <div class="eu-avatar-controls">
                                    <label class="eu-file-btn" for="badge-file-input">
                                        Choose File
                                        <input type="file" name="badge_icon" id="badge-file-input" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml" style="display:none;">
                                    </label>
                                    <p class="eu-avatar-hint">PNG, JPG, WEBP, SVG &middot; displayed at 50&times;50</p>
                                </div>
                            </div>
                        </div>

                        <script>
                            const badgeFile = document.getElementById('badge-file-input');
                            const badgeImg  = document.getElementById('badge-preview-img');

                            badgeFile.onchange = function () {
                                if (badgeFile.files.length > 0) {
                                    badgeImg.src = URL.createObjectURL(badgeFile.files[0]);
                                    badgeImg.classList.remove('placeholder');
                                }
                            };
                        </script>

                    </div><!-- /.eu-form-grid -->


                    <!-- ── Action Footer ── -->
                    <div class="eu-footer-actions">
                        <a href="/Implose.gg-src/pages/admin/achievement.php" class="btn-cancel">← Back to Achievements</a>
                        <button type="submit" class="btn-save">Create Achievement</button>
                    </div>

                </form>

            </div><!-- /.eu-card-body -->

        </div><!-- /.eu-card -->

    </div><!-- /.admin-main-content -->

</body>
</html>
