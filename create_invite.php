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

$server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
$user_id = $_SESSION['user_id'];

if (!$server_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server ID required']);
    exit();
}

// بررسی آیا کاربر مالک سرور است
$stmt = $pdo->prepare("SELECT owner_id FROM servers WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch();

if (!$server || $server['owner_id'] != $user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Only server owner can create invites']);
    exit();
}

$code = createServerInvite($server_id, $user_id);

if ($code) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'code' => $code]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Failed to create invite']);
}
?>