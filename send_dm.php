<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    $sender_id = $_SESSION['user_id'];
    
    if (empty($message)) {
        $_SESSION['error'] = 'پیام نمی‌تواند خالی باشد';
        header('Location: friends.php');
        exit();
    }
    
    // بررسی وجود دوستی
    $stmt = $pdo->prepare("
        SELECT * FROM friend_requests 
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
        AND status = 'accepted'
    ");
    $stmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
    $is_friend = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$is_friend) {
        $_SESSION['error'] = 'شما با این کاربر دوست نیستید';
        header('Location: friends.php');
        exit();
    }
    
    // ارسال پیام
    $stmt = $pdo->prepare("INSERT INTO direct_messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    if ($stmt->execute([$sender_id, $receiver_id, $message])) {
        $_SESSION['success'] = 'پیام ارسال شد';
    } else {
        $_SESSION['error'] = 'خطا در ارسال پیام';
    }
}

header('Location: friends.php');
exit();
?>