<?php
// api.php – BluesMinds AI Proxy (Full Case)

// ---------- 1. Configuration ----------
// Replace this with your actual API key (or set it as an environment variable)
// For production, use environment variable: $apiKey = getenv('BLUESMINDS_API_KEY');
$apiKey = 'sk-PHwj1BwGgpQtZCqT1bu0D47Q3GipH9TS4IPzGsb9mRqNImJt';  // <-- PASTE YOUR KEY HERE

// BluesMinds endpoint (verify from your dashboard)
$apiUrl = 'https://api.bluesminds.com/v1/chat/completions';

// Model to use – adjust based on your plan
$model = 'deepseek-v4-pro'; // or 'gpt-4', 'claude-3', etc.

// System prompt – customize as you like
$systemPrompt = 'You are a helpful AI assistant. Answer clearly and concisely.';

// ---------- 2. CORS & Preflight ----------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ---------- 3. Only POST allowed ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// ---------- 4. Read and validate input ----------
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing "message" field in JSON payload.']);
    exit;
}

$userMessage = trim($data['message']);
if (strlen($userMessage) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}

// Optional: limit message length
if (strlen($userMessage) > 4096) {
    http_response_code(400);
    echo json_encode(['error' => 'Message too long (max 4096 characters).']);
    exit;
}

// ---------- 5. Build payload for BluesMinds ----------
$payload = [
    'model' => $model,
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'temperature' => 0.7,
    'max_tokens' => 1024,
    'stream' => false
];

// ---------- 6. Call BluesMinds API via cURL ----------
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// ---------- 7. Handle cURL errors ----------
if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL error: ' . $curlError]);
    exit;
}

// ---------- 8. Parse and forward API response ----------
$result = json_decode($response, true);

if ($httpCode !== 200) {
    $errorMsg = 'BluesMinds API error (HTTP ' . $httpCode . ')';
    if (isset($result['error']['message'])) {
        $errorMsg .= ': ' . $result['error']['message'];
    } elseif (isset($result['error'])) {
        $errorMsg .= ': ' . json_encode($result['error']);
    }
    http_response_code($httpCode);
    echo json_encode(['error' => $errorMsg]);
    exit;
}

// Extract the reply
$reply = $result['choices'][0]['message']['content'] ?? 'No reply from AI.';

// ---------- 9. Return success response ----------
echo json_encode(['reply' => $reply]);
