<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$channel_id = isset($_GET['channel_id']) ? intval($_GET['channel_id']) : 0;
$last_message_id = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;
$user_id = $_SESSION['user_id'];

if (!$channel_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Channel ID required']);
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
$channel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$channel) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied or channel not found']);
    exit();
}

// دریافت پیام‌های جدید
if ($last_message_id > 0) {
    // فقط پیام‌های جدیدتر از last_message_id
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.avatar, u.id as user_id, u.verified
        FROM messages m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.channel_id = ? AND m.id > ?
        ORDER BY m.created_at
    ");
    $stmt->execute([$channel_id, $last_message_id]);
} else {
    // دریافت آخرین پیام‌ها (برای اولین بار)
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.avatar, u.id as user_id, u.verified
        FROM messages m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.channel_id = ? 
        ORDER BY m.created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$channel_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $messages = array_reverse($messages); // مرتب کردن از قدیمی به جدید
}

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// فرمت کردن پیام‌ها برای پاسخ JSON
$formatted_messages = [];
foreach ($messages as $message) {
    $formatted_messages[] = [
        'id' => $message['id'],
        'user_id' => $message['user_id'],
        'username' => $message['username'],
        'avatar' => $message['avatar'],
        'verified' => $message['verified'], // این خط اضافه شد
        'content' => htmlspecialchars($message['content']),
        'created_at' => $message['created_at'],
        'time' => date('H:i', strtotime($message['created_at']))
    ];
}

// پیدا کردن آخرین ID پیام
$last_id = 0;
if (!empty($formatted_messages)) {
    $last_id = end($formatted_messages)['id'];
}

header('Content-Type: application/json');
echo json_encode([
    'messages' => $formatted_messages,
    'last_message_id' => $last_id,
    'has_new_messages' => !empty($formatted_messages)
]);
?>