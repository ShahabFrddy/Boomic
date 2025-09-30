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

$server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
$verified = isset($_POST['verified']) ? intval($_POST['verified']) : 0;

if (!$server_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server ID required']);
    exit();
}

$stmt = $pdo->prepare("UPDATE servers SET verified = ? WHERE id = ?");
if ($stmt->execute([$verified, $server_id])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'وضعیت تایید سرور به‌روزرسانی شد']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'خطا در به‌روزرسانی وضعیت']);
}
?>