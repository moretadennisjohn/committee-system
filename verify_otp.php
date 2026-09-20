<?php
require_once '/var/www/html/backend/config/database.php';
corsHeaders();

$data  = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$otp   = $data['otp']   ?? '';

if (!$email || !$otp) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP required']);
    exit;
}

$result = supabaseRequest(
    'otp_codes?email=eq.' . urlencode($email) .
    '&otp_code=eq.' . $otp .
    '&used=eq.false&order=created_at.desc&limit=1'
);

if (empty($result)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
    exit;
}

$otpRecord = $result[0];

if (strtotime($otpRecord['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'OTP has expired']);
    exit;
}

supabaseRequest('otp_codes?id=eq.' . $otpRecord['id'], 'PATCH', ['used' => true]);

$user = supabaseRequest('users?email=eq.' . urlencode($email) . '&select=*');
$userData = !empty($user) ? $user[0] : ['email' => $email];

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user'    => $userData
]);
?>