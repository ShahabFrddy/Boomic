<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$server_id = isset($_GET['server_id']) ? intval($_GET['server_id']) : 0;
$user_id = $_SESSION['user_id'];

if (!$server_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server ID required']);
    exit();
}

// بررسی آیا کاربر مالک سرور است
$stmt = $pdo->prepare("SELECT owner_id FROM servers WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch();

if (!$server || $server['owner_id'] != $user_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied']);
    exit();
}

$invites = getServerInvites($server_id);

header('Content-Type: application/json');
echo json_encode(['invites' => $invites]);
?>