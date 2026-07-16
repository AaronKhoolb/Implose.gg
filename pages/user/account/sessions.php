<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/account/sessions.php
Description: user account center - manage sessions page
First Written on: Friday, 19-Jun-2026
Edited on: Friday, 19-Jun-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

        // Browser and device name displayed
        function session_browser_name($user_agent) {
            if (stripos($user_agent, 'Edg') !== false) {
                return 'Microsoft Edge';
            }

            if (stripos($user_agent, 'Chrome') !== false) {
                return 'Google Chrome';
            }

            if (stripos($user_agent, 'Safari') !== false) {
                return 'Safari';
            }

            if (stripos($user_agent, 'Firefox') !== false) {
                return 'Firefox';
            }

            return 'Unknown Browser';
        }

        function session_device_name($user_agent) {
            if (stripos($user_agent, 'Android') !== false) {
                return 'Android';
            }

            if (stripos($user_agent, 'iPhone') !== false || stripos($user_agent, 'iPad') !== false) {
                return 'iOS';
            }

            if (stripos($user_agent, 'Mac') !== false) {
                return 'macOS';
            }

            if (stripos($user_agent, 'Windows') !== false) {
                return 'Windows';
            }

            return 'Unknown Device';
        }

        function session_device_label($user_agent) {
            return session_browser_name($user_agent) . ' on ' . session_device_name($user_agent);
        }

        // Date and status display
        function session_format_date($date_value) {
            return date('j M Y, g:i A', strtotime($date_value));
        }

        function session_record_status($session) {
            if (!empty($session['logout_at'])) {
                return 'Logged Out';
            }

            if ($session['expires_at'] < date('Y-m-d H:i:s')) {
                return 'Expired';
            }
        }

        // Current session
        $user_id = $_SESSION['user_id'];
        $current_session_token = $_SESSION['session_token'];
        $current_session_sql = "SELECT * FROM SESSION_RECORD_T WHERE user_id = '$user_id' AND session_token = '$current_session_token' LIMIT 1";
        $current_session_result = mysqli_query($conn, $current_session_sql);
        $current_session = mysqli_fetch_assoc($current_session_result);

        // Other active sessions
        $active_sessions_sql = "SELECT * FROM SESSION_RECORD_T WHERE user_id = '$user_id' AND session_token != '$current_session_token' AND is_active = '1' AND expires_at >= NOW() ORDER BY login_at DESC";
        $active_sessions_result = mysqli_query($conn, $active_sessions_sql);

        $active_sessions = [];
        
        while ($session_row = mysqli_fetch_assoc($active_sessions_result)) {
            $active_sessions[] = $session_row;
        }

        $session_history = [];

        // Expired and logged out sessions
        $session_history_sql = "SELECT * FROM SESSION_RECORD_T WHERE user_id = '$user_id' AND session_token != '$current_session_token' AND (is_active != '1' OR logout_at IS NOT NULL OR expires_at < NOW()) ORDER BY login_at DESC";
        $session_history_result = mysqli_query($conn, $session_history_sql);

        while ($session_row = mysqli_fetch_assoc($session_history_result)) {
            $session_history[] = $session_row;
        }
    ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/complete_profile.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_account.css">
    <title>Account Center - Sessions</title>
</head>


