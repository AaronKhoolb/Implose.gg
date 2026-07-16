<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /pages/admin/edit_achievement.php
Description: Admin Edit Achievement Page
            - Load achievement by ?id= param
            - Edit title, description, badge image, points
            - Form submits to /actions/admin/update_achievement.php
First Written on: Saturday, 27-Jun-2026
Edited on: Saturday, 27-Jun-2026
-->

<?php
session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement.php');
$trigger_options = achievement_trigger_options();

// get achievement id
$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($edit_id <= 0) {
    header('Location: /Implose.gg-src/pages/admin/achievement.php');
    exit();
}

// fetch achievement
$edit_sql = "SELECT * FROM ACHIEVEMENT_T WHERE achievement_id = '$edit_id'";
$edit_result = mysqli_query($conn, $edit_sql);

if (!$edit_result || mysqli_num_rows($edit_result) !== 1) {
    $_SESSION['achievement_error'] = 'Achievement not found.';
    header('Location: /Implose.gg-src/pages/admin/achievement.php');
    exit();
}

$target = mysqli_fetch_assoc($edit_result);

// count how many users have unlocked it (shown to admin as context)
$unlock_sql = "SELECT COUNT(DISTINCT user_id) AS c FROM USER_ACHIEVEMENT_T WHERE achievement_id = '$edit_id'";
$unlock_res = mysqli_query($conn, $unlock_sql);
$unlock_row = $unlock_res ? mysqli_fetch_assoc($unlock_res) : ['c' => 0];
$unlock_count = (int)($unlock_row['c'] ?? 0);

// badge URL
$badge_url = !empty($target['badge_icon_path'])
    ? '/Implose.gg-src/' . $target['badge_icon_path']
    : '/Implose.gg-src/assets/images/icons/nav_achievement.svg';
$has_badge = !empty($target['badge_icon_path']);

// flash messages
$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['edit_achievement_success'])) {
    $success_msg = $_SESSION['edit_achievement_success'];
    unset($_SESSION['edit_achievement_success']);
}

if (isset($_SESSION['edit_achievement_error'])) {
    $error_msg = $_SESSION['edit_achievement_error'];
    unset($_SESSION['edit_achievement_error']);
}

$display_title = $target['title'] ?? '(Untitled)';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_edit_user.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_achievement.css?v=<?= time() ?>">
    <title>Edit Achievement — Implose.gg Admin</title>
    <meta name="description" content="Edit an achievement's badge image, title, description and points reward.">
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
                    <span class="current">Edit — <?= htmlspecialchars($display_title) ?></span>
                </nav>
                <h1>Edit — <?= htmlspecialchars($display_title) ?></h1>
                <p>Update this achievement's details. <?= $unlock_count ?> user<?= $unlock_count === 1 ? '' : 's' ?> already unlocked it.</p>
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
                <span class="eu-card-header-title">Achievement Details</span>
            </div>

            <div class="eu-card-divider"></div>

            <div class="eu-card-body">

                <form method="POST" action="/Implose.gg-src/actions/admin/update_achievement.php" enctype="multipart/form-data">

                    <input type="hidden" name="achievement_id" value="<?= (int)$target['achievement_id'] ?>">

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
                                value="<?= htmlspecialchars($target['title']) ?>"
                                maxlength="255"
                                minlength="2"
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
                                value="<?= (int)$target['points_reward'] ?>"
                                min="0"
                                max="100000"
                                required
                            >
                        </div>

                        <!-- Unlock Trigger -->
                        <?php
                            $current_trigger = $target['trigger_code'] ?? 'MANUAL';
                            // unknown / legacy values fall back to MANUAL so the dropdown stays valid
                            if (!array_key_exists($current_trigger, $trigger_options)) {
                                $current_trigger = 'MANUAL';
                            }
                        ?>
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label" for="field-trigger">
                                Unlock Trigger <span class="req">*</span>
                            </label>
                            <select id="field-trigger" name="trigger_code" class="eu-select" required>
                                <?php foreach ($trigger_options as $code => $label): ?>
                                    <option value="<?= htmlspecialchars($code) ?>" <?= $current_trigger === $code ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="eu-avatar-hint" style="margin-top:6px;">
                                Changing the trigger only affects future unlocks. Users who already unlocked this achievement keep it.
                            </p>
                        </div>

                        <!-- Description -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label" for="field-description">Description</label>
                            <textarea
                                id="field-description"
                                name="description"
                                class="eu-input"
                                rows="3"
                                maxlength="1000"
                                placeholder="Describe what unlocks this achievement..."><?= htmlspecialchars($target['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Badge Image -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label">Badge Image (50&times;50 preview)</label>

                            <div class="eu-avatar-row">
                                <div class="ach-badge-preview" id="badge-preview">
                                    <img id="badge-preview-img"
                                         src="<?= htmlspecialchars($badge_url) ?>"
                                         alt="Badge preview"
                                         class="<?= $has_badge ? '' : 'placeholder' ?>">
                                </div>

                                <div class="eu-avatar-controls">
                                    <label class="eu-file-btn" for="badge-file-input">
                                        <?= $has_badge ? 'Replace File' : 'Choose File' ?>
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
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>

                </form>

            </div><!-- /.eu-card-body -->

        </div><!-- /.eu-card -->

    </div><!-- /.admin-main-content -->

</body>
</html>
