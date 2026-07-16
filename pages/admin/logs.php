<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/admin/logs.php
Description: admin system logs
First Written on: Tuesday, 30-Jun-2026
Edited on: Friday, 3-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

    // get filters from url
    if (isset($_GET['search'])) {
        $search = trim($_GET['search']);
    } else {
        $search = '';
    }

    if (isset($_GET['action'])) {
        $action = $_GET['action'];
    } else {
        $action = '';
    }

    if (isset($_GET['from'])) {
        $from = $_GET['from'];
    } else {
        $from = '';
    }

    if (isset($_GET['to'])) {
        $to = $_GET['to'];
    } else {
        $to = '';
    }

    // build where sql query
    $where = "WHERE 1=1";

    if ($search != '') {
        $where = $where . " AND (description LIKE '%$search%' OR action_type LIKE '%$search%')";
    }
    if ($action != '') {
        $where = $where . " AND action_type = '$action'";
    }
    if ($from != '') {
        $where = $where . " AND created_at >= '$from 00:00:00'";
    }
    if ($to != '') {
        $where = $where . " AND created_at <= '$to 23:59:59'";
    }


    // total events
    $total_sql = "SELECT COUNT(*) AS total FROM SYSTEM_LOG_T";
    $total_result = mysqli_query($conn, $total_sql);
    $total = mysqli_fetch_assoc($total_result)['total'];

    // events today
    $today_sql = "SELECT COUNT(*) AS total FROM SYSTEM_LOG_T WHERE DATE(created_at) = CURDATE()";
    $today_result = mysqli_query($conn, $today_sql);
    $today = mysqli_fetch_assoc($today_result)['total'];

    // events this week
    $week_sql = "SELECT COUNT(*) AS total FROM SYSTEM_LOG_T WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $week_result = mysqli_query($conn, $week_sql);
    $week = mysqli_fetch_assoc($week_result)['total'];


    // graph data - last 7 days
    $graph = [];
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $day_sql = "SELECT COUNT(*) AS total FROM SYSTEM_LOG_T WHERE DATE(created_at) = '$day'";
        $day_result = mysqli_query($conn, $day_sql);
        $graph[$day] = mysqli_fetch_assoc($day_result)['total'];
    }
    $graph_max = max($graph);


    // all action types for the dropdown
    $action_sql = "SELECT DISTINCT action_type FROM SYSTEM_LOG_T ORDER BY action_type ASC";
    $action_result = mysqli_query($conn, $action_sql);


    // get all logs
    $list_sql = "SELECT log_id, user_id, action_type, description, created_at FROM SYSTEM_LOG_T $where ORDER BY created_at DESC";

    $list_result = mysqli_query($conn, $list_sql);
    $total_filtered = mysqli_num_rows($list_result);


    // export link (keep the same filters)
    $export_link = "/Implose.gg-src/actions/admin/export_logs_pdf.php"
        . "?search=" . urlencode($search)
        . "&action=" . urlencode($action)
        . "&from=" . urlencode($from)
        . "&to=" . urlencode($to);
    ?>

    <title>Logs - Implose.gg Admin</title>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_logs.css">
</head>


