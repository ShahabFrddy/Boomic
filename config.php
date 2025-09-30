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

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUser($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, username, email, password, avatar, bio, verified, created_at FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

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
?>