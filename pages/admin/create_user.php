<!--
Programmer Name: Chong Jun Yoong
Program Name: /pages/admin/create_user.php
Description: Admin create user page
First Written on: Tuesday, 16-Jun-2026
Edited on: Thursday, 18-Jun-2026
-->
<?php
    session_start();

    $success_msg = '';
    $error_msg = '';

    if (isset($_SESSION['create_user_success'])) {
        $success_msg = $_SESSION['create_user_success'];
        unset($_SESSION['create_user_success']);
    }
    if (isset($_SESSION['create_user_error'])) {
        $error_msg = $_SESSION['create_user_error'];
        unset($_SESSION['create_user_error']);
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_edit_user.css">
    <title>Create User — Implose.gg Admin</title>
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
                    <a href="/Implose.gg-src/pages/admin/users.php">Users Management</a><span class="sep">/</span><span class="current">Create User</span>
                </nav>

                <h1>Create User</h1>
                <p>Fill in all the fields below to create a new user account.</p>
            </div>
        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/admin_toast.php'); ?>

        <!-- Main Card -->
        <div class="eu-card">

            <div class="eu-card-header"><img class="eu-card-header-icon" src="/Implose.gg-src/assets/images/icons/users.svg" alt="User"><span class="eu-card-header-title">New User Information</span></div>
            <div class="eu-card-divider"></div>

            <div class="eu-card-body">
                <p class="eu-card-desc">All fields are required. The user will be able to sign in immediately after creation.</p>

                <form method="POST" action="/Implose.gg-src/actions/admin/create_user.php" enctype="multipart/form-data">
                    <div class="eu-form-grid">

                        <!-- Username -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-username">Username <span class="req">*</span></label>
                            <input type="text" id="field-username" name="username" class="eu-input" placeholder="e.g., JohnNg67" maxlength="32" minlength="2" required autocomplete="off">
                        </div>

                        <!-- Email -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-email">Email Address <span class="req">*</span></label>
                            <input type="email" id="field-email" name="email" class="eu-input" placeholder="e.g., user@example.com" required autocomplete="off">
                        </div>

                        <!-- Password -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-password">Password <span class="req">*</span></label>
                            <input type="password" id="field-password" name="password" class="eu-input" placeholder="Minimum 6 characters" minlength="6" required autocomplete="new-password">
                        </div>

                        <!-- Confirm Password -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-confirm-password">Confirm Password <span class="req">*</span></label>
                            <input type="password" id="field-confirm-password" name="confirm_password" class="eu-input" placeholder="Re-enter password" minlength="6" required autocomplete="new-password">
                        </div>

                        <!-- Role -->
                        <div class="eu-form-field">
                            <label class="eu-label" for="field-role">Role <span class="req">*</span></label>
                            <select id="field-role" name="role" class="eu-select" required>
                                <option value="user" selected>User</option>
                                <option value="moderator">Moderator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <!-- Avatar upload -->
                        <div class="eu-form-field eu-col-full">
                            <label class="eu-label">Profile Picture</label>

                            <div class="eu-avatar-row">
                                <div class="eu-avatar-preview eu-avatar-transparent" id="avatar-preview"><span id="avatar-initials" class="eu-hidden">?</span><img id="avatar-preview-img" src="/Implose.gg-src/assets/images/avatar_test/avatar_robot.png" alt="Avatar preview"></div>

                                <div class="eu-avatar-controls">
                                    <label class="eu-file-btn" for="avatar-file-input">Choose File<input type="file" name="avatar" id="avatar-file-input" class="eu-hidden" accept="image/png,image/jpeg,image/webp,image/gif"></label>
                                    <p class="eu-avatar-hint">PNG, JPG, WEBP</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="eu-footer-actions">
                        <a href="/Implose.gg-src/pages/admin/users.php" class="btn-cancel">← Back to Users</a>
                        <button type="submit" class="btn-save">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // preview avatar when user picks a file
        const avatarFile = document.getElementById('avatar-file-input');
        const avatarImg = document.getElementById('avatar-preview-img');
        const avatarInitials = document.getElementById('avatar-initials');
        const avatarPreview = document.getElementById('avatar-preview');

        avatarFile.onchange = function () {
            if (avatarFile.files.length > 0) {
                if (avatarInitials) avatarInitials.classList.add('eu-hidden');
                avatarPreview.classList.remove('eu-avatar-transparent');
                avatarImg.src = URL.createObjectURL(avatarFile.files[0]);
                avatarImg.classList.remove('eu-hidden');
            }
        };
    </script>

</body>
</html>
