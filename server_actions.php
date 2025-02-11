<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "Ошибка: не авторизован!";
    exit;
}

$userId = $_SESSION['user_id'];
$servers = readJson('servers.json');
$userServers = readJson('user_servers.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['server_id'])) {
    $serverId = $_POST['server_id'];

    if ($_POST['action'] === 'leave') {
        $serverExists = array_filter($servers, fn($s) => $s['id'] === $serverId);
        if (!$serverExists) {
            echo "Ошибка: сервер не найден!";
            exit;
        }

        $isOwner = array_filter($servers, fn($s) => $s['id'] === $serverId && $s['owner_id'] === $userId);
        if ($isOwner) {
            echo "Ошибка: владелец не может выйти, только удалить сервер!";
            exit;
        }

        $userServers = array_filter($userServers, fn($us) => !($us['user_id'] === $userId && $us['server_id'] === $serverId));
        writeJson('user_servers.json', $userServers);
        echo "success";
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $serverIndex = array_search($serverId, array_column($servers, 'id'));
        if ($serverIndex === false || $servers[$serverIndex]['owner_id'] !== $userId) {
            echo "Ошибка: у вас нет прав на удаление!";
            exit;
        }

        unset($servers[$serverIndex]);
        $userServers = array_filter($userServers, fn($us) => $us['server_id'] !== $serverId);

        writeJson('servers.json', array_values($servers));
        writeJson('user_servers.json', array_values($userServers));
        echo "success";
        exit;
    }
}

echo "Ошибка: неверный запрос!";
exit;
?>
