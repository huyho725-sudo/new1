<?php

$configPath = __DIR__ . '/config.ini';
$config = parse_ini_file($configPath);

header('Content-Type: application/json');

if ($config === false) {
    http_response_code(500);
    echo json_encode(['error' => 'không đọc được config']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'chỉ support POST method']);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'payload không hợp lệ']);
    exit;
}

$messageId = $payload['messageId'] ?? null;
$chatId = $payload['chatId'] ?? null;

if (!$messageId) {
    http_response_code(400);
    echo json_encode(['error' => 'thiếu messageId']);
    exit;
}

$targetChatId = $chatId ?: $config['chat_id'];

$telegramResponse = callTelegramApi('deleteMessage', [
    'chat_id' => $targetChatId,
    'message_id' => $messageId,
], $config['token']);

if (!$telegramResponse['ok']) {
    http_response_code(500);
    echo json_encode(['error' => 'lỗi xóa message', 'details' => $telegramResponse['description'] ?? 'unknown']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'xóa message thành công',
]);

function callTelegramApi(string $method, array $payload, string $token): array
{
    $url = sprintf('https://api.telegram.org/bot%s/%s', $token, $method);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return [
            'ok' => false,
            'description' => $error ?: 'curl error',
            'status' => $status,
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            'ok' => false,
            'description' => 'invalid json response',
            'status' => $status,
        ];
    }

    return $decoded;
}

