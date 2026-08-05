<?php
// api.php – Proxy for BluesMinds API

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing "message"']);
    exit;
}

$userMessage = trim($data['message']);
if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message empty']);
    exit;
}

// ---------- Your BluesMinds credentials ----------
// Option A: Hardcode (only for testing)
// $apiKey = 'sk-your-key-here';

// Option B: Read from environment (recommended)
$apiKey = getenv('sk-PHwj1BwGgpQtZCqT1bu0D47Q3GipH9TS4IPzGsb9mRqNImJt');
if (!$apiKey) {
    // Fallback – remove after testing
    $apiKey = 'sk-PHwj1BwGgpQtZCqT1bu0D47Q3GipH9TS4IPzGsb9mRqNImJt'; // <-- DELETE this line in production
}

$apiUrl = 'https://api.bluesminds.com/v1';
$model = 'deepseek-v4-pro'; // or 'gpt-4', etc.

$payload = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful AI assistant.'],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'temperature' => 0.7,
    'max_tokens' => 1024
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL error: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $errorMsg = $errorData['error']['message'] ?? 'Unknown API error';
    http_response_code($httpCode);
    echo json_encode(['error' => 'BluesMinds error: ' . $errorMsg]);
    exit;
}

$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? 'No reply.';
echo json_encode(['reply' => $reply]);
