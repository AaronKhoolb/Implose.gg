<!--
Programmer Name: Mr. Ng Jiun Chyn
Program Name: /actions/admin/reject_redemption.php
Description: Admin action to reject a pending reward redemption
            - Row-locks USER_T and REWARD_T inside a transaction
            - Refunds the reward's points back to the user's total_points
            - Increments REWARD_T.stock_quantity by 1
            - Records the rejection in SYSTEM_LOG_T
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
                   r.title AS reward_title, r.points_required, u.username
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


// ── Guard: already reviewed? ──
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


// ── Refund points + return stock inside a transaction ──
$user_id     = (int) $rr['user_id'];
$reward_id   = (int) $rr['reward_id'];
$refund_pts  = (int) ($rr['points_required'] ?? 0);
$error_msg   = null;

mysqli_autocommit($conn, false);
mysqli_begin_transaction($conn);

// Lock user row
$user_sql    = "SELECT total_points FROM USER_T WHERE user_id = $user_id FOR UPDATE";
$user_result = mysqli_query($conn, $user_sql);

if (!$user_result || mysqli_num_rows($user_result) !== 1) {
    $error_msg = 'User not found for refund.';
}

if ($error_msg === null) {
    // Lock reward row
    $reward_sql    = "SELECT stock_quantity FROM REWARD_T WHERE reward_id = $reward_id FOR UPDATE";
    $reward_result = mysqli_query($conn, $reward_sql);

    if (!$reward_result || mysqli_num_rows($reward_result) !== 1) {
        $error_msg = 'Reward not found for stock return.';
    }
}

if ($error_msg === null) {
    // Refund points
    $refund_sql = "UPDATE USER_T SET total_points = total_points + $refund_pts, updated_at = NOW() WHERE user_id = $user_id";
    if (!mysqli_query($conn, $refund_sql)) {
        $error_msg = 'Failed to refund points.';
    }
}

if ($error_msg === null) {
    // Return stock
    $stock_sql = "UPDATE REWARD_T SET stock_quantity = stock_quantity + 1, updated_at = NOW() WHERE reward_id = $reward_id";
    if (!mysqli_query($conn, $stock_sql)) {
        $error_msg = 'Failed to return stock.';
    }
}

if ($error_msg !== null) {
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);
    $_SESSION['reward_error'] = $error_msg;
    header('Location: /Implose.gg-src/pages/admin/rewards.php?filter=pending');
    exit();
}

mysqli_commit($conn);
mysqli_autocommit($conn, true);


// ── Record rejection in SYSTEM_LOG_T ──
$title_safe = mysqli_real_escape_string($conn, $rr['reward_title'] ?? '');
$user_safe  = mysqli_real_escape_string($conn, $rr['username']     ?? '');
$description = "redemption_id=$redemption_id|Rejected reward \"$title_safe\" for user \"$user_safe\" (refunded $refund_pts pts, returned 1 stock)";

add_system_log($conn, $admin_id, 'Reward Reject', $description);


$_SESSION['reward_success'] = "Redemption #$redemption_id rejected. Refunded $refund_pts pts and returned 1 stock.";
header('Location: /Implose.gg-src/pages/admin/rewards.php?filter=pending');
exit();

?>
