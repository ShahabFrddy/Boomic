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

// دیباگ: لاگ اطلاعات
error_log("Getting members for server: " . $server_id . ", user: " . $user_id);

// بررسی آیا کاربر عضو سرور است
$stmt = $pdo->prepare("
    SELECT * FROM server_members 
    WHERE server_id = ? AND user_id = ?
");
$stmt->execute([$server_id, $user_id]);
$is_member = $stmt->fetch(PDO::FETCH_ASSOC);

// بررسی آیا کاربر مالک سرور است
$stmt = $pdo->prepare("SELECT owner_id FROM servers WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);
$is_owner = ($server && $server['owner_id'] == $user_id);

error_log("Is member: " . ($is_member ? 'yes' : 'no') . ", Is owner: " . ($is_owner ? 'yes' : 'no'));

if (!$is_member && !$is_owner) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not a member of this server']);
    exit();
}

// دریافت اعضای سرور
try {
    $members = getServerMembers($server_id);
    error_log("Found " . count($members) . " members");
    
    header('Content-Type: application/json');
    echo json_encode(['members' => $members]);
} catch (Exception $e) {
    error_log("Error in get_members.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>