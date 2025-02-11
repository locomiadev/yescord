<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

$servers = readJson('servers.json');
$userServers = readJson('user_servers.json');

if (!array_filter($userServers, fn($s) => $s['user_id'] === $userId && $s['server_id'] === '1')) {
    $userServers[] = ['user_id' => $userId, 'server_id' => '1'];
    writeJson('user_servers.json', $userServers);
}

$joinedServers = array_filter($userServers, fn($us) => $us['user_id'] === $userId);
$serverId = $_GET['server_id'] ?? '1';

$currentServer = array_values(array_filter($servers, fn($s) => $s['id'] === $serverId))[0] ?? ['id' => '1', 'name' => 'Yescord Chat', 'owner_id' => null];

$isOwner = ($currentServer['owner_id'] ?? null) === $userId;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_server'])) {
    if (count(array_filter($servers, fn($s) => $s['owner_id'] === $userId)) >= 3) {
        $error = "Вы не можете создать больше 3 серверов!";
    } else {
        $newServerName = trim($_POST['server_name']);
        if (empty($newServerName)) {
            $error = "Введите название сервера!";
        } else {
            $newServerName = htmlspecialchars($newServerName, ENT_QUOTES, 'UTF-8');
            $newServerId = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
            
            $servers[] = ['id' => $newServerId, 'name' => $newServerName, 'owner_id' => $userId];
            $userServers[] = ['user_id' => $userId, 'server_id' => $newServerId];

            writeJson('servers.json', $servers);
            writeJson('user_servers.json', $userServers);

            header("Location: index.php?server_id=$newServerId");
            exit;
        }
    }
}

// Вход на сервер
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_server'])) {
    $inputServerId = trim($_POST['server_id']);
    if (empty($inputServerId)) {
        $error = "Введите ID сервера!";
    } elseif (!array_filter($servers, fn($s) => $s['id'] === $inputServerId)) {
        $error = "Сервер не найден!";
    } elseif (count(array_filter($userServers, fn($us) => $us['user_id'] === $userId)) >= 12) {
        $error = "Вы не можете быть в более чем 12 серверах!";
    } else {
        $userServers[] = ['user_id' => $userId, 'server_id' => $inputServerId];
        writeJson('user_servers.json', $userServers);
        header("Location: index.php?server_id=$inputServerId");
        exit;
    }
}

$serverUsernames = [];
$users = readJson('users.json');

foreach ($userServers as $us) {
    if ($us['server_id'] === $serverId) {
        $user = array_values(array_filter($users, fn($u) => $u['id'] === $us['user_id']))[0] ?? null;
        if ($user) {
            $serverUsernames[] = htmlspecialchars($user['username']);
        }
    }
}

function isUserOnline($userId) {
    $users = readJson('users.json');
    $user = array_values(array_filter($users, fn($u) => $u['id'] === $userId))[0] ?? null;

    if ($user) {
        return (time() - $user['last_active']) < 300; // 5 минут
    }

    return false;
}


updateUserActivity($userId);


?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title> Yescord </title>
    <meta charset="UTF-8">
    <meta name="description" content="Yescord - Fast, Beautiful & Secure texting service">
    <meta name="keywords" content="Discord, Yescord, Texting">  
    <meta name="author" content="makaronyevich">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <!-- icon -->
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="apple-touch-icon.png" />
    <link rel="apple-touch-icon" sizes="57x57" href="apple-touch-icon-57x57.png" />
    <link rel="apple-touch-icon" sizes="72x72" href="apple-touch-icon-72x72.png" />
    <link rel="apple-touch-icon" sizes="76x76" href="apple-touch-icon-76x76.png" />
    <link rel="apple-touch-icon" sizes="114x114" href="apple-touch-icon-114x114.png" />
    <link rel="apple-touch-icon" sizes="120x120" href="apple-touch-icon-120x120.png" />
    <link rel="apple-touch-icon" sizes="144x144" href="apple-touch-icon-144x144.png" />
    <link rel="apple-touch-icon" sizes="152x152" href="apple-touch-icon-152x152.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon-180x180.png" />
    <!-- icons -->


</head>
<script>


