<?php
require_once '../../config/database.php';
corsHeaders();

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, SUPABASE_URL . '/auth/v1/otp');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'apikey: ' . SUPABASE_ANON_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => $email,
    'create_user' => true,
    'options' => [
        'shouldCreateUser' => true
    ]
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 204) {
    echo json_encode(['success' => true, 'message' => 'OTP sent']);
} else {
    $result = json_decode($response, true);
    echo json_encode([
        'success' => false, 
        'message' => $result['msg'] ?? 'Failed to send OTP',
        'code' => $httpCode
    ]);
}
?>