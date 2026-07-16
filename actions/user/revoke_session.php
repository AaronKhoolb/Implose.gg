<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /actions/user/revoke_session.php
Description: revoke session action
First Written on: Sunday, 21-Jun-2026
Edited on: Sunday, 21-Jun-2026
-->

<?php

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');

$user_id = $_SESSION['user_id'];
$current_session_token = $_SESSION['session_token'];
$session_id = $_POST['session_id'];

$update_session_sql = "UPDATE SESSION_RECORD_T SET is_active = '0', logout_at = NOW() WHERE session_id = '$session_id' AND user_id = '$user_id' AND session_token != '$current_session_token'";

mysqli_query($conn, $update_session_sql);

header("Location: /Implose.gg-src/pages/user/account/sessions.php?view=active_sessions");
exit();

?>