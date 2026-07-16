<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/admin/update_ai_settings.php
Description: save AI settings backend
First Written on: Thursday, 02-Jul-2026
Edited on: Saturday, 04-Jul-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$admin_id = $_SESSION['user_id'];

$api_endpoint = $_POST['api_endpoint'];
$api_key = $_POST['api_key'];
$model = $_POST['model'];

$update_sql = "UPDATE AI_SETTING_T SET api_endpoint = '$api_endpoint',api_key = '$api_key',model = '$model',updated_by = '$admin_id',updated_at = NOW() WHERE ai_setting_id = 1";

mysqli_query($conn, $update_sql);

header("Location: /Implose.gg-src/pages/admin/ai_settings.php");
exit();

?>
