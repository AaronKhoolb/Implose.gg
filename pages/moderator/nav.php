<!--
Programmer Name: Mr. Damian Loh Yi Feng
Program Name: /pages/moderator/nav.php
Description: Moderator sidebar navigation
First Written on: Monday, 27-May-2026
Edited on: Monday, 22-Jun-2026
-->

<!-- Moderator Nav CSS -->
<link rel="stylesheet" href="/Implose.gg-src/assets/css/components/moderator_nav.css">

<!-- Moderator Nav JS -->
<script src="/Implose.gg-src/assets/js/moderator/nav.js"></script>

<?php
    // ── Load current moderator profile (for footer) ──
    $moderator_id       = $_SESSION['user_id'] ?? '';
    $moderator_username = 'Moderator';
    $moderator_email    = '';
    $moderator_avatar   = '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';

    $moderator_sql    = "SELECT username, email_address, avatar_path FROM USER_T WHERE user_id = '$moderator_id'";
    $moderator_result = mysqli_query($conn, $moderator_sql);

    if ($moderator_result && mysqli_num_rows($moderator_result) === 1) {
        $moderator = mysqli_fetch_assoc($moderator_result);

        if (!empty($moderator['username']))      $moderator_username = $moderator['username'];
        if (!empty($moderator['email_address'])) $moderator_email    = $moderator['email_address'];
        if (!empty($moderator['avatar_path']))   $moderator_avatar   = '/Implose.gg-src/' . $moderator['avatar_path'];
    }
?>

<nav class="mac-sidebar" id="mac-sidebar">

    <!-- ── Header: Brand Logo + Toggle ── -->
    <div class="mac-sidebar-header">
        <div class="mac-app-text">
            <span class="mac-app-name">IMPLOSE.gg</span>
            <span class="mac-app-role">Moderator</span>
        </div>
        <button id="mac_toggle_btn" onclick="switchMacSidebar()" aria-label="Toggle sidebar">
            <span class="toggle-brand">Implose.gg</span>
            <img class="toggle-icon" src="/Implose.gg-src/assets/images/icons/sidebar.leading.svg" alt="Menu">
        </button>
    </div>

    <!-- ── Navigation ── -->
    <div class="mac-nav">

        <!-- Overview -->
        <div class="mac-nav-group">
            <span class="mac-nav-category">Overview</span>

            <a href="/Implose.gg-src/pages/moderator/index.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'moderator_dashboard' ? 'active' : ''; ?>"
               id="nav-moderator-dashboard">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/dashboard.svg" alt="Dashboard">
                <span class="nav-label">Dashboard</span>
            </a>
        </div>

        <!-- Management -->
        <div class="mac-nav-group">
            <span class="mac-nav-category">Management</span>
            
            <a href="/Implose.gg-src/pages/moderator/achievement.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'moderator_achievements' ? 'active' : ''; ?>"
               id="nav-moderator-achievements">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/achievements.svg" alt="Achievements">
                <span class="nav-label">Achievements</span>
            </a>

            <a href="/Implose.gg-src/pages/moderator/users.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'moderator_users' ? 'active' : ''; ?>"
               id="nav-moderator-users">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/users.svg" alt="Users">
                <span class="nav-label">Users</span>
            </a>

            <a href="#"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'moderator_quiz' ? 'active' : ''; ?>"
               id="nav-moderator-quiz">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="Quiz">
                <span class="nav-label">Quiz</span>
            </a>

            <a href="#"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'moderator_marketplace' ? 'active' : ''; ?>"
               id="nav-moderator-marketplace">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/nav_marketplace.svg" alt="Marketplace">
                <span class="nav-label">Marketplace</span>
            </a>

            <a href="#"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'moderator_boss' ? 'active' : ''; ?>"
               id="nav-moderator-boss">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="Boss">
                <span class="nav-label">Boss</span>
            </a>

            <a href="/Implose.gg-src/pages/moderator/report.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'moderator_report' ? 'active' : ''; ?>"
               id="nav-moderator-report">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/infor.circle.solid.svg" alt="Report">
                <span class="nav-label">Report</span>
            </a>

            <a href="/Implose.gg-src/pages/moderator/feedback.php"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'moderator_feedback' ? 'active' : ''; ?>"
               id="nav-moderator-feedback">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/chat-error.svg" alt="Feedback">
                <span class="nav-label">Feedback</span>
            </a>
        </div>

        <!-- System -->
        <div class="mac-nav-group">
            <span class="mac-nav-category">System</span>

            <a href="#"
               class="mac-nav-link <?php echo ($current_page ?? '') === 'moderator_activitylogs' ? 'active' : ''; ?>"
               id="nav-moderator-activitylogs">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/activity.svg" alt="Activity Logs">
                <span class="nav-label">Activity Logs</span>
            </a>
        </div>

    </div>

    <!-- ── Footer: Profile + Logout ── -->
    <div class="mac-sidebar-footer">
        <div class="mac-profile-card">
            <a href="/Implose.gg-src/pages/moderator/edit_profile.php" class="mac-profile-link <?php echo ($current_page ?? '') === 'moderator_edit_profile' ? 'active' : ''; ?>" id="moderator-profile-btn">
                <span class="mac-profile-avatar">
                    <img src="<?php echo htmlspecialchars($moderator_avatar); ?>" alt="Moderator Avatar">
                </span>
                <span class="mac-profile-info">
                    <span class="mac-profile-name"><?php echo htmlspecialchars($moderator_username); ?></span>
                    <span class="mac-profile-email"><?php echo htmlspecialchars($moderator_email); ?></span>
                </span>
            </a>
            <a href="/Implose.gg-src/actions/auth/sign_out.php" class="mac-logout-btn" id="moderator-logout-btn" aria-label="Logout" title="Logout">
                <img class="nav-icon" src="/Implose.gg-src/assets/images/icons/logout.svg" alt="Logout">
                <span class="nav-label">Logout</span>
            </a>
        </div>
    </div>

</nav>
