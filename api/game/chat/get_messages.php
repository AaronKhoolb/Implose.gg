<?php
/*
Programmer Name: Max
Program Name: /api/game/chat/get_messages.php
Description: Return the last 50 global chat messages as JSON. Oldest at top, newest at bottom.
First Written on: Wednesday, 1-Jul-2026
Edited on: Wednesday, 1-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');


$user_id = $_SESSION['user_id'];

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'not_signed_in']);
    exit();
}


// grab the newest 50 messages
$sql = "SELECT c.message_id, c.sender_id, c.message_text, c.is_deleted, c.created_at, u.username
        FROM CHAT_MESSAGE_T c
        JOIN USER_T u ON u.user_id = c.sender_id
        ORDER BY c.created_at DESC
        LIMIT 50";

$result = mysqli_query($conn, $sql);

$messages = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {

        // fallback name if username is empty
        $display_name = $row['username'];
        if ($display_name == '' || $display_name == null) {
            $display_name = 'User #' . $row['sender_id'];
        }

        $messages[] = [
            'message_id'   => (int) $row['message_id'],
            'sender_id'    => (int) $row['sender_id'],
            'display_name' => $display_name,
            'message_text' => $row['message_text'],
            'is_deleted'   => (int) $row['is_deleted'],
            'created_at'   => $row['created_at'],
        ];
    }
}

// reverse so oldest is first (nicer for reading top-to-bottom)
$messages = array_reverse($messages);


echo json_encode(['messages' => $messages]);
exit();
?>
