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

// دیباگ: لاگ اطلاعات ورودی
error_log("Update server settings - Server ID: $server_id, Server Name: $server_name, User ID: $user_id");

if (!$server_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server ID required']);
    exit();
}

if (empty($server_name)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server name cannot be empty']);
    exit();
}

if (strlen($server_name) < 2 || strlen($server_name) > 100) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Server name must be between 2 and 100 characters']);
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
    echo json_encode(['success' => false, 'error' => 'Only server owner can edit settings']);
    exit();
}

// آپلود عکس جدید اگر وجود دارد
$server_icon = $server['icon']; // نگه داشتن عکس قبلی به عنوان پیش‌فرض

if (isset($_FILES['server_icon']) && $_FILES['server_icon']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['server_icon']['type'];
    $file_size = $_FILES['server_icon']['size'];
    $file_name = $_FILES['server_icon']['name'];
    
    error_log("File upload attempt - Name: $file_name, Type: $file_type, Size: $file_size");
    
    // بررسی نوع فایل
    if (!in_array($file_type, $allowed_types)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF and WebP are allowed']);
        exit();
    }
    
    // بررسی سایز فایل (حداکثر 5MB)
    if ($file_size > 5 * 1024 * 1024) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'File size must be less than 5MB']);
        exit();
    }
    
    // ایجاد نام فایل جدید
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $new_filename = 'server_' . $server_id . '_' . uniqid() . '.' . $file_extension;
    $upload_path = 'uploads/' . $new_filename;
    
    // اطمینان از وجود پوشه uploads
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
    }
    
    // آپلود فایل
    if (move_uploaded_file($_FILES['server_icon']['tmp_name'], $upload_path)) {
        // حذف عکس قبلی اگر وجود دارد و عکس پیش‌فرض نباشد
        if ($server['icon'] && $server['icon'] != 'server_default.png' && file_exists('uploads/' . $server['icon'])) {
            $delete_success = unlink('uploads/' . $server['icon']);
            error_log("Deleting old icon: " . ($delete_success ? 'success' : 'failed'));
        }
        
        $server_icon = $new_filename;
        error_log("New icon uploaded: $server_icon");
    } else {
        error_log("Failed to move uploaded file");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to upload server icon']);
        exit();
    }
} elseif (isset($_FILES['server_icon']) && $_FILES['server_icon']['error'] != 4) {
    // خطا 4 یعنی هیچ فایلی انتخاب نشده
    $upload_errors = [
        1 => 'File size exceeds upload_max_filesize',
        2 => 'File size exceeds MAX_FILE_SIZE',
        3 => 'File was only partially uploaded',
        6 => 'Missing temporary folder',
        7 => 'Failed to write file to disk',
        8 => 'A PHP extension stopped the file upload'
    ];
    $error_code = $_FILES['server_icon']['error'];
    $error_message = $upload_errors[$error_code] ?? 'Unknown upload error';
    
    error_log("File upload error: $error_code - $error_message");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Upload error: ' . $error_message]);
    exit();
}

// بروزرسانی اطلاعات سرور در دیتابیس
try {
    $pdo->beginTransaction();
    
    if ($server_icon != $server['icon']) {
        // اگر عکس تغییر کرده
        $stmt = $pdo->prepare("UPDATE servers SET name = ?, icon = ?, updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$server_name, $server_icon, $server_id]);
        error_log("Updating server with new icon: $server_name, $server_icon");
    } else {
        // اگر فقط نام تغییر کرده
        $stmt = $pdo->prepare("UPDATE servers SET name = ?, updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$server_name, $server_id]);
        error_log("Updating server name only: $server_name");
    }
    
    if ($success) {
        $pdo->commit();
        
        // لاگ موفقیت
        error_log("Server settings updated successfully for server ID: $server_id");
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'تنظیمات سرور با موفقیت به‌روزرسانی شد',
            'server_icon' => $server_icon
        ]);
    } else {
        $pdo->rollBack();
        error_log("Database update failed for server ID: $server_id");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'خطا در به‌روزرسانی تنظیمات در دیتابیس']);
    }
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>