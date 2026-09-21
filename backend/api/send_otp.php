<?php
require_once '/var/www/html/backend/config/database.php';
require_once '/var/www/html/backend/phpmailer/PHPMailer.php';
require_once '/var/www/html/backend/phpmailer/SMTP.php';
require_once '/var/www/html/backend/phpmailer/Exception.php';
corsHeaders();

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

$user = supabaseRequest('users?email=eq.' . urlencode($email) . '&select=*');

if (empty($user)) {
    echo json_encode(['success' => false, 'message' => 'Email not registered in system']);
    exit;
}

$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

supabaseRequest('otp_codes', 'POST', [
    'email'      => $email,
    'otp_code'   => $otp,
    'expires_at' => $expires,
    'used'       => false
]);

try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'moretadennisjohn@gmail.com';
    $mail->Password   = 'mhnodyqxtfufocbf';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->setFrom('moretadennisjohn@gmail.com', 'SK Committee System');
    $mail->addAddress($email);
    $mail->Subject = 'Your OTP Code - SK Committee System';
    $mail->Body    = 'Your OTP code is: ' . $otp . "\n\nThis code expires in 10 minutes.\n\nSK Committee Management System";
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Email failed: ' . $e->getMessage()]);
}
?>