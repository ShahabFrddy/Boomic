<?php
session_start();

$host = 'localhost';
$dbname = 'boomic_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ==================== تنظیمات آپلود فایل ====================
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('UPLOAD_PATH', 'uploads/');
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'mov', 'avi']);
define('ALLOWED_AUDIO_TYPES', ['mp3', 'wav', 'ogg', 'm4a', 'flac']);
define('MAX_IMAGE_DIMENSION', 4096); // حداکثر ابعاد عکس

// ایجاد پوشه آپلود اگر وجود ندارد
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

// ایجاد پوشه‌های فرعی برای سازماندهی بهتر
$subfolders = ['images', 'videos', 'audios', 'avatars', 'server_icons'];
foreach ($subfolders as $folder) {
    $path = UPLOAD_PATH . $folder;
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}

// ==================== توابع آپلود فایل ====================
function handleFileUpload($file, $type = 'message') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'خطا در آپلود فایل'];
    }
    
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // بررسی حجم فایل
    if ($file_size > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'حجم فایل بیش از حد مجاز است (حداکثر 50MB)'];
    }
    
    // بررسی نوع فایل و تعیین پوشه مقصد
    if (in_array($file_extension, ALLOWED_IMAGE_TYPES)) {
        $file_type = 'image';
        $subfolder = 'images';
        
        // بررسی ابعاد عکس
        $image_info = getimagesize($file_tmp);
        if ($image_info) {
            $width = $image_info[0];
            $height = $image_info[1];
            if ($width > MAX_IMAGE_DIMENSION || $height > MAX_IMAGE_DIMENSION) {
                return ['success' => false, 'error' => 'ابعاد عکس بیش از حد مجاز است'];
            }
        }
        
    } elseif (in_array($file_extension, ALLOWED_VIDEO_TYPES)) {
        $file_type = 'video';
        $subfolder = 'videos';
    } elseif (in_array($file_extension, ALLOWED_AUDIO_TYPES)) {
        $file_type = 'audio';
        $subfolder = 'audios';
    } else {
        return ['success' => false, 'error' => 'نوع فایل مجاز نیست'];
    }
    
    // تولید نام یکتا برای فایل
    $unique_name = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
    $upload_path = UPLOAD_PATH . $subfolder . '/' . $unique_name;
    
    // آپلود فایل
    if (move_uploaded_file($file_tmp, $upload_path)) {
        return [
            'success' => true,
            'file_path' => $subfolder . '/' . $unique_name,
            'file_type' => $file_type,
            'original_name' => $file_name,
            'file_size' => $file_size
        ];
    } else {
        return ['success' => false, 'error' => 'خطا در ذخیره فایل'];
    }
}

// تابع ایجاد thumbnail برای ویدیو
function createVideoThumbnail($videoPath, $filename) {
    if (!extension_loaded('ffmpeg')) {
        return false;
    }
    
    try {
        $thumbnail_name = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        $thumbnail_path = UPLOAD_PATH . 'images/' . $thumbnail_name;
        
        // گرفتن فریم از ثانیه 5 ویدیو
        $command = "ffmpeg -i " . escapeshellarg($videoPath) . " -ss 00:00:05 -vframes 1 -q:v 2 " . escapeshellarg($thumbnail_path) . " 2>&1";
        exec($command, $output, $returnCode);
        
        return $returnCode === 0 ? 'images/' . $thumbnail_name : false;
    } catch (Exception $e) {
        return false;
    }
}

// تابع بررسی درخواست AJAX
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// تابع حذف فایل‌های قدیمی (برای cron job)
function cleanupOldFiles($days = 30) {
    $files = glob(UPLOAD_PATH . '*/*');
    $now = time();
    $deleted = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
                unlink($file);
                $deleted++;
            }
        }
    }
    
    return $deleted;
}

// ==================== توابع اصلی ====================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUser($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, email, password, avatar, bio, verified, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return preg_match('/(android|iphone|ipod|blackberry|windows phone)/i', $userAgent);
}

function cleanupExpiredSessions() {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
    $stmt->execute();
}

// اجرای تمیزکاری در هر درخواست
cleanupExpiredSessions();

