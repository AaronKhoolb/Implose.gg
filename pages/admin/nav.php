<!--
Programmer Name: Mr. Chong Jun Yoong, Mr. Khoo Lay Bin
Program Name: /pages/admin/nav.php
Description: Admin sidebar navigation
First Written on: Monday, 27-May-2026
Edited on: Wednesday, 02-Jul-2026
-->

<!-- Admin Nav CSS -->
<link rel="stylesheet" href="/Implose.gg-src/assets/css/components/admin_nav.css">

<!-- Admin Nav JS -->
<script src="/Implose.gg-src/assets/js/admin/nav.js"></script>

<?php
    // Load admin profile for the footer
    $admin_id       = $_SESSION['user_id'];
    $admin_username = 'Admin';
    $admin_email    = '';
    $admin_avatar   = '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';

    $admin_sql    = "SELECT username, email_address, avatar_path FROM USER_T WHERE user_id = $admin_id";
    $admin_result = mysqli_query($conn, $admin_sql);

    if ($admin_result && mysqli_num_rows($admin_result) === 1) {
        $admin = mysqli_fetch_assoc($admin_result);

        if (!empty($admin['username']))      $admin_username = $admin['username'];
        if (!empty($admin['email_address'])) $admin_email    = $admin['email_address'];
        if (!empty($admin['avatar_path']))   $admin_avatar   = '/Implose.gg-src/' . $admin['avatar_path'];
    }
?>

<nav class="mac-sidebar" id="mac-sidebar">

    <!-- Header: Brand Logo + Toggle -->
    <div class="mac-sidebar-header">
        <div class="mac-app-text">
            <span class="mac-app-name">IMPLOSE.gg</span>
            <span class="mac-app-role">System Administrator</span>
        </div>
        <button id="mac_toggle_btn" onclick="switchMacSidebar()" aria-label="Toggle sidebar">
            <span class="toggle-brand">Implose.gg</span>
            <img class="toggle-icon" src="/Implose.gg-src/assets/images/icons/sidebar.leading.svg" alt="Menu">
        </button>
    </div>

    <!-- Navigation -->
    <div class="mac-nav">

        <!-- Overview -->
        <div class="mac-nav-group">
            <span class="mac-nav-category">Overview</span>

            <a href="/Implose.gg-src/pages/admin/index.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_dashboard' ? 'active' : ''; ?>"
               id="nav-admin-dashboard">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/dashboard.svg" alt="Dashboard">
                <span class="nav-label">Dashboard</span>
            </a>
        </div>

        <!-- Management -->
        <div class="mac-nav-group">
            <span class="mac-nav-category">Management</span>
            
            <a href="/Implose.gg-src/pages/admin/achievement.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_achievements' ? 'active' : ''; ?>"
               id="nav-admin-achievements">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/achievements.svg" alt="Achievements">
                <span class="nav-label">Achievements</span>
            </a>

            <a href="/Implose.gg-src/pages/admin/rewards.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_rewards' ? 'active' : ''; ?>"
               id="nav-admin-rewards">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/nav_reward.svg" alt="Rewards">
                <span class="nav-label">Rewards</span>
            </a>

            <a href="/Implose.gg-src/pages/admin/users.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_users' ? 'active' : ''; ?>"
               id="nav-admin-users">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/users.svg" alt="Users">
                <span class="nav-label">Users</span>
            </a>

            <a href="/Implose.gg-src/pages/admin/learning_analytics.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_learning_analytics' ? 'active' : ''; ?>"
               id="nav-admin-learning-analytics">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/activity.svg" alt="Learning Analytics">
                <span class="nav-label">Learning Analytics</span>
            </a>

            <a href="/Implose.gg-src/pages/admin/report.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_report' ? 'active' : ''; ?>"
               id="nav-admin-report">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/infor.circle.solid.svg" alt="Report">
                <span class="nav-label">Report</span>
            </a>

            <a href="/Implose.gg-src/pages/admin/feedback.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_feedback' ? 'active' : ''; ?>"
               id="nav-admin-feedback">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/chat-error.svg" alt="Feedback">
                <span class="nav-label">Feedback</span>
            </a>
        </div>

        <!-- System -->
        <div class="mac-nav-group">
            <span class="mac-nav-category">System</span>

            <a href="/Implose.gg-src/pages/admin/logs.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_activitylogs' ? 'active' : ''; ?>"
               id="nav-admin-activitylogs">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/list-box.svg" alt="Logs">
                <span class="nav-label">Logs</span>
            </a>

            <a href="/Implose.gg-src/pages/admin/file_management.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_filemanagement' ? 'active' : ''; ?>"
               id="nav-admin-filemanagement">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/folders.svg" alt="File Management">
                <span class="nav-label">File Management</span>
            </a>

            <a href="/Implose.gg-src/pages/admin/ai_settings.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'admin_aisettings' ? 'active' : ''; ?>"
               id="nav-admin-aisettings">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/ai-cpu.svg" alt="AI Settings">
                <span class="nav-label">AI Settings</span>
            </a>
        </div>

    </div>

    <!-- Footer: Profile + Logout -->
    <div class="mac-sidebar-footer">
        <div class="mac-profile-card">
            <a href="#" class="mac-profile-link" id="admin-profile-btn">
                <span class="mac-profile-avatar">
                    <img src="<?php echo htmlspecialchars($admin_avatar); ?>" alt="Admin Avatar">
                </span>
                <span class="mac-profile-info">
                    <span class="mac-profile-name"><?php echo htmlspecialchars($admin_username); ?></span>
                    <span class="mac-profile-email"><?php echo htmlspecialchars($admin_email); ?></span>
                </span>
            </a>
            <a href="/Implose.gg-src/actions/auth/sign_out.php" class="mac-logout-btn" id="admin-logout-btn" aria-label="Logout" title="Logout">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/logout.svg" alt="Logout">
                <span class="nav-label">Logout</span>
            </a>
        </div>
    </div>

</nav>
