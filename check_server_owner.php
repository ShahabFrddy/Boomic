<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['is_owner' => false, 'error' => 'Not logged in']);
    exit();
}

$server_id = isset($_GET['server_id']) ? intval($_GET['server_id']) : 0;
$user_id = $_SESSION['user_id'];

if (!$server_id) {
    header('Content-Type: application/json');
    echo json_encode(['is_owner' => false, 'error' => 'Server ID required']);
    exit();
}

// بررسی آیا کاربر مالک سرور است
$stmt = $pdo->prepare("SELECT owner_id FROM servers WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server) {
    header('Content-Type: application/json');
    echo json_encode(['is_owner' => false, 'error' => 'Server not found']);
    exit();
}

$is_owner = ($server['owner_id'] == $user_id);

header('Content-Type: application/json');
echo json_encode([
    'is_owner' => $is_owner,
    'server_name' => $server['name'] ?? 'Unknown'
]);
?>