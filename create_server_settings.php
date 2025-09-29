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
$server_name = isset($_POST['server_name']) ? trim($_POST['server_name']) : '';
$user_id = $_SESSION['user_id'];

if (!$server_id || empty($server_name)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server ID and name are required']);
    exit();
}

// بررسی آیا کاربر مالک سرور است
$stmt = $pdo->prepare("SELECT owner_id FROM servers WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$server || $server['owner_id'] != $user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Only server owner can edit settings']);
    exit();
}

// آپلود عکس جدید اگر وجود دارد
$server_icon = null;
if (isset($_FILES['server_icon']) && $_FILES['server_icon']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = $_FILES['server_icon']['type'];
    $file_size = $_FILES['server_icon']['size'];
    
    if (in_array($file_type, $allowed_types)) {
        if ($file_size <= 5 * 1024 * 1024) { // 5MB
            $file_extension = pathinfo($_FILES['server_icon']['name'], PATHINFO_EXTENSION);
            $new_filename = 'server_' . uniqid() . '.' . $file_extension;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['server_icon']['tmp_name'], $upload_path)) {
                $server_icon = $new_filename;
                
                // حذف عکس قبلی اگر وجود دارد
                if ($server['icon'] && $server['icon'] != 'server_default.png') {
                    @unlink('uploads/' . $server['icon']);
                }
            }
        }
    }
}

// بروزرسانی اطلاعات سرور
if ($server_icon) {
    $stmt = $pdo->prepare("UPDATE servers SET name = ?, icon = ? WHERE id = ?");
    $success = $stmt->execute([$server_name, $server_icon, $server_id]);
} else {
    $stmt = $pdo->prepare("UPDATE servers SET name = ? WHERE id = ?");
    $success = $stmt->execute([$server_name, $server_id]);
}

if ($success) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'تنظیمات سرور با موفقیت به‌روزرسانی شد']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'خطا در به‌روزرسانی تنظیمات']);
}
?>