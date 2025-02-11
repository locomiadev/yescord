<?php
function readJson($file) {
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

function writeJson($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function updateUserActivity($userId) {
    $users = readJson('users.json');

    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['last_active'] = time();
        }
    }

    writeJson('users.json', $users);
}

?>
