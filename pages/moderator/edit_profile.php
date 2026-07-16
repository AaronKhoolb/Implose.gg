<!--
Programmer Name: Mr. Damian Loh Yi Feng
Program Name: /pages/moderator/edit_profile.php
Description: Moderator Edit Profile Page
            - Load the currently signed-in moderator's data
            - Edit username, age, gender, avatar
            - Email is shown read-only (identity)
            - Submits to /actions/moderator/update_profile.php
First Written on: Wednesday, 01-Jul-2026
Edited on: Wednesday, 01-Jul-2026
-->
<?php
    session_start();
    include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

    $moderator_id = $_SESSION['user_id'] ?? 0;

    $me_sql    = "SELECT * FROM USER_T WHERE user_id = '$moderator_id'";
    $me_result = mysqli_query($conn, $me_sql);

    if (!$me_result || mysqli_num_rows($me_result) !== 1) {
        header('Location: /Implose.gg-src/pages/moderator/index.php');
        exit();
    }
    $me = mysqli_fetch_assoc($me_result);

    $avatar_url = !empty($me['avatar_path'])
        ? '/Implose.gg-src/' . $me['avatar_path']
        : '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';

    $avatar_colors = ['#6366f1','#8b5cf6','#f59e0b','#10b981','#ef4444','#f97316','#ec4899','#14b8a6'];
    $avatar_color  = $avatar_colors[$moderator_id % count($avatar_colors)];
    $initials      = strtoupper(substr($me['username'] ?? 'M', 0, 2));

    $display_name = $me['username'] ?? '(No username)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_edit_user.css">
    <title>Edit Profile — Implose.gg Moderator</title>
</head>

<body class="admin-body">
    <?php
        $current_page = 'moderator_edit_profile';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/moderator/nav.php');
    ?>

    <div class="admin-main-content">
        <div class="eu-page-header">
            <div class="eu-page-header-left">
                <nav class="eu-breadcrumb" aria-label="Breadcrumb">
                    <a href="/Implose.gg-src/pages/moderator/index.php">Dashboard</a><span class="sep">/</span><span class="current">Edit Profile</span>
                </nav>
                <h1>Edit Profile — <?php echo htmlspecialchars($display_name); ?></h1>
                <p>Update your moderator profile information.</p>
            </div>
        </div>

        <div class="eu-card">
            <div class="eu-card-header">
                <img class="eu-card-header-icon" src="/Implose.gg-src/assets/images/icons/pencil.svg" alt="Edit">
                <span class="eu-card-header-title">Profile Information</span>
            </div>
            <div class="eu-card-divider"></div>

            <div class="eu-card-body">
                <p class="eu-card-desc">Changes will be reflected on your account immediately after saving.</p>

                <form method="POST" action="/Implose.gg-src/actions/moderator/update_profile.php" enctype="multipart/form-data">

                    <div class="eu-form-grid">

                        <div class="eu-form-field">
                            <label class="eu-label" for="field-username">Username <span class="req">*</span></label>
                            <input type="text" id="field-username" name="username" class="eu-input" placeholder="e.g., ModMax" maxlength="32" minlength="2" required autocomplete="off" value="<?php echo htmlspecialchars($me['username'] ?? ''); ?>">
                        </div>

                        <div class="eu-form-field">
                            <label class="eu-label" for="field-email">Email Address</label>
                            <input type="email" id="field-email" class="eu-input" disabled value="<?php echo htmlspecialchars($me['email_address'] ?? ''); ?>">
                        </div>

                        <div class="eu-form-field">
                            <label class="eu-label" for="field-age">Age <span class="req">*</span></label>
                            <input type="number" id="field-age" name="age" class="eu-input" placeholder="Enter your age" min="1" max="120" required value="<?php echo htmlspecialchars($me['age'] ?? ''); ?>">
                        </div>

                        <div class="eu-form-field">
                            <label class="eu-label" for="field-gender">Gender <span class="req">*</span></label>
                            <select id="field-gender" name="gender" class="eu-select" required>
                                <option value="male"   <?php if (($me['gender'] ?? '') === 'male')   echo 'selected'; ?>>Male</option>
                                <option value="female" <?php if (($me['gender'] ?? '') === 'female') echo 'selected'; ?>>Female</option>
                            </select>
                        </div>

                        <div class="eu-form-field">
                            <label class="eu-label">Joined</label>
                            <input type="text" class="eu-input" disabled value="<?php echo date('j M Y', strtotime($me['created_at'])); ?>">
                        </div>

                        <div class="eu-form-field">
                            <label class="eu-label">Profile Picture</label>
                            <div class="eu-avatar-row">
                                <div class="eu-avatar-preview" id="avatar-preview" style="<?php echo !empty($me['avatar_path']) ? '' : 'background:' . $avatar_color; ?>">
                                    <?php if (!empty($me['avatar_path'])): ?>
                                        <img id="avatar-preview-img" src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar preview">
                                    <?php else: ?>
                                        <span id="avatar-initials"><?php echo $initials; ?></span>
                                        <img id="avatar-preview-img" src="" alt="Avatar preview" style="display:none;">
                                    <?php endif; ?>
                                </div>
                                <div class="eu-avatar-controls">
                                    <label class="eu-file-btn" for="avatar-file-input">
                                        Choose File
                                        <input type="file" name="avatar_file" id="avatar-file-input" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none;">
                                    </label>
                                    <p class="eu-avatar-hint">PNG, JPG, WEBP, GIF</p>
                                </div>
                            </div>
                        </div>

                        <script>
                            const avatarFile     = document.getElementById('avatar-file-input');
                            const avatarImg      = document.getElementById('avatar-preview-img');
                            const avatarInitials = document.getElementById('avatar-initials');
                            const avatarPreview  = document.getElementById('avatar-preview');
                            avatarFile.onchange = function () {
                                if (avatarFile.files.length > 0) {
                                    if (avatarInitials) avatarInitials.style.display = 'none';
                                    avatarPreview.style.background = '';
                                    avatarImg.style.display = '';
                                    avatarImg.src = URL.createObjectURL(avatarFile.files[0]);
                                }
                            };
                        </script>
                    </div>

                    <div class="eu-footer-actions">
                        <a href="/Implose.gg-src/pages/moderator/index.php" class="btn-cancel">← Back to Dashboard</a>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
