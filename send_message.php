<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = $_POST['message'];
    $channel_id = $_POST['channel_id'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO messages (content, user_id, channel_id) VALUES (?, ?, ?)");
    $stmt->execute([$content, $user_id, $channel_id]);
    
    // برگشت به صفحه سرور
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>