function getFriends($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.avatar, u.bio,
               CASE 
                   WHEN fr.sender_id = ? THEN 'outgoing'
                   WHEN fr.receiver_id = ? THEN 'incoming'
               END as request_direction
        FROM friend_requests fr
        JOIN users u ON (fr.sender_id = u.id OR fr.receiver_id = u.id) AND u.id != ?
        WHERE (fr.sender_id = ? OR fr.receiver_id = ?) AND fr.status = 'accepted'
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPendingRequests($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT fr.*, u.username, u.avatar 
        FROM friend_requests fr 
        JOIN users u ON fr.sender_id = u.id 
        WHERE fr.receiver_id = ? AND fr.status = 'pending'
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDirectMessages($user1_id, $user2_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT dm.*, u.username, u.avatar, u.verified 
        FROM direct_messages dm 
        JOIN users u ON dm.sender_id = u.id 
        WHERE (dm.sender_id = ? AND dm.receiver_id = ?) 
           OR (dm.sender_id = ? AND dm.receiver_id = ?)
        ORDER BY dm.created_at
    ");
    $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOrCreateDirectChannel($user1_id, $user2_id) {
    global $pdo;
    
    // بررسی وجود کانال
    $stmt = $pdo->prepare("
        SELECT * FROM direct_channels 
        WHERE (user1_id = ? AND user2_id = ?) 
           OR (user1_id = ? AND user2_id = ?)
    ");
    $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
    $channel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$channel) {
        // ایجاد کانال جدید
        $stmt = $pdo->prepare("INSERT INTO direct_channels (user1_id, user2_id) VALUES (?, ?)");
        $stmt->execute([min($user1_id, $user2_id), max($user1_id, $user2_id)]);
        return $pdo->lastInsertId();
    }
    
    return $channel['id'];
}

function getServerMembers($server_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.avatar, u.bio, u.verified, u.created_at,
               CASE WHEN s.owner_id = u.id THEN 1 ELSE 0 END as is_owner
        FROM users u
        LEFT JOIN server_members sm ON u.id = sm.user_id AND sm.server_id = ?
        LEFT JOIN servers s ON s.id = ? AND s.owner_id = u.id
        WHERE sm.user_id IS NOT NULL OR s.owner_id = u.id
        ORDER BY is_owner DESC, u.username
    ");
    $stmt->execute([$server_id, $server_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateInviteCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function createServerInvite($server_id, $user_id, $max_uses = 0, $expires_hours = 24) {
    global $pdo;
    
    $code = generateInviteCode();
    $expires_at = $expires_hours > 0 ? date('Y-m-d H:i:s', strtotime("+$expires_hours hours")) : null;
    
    $stmt = $pdo->prepare("
        INSERT INTO server_invites (server_id, code, created_by, max_uses, expires_at) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$server_id, $code, $user_id, $max_uses, $expires_at])) {
        return $code;
    }
    
    return false;
}

function getServerInvites($server_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT si.*, u.username as created_by_username
        FROM server_invites si
        JOIN users u ON si.created_by = u.id
        WHERE si.server_id = ?
        ORDER BY si.created_at DESC
    ");
    $stmt->execute([$server_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function isValidInvite($code) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT si.*, s.name as server_name
        FROM server_invites si
        JOIN servers s ON si.server_id = s.id
        WHERE si.code = ? 
        AND (si.max_uses = 0 OR si.uses_count < si.max_uses)
        AND (si.expires_at IS NULL OR si.expires_at > NOW())
    ");
    $stmt->execute([$code]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function useInvite($code, $user_id) {
    global $pdo;
    
    $invite = isValidInvite($code);
    if (!$invite) {
        return false;
    }
    
    // بررسی آیا کاربر قبلاً عضو است
    $stmt = $pdo->prepare("SELECT * FROM server_members WHERE server_id = ? AND user_id = ?");
    $stmt->execute([$invite['server_id'], $user_id]);
    if ($stmt->fetch()) {
        return false; // کاربر قبلاً عضو است
    }
    
    $pdo->beginTransaction();
    
    try {
        // افزودن کاربر به سرور
        $stmt = $pdo->prepare("INSERT INTO server_members (server_id, user_id) VALUES (?, ?)");
        $stmt->execute([$invite['server_id'], $user_id]);
        
        // افزایش تعداد استفاده
        $stmt = $pdo->prepare("UPDATE server_invites SET uses_count = uses_count + 1 WHERE code = ?");
        $stmt->execute([$code]);
        
        $pdo->commit();
        return $invite['server_id'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// تابع جدید: دریافت اطلاعات فایل برای نمایش
function getFileInfo($file_path) {
    $full_path = UPLOAD_PATH . $file_path;
    if (!file_exists($full_path)) {
        return null;
    }
    
    $file_info = pathinfo($full_path);
    $file_type = getFileType($file_info['extension']);
    
    return [
        'path' => $file_path,
        'full_path' => $full_path,
        'name' => $file_info['basename'],
        'extension' => $file_info['extension'],
        'type' => $file_type,
        'size' => filesize($full_path),
        'url' => $full_path // یا آدرس کامل اگر نیاز باشد
    ];
}

// تابع جدید: تشخیص نوع فایل
function getFileType($extension) {
    $extension = strtolower($extension);
    
    if (in_array($extension, ALLOWED_IMAGE_TYPES)) {
        return 'image';
    } elseif (in_array($extension, ALLOWED_VIDEO_TYPES)) {
        return 'video';
    } elseif (in_array($extension, ALLOWED_AUDIO_TYPES)) {
        return 'audio';
    } else {
        return 'unknown';
    }
}

// تابع جدید: بررسی امنیت فایل آپلود شده
function isFileSafe($file_path) {
    $allowed_mime_types = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'video/quicktime',
        'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/x-m4a'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_path);
    finfo_close($finfo);
    
    return in_array($mime_type, $allowed_mime_types);
}
?>