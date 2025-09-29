<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $server_id = $_POST['server_id'];
    $channel_name = trim($_POST['channel_name']);
    $channel_type = $_POST['channel_type'];
    $user_id = $_SESSION['user_id'];
    
    // بررسی مالکیت سرور
    $stmt = $pdo->prepare("SELECT owner_id FROM servers WHERE id = ?");
    $stmt->execute([$server_id]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$server || $server['owner_id'] != $user_id) {
        $_SESSION['error'] = 'شما اجازه ایجاد کانال در این سرور را ندارید';
        header('Location: index.php?server=' . $server_id);
        exit();
    }
    
    if (empty($channel_name)) {
        $_SESSION['error'] = 'نام کانال نمی‌تواند خالی باشد';
        header('Location: index.php?server=' . $server_id);
        exit();
    }
    
    $stmt = $pdo->prepare("INSERT INTO channels (server_id, name, type) VALUES (?, ?, ?)");
    if ($stmt->execute([$server_id, $channel_name, $channel_type])) {
        $_SESSION['success'] = 'کانال با موفقیت ایجاد شد';
    } else {
        $_SESSION['error'] = 'خطا در ایجاد کانال';
    }
}

header('Location: index.php?server=' . $server_id);
exit();
?>