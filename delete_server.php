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
$confirmation = isset($_POST['confirmation']) ? trim($_POST['confirmation']) : '';
$user_id = $_SESSION['user_id'];

if (!$server_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server ID required']);
    exit();
}

if ($confirmation !== 'delete') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Please type "delete" to confirm']);
    exit();
}

// بررسی آیا کاربر مالک سرور است
$stmt = $pdo->prepare("SELECT id, name, icon, owner_id FROM servers WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server not found']);
    exit();
}

if ($server['owner_id'] != $user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Only server owner can delete the server']);
    exit();
}

// حذف سرور و داده‌های مرتبط
try {
    $pdo->beginTransaction();

    // 1. حذف پیام‌های کانال‌ها
    $stmt = $pdo->prepare("
        DELETE m FROM messages m 
        JOIN channels c ON m.channel_id = c.id 
        WHERE c.server_id = ?
    ");
    $stmt->execute([$server_id]);

    // 2. حذف کانال‌ها
    $stmt = $pdo->prepare("DELETE FROM channels WHERE server_id = ?");
    $stmt->execute([$server_id]);

    // 3. حذف لینک‌های دعوت
    $stmt = $pdo->prepare("DELETE FROM server_invites WHERE server_id = ?");
    $stmt->execute([$server_id]);

    // 4. حذف اعضا
    $stmt = $pdo->prepare("DELETE FROM server_members WHERE server_id = ?");
    $stmt->execute([$server_id]);

    // 5. حذف آیکون سرور اگر وجود دارد
    if ($server['icon'] && $server['icon'] != 'server_default.png' && file_exists('uploads/' . $server['icon'])) {
        unlink('uploads/' . $server['icon']);
    }

    // 6. حذف خود سرور
    $stmt = $pdo->prepare("DELETE FROM servers WHERE id = ?");
    $stmt->execute([$server_id]);

    $pdo->commit();

    // لاگ موفقیت
    error_log("Server deleted successfully: " . $server['name'] . " (ID: $server_id)");

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'سرور با موفقیت حذف شد',
        'redirect' => 'index.php'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error deleting server: " . $e->getMessage());

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'خطا در حذف سرور: ' . $e->getMessage()]);
}
?>