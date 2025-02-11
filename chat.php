<?php
session_start();
require 'db.php';

$messagesFile = 'messages.json';

if (!isset($_SESSION['user_id']) || !isset($_POST['server_id'])) {
    die("Ошибка: нет доступа!");
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$serverId = $_POST['server_id'];
$message = trim($_POST['message']);

if ($message === '') {
    die("Ошибка: сообщение не может быть пустым!");
}

function convertEmojis($text) {
    $emojiMap = [
        ':)'  => '😊',
        ':D'  => '😃',
        ':P'  => '😛',
        ':('  => '☹️',
        ';)'  => '😉',
        ':o'  => '😮',
        ':O'  => '😲',
        'xD'  => '😂',
        '<3'  => '❤️',
        ':|'  => '😐',
        'B)'  => '😎',
        ':/'  => '😕'
    ];

    return str_replace(array_keys($emojiMap), array_values($emojiMap), $text);
}

$safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
$safeMessage = convertEmojis($safeMessage);

$messages = readJson($messagesFile);

$newMessage = [
    "server_id" => $serverId,
    "user_id" => $userId,
    "username" => $username,
    "message" => $safeMessage, // Тут уже с эмодзи
    "timestamp" => date("Y-m-d H:i:s")
];

$messages[] = $newMessage;

writeJson($messagesFile, $messages);

echo json_encode(["success" => true]);
?>
