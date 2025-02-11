<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data["text"]) || empty($data["text"])) {
        echo json_encode(["success" => false, "error" => "Сообщение пустое"]);
        exit;
    }

    $text = htmlspecialchars($data["text"], ENT_QUOTES, "UTF-8");

    $message = [
        "user" => "Гость",
        "text" => $text
    ];

    $messages = json_decode(file_get_contents("messages.json"), true);
    $messages[] = $message;
    
    file_put_contents("messages.json", json_encode($messages, JSON_PRETTY_PRINT));

    echo json_encode(["success" => true]);
}
?>
