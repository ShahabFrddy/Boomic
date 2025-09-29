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
    
    // بررسی عضویت در کانال
    $stmt = $pdo->prepare("
        SELECT c.id FROM channels c 
        JOIN servers s ON c.server_id = s.id 
        LEFT JOIN server_members sm ON s.id = sm.server_id 
        WHERE c.id = ? AND (s.owner_id = ? OR sm.user_id = ?)
    ");
    $stmt->execute([$channel_id, $user_id, $user_id]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO messages (channel_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$channel_id, $user_id, $message]);
    }
}

// بازگشت به صفحه قبلی
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $referer);
exit();
?>