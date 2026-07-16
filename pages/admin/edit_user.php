<!--
Programmer Name: Chong Jun Yoong
Program Name: /pages/admin/edit_user.php
Description: Admin edit user page
First Written on: Thursday, 4-Jun-2026
Edited on: Thursday, 18-Jun-2026
-->
<?php
    session_start();
    include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

    $edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($edit_id <= 0) {
        header('Location: /Implose.gg-src/pages/admin/users.php');
        exit();
    }

    $edit_sql = "SELECT * FROM USER_T WHERE user_id = '$edit_id'";
    $edit_result = mysqli_query($conn, $edit_sql);

    if (!$edit_result || mysqli_num_rows($edit_result) != 1) {
        header('Location: /Implose.gg-src/pages/admin/users.php');
        exit();
    }
    $target_user = mysqli_fetch_assoc($edit_result);

    // empty avatar path falls back to initials
    $avatar_url = '';
    if (!empty($target_user['avatar_path'])) {
        $avatar_url = '/Implose.gg-src/' . $target_user['avatar_path'];
    }

    $avatar_colors = ['#6366f1','#8b5cf6','#f59e0b','#10b981','#ef4444','#f97316','#ec4899','#14b8a6'];
    $avatar_color = $avatar_colors[$edit_id % count($avatar_colors)];
    $initials = strtoupper(substr($target_user['username'] ?? 'U', 0, 2));


    $success_msg = '';
    $error_msg = '';

    if (isset($_SESSION['edit_user_success'])) {
        $success_msg = $_SESSION['edit_user_success'];
        unset($_SESSION['edit_user_success']);
    }
    if (isset($_SESSION['edit_user_error'])) {
        $error_msg = $_SESSION['edit_user_error'];
        unset($_SESSION['edit_user_error']);
    }

    $display_name = $target_user['username'] ?? '(No username)';
    $is_active = ($target_user['account_status'] == 'active');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_edit_user.css">
    <title>Edit User — Implose.gg Admin</title>
</head>


<body class="admin-body">

    <?php
        $current_page = 'admin_users';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <!-- Page Header -->
        <div class="eu-page-header">
            <div class="eu-page-header-left">
                <nav class="eu-breadcrumb" aria-label="Breadcrumb">
                    <a href="/Implose.gg-src/pages/admin/users.php">Users Management</a><span class="sep">/</span><span class="current">Edit — <?php echo htmlspecialchars($display_name); ?></span>
                </nav>

                <h1>Edit — <?php echo htmlspecialchars($display_name); ?></h1>
                <p>Update the user's profile information and account settings.</p>
            </div>
        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/admin_toast.php'); ?>

        <!-- Main Card -->
        <div class="eu-card">

            <div class="eu-card-header"><img class="eu-card-header-icon" src="/Implose.gg-src/assets/images/icons/users.svg" alt="User"><span class="eu-card-header-title">User Information</span></div>
            <div class="eu-card-divider"></div>

            <div class="eu-card-body">
                <p class="eu-card-desc">Changes made here will be reflected on the user's account immediately after saving.</p>

                <form method="POST" action="/Implose.gg-src/actions/admin/update_user.php" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo $edit_id; ?>">

                    <div class="eu-form-grid">

                        <!-- Username -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-username">Username <span class="req">*</span></label>
                            <input type="text" id="field-username" name="username" class="eu-input" placeholder="e.g., JohnNg67" maxlength="32" minlength="2" required autocomplete="off" value="<?php echo htmlspecialchars($target_user['username'] ?? ''); ?>">
                        </div>

                        <!-- Email -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-email">Email Address <span class="req">*</span></label>
                            <input type="email" id="field-email" name="email" class="eu-input" placeholder="e.g., user@example.com" required autocomplete="off" value="<?php echo htmlspecialchars($target_user['email_address'] ?? ''); ?>">
                        </div>

                        <!-- Role -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-role">Role</label>
                            <select id="field-role" name="role" class="eu-select">
                                <option value="user" <?php if ($target_user['role'] == 'user') echo 'selected'; ?>>User</option>
                                <option value="moderator" <?php if ($target_user['role'] == 'moderator') echo 'selected'; ?>>Moderator</option>
                                <option value="admin" <?php if ($target_user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </div>

                        <!-- Joined date (readonly) -->
                        <div class="eu-form-field">
                            <label class="eu-label">Joined</label>
                            <input type="text" id="field-joined" class="eu-input" disabled value="<?php echo date('j M Y', strtotime($target_user['created_at'])); ?>">
                        </div>

                        <!-- Account status toggle -->
                        <div class="eu-form-field">
                            <label class="eu-label">Account Status <span class="req">*</span></label>

                            <div class="eu-status-row">
                                <div class="eu-status-info"><span class="eu-status-title">Account Active</span><span class="eu-status-desc">Allow this user to sign in to Implose.gg</span></div>

                                <div class="eu-status-right"><span id="status-badge" class="eu-status-live-badge <?php echo $is_active ? 'active' : 'suspended'; ?>"><?php echo $is_active ? 'Active' : 'Suspended'; ?></span><label id="status-toggle-label" class="eu-toggle <?php echo $is_active ? '' : 'suspended'; ?>" title="Toggle account status"><input type="checkbox" name="status_active" id="status-checkbox" <?php echo $is_active ? 'checked' : ''; ?>><span class="eu-toggle-track"></span></label></div>
                            </div>
                        </div>


                        <!-- profile pic -->
                        <div class="eu-form-field">
                            <label class="eu-label">Profile Picture</label>

                            <div class="eu-avatar-row">
                                <div class="eu-avatar-preview" id="avatar-preview" style="<?php if (!$avatar_url) echo 'background:' . $avatar_color; ?>">
                                    <?php if ($avatar_url) { ?>
                                        <img id="avatar-preview-img" src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar preview">
                                    <?php } else { ?>
                                        <span id="avatar-initials"><?php echo $initials; ?></span><img id="avatar-preview-img" src="" alt="Avatar preview" style="display:none;">
                                    <?php } ?>
                                </div>

                                <div class="eu-avatar-controls">
                                    <label class="eu-file-btn" for="avatar-file-input">Choose File<input type="file" name="avatar" id="avatar-file-input" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none;"></label>
                                    <p class="eu-avatar-hint">PNG, JPG, WEBP</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="eu-footer-actions">
                        <a href="/Implose.gg-src/pages/admin/users.php" class="btn-cancel">← Back to Users</a>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        document.getElementById('status-checkbox').onchange = function () {
            var badge = document.getElementById('status-badge');
            var label = document.getElementById('status-toggle-label');

            if (this.checked) {
                badge.textContent = 'Active';
                badge.className = 'eu-status-live-badge active';
                label.className = 'eu-toggle';
            } else {
                badge.textContent = 'Suspended';
                badge.className = 'eu-status-live-badge suspended';
                label.className = 'eu-toggle suspended';
            }
        };


        // preview avatar when user picks a new file
        const avatarFile = document.getElementById('avatar-file-input');
        const avatarImg = document.getElementById('avatar-preview-img');
        const avatarInitials = document.getElementById('avatar-initials');
        const avatarPreview = document.getElementById('avatar-preview');

        avatarFile.onchange = function () {
            if (avatarFile.files.length > 0) {
                if (avatarInitials) avatarInitials.style.display = 'none';
                avatarPreview.style.background = '';
                avatarImg.style.display = 'block';
                avatarImg.src = URL.createObjectURL(avatarFile.files[0]);
            }
        };
    </script>

</body>
</html>
