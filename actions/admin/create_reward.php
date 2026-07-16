<?php
/*
Programmer Name: Mr. Ng Jiunn Chyn
Program Name: /actions/admin/create_reward.php
Description: Create a new reward from the admin panel
            - POST-only; validates all inputs
            - Optional image upload to uploads/rewards/{reward_id}.{ext}
            - Inserts into REWARD_T with created_by / updated_by
            - Logs the action to SYSTEM_LOG_T
            - On error: preserves form input + flash back to create_reward.php
            - On success: flash to rewards.php list
First Written on: Wednesday, 25-Jun-2026
Edited on: Wednesday, 02-Jul-2026
*/

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');


// only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /Implose.gg-src/pages/admin/rewards.php');
    exit();
}


$title           = trim($_POST['title'] ?? '');
$description     = trim($_POST['description'] ?? '');
$points_required = ($_POST['points_required'] ?? 0);
$stock_quantity  = ($_POST['stock_quantity'] ?? 0);


// redirect URL (back to the create page)
$redirect_url = '/Implose.gg-src/pages/admin/create_reward.php';


// helper: preserve inputs + set error + redirect back
function redirect_with_error($msg) {
    global $redirect_url, $title, $description, $points_required, $stock_quantity;
    $_SESSION['create_reward_error'] = $msg;
    $form = array();
    $form['title']           = $title;
    $form['description']     = $description;
    $form['points_required'] = $points_required > 0 ? $points_required : '';
    $form['stock_quantity']  = $stock_quantity  > 0 ? $stock_quantity  : '';
    $_SESSION['create_reward_form'] = $form;
    header('Location: ' . $redirect_url);
    exit();
}


// ─────────────────────────────────────────────
// Validation
// ─────────────────────────────────────────────
if (strlen($title) < 2 || strlen($title) > 255) {
    redirect_with_error('Reward title must be 2-255 characters.');
}

if ($points_required <= 0) {
    redirect_with_error('Points required must be greater than 0.');
}

if ($stock_quantity < 0) {
    redirect_with_error('Stock quantity cannot be negative.');
}


// validate image upload (optional)
$has_image = false;
$file      = null;

if (isset($_FILES['reward_image']) && $_FILES['reward_image']['error'] === UPLOAD_ERR_OK) {
    $has_image = true;
    $file      = $_FILES['reward_image'];

    $allowed_types = array('image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/avif');
    if (!in_array($file['type'], $allowed_types)) {
        redirect_with_error('Invalid image type. Please use PNG, JPG, WEBP, GIF or AVIF.');
    }
} elseif (isset($_FILES['reward_image']) && $_FILES['reward_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    redirect_with_error('Error uploading reward image.');
}


// ─────────────────────────────────────────────
// Insert into REWARD_T
// ─────────────────────────────────────────────
$admin_id  = ($_SESSION['user_id'] ?? 0);
$title_esc = mysqli_real_escape_string($conn, $title);
$desc_esc  = mysqli_real_escape_string($conn, $description);

$insert_sql = "INSERT INTO REWARD_T
                    (title, description, points_required, stock_quantity,
                     created_at, created_by, updated_at, updated_by)
               VALUES
                    ('$title_esc', '$desc_esc', $points_required, $stock_quantity,
                     NOW(), $admin_id, NOW(), $admin_id)";

if (!mysqli_query($conn, $insert_sql)) {
    redirect_with_error('Failed to create reward. Please try again.');
}

$new_reward_id = mysqli_insert_id($conn);


// ─────────────────────────────────────────────
// Upload image if provided
// ─────────────────────────────────────────────
if ($has_image) {
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $new_reward_id . '.' . $ext;

    $target_dir  = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/uploads/rewards/';
    $target_path = $target_dir . $filename;

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        chmod($target_path, 0777);
    }
}


// ─────────────────────────────────────────────
// Log + success flash
// ─────────────────────────────────────────────
add_system_log($conn, $admin_id, 'Admin Create Reward', "Admin created reward #$new_reward_id ($title).");

$_SESSION['reward_success'] = "Reward \"$title\" created successfully.";
header('Location: /Implose.gg-src/pages/admin/rewards.php');
exit();

?>