<body>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">

        <!-- Top -->
        <div class="account-top">
            <span class="account-title pixel-title">Account Center</span>
            <span class="account-description">Manage your account settings</span>

            <hr>
        </div>

        <!-- Body -->
        <!-- Left nav -->
        <div class="account-body">
            <?php
                $current_page = 'sessions';
                include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/account/nav.php');
            ?>


            <!-- Right content -->
            <div class="account-right">
                <div class="profile_setup-container acc-session-container pixel-panel">

                    <div class="profile_setup-header">
                        <span class="title pixel-title">Manage Sessions</span>

                        <span class="subtitle">Overview and control your active sessions</span>
                    </div>

                    <div class="session-content">

                        <!-- Current Session -->
                        <div class="session-part">
                            <div class="session-part-header">
                                <div class="session-part-title">Current Session</div>
                            </div>

                            <div class="current-session-card">
                                <div class="current-session-item">
                                    <span class="item-label">Device</span>
                                    <span class="item-value"><?php echo session_device_label($current_session['user_agent']); ?></span>
                                </div>

                                <div class="current-session-item">
                                    <span class="item-label">IP Address</span>
                                    <span class="item-value"><?php echo $current_session['ip_address']; ?></span>
                                </div>

                                <div class="current-session-item">
                                    <span class="item-label">Login Time</span>
                                    <span class="item-value"><?php echo session_format_date($current_session['login_at']); ?></span>
                                </div>

                                <div class="current-session-item">
                                    <span class="item-label">Expires At</span>
                                    <span class="item-value"><?php echo session_format_date($current_session['expires_at']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Session Records -->
                        <div class="session-part">
                            <div class="session-part-header">
                                <div class="session-part-title">Session Records</div>

                                <div class="session-switch">
                                    <a href="/Implose.gg-src/pages/user/account/sessions.php?view=active_sessions" class="<?php if ($_GET['view'] == 'active_sessions') { echo 'active'; } ?>">
                                        Active Sessions
                                    </a>

                                    <a href="/Implose.gg-src/pages/user/account/sessions.php?view=session_history" class="<?php if ($_GET['view'] == 'session_history') { echo 'active'; } ?>">
                                        Session History
                                    </a>
                                </div>
                            </div>

                            <div class="session-table-panel">
                                <table class="session-table">
                                    <?php
                                        if ($_GET['view'] == 'active_sessions') {
                                    ?>

                                        <!-- Active Sessions Table -->
                                        <thead>
                                            <tr>
                                                <th>Device</th>
                                                <th>IP Address</th>
                                                <th>Login Time</th>
                                                <th>Expires At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php 
                                                if (count($active_sessions) === 0) {
                                            ?>
                                                <tr>
                                                    <td colspan="5">No other active sessions found.</td>
                                                </tr>
                                            <?php
                                                } else {
                                                    foreach ($active_sessions as $session) {
                                            ?>
                                                    <tr>
                                                        <td class="device-cell">
                                                            <?php echo session_device_label($session['user_agent']); ?>
                                                        </td>
                                                        
                                                        <td>
                                                            <?php echo $session['ip_address']; ?>
                                                        </td>
                                                        
                                                        <td>
                                                            <?php echo session_format_date($session['login_at']); ?>
                                                        </td>
                                                        
                                                        <td><?php echo session_format_date($session['expires_at']); ?></td>
                                                        
                                                        <td>
                                                            <form action="/Implose.gg-src/actions/user/revoke_session.php" method="post">
                                                                <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">

                                                                <button type="submit" class="session-action-btn btn-pixel">
                                                                    Revoke
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php
                                                    }
                                                ?>
                                            <?php
                                                }
                                            ?>
                                        </tbody>


                                    <?php
                                        } else {
                                    ?>
                                        <!-- Session History Table -->
                                        <thead>
                                            <tr>
                                                <th>Device</th>
                                                <th>IP Address</th>
                                                <th>Login Time</th>
                                                <th>End Time</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                                if (count($session_history) === 0) {
                                            ?>
                                                <tr>
                                                    <td colspan="5">No session history found.</td>
                                                </tr>
                                            <?php
                                                } else {
                                                    foreach ($session_history as $session) {
                                                        $session_status = session_record_status($session);
                                                        $end_time = $session_status === 'Expired' ? $session['expires_at'] : $session['logout_at'];
                                            ?>
                                                    <tr>
                                                        <td class="device-cell">
                                                            <?php echo session_device_label($session['user_agent']); ?>
                                                        </td>

                                                        <td>
                                                            <?php echo $session['ip_address']; ?>
                                                        </td>

                                                        <td>
                                                            <?php echo session_format_date($session['login_at']); ?>
                                                        </td>

                                                        <td>
                                                            <?php echo session_format_date($end_time); ?>
                                                        </td>

                                                        <td>
                                                            <span class="session-status-badge">
                                                                <?php echo $session_status; ?>
                                                            </span>
                                                        </td>
                                                    </tr>

                                                <?php
                                                    }
                                                ?>
                                            <?php
                                                }
                                            ?>
                                        </tbody>


                                    <?php
                                        }
                                    ?>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
