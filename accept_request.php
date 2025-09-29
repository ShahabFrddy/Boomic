<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];
    $user_id = $_SESSION['user_id'];
    
    // بررسی مالکیت درخواست
    $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
    $stmt->execute([$request_id, $user_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        // پذیرش درخواست
        $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
        if ($stmt->execute([$request_id])) {
            $_SESSION['success'] = 'درخواست دوستی پذیرفته شد';
        } else {
            $_SESSION['error'] = 'خطا در پذیرش درخواست';
        }
    } else {
        $_SESSION['error'] = 'درخواست پیدا نشد';
    }
}

header('Location: friends.php');
exit();
?>