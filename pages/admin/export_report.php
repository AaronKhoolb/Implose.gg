<!--
Programmer Name: Chong Jun Yoong
Program Name: /pages/admin/export_report.php
Description: Admin export report page
First Written on: Sunday, 22-Jun-2026
Edited on: Sunday, 22-Jun-2026
-->
<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');


    // date range: ?from=YYYY-MM-DD&to=YYYY-MM-DD, defaults to last 30 days
    if (isset($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'])) {
        $date_to = $_GET['to'];
    } else {
        $date_to = date('Y-m-d');
    }

    if (isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'])) {
        $date_from = $_GET['from'];
    } else {
        $date_from = date('Y-m-d', strtotime('-30 days', strtotime($date_to)));
    }

    $display_from = date('d-n-Y', strtotime($date_from));
    $display_to = date('d-n-Y', strtotime($date_to));
    $display_range = $display_from . ' – ' . $display_to;
    $generated_on = date('d-n-Y');

    $sql_from = mysqli_real_escape_string($conn, $date_from . ' 00:00:00');
    $sql_to = mysqli_real_escape_string($conn, $date_to . ' 23:59:59');
    $date_where = "created_at BETWEEN '$sql_from' AND '$sql_to'";


    $total_reports_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE $date_where";
    $total_reports = mysqli_fetch_assoc(mysqli_query($conn, $total_reports_sql))['total'];

    $chat_reports_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE $date_where AND reported_message_id IS NOT NULL";
    $chat_reports = mysqli_fetch_assoc(mysqli_query($conn, $chat_reports_sql))['total'];

    $course_reports_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE $date_where AND reported_marketplace_course_id IS NOT NULL";
    $course_reports = mysqli_fetch_assoc(mysqli_query($conn, $course_reports_sql))['total'];

    $resolved_count_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE $date_where AND report_status IN ('resolved','reviewed','rejected')";
    $resolved_count = mysqli_fetch_assoc(mysqli_query($conn, $resolved_count_sql))['total'];


    $source_chat_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE $date_where AND reported_message_id IS NOT NULL";
    $source_chat = mysqli_fetch_assoc(mysqli_query($conn, $source_chat_sql))['total'];

    $source_course_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE $date_where AND reported_marketplace_course_id IS NOT NULL";
    $source_course = mysqli_fetch_assoc(mysqli_query($conn, $source_course_sql))['total'];

    if ($total_reports > 0) {
        $pct_chat = round(($source_chat / $total_reports) * 100, 2);
        $pct_course = round(($source_course / $total_reports) * 100, 2);
    } else {
        $pct_chat = 0;
        $pct_course = 0;
    }


    $recent_reports_sql = "SELECT created_at, CASE WHEN reported_message_id IS NOT NULL THEN 'Global Chat Report' WHEN reported_marketplace_course_id IS NOT NULL THEN 'Course Report' ELSE 'Other' END AS source, reason FROM REPORT_T WHERE $date_where ORDER BY created_at DESC LIMIT 5";
    $recent_reports_result = mysqli_query($conn, $recent_reports_sql);
    $recent_reports = [];
    if ($recent_reports_result) {
        while ($row = mysqli_fetch_assoc($recent_reports_result)) {
            $recent_reports[] = $row;
        }
    }


    $trend_sql = "SELECT YEARWEEK(created_at, 1) AS yw, MIN(DATE(created_at)) AS week_start, COUNT(*) AS c FROM REPORT_T WHERE $date_where GROUP BY YEARWEEK(created_at, 1) ORDER BY yw ASC";
    $trend_result = mysqli_query($conn, $trend_sql);
    $trend_data = [];
    if ($trend_result) {
        while ($row = mysqli_fetch_assoc($trend_result)) {
            $trend_data[] = ['label' => 'W' . (count($trend_data) + 1), 'count' => (int)$row['c']];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_export_report.css">
    <title>User Reporting Report Dashboard</title>
</head>


<body class="admin-body">

    <?php
        $current_page = 'admin_report';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <!-- Page Header -->
        <div class="export-page-header">
            <div class="export-page-header-left">
                <nav class="export-breadcrumb" aria-label="Breadcrumb">
                    <a href="/Implose.gg-src/pages/admin/report.php">Report Management</a><span class="sep">/</span><span class="current">Export Report</span>
                </nav>
                <h1>User Reporting Report</h1>
                <p>A printable summary of user reports and moderation actions across the Implose ecosystem.</p>
            </div>
        </div>


        <!-- Filter Bar -->
        <form method="GET" class="export-filter-bar" id="export-filter-form">
            <div class="export-filter-btn export-date-picker-wrap">
                <img src="/Implose.gg-src/assets/images/icons/activity.svg" alt="calendar" class="custom-calendar-icon">
                <input type="date" name="from" id="export-date-from" value="<?php echo htmlspecialchars($date_from); ?>" class="custom-date-input">
                <span class="date-sep">–</span>
                <input type="date" name="to" id="export-date-to" value="<?php echo htmlspecialchars($date_to); ?>" class="custom-date-input">
            </div>

            <button type="submit" class="export-filter-btn apply-btn">
                <img src="/Implose.gg-src/assets/images/icons/search.svg" alt="apply">
                Apply
            </button>
        </form>


        <div class="export-main-layout">


            <!-- Report document -->
            <div class="export-document">

                <div class="export-doc-title">
                    <h2>User Reporting Report</h2>
                    <span><?php echo htmlspecialchars($display_range); ?></span>
                </div>


                <!-- Report Overview -->
                <div class="export-section">
                    <div class="export-section-heading"><span class="section-num">1</span>Report Overview</div>

                    <div class="export-overview-grid">
                        <div class="export-overview-card"><span class="overview-label">Total Report</span><span class="overview-value"><?php echo $total_reports; ?></span><div class="overview-icon"><img src="/Implose.gg-src/assets/images/icons/alert.svg" alt="total"></div></div>
                        <div class="export-overview-card"><span class="overview-label">Chat Report</span><span class="overview-value"><?php echo $chat_reports; ?></span><div class="overview-icon"><img src="/Implose.gg-src/assets/images/icons/chat-error.svg" alt="chat"></div></div>
                        <div class="export-overview-card"><span class="overview-label">Course Report</span><span class="overview-value"><?php echo $course_reports; ?></span><div class="overview-icon"><img src="/Implose.gg-src/assets/images/icons/course.svg" alt="course"></div></div>
                        <div class="export-overview-card"><span class="overview-label">Resolved Done</span><span class="overview-value"><?php echo $resolved_count; ?></span><div class="overview-icon"><img src="/Implose.gg-src/assets/images/icons/done.svg" alt="resolved"></div></div>
                    </div>
                </div>


                <!-- Report Source -->
                <div class="export-section">
                    <div class="export-section-heading"><span class="section-num">2</span>Report Source</div>

                    <table class="export-data-table">
                        <thead>
                            <tr>
                                <th class="col-source">Source</th>
                                <th class="col-total">Total Reports</th>
                                <th class="col-percentage">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="col-source">Global Live Chat</td>
                                <td class="col-total"><?php echo $source_chat; ?></td>
                                <td class="col-percentage">
                                    <div class="percentage-cell">
                                        <span class="percentage-text"><?php echo number_format($pct_chat, 2); ?>%</span>
                                        <div class="percentage-bar-track"><div class="percentage-bar-fill bar-cyan" style="width: <?php echo $pct_chat; ?>%"></div></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-source">Course</td>
                                <td class="col-total"><?php echo $source_course; ?></td>
                                <td class="col-percentage">
                                    <div class="percentage-cell">
                                        <span class="percentage-text"><?php echo number_format($pct_course, 2); ?>%</span>
                                        <div class="percentage-bar-track"><div class="percentage-bar-fill bar-cyan" style="width: <?php echo $pct_course; ?>%"></div></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>


                <!-- Recent Reports Log -->
                <div class="export-section">
                    <div class="export-section-heading"><span class="section-num">3</span>Recent Reports Log</div>

                    <table class="export-data-table">
                        <thead>
                            <tr>
                                <th class="col-date col-date-25">Date</th>
                                <th class="col-source col-source-25">Source</th>
                                <th class="col-reason col-reason-50">Reason Snippet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_reports)) { ?>
                                <?php foreach ($recent_reports as $report) {
                                    $reason_display = mb_strimwidth($report['reason'], 0, 70, '...');
                                    $formatted_date = date('d-m-Y g:ia', strtotime($report['created_at']));
                                ?>
                                    <tr>
                                        <td class="recent-log-date"><?php echo $formatted_date; ?></td>
                                        <td class="col-source"><?php echo htmlspecialchars($report['source']); ?></td>
                                        <td><div class="recent-log-reason">"<?php echo htmlspecialchars($reason_display); ?>"</div></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr><td colspan="3" class="recent-log-empty">No reports found in this date range.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>


            <!-- Sidebar -->
            <div class="export-sidebar">

                <div class="export-summary-card">
                    <h3>Report Summary</h3>

                    <div class="summary-row"><span class="summary-label">Total Reports</span><span class="summary-value"><?php echo $total_reports; ?></span></div>
                    <div class="summary-row"><span class="summary-label">Date Range</span><span class="summary-value"><?php echo htmlspecialchars($display_range); ?></span></div>
                    <div class="summary-row"><span class="summary-label">Generated On</span><span class="summary-value"><?php echo $generated_on; ?></span></div>

                    <a href="/Implose.gg-src/actions/admin/export_report_pdf.php?from=<?php echo urlencode($date_from); ?>&to=<?php echo urlencode($date_to); ?>" class="btn-export-pdf" id="btn-export-pdf" target="_blank">
                        <img src="/Implose.gg-src/assets/images/icons/upload.svg" alt="export" class="icon-flip">
                        Export PDF
                    </a>
                </div>


                <div class="export-trend-card">
                    <h3>Weekly Report Count</h3>

                    <div class="trend-chart-placeholder">
                        <?php if (!empty($trend_data)) { ?>
                            <canvas id="trendChart"></canvas>
                        <?php } else { ?>
                            <div class="trend-empty">No trend data available.</div>
                        <?php } ?>
                    </div>
                </div>

            </div>

        </div>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const trendData = <?php echo json_encode($trend_data); ?>;
            if (!trendData || trendData.length === 0) return;

            const labels = trendData.map(function (d) { return d.label; });
            const data = trendData.map(function (d) { return d.count; });

            const ctx = document.getElementById('trendChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Reports',
                        data: data,
                        borderColor: '#22d3ee',
                        backgroundColor: 'rgba(34, 211, 238, 0.2)',
                        borderWidth: 2,
                        pointBackgroundColor: '#22d3ee',
                        pointRadius: 4,
                        fill: true,
                        tension: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend:  { display: false },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, color: 'rgba(255, 255, 255, 0.5)' },
                            grid:  { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        x: {
                            ticks: { color: 'rgba(255, 255, 255, 0.5)' },
                            grid:  { display: false }
                        }
                    }
                }
            });
        });
    </script>

</body>
</html>
