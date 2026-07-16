<!--
Programmer Name: Chong Jun Yoong
Program Name: /pages/admin/users.php
Description: Admin users management page
First Written on: Wednesday, 2-Jun-2026
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
    if (isset($_SESSION['action_error'])) {
        $error_msg = $_SESSION['action_error'];
        unset($_SESSION['action_error']);
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_users.css">
    <title>Users Management — Implose.gg Admin</title>
    <meta name="description" content="Manage all registered users and moderator access permissions.">
</head>


<body class="admin-body">

    <?php
        $current_page = 'admin_users';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>


    <div class="admin-main-content">

        <?php
            function pick_log_icon($action_type) {
                $type = strtolower($action_type);
                if (str_contains($type, 'login'))        return 'users.svg';
                if (str_contains($type, 'registration')) return 'user-shield.svg';
                if (str_contains($type, 'profile'))      return 'pencil.svg';
                if (str_contains($type, 'suspend'))      return 'suspend.user.svg';
                if (str_contains($type, 'report'))       return 'alert.svg';
                if (str_contains($type, 'delete'))       return 'trash.svg';
                if (str_contains($type, 'admin'))        return 'moderator.svg';
                return 'activity.svg';
            }

            function log_time_ago($date_string) {
                $diff = time() - strtotime($date_string);
                if ($diff < 60)    return "Just now";
                if ($diff < 3600)  return floor($diff / 60)   . "m ago";
                if ($diff < 86400) return floor($diff / 3600) . "h ago";
                return floor($diff / 86400) . "d ago";
            }


            // collect stat card counts in the same loop as loading users
            $users_sql = "SELECT user_id, username, email_address, role, account_status, avatar_path, last_login_at, created_at FROM USER_T ORDER BY created_at DESC";
            $users_result = mysqli_query($conn, $users_sql);

            $stat_total = 0;
            $stat_registered = 0;
            $stat_moderators = 0;
            $stat_suspended = 0;

            $all_users = [];
            while ($row = mysqli_fetch_assoc($users_result)) {
                $all_users[] = $row;
                $stat_total++;
                if ($row['account_status'] != 'suspended')   $stat_registered++;
                if (strtolower($row['role']) == 'moderator') $stat_moderators++;
                if ($row['account_status'] == 'suspended')   $stat_suspended++;
            }


            // pre-fetch each user's own actions + admin actions on them
            $logs_by_user = [];
            foreach ($all_users as $uu) {
                $uu_id = (int) $uu['user_id'];
                $log_sql = "SELECT log_id, action_type, description, created_at, user_id FROM SYSTEM_LOG_T WHERE user_id = $uu_id OR description LIKE '%user #$uu_id (%' ORDER BY created_at DESC LIMIT 20";
                $log_result = mysqli_query($conn, $log_sql);

                $logs_by_user[$uu_id] = [];
                if ($log_result) {
                    while ($lrow = mysqli_fetch_assoc($log_result)) {
                        $logs_by_user[$uu_id][] = $lrow;
                    }
                }
            }
        ?>


        <!-- Page Header -->
        <div class="users-page-header">
            <div class="users-page-header-left">
                <h1>Users Management</h1>
                <p>Manage all registered users and moderator access permissions.</p>
            </div>
            <a href="/Implose.gg-src/pages/admin/create_user.php" class="btn-create-user">+ Create User</a>
        </div>

        <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/admin_toast.php'); ?>


        <!-- Stat Cards -->
        <div class="users-stats-row">
            <div class="stat-card"><span class="stat-card-label">Total Users</span><span class="stat-card-value" id="stat-total"><?php echo $stat_total; ?></span><div class="stat-card-icon"><img src="/Implose.gg-src/assets/images/icons/users2.svg" alt="users2"></div></div>
            <div class="stat-card"><span class="stat-card-label">Registered Users</span><span class="stat-card-value" id="stat-registered"><?php echo $stat_registered; ?></span><div class="stat-card-icon"><img src="/Implose.gg-src/assets/images/icons/users.svg" alt="users"></div></div>
            <div class="stat-card"><span class="stat-card-label">Total Moderators</span><span class="stat-card-value" id="stat-moderators"><?php echo $stat_moderators; ?></span><div class="stat-card-icon"><img src="/Implose.gg-src/assets/images/icons/moderator.svg" alt="moderator"></div></div>
            <div class="stat-card"><span class="stat-card-label">Suspended Users</span><span class="stat-card-value" id="stat-suspended"><?php echo $stat_suspended; ?></span><div class="stat-card-icon"><img src="/Implose.gg-src/assets/images/icons/suspend.user.svg" alt="suspend.user"></div></div>
        </div>


        <div class="users-main-layout" id="users-main-layout">

            <!-- Users Table -->
            <div class="users-table-card">

                <div class="users-toolbar">
                    <div class="users-search-wrap">
                        <input type="text" id="users-search" class="users-search" placeholder="Search users..." oninput="filterUsers()">
                        <img class="search-icon" src="/Implose.gg-src/assets/images/icons/search.svg" alt="search">
                    </div>

                    <select class="users-filter-select" id="filter-status" onchange="filterUsers()">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>

                    <select class="users-filter-select" id="filter-role" onchange="filterUsers()">
                        <option value="">Role</option>
                        <option value="User">User</option>
                        <option value="Moderator">Moderator</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>


                <div class="users-table-scroll">
                    <table class="users-table" id="users-table">
                        <colgroup>
                            <col class="col-user">
                            <col class="col-role">
                            <col class="col-email">
                            <col class="col-login">
                            <col class="col-status">
                            <col class="col-action">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Last Login</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody">
                            <?php if (count($all_users) > 0) { ?>
                                <?php foreach ($all_users as $u) {
                                    $u_id = (int) $u['user_id'];
                                    $u_name = $u['username'] ?? '(No username)';
                                    $u_role = ucfirst($u['role']);
                                    $u_email = $u['email_address'];
                                    $u_status = $u['account_status'];
                                    $u_last = $u['last_login_at'] ? date('j M Y, g:ia', strtotime($u['last_login_at'])) : 'Never';
                                    $u_joined = 'Joined ' . date('j M Y', strtotime($u['created_at']));
                                    $u_avatar = $u['avatar_path'] ? '/Implose.gg-src/' . $u['avatar_path'] : '';
                                    $name_js = htmlspecialchars(str_replace(["'", '"'], ['\\\'','\\"'], $u_name), ENT_QUOTES);
                                ?>
                                    <tr data-user-id="<?php echo $u_id; ?>" data-name="<?php echo htmlspecialchars($u_name); ?>" data-email="<?php echo htmlspecialchars($u_email); ?>" data-role="<?php echo $u_role; ?>" data-status="<?php echo $u_status; ?>" data-avatar="<?php echo htmlspecialchars($u_avatar); ?>" data-joined="<?php echo htmlspecialchars($u_joined); ?>" onclick="selectUser(this)">
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar-sm"><?php if ($u_avatar) { ?><img src="<?php echo htmlspecialchars($u_avatar); ?>" alt="<?php echo htmlspecialchars($u_name); ?>" onerror="this.style.display='none';"><?php } ?></div>
                                                <span class="user-name" title="<?php echo htmlspecialchars($u_name); ?>"><?php echo htmlspecialchars($u_name); ?></span>
                                            </div>
                                        </td>
                                        <td><span class="role-badge <?php echo strtolower($u_role); ?>"><?php echo $u_role; ?></span></td>
                                        <td class="users-email-cell" title="<?php echo htmlspecialchars($u_email); ?>"><?php echo htmlspecialchars($u_email); ?></td>
                                        <td><span class="last-login"><?php echo $u_last; ?></span></td>
                                        <td><span class="status-badge <?php echo $u_status; ?>"><?php echo ucfirst($u_status); ?></span></td>
                                        <td>
                                            <div class="row-actions">
                                                <a class="row-action-btn" title="Edit" href="/Implose.gg-src/pages/admin/edit_user.php?id=<?php echo $u_id; ?>" onclick="event.stopPropagation();"><img src="/Implose.gg-src/assets/images/icons/pencil.svg" alt="Edit"></a>
                                                <button type="button" class="row-action-btn view" title="View Details" onclick="event.stopPropagation(); selectUser(this.closest('tr'));"><img src="/Implose.gg-src/assets/images/icons/eye.svg" alt="View"></button>
                                                <form method="POST" action="/Implose.gg-src/actions/admin/delete_user.php" class="users-inline-form" onclick="event.stopPropagation();" onsubmit="return confirm('Are you sure you want to delete user &quot;<?php echo $name_js; ?>&quot;? This action cannot be undone.');">
                                                    <input type="hidden" name="user_id" value="<?php echo $u_id; ?>">
                                                    <button type="submit" class="row-action-btn delete" title="Delete"><img src="/Implose.gg-src/assets/images/icons/trash.svg" alt="Delete"></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr><td colspan="6" class="users-empty-row">No users found.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>


            <!-- User Detail Side Panel -->
            <div class="user-detail-panel" id="user-detail-panel">

                <div class="panel-empty" id="panel-empty">
                    <img class="panel-empty-icon" src="/Implose.gg-src/assets/images/icons/users.svg" alt="users">
                    <span>Select a user to view details</span>
                </div>


                <div id="panel-content" style="display:none;">

                    <div class="panel-avatar-section">
                        <div class="panel-avatar" id="panel-avatar">
                            <img id="panel-avatar-img" src="" alt="" style="display:none;">
                        </div>
                        <div class="panel-user-name-row">
                            <span class="panel-user-name" id="panel-name"></span>
                            <span class="status-badge" id="panel-status-badge"></span>
                        </div>
                        <div class="panel-user-email" id="panel-email"></div>
                        <div class="panel-user-joined" id="panel-joined"></div>
                    </div>


                    <div class="panel-tabs">
                        <button type="button" class="panel-tab active" onclick="switchTab(this, 'tab-overview')" id="tab-btn-overview">Overview</button>
                        <button type="button" class="panel-tab" onclick="switchTab(this, 'tab-activity')">Activity Record</button>
                    </div>


                    <!-- Overview Tab -->
                    <div class="panel-body" id="tab-overview">

                        <form method="POST" action="/Implose.gg-src/actions/admin/update_user_role.php" id="role-form" onsubmit="rememberSelected();">
                            <div>
                                <div class="panel-field-label">Role</div>
                                <input type="hidden" name="user_id" id="role-form-user-id" value="">

                                <select class="panel-role-select" id="panel-role-select" name="role" onchange="onRoleChange()">
                                    <option value="User">User</option>
                                    <option value="Moderator">Moderator</option>
                                    <option value="Admin">Admin</option>
                                </select>

                                <button type="submit" class="role-save-btn" id="role-save-btn" style="display:none;">Save Changes</button>
                            </div>
                        </form>


                        <!-- one form, two submit buttons -->
                        <div>
                            <div class="panel-field-label">Account Status</div>
                            <form method="POST" action="/Implose.gg-src/actions/admin/update_user_status.php" onsubmit="rememberSelected();">
                                <input type="hidden" name="user_id" id="status-form-user-id" value="">
                                <div class="panel-status-row">
                                    <button type="submit" name="status" value="suspended" class="panel-status-btn suspend-btn" id="btn-suspend">Suspend</button>
                                    <button type="submit" name="status" value="active"    class="panel-status-btn active-btn"  id="btn-active">Active</button>
                                </div>
                            </form>
                        </div>


                        <div>
                            <div class="panel-field-label">Actions</div>
                            <div class="panel-actions">
                                <button type="button" class="panel-action-btn default" onclick="editSelectedUser()">Edit Profile</button>
                                <button type="button" class="panel-action-btn default" onclick="document.querySelectorAll('.panel-tab')[1].click()">View Activity Record</button>

                                <form method="POST" action="/Implose.gg-src/actions/admin/delete_user.php" id="panel-delete-form" onsubmit="return confirmPanelDelete();">
                                    <input type="hidden" name="user_id" id="panel-delete-user-id" value="">
                                    <button type="submit" class="panel-action-btn danger">Delete Profile</button>
                                </form>
                            </div>
                        </div>

                    </div>


                    <!-- one hidden container per user, JS shows the matching one -->
                    <div class="panel-body" id="tab-activity" style="display:none;">
                        <?php foreach ($all_users as $uu) {
                            $uu_id = (int) $uu['user_id'];
                            $uu_logs = $logs_by_user[$uu_id] ?? [];
                        ?>
                            <div class="activity-container" data-activity-user-id="<?php echo $uu_id; ?>" style="display:none;">
                                <?php if (count($uu_logs) > 0) { ?>
                                    <ul class="activity-list">
                                        <?php foreach ($uu_logs as $log) { ?>
                                            <li class="activity-row">
                                                <div class="activity-icon"><img src="/Implose.gg-src/assets/images/icons/<?php echo pick_log_icon($log['action_type']); ?>" alt="event"></div>
                                                <div class="activity-body">
                                                    <span class="activity-title"><?php echo htmlspecialchars($log['action_type']); ?></span>
                                                    <span class="activity-desc"><?php echo htmlspecialchars($log['description']); ?></span>
                                                </div>
                                                <span class="activity-time"><?php echo log_time_ago($log['created_at']); ?></span>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } else { ?>
                                    <div class="panel-empty panel-empty-pad">
                                        <div class="panel-empty-icon"><img src="/Implose.gg-src/assets/images/icons/activity.svg" alt="activity"></div>
                                        <span>No activity records</span>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <div class="panel-empty panel-empty-pad" id="activity-fallback">
                            <div class="panel-empty-icon"><img src="/Implose.gg-src/assets/images/icons/activity.svg" alt="activity"></div>
                            <span>No activity records</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>
    <!-- /.admin-main-content -->


    <script>
        var selectedUserId = null;
        var selectedUserRole = null;


        // re-select the same row after redirect from an update
        document.addEventListener('DOMContentLoaded', function () {
            var savedId = sessionStorage.getItem('admin_users_selected_id');
            if (savedId) {
                var row = document.querySelector('#users-tbody tr[data-user-id="' + savedId + '"]');
                if (row) selectUser(row);
                sessionStorage.removeItem('admin_users_selected_id');
            }
        });


        function filterUsers() {
            var q = document.getElementById('users-search').value.toLowerCase();
            var status = document.getElementById('filter-status').value;
            var role = document.getElementById('filter-role').value;

            var rows = document.querySelectorAll('#users-tbody tr[data-user-id]');
            rows.forEach(function (tr) {
                var name = (tr.dataset.name || '').toLowerCase();
                var email = (tr.dataset.email || '').toLowerCase();
                var st = tr.dataset.status || '';
                var rl = tr.dataset.role || '';

                var matchQ = !q || name.includes(q) || email.includes(q);
                var matchStatus = !status || st == status;
                var matchRole = !role || rl == role;

                tr.style.display = (matchQ && matchStatus && matchRole) ? '' : 'none';
            });
        }


        function selectUser(rowEl) {
            if (!rowEl) return;
            var d = rowEl.dataset;
            selectedUserId = parseInt(d.userId);
            selectedUserRole = d.role;

            document.querySelectorAll('#users-tbody tr').forEach(function (tr) {
                tr.classList.toggle('selected', tr === rowEl);
            });

            var imgEl = document.getElementById('panel-avatar-img');
            if (d.avatar) {
                imgEl.src = d.avatar;
                imgEl.style.display = 'block';
            } else {
                imgEl.style.display = 'none';
            }

            document.getElementById('panel-name').textContent = d.name;
            document.getElementById('panel-email').textContent = d.email;
            document.getElementById('panel-joined').textContent = d.joined;

            var badge = document.getElementById('panel-status-badge');
            badge.className = 'status-badge ' + d.status;
            badge.textContent = capitalise(d.status);

            var roleSelect = document.getElementById('panel-role-select');
            roleSelect.value = d.role;
            document.getElementById('role-save-btn').style.display = 'none';

            // sync hidden user_id inputs across role/status/delete forms
            document.getElementById('role-form-user-id').value = selectedUserId;
            document.getElementById('status-form-user-id').value = selectedUserId;
            document.getElementById('panel-delete-user-id').value = selectedUserId;

            var actContainers = document.querySelectorAll('.activity-container');
            var matched = false;
            actContainers.forEach(function (ac) {
                if (parseInt(ac.dataset.activityUserId) == selectedUserId) {
                    ac.style.display = 'block';
                    matched = true;
                } else {
                    ac.style.display = 'none';
                }
            });
            var fallback = document.getElementById('activity-fallback');
            if (fallback) fallback.style.display = matched ? 'none' : 'block';

            updateStatusButtons(d.status);

            document.getElementById('panel-empty').style.display = 'none';
            document.getElementById('panel-content').style.display = 'block';
        }


        function onRoleChange() {
            var select = document.getElementById('panel-role-select');
            var saveBtn = document.getElementById('role-save-btn');
            saveBtn.style.display = (select.value != selectedUserRole) ? 'block' : 'none';
        }

        function updateStatusButtons(st) {
            var suspendBtn = document.getElementById('btn-suspend');
            var activeBtn = document.getElementById('btn-active');
            suspendBtn.className = 'panel-status-btn suspend-btn ' + (st == 'suspended' ? 'selected' : '');
            activeBtn.className = 'panel-status-btn active-btn ' + (st == 'active' ? 'selected' : '');
        }


        function switchTab(btnEl, tabId) {
            ['tab-overview', 'tab-activity'].forEach(function (id) {
                document.getElementById(id).style.display = (id == tabId) ? 'flex' : 'none';
            });
            document.querySelectorAll('.panel-tab').forEach(function (b) { b.classList.remove('active'); });
            btnEl.classList.add('active');
        }


        // save selected user id so selectUser can restore it after the redirect
        function rememberSelected() {
            if (selectedUserId) {
                sessionStorage.setItem('admin_users_selected_id', selectedUserId);
            }
        }

        function confirmPanelDelete() {
            var name = document.getElementById('panel-name').textContent;
            return confirm('Are you sure you want to delete user "' + name + '"? This action cannot be undone.');
        }

        function editSelectedUser() {
            if (selectedUserId) {
                window.location.href = '/Implose.gg-src/pages/admin/edit_user.php?id=' + selectedUserId;
            }
        }

        function capitalise(str) {
            if (!str) return '';
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    </script>

</body>
</html>
