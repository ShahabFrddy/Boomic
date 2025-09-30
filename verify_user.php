<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// فقط کاربران خاص (مثلاً ادمین) می‌توانند تایید کنند
$admin_user_id = 1; // ID کاربر ادمین
$current_user_id = $_SESSION['user_id'];

if ($current_user_id != $admin_user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$verified = isset($_POST['verified']) ? intval($_POST['verified']) : 0;

if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit();
}

$stmt = $pdo->prepare("UPDATE users SET verified = ? WHERE id = ?");
if ($stmt->execute([$verified, $user_id])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'وضعیت تایید کاربر به‌روزرسانی شد']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'خطا در به‌روزرسانی وضعیت']);
}
?>