<?php
include_once 'config/functions.php';
include 'content/session.php';

// form response

// add message to chat
if(isset($_POST['message'])) {
    $stmt = $con->prepare('SELECT * FROM User WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_array();
    $stmt->close();

    add_message($con, $user['id'], $_POST['message']);
} 
// get current chat messages
else {
    $state = get_game_day($con);
    echo json_encode(get_messages($con, $state['chat_message_count']));
}