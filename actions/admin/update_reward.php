<!--
Programmer Name: Reward System (JOHN)
Program Name: /actions/admin/update_reward.php
Description: Admin action to update an existing reward
            - Validates inputs
            - Updates REWARD_T record with updated_by
            - Replaces image if new one is uploaded
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

// ── Validate inputs ──
$title           = trim($_POST['title'] ?? '');
$description     = trim($_POST['description'] ?? '');
$points_required = (int) ($_POST['points_required'] ?? 0);
$stock_quantity  = (int) ($_POST['stock_quantity'] ?? 0);

if (empty($title)) {
    $_SESSION['reward_error'] = 'Reward title is required.';
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}

if ($points_required <= 0) {
    $_SESSION['reward_error'] = 'Points required must be greater than 0.';
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}

if ($stock_quantity < 0) {
    $_SESSION['reward_error'] = 'Stock quantity cannot be negative.';
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}


// ── Escape strings ──
$title_esc = mysqli_real_escape_string($conn, $title);
$desc_esc  = mysqli_real_escape_string($conn, $description);


// ── Update reward ──
$update_sql = "UPDATE REWARD_T 
               SET title = '$title_esc', 
                   description = '$desc_esc', 
                   points_required = $points_required, 
                   stock_quantity = $stock_quantity, 
                   updated_at = NOW(), 
                   updated_by = $admin_id 
               WHERE reward_id = $reward_id";

$update_ok = mysqli_query($conn, $update_sql);

if (!$update_ok) {
    $_SESSION['reward_error'] = 'Failed to update reward: ' . mysqli_error($conn);
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}


// ── Upload new image (if provided) ──
if (isset($_FILES['reward_image']) && $_FILES['reward_image']['error'] === 0) {
    $file = $_FILES['reward_image'];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
    if (in_array($ext, $allowed)) {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/rewards/';

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Remove old images for this reward (different extensions)
        foreach ($allowed as $old_ext) {
            $old_file = $target_dir . $reward_id . '.' . $old_ext;
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }

        $filename    = $reward_id . '.' . $ext;
        $target_path = $target_dir . $filename;

        move_uploaded_file($file['tmp_name'], $target_path);
        chmod($target_path, 0777);
    }
}


// ── Log action ──
add_system_log($conn, $admin_id, 'Admin Update Reward', "Admin updated reward: $title (ID: $reward_id).");

$_SESSION['reward_success'] = "Reward \"$title\" updated successfully!";
header('Location: /Implose.gg-src/pages/admin/rewards.php');
exit();

?>
