<!--
Programmer Name: Chong Jun Yoong
Program Name: /pages/admin/report.php
Description: Admin report management page
First Written on: Thursday, 18-Jun-2026
Edited on: Thursday, 18-Jun-2026
-->
<?php
    session_start();

    $success_msg = '';
    $error_msg = '';
    if (isset($_SESSION['success_msg'])) {
        $success_msg = $_SESSION['success_msg'];
        unset($_SESSION['success_msg']);
    }
    if (isset($_SESSION['error_msg'])) {
        $error_msg = $_SESSION['error_msg'];
        unset($_SESSION['error_msg']);
    }

    include_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');


    $active_tab = (isset($_GET['tab']) && $_GET['tab'] === 'resolved') ? 'resolved' : 'pending';

    if ($active_tab === 'resolved') {
        $status_filter = "'resolved','reviewed','rejected'";
    } else {
        $status_filter = "'pending'";
    }


    $total_reports_sql = "SELECT COUNT(*) AS total FROM REPORT_T";
    $total_reports = mysqli_fetch_assoc(mysqli_query($conn, $total_reports_sql))['total'];

    $chat_reports_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE reported_message_id IS NOT NULL";
    $chat_reports = mysqli_fetch_assoc(mysqli_query($conn, $chat_reports_sql))['total'];

    $course_reports_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE reported_marketplace_course_id IS NOT NULL";
    $course_reports = mysqli_fetch_assoc(mysqli_query($conn, $course_reports_sql))['total'];

    $resolved_count_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE report_status IN ('resolved','reviewed','rejected')";
    $resolved_count = mysqli_fetch_assoc(mysqli_query($conn, $resolved_count_sql))['total'];


    $filter_category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $cat_where = "";

    if ($filter_category == 'Course Report') {
        $cat_where = " AND reported_marketplace_course_id IS NOT NULL";
    } else if ($filter_category == 'Global Chat Report') {
        $cat_where = " AND reported_message_id IS NOT NULL";
    }


    $items_per_page = 3;
    $page_num = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

    if ($active_tab === 'resolved') {
        $current_tab_total_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE report_status IN ('resolved','reviewed','rejected') $cat_where";
    } else {
        $current_tab_total_sql = "SELECT COUNT(*) AS total FROM REPORT_T WHERE report_status = 'pending' $cat_where";
    }
    $current_tab_total = mysqli_fetch_assoc(mysqli_query($conn, $current_tab_total_sql))['total'];

    $total_pages = max(1, ceil($current_tab_total / $items_per_page));
    if ($page_num > $total_pages) $page_num = $total_pages;
    $offset = ($page_num - 1) * $items_per_page;


    $sql = "SELECT r.report_id, r.reason, r.report_status, r.created_at, r.reported_marketplace_course_id, r.reported_message_id, reporter.user_id AS reporter_id, reporter.username AS reporter_name, reporter.email_address AS reporter_email, reporter.avatar_path AS reporter_avatar, CASE WHEN r.reported_message_id IS NOT NULL THEN 'Global Chat Report' WHEN r.reported_marketplace_course_id IS NOT NULL THEN 'Course Report' ELSE 'Other' END AS category, COALESCE(cm.message_text, ct.title) AS content_text, CASE WHEN r.reported_message_id IS NOT NULL THEN msg_user.user_id ELSE NULL END AS reported_user_id, CASE WHEN r.reported_message_id IS NOT NULL THEN msg_user.username ELSE NULL END AS reported_username, CASE WHEN r.reported_message_id IS NOT NULL THEN msg_user.email_address ELSE NULL END AS reported_email, CASE WHEN r.reported_message_id IS NOT NULL THEN msg_user.avatar_path ELSE NULL END AS reported_avatar FROM REPORT_T r LEFT JOIN USER_T reporter ON r.reporter_id = reporter.user_id LEFT JOIN CHAT_MESSAGE_T cm ON r.reported_message_id = cm.message_id LEFT JOIN USER_T msg_user ON cm.sender_id = msg_user.user_id LEFT JOIN MARKETPLACE_COURSE_T ct ON r.reported_marketplace_course_id = ct.marketplace_course_id LEFT JOIN USER_T c_user ON ct.creator_id = c_user.user_id WHERE r.report_status IN ($status_filter) $cat_where ORDER BY r.created_at DESC LIMIT $items_per_page OFFSET $offset";

    $result = mysqli_query($conn, $sql);
    if ($result) {
        $reports_data = mysqli_fetch_all($result, MYSQLI_ASSOC);
    } else {
        $reports_data = [];
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_report.css">
    <title>Report Management</title>
</head>


<body class="admin-body">

    <?php
        $current_page = 'admin_report';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/admin_toast.php'); ?>

    <div class="admin-main-content">

        <!-- Page Header -->
        <div class="report-page-header">
            <div class="report-page-header-left">
                <h1>Report Management</h1>
                <p>Review and take action on chat and course reports</p>
            </div>

            <a href="/Implose.gg-src/pages/admin/export_report.php" class="btn-export-report">
                <img src="/Implose.gg-src/assets/images/icons/export.svg" alt="export">
                Export Report
            </a>
        </div>

        <!-- Stat Cards -->
        <div class="report-stats-row">
            <div class="stat-card"><span class="stat-card-label">Total Report</span><span class="stat-card-value"><?php echo $total_reports; ?></span><div class="stat-card-icon"><img src="/Implose.gg-src/assets/images/icons/alert.svg" alt="report"></div></div>
            <div class="stat-card"><span class="stat-card-label">Chat Report</span><span class="stat-card-value"><?php echo $chat_reports; ?></span><div class="stat-card-icon"><img src="/Implose.gg-src/assets/images/icons/chat-error.svg" alt="chat"></div></div>
            <div class="stat-card"><span class="stat-card-label">Course Report</span><span class="stat-card-value"><?php echo $course_reports; ?></span><div class="stat-card-icon"><img src="/Implose.gg-src/assets/images/icons/course.svg" alt="course"></div></div>
            <div class="stat-card"><span class="stat-card-label">Resolved Done</span><span class="stat-card-value"><?php echo $resolved_count; ?></span><div class="stat-card-icon"><img src="/Implose.gg-src/assets/images/icons/done.svg" alt="resolved"></div></div>
        </div>


        <div class="report-main-layout">

            <!-- Report list -->
            <div class="report-list-column">

                <div class="report-tabs">
                    <a href="?tab=pending"  class="report-tab <?php echo $active_tab == 'pending'  ? 'active' : ''; ?>">Pending Report</a>
                    <a href="?tab=resolved" class="report-tab <?php echo $active_tab == 'resolved' ? 'active' : ''; ?>">Resolved Report</a>
                </div>

                <div class="report-toolbar">
                    <div class="report-search-wrap">
                        <input type="text" id="report-search-input" class="report-search" placeholder="Search reports...">
                        <img class="search-icon" src="/Implose.gg-src/assets/images/icons/search.svg" alt="search">
                    </div>

                    <select id="report-category-filter" class="report-filter-select" onchange="window.location.href='?tab=<?php echo $active_tab; ?>&category=' + encodeURIComponent(this.value);">
                        <option value="all" <?php if ($filter_category == 'all') echo 'selected'; ?>>All Categories</option>
                        <option value="Course Report" <?php if ($filter_category == 'Course Report') echo 'selected'; ?>>Course Report</option>
                        <option value="Global Chat Report" <?php if ($filter_category == 'Global Chat Report') echo 'selected'; ?>>Global Chat Report</option>
                    </select>
                </div>


                <?php if (!empty($reports_data)) { ?>

                    <?php foreach ($reports_data as $report) { ?>
                        <?php
                            $created = new DateTime($report['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($created);
                            if ($diff->days >= 1) $time_ago = $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                            elseif ($diff->h >= 1) $time_ago = $diff->h . ' hr' . ($diff->h > 1 ? 's' : '') . ' ago';
                            else $time_ago = max(1, $diff->i) . ' min ago';
                        ?>
                        <div class="report-card" data-id="<?php echo $report['report_id']; ?>" data-category="<?php echo htmlspecialchars($report['category']); ?>" data-reason="<?php echo htmlspecialchars($report['reason']); ?>" data-content="<?php echo htmlspecialchars($report['content_text'] ?? 'N/A'); ?>" data-created="<?php echo htmlspecialchars(date('d-m-Y g:ia', strtotime($report['created_at']))); ?>" data-reporter-name="<?php echo htmlspecialchars($report['reporter_name'] ?? 'Unknown'); ?>" data-reporter-email="<?php echo htmlspecialchars($report['reporter_email'] ?? ''); ?>" data-reporter-avatar="<?php echo htmlspecialchars($report['reporter_avatar'] ?? ''); ?>" data-reported-name="<?php echo htmlspecialchars($report['reported_username'] ?? 'Unknown'); ?>" data-reported-email="<?php echo htmlspecialchars($report['reported_email'] ?? ''); ?>" data-reported-avatar="<?php echo htmlspecialchars($report['reported_avatar'] ?? ''); ?>" data-status="<?php echo htmlspecialchars($report['report_status']); ?>">
                            <div class="report-card-header">
                                <span class="report-card-title"><?php echo htmlspecialchars($report['category']); ?></span>

                                <?php if ($active_tab === 'resolved') { ?>
                                    <?php if ($report['report_status'] === 'resolved' || $report['report_status'] === 'reviewed') { ?>
                                        <span class="report-status-tag resolved">Resolved</span>
                                    <?php } else if ($report['report_status'] === 'rejected') { ?>
                                        <span class="report-status-tag rejected">Rejected</span>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                            <div class="report-card-desc"><?php echo htmlspecialchars(mb_strimwidth($report['content_text'] ?? 'No content available.', 0, 80, '...')); ?></div>
                            <div class="report-card-footer">
                                <div class="report-card-user"><span>Report by <?php echo htmlspecialchars($report['reporter_name'] ?? 'Unknown'); ?></span></div>
                                <span><?php echo $time_ago; ?></span>
                            </div>
                        </div>
                    <?php } ?>


                    <!-- Pagination -->
                    <?php if ($total_pages > 1) { ?>
                        <div class="pagination">
                            <?php
                                $start_page = max(1, $page_num - 1);
                                $end_page = min($total_pages, $page_num + 1);
                                $cat_param = $filter_category !== 'all' ? '&category=' . urlencode($filter_category) : '';

                                if ($start_page > 1) {
                                    echo '<a href="?tab='.$active_tab.$cat_param.'&page=1" class="page-btn">1</a>';
                                    if ($start_page > 2) {
                                        echo '<div class="page-btn is-dots">..</div>';
                                    }
                                }

                                for ($p = $start_page; $p <= $end_page; $p++) {
                                    $activeClass = ($p === $page_num) ? 'active' : '';
                                    echo '<a href="?tab='.$active_tab.$cat_param.'&page='.$p.'" class="page-btn '.$activeClass.'">'.$p.'</a>';
                                }

                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<div class="page-btn is-dots">..</div>';
                                    }
                                    echo '<a href="?tab='.$active_tab.$cat_param.'&page='.$total_pages.'" class="page-btn">'.$total_pages.'</a>';
                                }
                            ?>
                        </div>
                    <?php } ?>

                <?php } else { ?>
                    <div class="report-empty-state">
                        <img src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="empty">
                        <div class="report-empty-title">No Reports Found</div>
                        <div class="report-empty-desc">There are currently no reports to display.</div>
                    </div>
                <?php } ?>
            </div>


            <!-- Detail panel -->
            <div class="report-detail-panel">

                <?php if (!empty($reports_data)) { ?>

                    <div id="report-empty-selection" class="report-empty-state is-full">
                        <img src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="empty">
                        <div class="report-empty-title">No Report Selected</div>
                        <div class="report-empty-desc">Please click any report from the list to view its details.</div>
                    </div>

                    <div id="report-detail-content">
                        <div class="panel-header">
                            <img src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="file">
                            Report Details
                        </div>

                        <div class="detail-cards-row">
                            <div class="detail-card">
                                <span class="detail-card-label">REPORTED CONTENT</span>
                                <span class="detail-card-title" id="detail-category">—</span>
                                <div class="detail-card-text" id="detail-content">—</div>
                                <span class="detail-card-time" id="detail-created">—</span>
                            </div>
                            <div class="detail-card">
                                <span class="detail-card-label">REPORTED REASON</span>
                                <span class="detail-card-title" id="detail-reason">—</span>
                                <span class="detail-card-time" id="detail-created-2">—</span>
                            </div>
                        </div>

                        <div class="detail-cards-row">
                            <div class="detail-card">
                                <span class="detail-card-label">REPORTER</span>
                                <div class="user-card-content">
                                    <div class="user-avatar"><img id="detail-reporter-avatar" src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="user"></div>
                                    <div class="user-info"><span class="user-name" id="detail-reporter-name">—</span><span class="user-email" id="detail-reporter-email">—</span></div>
                                </div>
                            </div>

                            <div class="detail-card">
                                <span class="detail-card-label">REPORTED USER</span>
                                <div class="user-card-content" id="detail-reported-user-content">
                                    <div class="user-avatar warning" id="detail-reported-avatar-container"><img id="detail-reported-avatar" src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="warn"></div>
                                    <div class="user-info"><span class="user-name" id="detail-reported-name">—</span><span class="user-email" id="detail-reported-email">—</span></div>
                                </div>
                                <span class="detail-card-title detail-reported-user-empty" id="detail-reported-user-empty">—</span>
                            </div>
                        </div>

                        <!-- action buttons: populated by JS based on category + status -->
                        <div class="action-section">
                            <div class="action-label">ACTION & MODERATION TOOLS</div>
                            <div class="action-row">
                                <div id="dynamic-action-block">
                                    <div class="action-group-label" id="dynamic-action-label">Content Actions</div>
                                    <div class="action-group" id="dynamic-action-buttons">
                                        <button class="btn-action btn-danger"><img src="/Implose.gg-src/assets/images/icons/trash.svg" alt="del"> Delete</button>
                                        <button class="btn-action"><img src="/Implose.gg-src/assets/images/icons/user_mod.svg" alt="dismiss"> Dismiss</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php } else { ?>
                    <div class="report-empty-state is-full">
                        <img src="/Implose.gg-src/assets/images/icons/info.circle.svg" alt="empty">
                        <div class="report-empty-title">Nothing to Display</div>
                        <div class="report-empty-desc">There are no reports available to review.</div>
                    </div>
                <?php } ?>
            </div>

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const reportCards = document.querySelectorAll('.report-card');
            const emptySelection = document.getElementById('report-empty-selection');
            const detailContent = document.getElementById('report-detail-content');


            reportCards.forEach(function (card) {
                card.addEventListener('click', function () {

                    reportCards.forEach(function (c) { c.classList.remove('active'); });
                    this.classList.add('active');

                    if (emptySelection) emptySelection.style.display = 'none';
                    if (detailContent)  detailContent.classList.add('is-open');

                    const d = this.dataset;

                    document.getElementById('detail-category').textContent = d.category || '—';
                    document.getElementById('detail-content').textContent = d.content || '—';
                    document.getElementById('detail-created').textContent = d.created || '—';
                    document.getElementById('detail-created-2').textContent = d.created || '—';
                    document.getElementById('detail-reason').textContent = d.reason || '—';
                    document.getElementById('detail-reporter-name').textContent = d.reporterName || '—';
                    document.getElementById('detail-reporter-email').textContent = d.reporterEmail || '—';


                    // hide the reported-user card when the reporter is Unknown (e.g. deleted account)
                    const reportedName = d.reportedName || 'Unknown';
                    const reportedContent = document.getElementById('detail-reported-user-content');
                    const reportedEmpty = document.getElementById('detail-reported-user-empty');

                    if (reportedName === 'Unknown' || reportedName === '—') {
                        if (reportedContent) reportedContent.style.display = 'none';
                        if (reportedEmpty) reportedEmpty.style.display = 'block';
                    } else {
                        if (reportedContent) reportedContent.style.display = 'flex';
                        if (reportedEmpty) reportedEmpty.style.display = 'none';
                        document.getElementById('detail-reported-name').textContent = reportedName;
                        document.getElementById('detail-reported-email').textContent = d.reportedEmail || '—';
                    }


                    const avatarImg = document.getElementById('detail-reporter-avatar');
                    if (d.reporterAvatar) {
                        avatarImg.src = '/Implose.gg-src/' + d.reporterAvatar;
                        avatarImg.classList.add('is-real-avatar');
                    } else {
                        avatarImg.src = '/Implose.gg-src/assets/images/icons/info.circle.svg';
                        avatarImg.classList.remove('is-real-avatar');
                    }


                    // build the action buttons based on category (chat = suspend user, course = delete content) and status
                    const actionSection = document.querySelector('.action-section');
                    const actionLabel = document.getElementById('dynamic-action-label');
                    const actionButtons = document.getElementById('dynamic-action-buttons');

                    if (d.status === 'resolved' || d.status === 'rejected' || d.status === 'reviewed') {
                        if (actionSection) actionSection.style.display = 'none';
                    } else {
                        if (actionSection) actionSection.style.display = 'block';

                        if (d.category === 'Global Chat Report') {
                            actionLabel.textContent = 'User Account Actions';
                            actionButtons.innerHTML = ''
                                + '<form method="POST" action="/Implose.gg-src/actions/admin/report_action.php" class="action-form">'
                                +     '<input type="hidden" name="action" value="suspend">'
                                +     '<input type="hidden" name="report_id" value="' + d.id + '">'
                                +     '<button type="submit" class="btn-action btn-danger"><img src="/Implose.gg-src/assets/images/icons/suspend.user.svg" alt="suspend"> Suspend</button>'
                                + '</form>'
                                + '<form method="POST" action="/Implose.gg-src/actions/admin/report_action.php" class="action-form">'
                                +     '<input type="hidden" name="action" value="dismiss">'
                                +     '<input type="hidden" name="report_id" value="' + d.id + '">'
                                +     '<button type="submit" class="btn-action"><img src="/Implose.gg-src/assets/images/icons/user_mod.svg" alt="dismiss"> Dismiss</button>'
                                + '</form>';
                        } else {
                            actionLabel.textContent = 'Content Actions';
                            actionButtons.innerHTML = ''
                                + '<form method="POST" action="/Implose.gg-src/actions/admin/report_action.php" class="action-form">'
                                +     '<input type="hidden" name="action" value="delete">'
                                +     '<input type="hidden" name="report_id" value="' + d.id + '">'
                                +     '<button type="submit" class="btn-action btn-danger"><img src="/Implose.gg-src/assets/images/icons/trash.svg" alt="delete"> Delete</button>'
                                + '</form>'
                                + '<form method="POST" action="/Implose.gg-src/actions/admin/report_action.php" class="action-form">'
                                +     '<input type="hidden" name="action" value="dismiss">'
                                +     '<input type="hidden" name="report_id" value="' + d.id + '">'
                                +     '<button type="submit" class="btn-action"><img src="/Implose.gg-src/assets/images/icons/user_mod.svg" alt="dismiss"> Dismiss</button>'
                                + '</form>';
                        }
                    }


                    const reportedAvatarImg = document.getElementById('detail-reported-avatar');
                    const reportedAvatarContainer = document.getElementById('detail-reported-avatar-container');

                    if (reportedAvatarImg && reportedAvatarContainer) {
                        if (d.reportedAvatar) {
                            reportedAvatarImg.src = '/Implose.gg-src/' + d.reportedAvatar;
                            reportedAvatarImg.classList.add('is-real-avatar');
                            reportedAvatarContainer.classList.remove('warning');
                        } else {
                            reportedAvatarImg.src = '/Implose.gg-src/assets/images/icons/info.circle.svg';
                            reportedAvatarImg.classList.remove('is-real-avatar');
                            reportedAvatarContainer.classList.add('warning');
                        }
                    }
                });
            });


            const searchInput = document.getElementById('report-search-input');

            function filterReports() {
                const search = searchInput ? searchInput.value.toLowerCase() : '';

                reportCards.forEach(function (card) {
                    const cardContent = card.dataset.content ? card.dataset.content.toLowerCase() : '';
                    const cardReason = card.dataset.reason ? card.dataset.reason.toLowerCase() : '';
                    const cardRepName = card.dataset.reporterName ? card.dataset.reporterName.toLowerCase() : '';

                    let matchSearch = (search === '' || cardContent.includes(search) || cardReason.includes(search) || cardRepName.includes(search));

                    card.style.display = matchSearch ? '' : 'none';
                });
            }
            if (searchInput) searchInput.addEventListener('input', filterReports);


            const confirmMessages = {
                suspend : 'Suspend this user and delete the message?',
                delete  : 'Permanently delete this content? This cannot be undone.',
                dismiss : 'Dismiss this report?'
            };

            document.addEventListener('submit', function (e) {
                const form = e.target.closest('.action-form');
                if (!form) return;

                const action = form.querySelector('[name="action"]').value;
                const msg = confirmMessages[action] || 'Are you sure?';

                if (!confirm(msg)) e.preventDefault();
            });
        });
    </script>

</body>
</html>
