<?php
/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /includes/system_log.php
Description: system log function
First Written on: Wednesday, 26-May-2026
Edited on: Tuesday, 9-Jun-2026
*/

function add_system_log($conn, $user_id, $action_type, $description) {
    if ($user_id == null) {
        $user_id_value = "NULL";
    } else {
        $user_id_value = "'$user_id'";
    }

    $log_sql = "INSERT INTO SYSTEM_LOG_T (user_id, action_type, description, created_at) VALUES ($user_id_value, '$action_type', '$description', NOW())";

    mysqli_query($conn, $log_sql);
}
?>