document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".resizable").forEach(container => {
        const resizer = document.createElement("div");
        resizer.classList.add("resizer");
        container.appendChild(resizer);

        const savedWidth = localStorage.getItem(`width_${container.classList[1]}`);
        if (savedWidth) {
            container.style.flexBasis = savedWidth + "px";
        }

        resizer.addEventListener("mousedown", function (event) {
            event.preventDefault();
            const startX = event.clientX;
            const startWidth = container.offsetWidth;

            function onMouseMove(e) {
                let newWidth = startWidth + (e.clientX - startX);
                newWidth = Math.max(150, newWidth);
                container.style.flexBasis = newWidth + "px";
                localStorage.setItem(`width_${container.classList[1]}`, newWidth);
            }

            function onMouseUp() {
                document.removeEventListener("mousemove", onMouseMove);
                document.removeEventListener("mouseup", onMouseUp);
            }

            document.addEventListener("mousemove", onMouseMove);
            document.addEventListener("mouseup", onMouseUp);
        });

        const hideButton = document.createElement("button");
        hideButton.textContent = "Скрыть";
        hideButton.classList.add("hide-button");
        container.appendChild(hideButton);

        hideButton.addEventListener("click", function () {
            container.classList.add("hidden-container");
            localStorage.setItem(`hidden_${container.classList[1]}`, "true");
            showButton.style.display = "block";
        });

        const showButton = document.createElement("button");
        showButton.textContent = "Показать";
        showButton.classList.add("show-button");
        showButton.style.display = "none";
        document.body.appendChild(showButton);

        showButton.addEventListener("click", function () {
            container.classList.remove("hidden-container");
            localStorage.removeItem(`hidden_${container.classList[1]}`);
            showButton.style.display = "none";
        });

        if (localStorage.getItem(`hidden_${container.classList[1]}`) === "true") {
            container.classList.add("hidden-container");
            showButton.style.display = "block";
        }
    });
});
</script>


<body>
    <div class="main-container">
        <div class="container servers-container resizable">

            <h3>Ваши сервера</h3>
            <?php if (!empty($error)): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <div class="server-list">
                <?php foreach ($joinedServers as $us) {
                    $server = array_values(array_filter($servers, fn($s) => $s['id'] === $us['server_id']))[0] ?? ['id' => '1', 'name' => 'Yescord Chat'];
                    echo "<button onclick=\"location.href='index.php?server_id={$server['id']}'\">{$server['name']}</button>";
                } ?>
            </div>
            <form method="POST">
                <input type="text" name="server_id" placeholder="ID сервера">
                <button type="submit" name="join_server">Войти</button>
            </form>
            <form method="POST">
                <input type="text" name="server_name" maxlength="20" placeholder="Название сервера">
                <button type="submit" name="create_server">Создать сервер</button>
            </form>
        </div>

        <div class="container chat-container resizable">

            <h2>Чат сервера <?= htmlspecialchars($currentServer['name']) ?></h2>
            <hr>
            <button onclick="copyServerId()" id="btn">Копировать ID</button>
            <?php if ($isOwner && $serverId !== '1'): ?>
            <button onclick="deleteServer()" id="btn">Удалить сервер</button>
            <?php elseif (!$isOwner && $serverId !== '1'): ?>
            <button onclick="leaveServer()" id="btn">Выйти</button>
            <?php endif; ?>

            <div id="messages" class="chat-box"></div>
            <div class="chat-input">
                <input type="text" id="messageInput" maxlength="101" placeholder="Введите сообщение...">
                <div class="emoji-picker">
    <button id="emojiButton">😀</button>
    <div id="emojiList" class="emoji-list hidden">
        <span class="emoji">😊</span>
        <span class="emoji">😃</span>
        <span class="emoji">😛</span>
        <span class="emoji">☹️</span>
        <span class="emoji">😉</span>
        <span class="emoji">😂</span>
        <span class="emoji">❤️</span>
        <span class="emoji">😮</span>
        <span class="emoji">😲</span>
        <span class="emoji">😕</span>
        <span class="emoji">😎</span>
    </div>
</div>


                <button id="sendMessage">Отправить</button>
                <p id="charWarning" style="color: red; font-size: 12px; display: none;">Вы превысили лимит в 100 символов!</p>

            </div>
            <p style="font-size: 10px; text-align: center; opacity: 0.5;">2025 (c) makaronyevich | yescord-0.12.1 | powered by
            <img src="ssnm.webp" width="88px"></p>
        </div>

        <div class="container profile-container resizable">
            <h3>Вы: <?= $username ?></h3>
            <a href="logout.php">Выйти</a>
            <div class="server-users">
    <h3>Люди на сервере</h3>
    <ul>
        <?php foreach ($serverUsernames as $userId => $username): ?>
            <?php $isOnline = isUserOnline($userId); ?>
            <li>
                <span class="status-circle <?= $isOnline ? 'online' : 'offline' ?>"></span>
                <?= htmlspecialchars($username) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<button id="toggle-theme">🌙 Переключить тему</button>

        </div>
    </div>

    <script>
