<!--
Programmer Name: Reward System (JOHN)
Program Name: /actions/admin/delete_reward.php
Description: Admin action to delete a reward
            - Removes REWARD_T record
            - Deletes associated image file
            - Redirects back with flash message
First Written on: Wednesday, 25-Jun-2026
Edited on: Wednesday, 25-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$admin_id  = $_SESSION['user_id'] ?? 0;
$reward_id = (int) ($_POST['reward_id'] ?? 0);

if ($reward_id <= 0) {
    $_SESSION['reward_error'] = 'Invalid reward ID.';
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}

// ── Get reward title before deleting (for log) ──
$get_sql = "SELECT title FROM REWARD_T WHERE reward_id = $reward_id";
$get_result = mysqli_query($conn, $get_sql);
$reward_title = 'Unknown';
if ($get_result && $row = mysqli_fetch_assoc($get_result)) {
    $reward_title = $row['title'];
}


// ── Delete associated redemption records first ──
$del_redemptions_sql = "DELETE FROM REWARD_REDEMPTION_T WHERE reward_id = $reward_id";
mysqli_query($conn, $del_redemptions_sql);


// ── Delete reward record ──
$del_sql = "DELETE FROM REWARD_T WHERE reward_id = $reward_id";
$del_ok  = mysqli_query($conn, $del_sql);

if (!$del_ok) {
    $_SESSION['reward_error'] = 'Failed to delete reward: ' . mysqli_error($conn);
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}


// ── Delete image file ──
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
$target_dir = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/rewards/';

foreach ($allowed as $ext) {
    $file_path = $target_dir . $reward_id . '.' . $ext;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}


// ── Log action ──
add_system_log($conn, $admin_id, 'Admin Delete Reward', "Admin deleted reward: $reward_title (ID: $reward_id).");

$_SESSION['reward_success'] = "Reward \"$reward_title\" deleted successfully.";
header('Location: /Implose.gg-src/pages/admin/rewards.php');
exit();

?>