<body class="admin-body">
    <?php
        $current_page = 'admin_activitylogs';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <!-- Top -->
        <div class="logs-top">
            <div class="logs-title-block">
                <span class="logs-page-tag">AUDIT TRAIL</span>

                <h1>System Logs</h1>
            </div>


            <a href="<?php echo $export_link; ?>" class="logs-export" target="_blank">
                <img src="/Implose.gg-src/assets/images/icons/export.svg" alt="">

                Export PDF
            </a>
        </div>


        <!-- LHS Statistics -->
        <div class="logs-stats">

            <!-- Total -->
            <div class="logs-stats-main">
                <div class="logs-stats-numbers">
                    <span class="logs-stats-label">
                        TOTAL EVENTS
                    </span>

                    <span class="logs-stats-value">
                        <?php echo $total; ?>
                    </span>
                </div>

                <div class="logs-graph">
                    <?php
                        foreach ($graph as $day => $count) {
                            $height_pct = round(($count / $graph_max) * 100);
                            $day_label = strtoupper(date('D', strtotime($day)));
                    ?>
                        <div class="logs-graph-col">
                            <span class="logs-graph-number">
                                <?php echo $count; ?>
                            </span>

                            <div class="logs-graph-bar" style="height: <?php echo $height_pct; ?>%;"></div>

                            <span class="logs-graph-day">
                                <?php echo $day_label; ?>
                            </span>
                        </div>
                    <?php
                        }
                    ?>
                </div>
            </div>

            <!-- RHS Statistics -->
            <div class="logs-stats-side">
                <div class="logs-side-row">
                    <span class="logs-side-label">TODAY</span>

                    <span class="logs-side-value">
                        <?php echo $today; ?>
                    </span>
                </div>

                <div class="logs-side-row">
                    <span class="logs-side-label">THIS WEEK</span>

                    <span class="logs-side-value">
                        <?php echo $week; ?>
                    </span>
                </div>

            </div>

        </div>


        <!-- Filter -->
        <form method="get" class="logs-filter">
            <div class="logs-search">
                <img src="/Implose.gg-src/assets/images/icons/search.svg" alt="">

                <input type="text" name="search" placeholder="Search description, action or user..." value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <select name="action">
                <option value="">All actions</option>

                <?php while ($row = mysqli_fetch_assoc($action_result)) { ?>
                    <option value="<?php echo $row['action_type']; ?>" <?php if ($action == $row['action_type']) echo 'selected'; ?>>
                        <?php echo $row['action_type']; ?>
                    </option>
                <?php } ?>
            </select>

            <input type="date" name="from" value="<?php echo $from; ?>">

            <input type="date" name="to" value="<?php echo $to; ?>">

            <button type="submit">Apply</button>
            
            <a href="/Implose.gg-src/pages/admin/logs.php" class="logs-reset">Reset</a>
        </form>


        <!-- List -->
        <div class="logs-list">
            <?php
                if ($total_filtered == 0) {
            ?>
                <div class="logs-empty">
                    <img src="/Implose.gg-src/assets/images/icons/list-box.svg" alt="">

                    <span>NO LOGS MATCH THESE FILTERS.</span>
                </div>


            <?php
                } else {
                    $last_group = '';

                    while ($row = mysqli_fetch_assoc($list_result)) {

                        // decide day group
                        $log_time = strtotime($row['created_at']);

                        if ($log_time >= strtotime(date('Y-m-d'))) {
                            $group = 'TODAY';
                        } else if ($log_time >= strtotime('yesterday')) {
                            $group = 'YESTERDAY';
                        } else if ($log_time >= strtotime('-7 days')) {
                            $group = 'EARLIER THIS WEEK';
                        } else {
                            $group = 'EARLIER';
                        }

                        // print day group header
                        if ($group != $last_group) {
                ?>
                            <div class="logs-group">
                                <span class="logs-group-dot"></span>

                                <span class="logs-group-label">
                                    <?php echo $group; ?>
                                </span>

                                <span class="logs-group-line"></span>
                            </div>


                    <?php
                            $last_group = $group;
                        }

                        // dot color
                        $type = strtolower($row['action_type']);

                        if (str_contains($type, 'login') || str_contains($type, 'session')) {
                            $dot_color = 'sky';
                        } else if (str_contains($type, 'admin') || str_contains($type, 'update')) {
                            $dot_color = 'lavender';
                        } else if (str_contains($type, 'registration') || str_contains($type, 'profile')) {
                            $dot_color = 'mint';
                        } else if (str_contains($type, 'report') || str_contains($type, 'suspend') || str_contains($type, 'delete')) {
                            $dot_color = 'rose';
                        } else if (str_contains($type, 'export')) {
                            $dot_color = 'coral';
                        } else {
                            $dot_color = 'sand';
                        }

                        // time display depends on group
                        if ($group == 'TODAY' || $group == 'YESTERDAY') {
                            $time_display = date('g:i A', $log_time);
                        } else if ($group == 'EARLIER THIS WEEK') {
                            $time_display = date('D, g:i A', $log_time);
                        } else {
                            $time_display = date('j M Y, g:i A', $log_time);
                        }

                        // user info
                        if ($row['user_id'] != '') {
                            $user_id = $row['user_id'];
                            $user_sql = "SELECT username, avatar_path FROM USER_T WHERE user_id = '$user_id'";
                            $user_result = mysqli_query($conn, $user_sql);
                            $user = mysqli_fetch_assoc($user_result);

                            $name = $user['username'];
                            $avatar = '/Implose.gg-src/' . $user['avatar_path'];
                            $avatar_class = 'logs-row-avatar';

                        // null = system
                        } else {
                            $name = 'System';
                            $avatar = '/Implose.gg-src/assets/images/icons/robot-face-sleeping.svg';
                            $avatar_class = 'logs-row-avatar logs-row-avatar-system';
                        }
                    ?>

                        <div class="logs-row">
                            <span class="logs-row-dot logs-dot-<?php echo $dot_color; ?>"></span>

                            <img class="<?php echo $avatar_class; ?>" src="<?php echo $avatar; ?>" alt="">

                            <div class="logs-row-body">
                                <div class="logs-row-head">
                                    <span class="logs-row-name">
                                        <?php echo $name; ?>
                                    </span>

                                    <span class="logs-row-action">
                                        <?php echo strtoupper($row['action_type']); ?>
                                    </span>
                                </div>


                                <p class="logs-row-description">
                                    <?php echo $row['description']; ?>
                                </p>
                            </div>

                            <div class="logs-row-meta">
                                <span class="logs-row-time">
                                    <?php echo $time_display; ?>
                                </span>

                                <span class="logs-row-id">
                                    #<?php echo $row['log_id']; ?>
                                </span>
                            </div>
                        </div>

            <?php
                    }
                }
            ?>
        </div>

    </div>

</body>

</html>