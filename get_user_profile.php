<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$current_user_id = $_SESSION['user_id'];

if (!$target_user_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User ID required']);
    exit();
}

// دریافت اطلاعات کاربر
$user = getUser($target_user_id);
if (!$user) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not found']);
    exit();
}

// بررسی آیا دوست هستند
$stmt = $pdo->prepare("
    SELECT * FROM friend_requests 
    WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
    AND status = 'accepted'
");
$stmt->execute([$current_user_id, $target_user_id, $target_user_id, $current_user_id]);
$is_friend = $stmt->fetch() ? true : false;

// بررسی آیا درخواست pending وجود دارد
$stmt = $pdo->prepare("
    SELECT * FROM friend_requests 
    WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
    AND status = 'pending'
");
$stmt->execute([$current_user_id, $target_user_id, $target_user_id, $current_user_id]);
$pending_request = $stmt->fetch();

$response = [
    'id' => $user['id'],
    'username' => $user['username'],
    'avatar' => $user['avatar'],
    'bio' => $user['bio'] ?? '',
    'verified' => $user['verified'] ?? 0, // این خط اضافه شود
    'join_date' => date('Y/m/d', strtotime($user['created_at'])),
    'is_friend' => $is_friend,
    'has_pending_request' => $pending_request ? true : false
];

if ($pending_request) {
    if ($pending_request['sender_id'] == $current_user_id) {
        $response['error'] = 'شما قبلاً درخواست دوستی ارسال کرده‌اید';
    } else {
        $response['error'] = 'این کاربر برای شما درخواست دوستی ارسال کرده است';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>