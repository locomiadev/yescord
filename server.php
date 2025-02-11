<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Вы не авторизованы!");
}

$userId = $_SESSION['user_id'];
$servers = readJson('servers.json');
$userServers = readJson('user_servers.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_server'])) {
        $serverId = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
        $serverName = trim($_POST['server_name']);

        $servers[] = ['id' => $serverId, 'name' => $serverName, 'owner_id' => $userId];
        $userServers[] = ['user_id' => $userId, 'server_id' => $serverId];

        writeJson('servers.json', $servers);
        writeJson('user_servers.json', $userServers);

        header('Location: index.php');
        exit;
    }
}
?>
<form method="POST">
    <input type="text" name="server_name" placeholder="Название сервера" required>
    <button type="submit" name="create_server">Создать сервер</button>
</form>
