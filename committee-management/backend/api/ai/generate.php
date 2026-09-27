<?php
require_once __DIR__ . '/../../config/database.php';
corsHeaders();

$data = json_decode(file_get_contents('php://input'), true);
$prompt = $data['prompt'] ?? '';
$model = $data['model'] ?? 'gemini-2.5-flash';

if (!$prompt) {
    http_response_code(400);
    echo json_encode([
        'error' => [
            'code' => 400,
            'message' => 'Prompt is required'
        ]
    ]);
    exit;
}

$geminiKey = defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '' 
    ? GEMINI_API_KEY 
    : (getenv('GEMINI_API_KEY') ?: '');

if (!$geminiKey) {
    http_response_code(500);
    echo json_encode([
        'error' => [
            'code' => 500,
            'message' => 'Gemini API key is not configured in .env. Please set GEMINI_API_KEY in your .env file.'
        ]
    ]);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($model) . ':generateContent?key=' . $geminiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]]
]));

$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    echo json_encode([
        'error' => [
            'code' => 502,
            'message' => 'cURL error connecting to Gemini API: ' . $curlErr
        ]
    ]);
    exit;
}

if ($httpCode) {
    http_response_code($httpCode);
}

echo $response;
?>
