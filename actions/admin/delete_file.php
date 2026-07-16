<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/admin/delete_file.php
Description: admin action to delete a pdf file
First Written on: Tuesday, 30-Jun-2026
Edited on: Saturday, 4-Jul-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$admin_id = $_SESSION['user_id'];
$folder   = $_GET['folder'];
$file_name     = $_GET['name'];


// folder to path
$paths = array(
    'logs'    => 'uploads/logs',
    'reports' => 'uploads/reports'
);


// delete the file
$file_path = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/' . $paths[$folder] . '/' . $file_name;
unlink($file_path);


// log the action
add_system_log($conn, $admin_id, 'Admin Delete File', "Admin deleted file: $file_name from $folder.");


header('Location: /Implose.gg-src/pages/admin/file_management.php?folder=' . $folder);
exit();

?>
