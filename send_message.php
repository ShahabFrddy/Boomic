<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $channel_id = $_POST['channel_id'];
    $message = trim($_POST['message']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($message)) {
        header('Location: index.php');
        exit();
    }
    
    // بررسی دسترسی کاربر به کانال
    $stmt = $pdo->prepare("
        SELECT c.* FROM channels c
        JOIN servers s ON c.server_id = s.id
        LEFT JOIN server_members sm ON s.id = sm.server_id AND sm.user_id = ?
        WHERE c.id = ? AND (s.owner_id = ? OR sm.user_id IS NOT NULL)
    ");
    $stmt->execute([$user_id, $channel_id, $user_id]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO messages (channel_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$channel_id, $user_id, $message]);
        
        // پاسخ JSON برای درخواست‌های AJAX
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();
        }
    }
}

// برای درخواست‌های غیر-AJAX
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $referer);
exit();
?>