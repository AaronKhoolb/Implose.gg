<?php
/*
Programmer Name: Mr. Ng Jiunn Chyn
Program Name: /actions/user/redeem_reward.php
Description: Backend action to redeem a reward
            - Reads user_id from session, reward_id from POST
            - Wraps the whole flow in a single transaction using
              SELECT ... FOR UPDATE row-level locks on USER_T and REWARD_T
              (prevents duplicate deductions / overselling under concurrency)
            - Validates: reward exists, in stock, user has enough points
            - Deducts points from USER_T, decrements stock in REWARD_T,
              creates REWARD_REDEMPTION_T record
            - Generates a deterministic claim token from redemption_id
            - Writes SYSTEM_LOG_T entry
            - On success: sets session flash and redirects back to rewards.php
            - On error: sets session error flash and redirects back
First Written on: Tuesday, 24-Jun-2026
Edited on: Wednesday, 02-Jul-2026
*/

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$redirect_url = '/Implose.gg-src/pages/user/rewards.php';

// ─────────────────────────────────────────────
// 1) Auth + input validation
// ─────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redeem_error'] = 'Please sign in to redeem rewards.';
    header('Location: ' . $redirect_url);
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_POST['reward_id']) || !is_numeric($_POST['reward_id'])) {
    $_SESSION['redeem_error'] = 'Invalid reward selected.';
    header('Location: ' . $redirect_url);
    exit();
}

$reward_id = $_POST['reward_id'];


// ─────────────────────────────────────────────
// 2) Deterministic claim-token helper
//    Format: RWD-{year}-{redemption_id padded to 6}-{4 hex}
//    The 4-hex piece is a hash of (redemption_id | user_id | reward_id)
//    so the same redemption always produces the same token.
// ─────────────────────────────────────────────
function generateClaimToken($redemption_id, $user_id, $reward_id, $redeemed_at = null) {
    $year      = $redeemed_at ? date('Y', strtotime($redeemed_at)) : date('Y');
    $id_padded = str_pad($redemption_id, 6, '0', STR_PAD_LEFT);
    $hash      = strtoupper(substr(
        hash('sha256', $redemption_id . '|implose_gg|' . $user_id . '|' . $reward_id),
        0, 4
    ));
    return "RWD-{$year}-{$id_padded}-{$hash}";
}


// ─────────────────────────────────────────────
// 3) Transactional redemption (row-locked)
// ─────────────────────────────────────────────
mysqli_autocommit($conn, false);
mysqli_begin_transaction($conn);

$error_message = null;

// (a) Lock the reward row — blocks concurrent redemptions
$reward_sql = "SELECT reward_id, title, points_required, stock_quantity FROM REWARD_T WHERE reward_id = $reward_id FOR UPDATE";
$reward_result = mysqli_query($conn, $reward_sql);

if (!$reward_result || mysqli_num_rows($reward_result) !== 1) {
    $error_message = 'Reward not found.';
}

if ($error_message === null) {
    $reward = mysqli_fetch_assoc($reward_result);

    // (b) Stock check
    $stock = $reward['stock_quantity'];
    if ($stock <= 0) {
        $error_message = 'Out of stock! This reward is no longer available.';
    }
}

if ($error_message === null) {
    // (c) Lock the user row — blocks parallel deductions on the same account
    $user_sql    = "SELECT total_points FROM USER_T WHERE user_id = $user_id FOR UPDATE";
    $user_result = mysqli_query($conn, $user_sql);

    if (!$user_result || mysqli_num_rows($user_result) !== 1) {
        $error_message = 'User account not found.';
    }
}

if ($error_message === null) {
    $user_data       = mysqli_fetch_assoc($user_result);
    $user_points     = $user_data['total_points'];
    $required_points = $reward['points_required'];

    // (d) Balance check
    if ($user_points < $required_points) {
        $error_message = 'Insufficient points! You need ' . $required_points .
                         ' points but only have ' . $user_points . '.';
    }
}

if ($error_message === null) {
    $new_points = $user_points - $required_points;

    // (e) Deduct points from USER_T
    $deduct_sql = "UPDATE USER_T SET total_points = $new_points, updated_at = NOW() WHERE user_id = $user_id";
    if (!mysqli_query($conn, $deduct_sql)) {
        $error_message = 'Failed to deduct points.';
    }
}

if ($error_message === null) {
    // (f) Deduct stock in REWARD_T (guarded WHERE for belt-and-suspenders)
    $stock_sql = "UPDATE REWARD_T SET stock_quantity = stock_quantity - 1, updated_at = NOW() WHERE reward_id = $reward_id AND stock_quantity > 0";
    if (!mysqli_query($conn, $stock_sql) || mysqli_affected_rows($conn) === 0) {
        $error_message = 'Failed to update stock.';
    }
}

if ($error_message === null) {
    // (g) Insert redemption record
    $redeem_sql = "INSERT INTO REWARD_REDEMPTION_T (user_id, reward_id, redeemed_at) VALUES ($user_id, $reward_id, NOW())";
    if (!mysqli_query($conn, $redeem_sql)) {
        $error_message = 'Failed to create redemption record.';
    }
}

if ($error_message !== null) {
    mysqli_rollback($conn);
    mysqli_autocommit($conn, true);
    header('Location: ' . $redirect_url . '?error=' . urlencode($error_message));
    exit();
}

// Commit — releases all row locks
$redemption_id = mysqli_insert_id($conn);
mysqli_commit($conn);
mysqli_autocommit($conn, true);

// (h) Generate the claim token now that we have redemption_id
$claim_token = generateClaimToken($redemption_id, $user_id, $reward_id);

// (i) Audit trail
$reward_title_esc = mysqli_real_escape_string($conn, $reward['title']);
add_system_log(
    $conn,
    $user_id,
    'Reward Redemption',
    "User redeemed reward: $reward_title_esc for $required_points points. Token: $claim_token"
);

// (j) Redirect back with success info in URL
header('Location: ' . $redirect_url . '?success=1&token=' . urlencode($claim_token) . '&title=' . urlencode($reward['title']) . '&points=' . $required_points);
exit();

?>
