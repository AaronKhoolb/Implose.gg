<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/nav.php
Description: User top nav
First Written on: Tuesday, 26-May-2026
Edited on: Friday, 19-Jun-2026
-->

<link rel="stylesheet" href="/Implose.gg-src/assets/css/components/user_nav.css">

<?php
    $user_id = $_SESSION['user_id'];
    $user_avatar = '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';

    $user_sql = "SELECT username, avatar_path, total_points, streak_count FROM USER_T WHERE user_id = '$user_id'";
    $user_result = mysqli_query($conn, $user_sql);

    if ($user_result && mysqli_num_rows($user_result) === 1) {
        $user = mysqli_fetch_assoc($user_result);

        $user_avatar = '/Implose.gg-src/' . $user['avatar_path'];

        $username = $user['username'];
        $total_points = $user['total_points'];
        $streak_count = $user['streak_count'] ?? 0;
    }
?>

<nav class="top-nav">
    <div class="top-nav-inner">
        <!-- nav button -->
        <div class="nav-section">
            <a href="/Implose.gg-src/pages/user/index.php" class="nav-btn <?php if ($current_page === 'user_homepage') { echo "btn-pixel"; } ?>">
                <img src="/Implose.gg-src/assets/images/icons/nav_home.svg" alt="home">
                <span>Home</span>
            </a>

            <a href="/Implose.gg-src/pages/user/game/view_course.php" class="nav-btn <?php if ($current_page === 'user_course') { echo "btn-pixel"; } ?>">
                <img src="/Implose.gg-src/assets/images/icons/nav_course.svg" alt="course">
                <span>Course</span>
            </a>

            <a href="/Implose.gg-src/pages/user/marketplace/index.php?sort=newest" class="nav-btn <?php if ($current_page === 'user_marketplace') { echo "btn-pixel"; } ?>">
                <img src="/Implose.gg-src/assets/images/icons/nav_marketplace.svg" alt="marketplace">
                <span>Marketplace</span>
            </a>

            <a href="/Implose.gg-src/pages/user/achievement/achievement.php" class="nav-btn <?php if ($current_page === 'user_achievement') { echo "btn-pixel"; } ?>">
                <img src="/Implose.gg-src/assets/images/icons/nav_achievement.svg" alt="achievement">
                <span>Achievement</span>
            </a>

            <a href="/Implose.gg-src/pages/user/leaderboard.php" class="nav-btn <?php if ($current_page === 'user_leaderboard') { echo "btn-pixel"; } ?>">
                <img src="/Implose.gg-src/assets/images/icons/nav_leaderboard.svg" alt="leaderboard">
                <span>Leaderboard</span>
            </a>
        </div>

        <div class="nav-right">
            <!-- Profile -->
            <a href="/Implose.gg-src/pages/user/account/index.php" class="profile btn-pixel">
                <div class="nav-avatar">
                    <img src="<?php echo $user_avatar; ?>" alt="profile avatar">
                </div>

                <span class="nav-username">
                    <?php echo $username; ?>
                </span>
            </a>

            <!-- Shop coins -->
            <a href="/Implose.gg-src/pages/user/rewards.php" class="shop-btn btn-pixel">
                <img src="/Implose.gg-src/assets/images/icons/nav_coin.svg" alt="coins">
                <span><?php echo $total_points; ?></span>
            </a>
        </div>

    </div>
</nav>

<?php
    // Auto-render any pending achievement-unlock popups for this user.
    include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/achievement_popup.php');

    // Auto-render any pending post-quiz feedback popup.
    include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/feedback_popup.php');
?>
