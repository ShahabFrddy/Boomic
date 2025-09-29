<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $server_name = trim($_POST['server_name']);
    $owner_id = $_SESSION['user_id'];
    
    if (empty($server_name)) {
        $_SESSION['error'] = 'نام سرور نمی‌تواند خالی باشد';
        header('Location: index.php');
        exit();
    }
    
    // آپلود آیکون سرور
    $server_icon = 'server_default.png';
    if (isset($_FILES['server_icon']) && $_FILES['server_icon']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['server_icon']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['server_icon']['name'], PATHINFO_EXTENSION);
            $new_filename = 'server_' . uniqid() . '.' . $file_extension;
            $upload_path = 'uploads/' . $new_filename;
            
            if (move_uploaded_file($_FILES['server_icon']['tmp_name'], $upload_path)) {
                $server_icon = $new_filename;
            }
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // ایجاد سرور
        $stmt = $pdo->prepare("INSERT INTO servers (name, owner_id, icon) VALUES (?, ?, ?)");
        $stmt->execute([$server_name, $owner_id, $server_icon]);
        $server_id = $pdo->lastInsertId();
        
        // ایجاد کانال عمومی پیش‌فرض
        $stmt = $pdo->prepare("INSERT INTO channels (server_id, name, type) VALUES (?, ?, ?)");
        $stmt->execute([$server_id, 'عمومی', 'text']);
        
        $pdo->commit();
        
        $_SESSION['success'] = 'سرور با موفقیت ایجاد شد';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'خطا در ایجاد سرور';
    }
}

header('Location: index.php');
exit();
?>