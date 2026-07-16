<?php
/*
Programmer Name: Max
Program Name: /actions/user/chat/send_message.php
Description: Save a new global chat message to CHAT_MESSAGE_T. POST message_text, replies "ok" or an error.
First Written on: Wednesday, 1-Jul-2026
Edited on: Wednesday, 1-Jul-2026
*/

session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

// set MySQL timezone to Malaysia so NOW() returns the correct local time
mysqli_query($conn, "SET time_zone = '+08:00'");

header('Content-Type: text/plain; charset=utf-8');


$user_id = $_SESSION['user_id'];

if (!$user_id) {
    http_response_code(401);
    echo 'Not signed in.';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo 'POST required.';
    exit();
}

$message_text = trim($_POST['message_text'] ?? '');

if ($message_text == '') {
    http_response_code(400);
    echo 'Message cannot be empty.';
    exit();
}

if (strlen($message_text) > 500) {
    http_response_code(400);
    echo 'Message too long (max 500 characters).';
    exit();
}


// bad-word filter - block messages containing any of these words
$banned_words = ['porn', 'gay', 'lesbian', 'cb', 'cibai', 'lj', 'lanjiao', 'fuck', 'shit', 'bitch', 'dick', 'cunt', 'asshole', 'bastard', 'nigga', 'nigger', 'faggot', 'gooning', 'goon'];

foreach ($banned_words as $word) {
    if (stripos($message_text, $word) !== false) {
        http_response_code(400);
        echo 'Please keep the chat clean. No bad words allowed.';
        exit();
    }
}


$message_text = mysqli_real_escape_string($conn, $message_text);


// save the message
$sql = "INSERT INTO CHAT_MESSAGE_T (sender_id, message_text, is_deleted, created_at) VALUES ('$user_id', '$message_text', 0, NOW())";

if (!mysqli_query($conn, $sql)) {
    http_response_code(500);
    echo 'Could not save the message.';
    exit();
}

echo 'ok';
exit();
?>
