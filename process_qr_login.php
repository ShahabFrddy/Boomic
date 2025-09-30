<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// دریافت داده‌های JSON
$input = json_decode(file_get_contents('php://input'), true);
$qrToken = $input['qr_token'] ?? '';
$userId = $input['user_id'] ?? '';

if (empty($qrToken) || empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'داده‌های ناقص']);
    exit();
}

try {
    // ایجاد session برای کاربر
    $sessionToken = generateToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, device_type, expires_at) VALUES (?, ?, 'mobile', ?)");
    $stmt->execute([$userId, $sessionToken, $expiresAt]);
    
    // آپدیت توکن QR برای شناسایی توسط دسکتاپ
    $stmt = $pdo->prepare("UPDATE user_sessions SET session_token = ? WHERE session_token = ?");
    $stmt->execute([$sessionToken, $qrToken]);
    
    echo json_encode(['success' => true, 'message' => 'Session created successfully']);
    
} catch (Exception $e) {
    error_log("QR Login Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'خطای سرور']);
}
?>