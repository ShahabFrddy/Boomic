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
$stmt = $pdo->prepare("SELECT * FROM servers WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server not found']);
    exit();
}

// فقط مالک سرور می‌تواند اطلاعات تنظیمات را ببیند
if ($server['owner_id'] != $user_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Only server owner can view settings']);
    exit();
}

// دریافت تعداد اعضا
$stmt = $pdo->prepare("
    SELECT COUNT(*) as member_count 
    FROM server_members 
    WHERE server_id = ?
    UNION ALL
    SELECT 1
    FROM servers 
    WHERE id = ? AND owner_id IS NOT NULL
");
$stmt->execute([$server_id, $server_id]);
$member_count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $member_count += $row['member_count'];
}

// دریافت تعداد کانال‌ها
$stmt = $pdo->prepare("SELECT COUNT(*) as channel_count FROM channels WHERE server_id = ?");
$stmt->execute([$server_id]);
$channel_count = $stmt->fetch(PDO::FETCH_ASSOC)['channel_count'];

header('Content-Type: application/json');
echo json_encode([
    'server' => [
        'id' => $server['id'],
        'name' => $server['name'],
        'icon' => $server['icon'],
        'owner_id' => $server['owner_id'],
        'created_at' => $server['created_at'],
        'member_count' => $member_count,
        'channel_count' => $channel_count,
        'is_owner' => true
    ]
]);
?>