function scrollToBottom() {
    let messagesDiv = document.getElementById("messages");
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

window.onload = () => {
    scrollToBottom();
    loadMessages();
};


        function copyServerId() {
            navigator.clipboard.writeText("<?= $serverId ?>").then(() => {
                alert("ID сервера скопирован!");
            });
        }

function loadMessages() {
    let messagesDiv = document.getElementById("messages");
    let isAtBottom = messagesDiv.scrollTop + messagesDiv.clientHeight >= messagesDiv.scrollHeight - 10;

    return fetch(`load_messages.php?server_id=<?= $serverId ?>`)
        .then(response => response.json())
        .then(messages => {
            messagesDiv.innerHTML = "";
            messages.forEach(msg => {
                let safeMessage = msg.message.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                messagesDiv.innerHTML += `
                    <p>
                        <small style="display: block; font-size: 10px; opacity: 0.7;">${msg.timestamp}</small>
                        <strong>${msg.username}:</strong> ${safeMessage}
                    </p>`;
            });

            if (isAtBottom) {
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }
        });
}



document.getElementById("sendMessage").addEventListener("click", () => {
    let messageInput = document.getElementById("messageInput");
    let message = messageInput.value.trim();
    let now = Date.now();

    if (message === "" || message.length > 100 || now - lastMessageTime < 400) return;

    lastMessageTime = now;

    fetch("chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `server_id=<?= $serverId ?>&message=${encodeURIComponent(message)}`
    }).then(() => {
        messageInput.value = "";
        loadMessages();
    });
});

        

        loadMessages();
        setInterval(() => {
    let messagesDiv = document.getElementById("messages");
    let isAtBottom = messagesDiv.scrollTop + messagesDiv.clientHeight >= messagesDiv.scrollHeight - 10;
    
    loadMessages().then(() => {
        if (isAtBottom) {
            scrollToBottom();
        }
    });
}, 1000);



document.getElementById("messageInput").addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        event.preventDefault();
        document.getElementById("sendMessage").click();
    }
});

function deleteServer() {
    if (confirm("Вы уверены, что хотите удалить сервер?")) {
        fetch("server_actions.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=delete&server_id=<?= $serverId ?>"
        }).then(() => {
            window.location.href = "index.php";
        });
    }
}

function leaveServer() {
    if (confirm("Вы уверены, что хотите выйти с сервера?")) {
        fetch("server_actions.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=leave&server_id=<?= $serverId ?>"
        }).then(() => {
            window.location.href = "index.php";
        });
    }
}


// UnderMaintenance from JSYesc
const siteUnderMaintenance = false;
        if (siteUnderMaintenance) {
            window.location.href = "making.html";
        }

        let lastMessageTime = 0;

document.getElementById("messageInput").addEventListener("input", function () {
    let message = this.value;
    let sendButton = document.getElementById("sendMessage");
    let warning = document.getElementById("charWarning");

    if (message.length > 100) {
        sendButton.disabled = true;
        warning.style.display = "block";
    } else {
        sendButton.disabled = false;
        warning.style.display = "none";
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const emojiButton = document.getElementById("emojiButton");
    const emojiList = document.getElementById("emojiList");
    const messageInput = document.getElementById("messageInput");

    document.querySelectorAll(".emoji").forEach(emoji => {
        emoji.addEventListener("click", function () {
            messageInput.value += ` ${this.innerText} `;
            emojiList.classList.add("hidden");
        });
    });

    emojiButton.onclick = function () {
        emojiList.classList.toggle("hidden");
    };

    document.addEventListener("click", function (event) {
        if (!emojiButton.contains(event.target) && !emojiList.contains(event.target)) {
            emojiList.classList.add("hidden");
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const themeButton = document.getElementById("toggle-theme");
    const body = document.body;

    // Проверяем, есть ли сохранённая тема
    if (localStorage.getItem("theme") === "dark") {
        body.classList.add("dark-theme");
    }

    themeButton.addEventListener("click", function () {
        body.classList.toggle("dark-theme");

        // Сохраняем выбор темы в localStorage
        if (body.classList.contains("dark-theme")) {
            localStorage.setItem("theme", "dark");
        } else {
            localStorage.setItem("theme", "light");
        }
    });
});

document.getElementById("toggle-servers").addEventListener("click", function () {
    document.getElementById("servers-list").classList.toggle("open");
});

document.getElementById("toggle-profile").addEventListener("click", function () {
    document.getElementById("profile").classList.toggle("open");
});



    </script>
</body>
</html>
