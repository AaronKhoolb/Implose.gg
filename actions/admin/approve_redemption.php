<!--
Programmer Name: Mr. Ng Jiun Chyn
Program Name: /actions/admin/approve_redemption.php
Description: Admin action to approve a pending reward redemption
            - Verifies redemption exists and is not already reviewed
            - Records the approval in SYSTEM_LOG_T
              (description prefixed with "redemption_id=<id>|" so
               rewards.php can look up the latest decision per redemption)
            - Redirects back to rewards.php with flash message
First Written on: Sunday, 05-Jul-2026
Edited on: Sunday, 05-Jul-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$admin_id      = $_SESSION['user_id'] ?? 0;
$redemption_id = (int) ($_POST['redemption_id'] ?? 0);

if ($admin_id <= 0) {
    $_SESSION['reward_error'] = 'Not authorised.';
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}

if ($redemption_id <= 0) {
    $_SESSION['reward_error'] = 'Invalid redemption ID.';
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}


// ── Load redemption record ──
$get_sql = "SELECT rr.redemption_id, rr.user_id, rr.reward_id, rr.redeemed_at,
                   r.title AS reward_title, u.username
            FROM REWARD_REDEMPTION_T rr
            LEFT JOIN REWARD_T r ON rr.reward_id = r.reward_id
            LEFT JOIN USER_T   u ON rr.user_id   = u.user_id
            WHERE rr.redemption_id = $redemption_id";
$get_result = mysqli_query($conn, $get_sql);

if (!$get_result || mysqli_num_rows($get_result) !== 1) {
    $_SESSION['reward_error'] = 'Redemption not found.';
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}
$rr = mysqli_fetch_assoc($get_result);


// ── Guard: already reviewed? (check SYSTEM_LOG_T for a prior decision) ──
$check_sql = "SELECT log_id FROM SYSTEM_LOG_T
              WHERE action_type IN ('Reward Approve', 'Reward Reject')
                AND description LIKE 'redemption_id=$redemption_id|%'
              LIMIT 1";
$check_result = mysqli_query($conn, $check_sql);
if ($check_result && mysqli_num_rows($check_result) > 0) {
    $_SESSION['reward_error'] = 'Redemption has already been reviewed.';
    header('Location: /Implose.gg-src/pages/admin/rewards.php?filter=pending');
    exit();
}


// ── Record approval in SYSTEM_LOG_T ──
$title_safe = mysqli_real_escape_string($conn, $rr['reward_title'] ?? '');
$user_safe  = mysqli_real_escape_string($conn, $rr['username']     ?? '');
$description = "redemption_id=$redemption_id|Approved reward \"$title_safe\" for user \"$user_safe\"";

add_system_log($conn, $admin_id, 'Reward Approve', $description);


$_SESSION['reward_success'] = "Redemption #$redemption_id approved.";
header('Location: /Implose.gg-src/pages/admin/rewards.php?filter=pending');
exit();

?>
