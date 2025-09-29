<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$friend_id = isset($_GET['friend_id']) ? $_GET['friend_id'] : null;
$current_count = isset($_GET['count']) ? intval($_GET['count']) : 0;

if (!$friend_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Friend ID required']);
    exit();
}

// دریافت تمام پیام‌ها
$messages = getDirectMessages($user_id, $friend_id);

// اگر تعداد پیام‌ها بیشتر از تعداد فعلی است، پیام‌های جدید را برگردان
if (count($messages) > $current_count) {
    $new_messages = array_slice($messages, $current_count);
    $formatted_messages = [];
    
    foreach ($new_messages as $message) {
        $formatted_messages[] = [
            'id' => $message['id'],
            'username' => $message['username'],
            'avatar' => $message['avatar'],
            'content' => htmlspecialchars($message['content']),
            'time' => date('H:i', strtotime($message['created_at']))
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'newMessages' => $formatted_messages,
        'totalCount' => count($messages)
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'newMessages' => [],
        'totalCount' => count($messages)
    ]);
}
?>