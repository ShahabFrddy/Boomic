<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$target_user_id = isset($_POST['target_user_id']) ? intval($_POST['target_user_id']) : 0;
$current_user_id = $_SESSION['user_id'];

if (!$target_user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit();
}

if ($target_user_id == $current_user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Cannot send friend request to yourself']);
    exit();
}

// بررسی وجود درخواست قبلی
$stmt = $pdo->prepare("
    SELECT * FROM friend_requests 
    WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
");
$stmt->execute([$current_user_id, $target_user_id, $target_user_id, $current_user_id]);
$existing_request = $stmt->fetch();

if ($existing_request) {
    if ($existing_request['status'] == 'pending') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Friend request already sent']);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Already friends']);
        exit();
    }
}

// ارسال درخواست جدید
$stmt = $pdo->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
if ($stmt->execute([$current_user_id, $target_user_id])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Failed to send friend request']);
}
?>