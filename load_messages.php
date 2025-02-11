<?php
session_start();
require 'db.php';

$messagesFile = 'messages.json';

if (!isset($_GET['server_id'])) {
    die(json_encode([]));
}

$serverId = $_GET['server_id'];
$messages = readJson($messagesFile);

$serverMessages = array_filter($messages, fn($msg) => $msg['server_id'] === $serverId);

echo json_encode(array_values($serverMessages));


?>
