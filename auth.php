<?php
session_start();
require 'db.php';

$usersFile = 'users.json';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
    $password = trim($_POST['password']);

    if ($username === '' || $password === '') {
        $error = "Заполните все поля!";
    } elseif (mb_strlen($username) > 15) {
        $error = "Ошибка: Ник не может быть длиннее 15 символов.";
    } else {
        $users = readJson($usersFile);
        
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $error = "Такой пользователь уже существует!";
                break;
            }
        }

        if (!isset($error)) {
            $userId = uniqid();
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $userIp = $_SERVER['REMOTE_ADDR'];

            $users[] = [
                "id" => $userId,
                "username" => $username,
                "password" => $hashedPassword,
                "ip" => $userIp 
            ];
            writeJson($usersFile, $users);

            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;

            header("Location: index.php");
            exit;
        }
    }
}


// Вход
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $users = readJson($usersFile);

    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;

            header("Location: index.php");
            exit;
        }
    }

    $error = "Неверные данные!";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Авторизация</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit" name="login">Войти</button>
            <button type="submit" name="register">Зарегистрироваться</button>
        </form>
        <a href="license.html">Регистрируя аккаунт на сервисе Yescord вы автоматически соглашаетесь с лицензией YESCORD</a>
        <p style="font-size: 10px; text-align: center; opacity: 0.5;">2025 (c) makaronyevich | yescord-0.12.1 | powered by
        <img src="ssnm.webp" width="88px"></p>
    </div>
</body>
</html>
