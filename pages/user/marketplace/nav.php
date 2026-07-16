<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/marketplace/nav.php
Description: marketplace left nav (included in discover, mine, forks, view)
First Written on: Wednesday, 25-Jun-2026
Edited on: Monday, 29-Jun-2026
-->

<?php
    $user_id = $_SESSION['user_id'];

    $count_discover_sql = "SELECT COUNT(*) AS total_count FROM MARKETPLACE_COURSE_T WHERE is_deleted = 0";
    $count_discover_result = mysqli_query($conn, $count_discover_sql);
    $count_discover = mysqli_fetch_assoc($count_discover_result)['total_count'];

    $count_mine_sql = "SELECT COUNT(*) AS mine_count FROM MARKETPLACE_COURSE_T WHERE creator_id = '$user_id' AND is_deleted = 0";
    $count_mine_result = mysqli_query($conn, $count_mine_sql);
    $count_mine = mysqli_fetch_assoc($count_mine_result)['mine_count'];

    $count_forks_sql = "SELECT COUNT(*) AS forks_count FROM COURSE_T WHERE creator_id = '$user_id' AND forked_from IS NOT NULL";
    $count_forks_result = mysqli_query($conn, $count_forks_sql);
    $count_forks = mysqli_fetch_assoc($count_forks_result)['forks_count'];
?>


<div class="mkt-left-nav pixel-panel">
    <ul>
        <li>
            <div class="group-label">BROWSE</div>
        </li>

        <li>
            <a href="/Implose.gg-src/pages/user/marketplace/index.php?sort=newest" class="<?php if ($current_page === 'discover') { echo 'active'; } ?>">
                <span>Discover</span>
                <span class="pill"><?php echo $count_discover; ?></span>
            </a>
        </li>



        <li>
            <div class="group-label">YOUR LIBRARY</div>
        </li>

        <li>
            <a href="/Implose.gg-src/pages/user/marketplace/my_publish.php" class="<?php if ($current_page === 'mine') { echo 'active'; } ?>">
                <span>My Published</span>
                <span class="pill"><?php echo $count_mine; ?></span>
            </a>
        </li>

        <li>
            <a href="/Implose.gg-src/pages/user/marketplace/my_forks.php" class="<?php if ($current_page === 'my_forks') { echo 'active'; } ?>">
                <span>My Forks</span>
                <span class="pill"><?php echo $count_forks; ?></span>
            </a>
        </li>

        <li>
            <div class="divider"></div>
        </li>
    </ul>

    <a href="/Implose.gg-src/pages/user/marketplace/publish_course.php" class="publish-li btn-red">
        + Publish Course
    </a>
</div>
