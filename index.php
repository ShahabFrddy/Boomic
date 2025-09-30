<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);

// دریافت سرورهای کاربر
$stmt = $pdo->prepare("
    SELECT s.* FROM servers s 
    JOIN server_members sm ON s.id = sm.server_id 
    WHERE sm.user_id = ?
    UNION 
    SELECT s.* FROM servers s 
    WHERE s.owner_id = ?
");
$stmt->execute([$user_id, $user_id]);
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// اگر سروری انتخاب شده باشد
$selected_server_id = isset($_GET['server']) ? $_GET['server'] : (count($servers) > 0 ? $servers[0]['id'] : null);

if ($selected_server_id) {
    // دریافت اطلاعات سرور
    $stmt = $pdo->prepare("SELECT * FROM servers WHERE id = ?");
    $stmt->execute([$selected_server_id]);
    $selected_server = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // بررسی آیا کاربر عضو سرور است
    $stmt = $pdo->prepare("SELECT * FROM server_members WHERE server_id = ? AND user_id = ?");
    $stmt->execute([$selected_server_id, $user_id]);
    $is_member = $stmt->fetch(PDO::FETCH_ASSOC) || $selected_server['owner_id'] == $user_id;
    
    if (!$is_member) {
        $selected_server_id = null;
        $selected_server = null;
    } else {
        // دریافت کانال‌های سرور
        $stmt = $pdo->prepare("SELECT * FROM channels WHERE server_id = ? ORDER BY created_at");
        $stmt->execute([$selected_server_id]);
        $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // اگر کانالی انتخاب شده باشد
        $selected_channel_id = isset($_GET['channel']) ? $_GET['channel'] : (count($channels) > 0 ? $channels[0]['id'] : null);
        
        if ($selected_channel_id) {
            // دریافت پیام‌های کانال - نسخه اصلاح شده
            $stmt = $pdo->prepare("
                SELECT m.*, u.username, u.avatar, u.id as user_id
                FROM messages m 
                JOIN users u ON m.user_id = u.id 
                WHERE m.channel_id = ? 
                ORDER BY m.created_at
            ");
            $stmt->execute([$selected_channel_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Clone</title>
    <link rel="stylesheet" href="style.css?v=24">
    <script src="script.js" defer></script>
    <style>
        /* استایل‌های اضافی برای عناصر جدید */
        .context-menu {
            position: fixed;
            background: #18191c;
            border-radius: 8px;
            padding: 6px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.24);
            border: 1px solid #33353b;
            z-index: 10000;
            min-width: 180px;
            display: none;
            animation: contextMenuAppear 0.1s ease-out;
        }

        @keyframes contextMenuAppear {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .context-menu-item {
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            color: #b9bbbe;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: background-color 0.2s, color 0.2s;
        }

        .context-menu-item:hover {
            background-color: #4752c4;
            color: white;
        }

        .context-menu-item span {
            margin-right: 8px;
        }

        /* استایل‌های مودال پروفایل کاربر */
        .user-profile-modal .modal-content {
            max-width: 400px;
            background-color: #36393f;
            border-radius: 8px;
            box-shadow: 0 2px 10px 0 rgba(0, 0, 0, 0.2);
        }

        .user-profile-header {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #5865f2 0%, #4752c4 100%);
            border-radius: 8px 8px 0 0;
        }

        .user-profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid white;
            margin-bottom: 10px;
            object-fit: cover;
        }

        .user-profile-name {
            color: white;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .user-profile-info {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .user-profile-body {
            padding: 20px;
        }

        .user-profile-bio {
            background-color: #2f3136;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .user-profile-bio h4 {
            color: white;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .user-profile-bio p {
            color: #dcddde;
            line-height: 1.5;
            font-size: 14px;
        }

        .user-profile-actions {
            display: flex;
            gap: 10px;
        }

        .user-profile-actions .btn {
            flex: 1;
            padding: 10px;
            font-size: 14px;
        }

        .btn-friend {
            background-color: #3ba55c;
        }

        .btn-friend:hover {
            background-color: #2d7c46;
        }

        .btn-message {
            background-color: #5865f2;
        }

        .btn-message:hover {
            background-color: #4752c4;
        }

        .btn-pending {
            background-color: #747f8d;
            cursor: not-allowed;
        }

        .btn-pending:hover {
            background-color: #747f8d;
        }

        /* استایل‌های عضو */
        .member-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 2px;
            transition: background-color 0.2s;
        }

        .member-item:hover {
            background-color: rgba(79, 84, 92, 0.32);
        }

        .friend-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-left: 12px;
            object-fit: cover;
        }

        /* استایل‌های مودال */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #36393f;
            border-radius: 8px;
            width: 440px;
            max-width: 90%;
            box-shadow: 0 2px 10px 0 rgba(0, 0, 0, 0.2);
            animation: modalAppear 0.2s ease-out;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            padding: 16px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: white;
            margin: 0;
            flex-grow: 1;
            text-align: center;
        }

        .modal-body {
            padding: 16px;
        }

        .back-button {
            background: none;
            border: none;
            color: #b9bbbe;
            cursor: pointer;
            font-size: 20px;
            padding: 5px 10px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .back-button:hover {
            background-color: rgba(79, 84, 92, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    
    <!-- Sidebar سرورها -->
    <div class="servers-sidebar">
        <?php foreach($servers as $server): ?>
            <div class="server-icon" 
                onclick="location.href='index.php?server=<?= $server['id'] ?>'" 
                oncontextmenu="showContextMenu(event, <?= $server['id'] ?>)"
                title="<?= htmlspecialchars($server['name']) ?>">
                <?php if($server['icon'] && $server['icon'] != 'server_default.png'): ?>
                    <img src="uploads/<?= $server['icon'] ?>" alt="<?= $server['name'] ?>">
                <?php else: ?>
                    <?= substr($server['name'], 0, 1) ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="server-icon add-server" onclick="openModal('createServerModal')">
            +
        </div>

        <!-- آیکون دوستان -->
        <div class="server-icon" onclick="location.href='friends.php'" title="دوستان">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 5.5V7H9V5.5L3 7V9L9 10.5V12H15V10.5L21 9ZM15 19H9V20C9 21.1 9.9 22 11 22H13C14.1 22 15 21.1 15 20V19ZM18 14H6V16H18V14ZM21 17H3V19H21V17Z"/>
            </svg>
        </div>
        
        <div class="server-icon" onclick="location.href='profile.php'">
            <img src="uploads/<?= $user['avatar'] ?>" alt="پروفایل"
                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iMTIiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMTIiIGN5PSI5IiByPSI0LjUiIGZpbGw9IiNkY2RkZGUiLz4KPHBhdGggZD0iTTEyIDE1QzE1IDE1IDE4IDE3IDE4IDE4LjVWMjJINlYxOC41QzYgMTcgOSAxNSAxMiAxNVoiIGZpbGw9IiNkY2RkZGUiLz4KPC9zdmc+'">
        </div>
    </div>
    
    <?php if($selected_server_id): ?>
    <!-- Sidebar کانال‌ها -->
    <div class="channels-sidebar">
        <div class="server-header">
            <?= $selected_server['name'] ?>
            <?php if($selected_server['verified'] == 1): ?>
                <span class="verified-badge" title="سرور تایید شده">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </span>
            <?php endif; ?>
            
        </div>       
        
        <div class="channels-list">
            <div class="channel-category">کانال‌های متنی</div>
            <?php foreach($channels as $channel): ?>
                <div class="channel-item <?= $selected_channel_id == $channel['id'] ? 'active' : '' ?>" 
                     onclick="location.href='index.php?server=<?= $selected_server_id ?>&channel=<?= $channel['id'] ?>'">
                    <span class="channel-icon">#</span>
                    <?= $channel['name'] ?>
                </div>
            <?php endforeach; ?>
            
            <?php if($selected_server['owner_id'] == $user_id): ?>
                <div class="channel-item" onclick="openModal('createChannelModal')">
                    <span class="channel-icon">+</span>
                    ایجاد کانال
                </div>
            <?php endif; ?>
        </div>
        
        <!-- در قسمت user-menu در index.php -->
        <div class="user-menu">
            <img class="user-avatar" src="uploads/<?= $user['avatar'] ?>" alt="<?= $user['username'] ?>"
                onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxMiIgcj0iNiIgZmlsbD0iI2RjZGRkZSIvPgo8cGF0aCBkPSJNMTYgMjBDMjAgMjAgMjQgMjIgMjQgMjZIMThDMTggMjIgMTYgMjAgMTYgMjBaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
            <div class="user-info">
                <div class="username"><?= $user['username'] ?></div>
                <div class="user-tag">#<?= $user_id ?></div>
            </div>
            <div class="user-actions">
                <button class="logout-btn" onclick="showLogoutConfirmation()" title="خروج">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- ناحیه چت -->
    <div class="chat-area">
        <?php if($selected_channel_id): ?>
            <div class="chat-header">
                <div class="channel-name">
                    <?php 
                    $channel_name = '';
                    foreach($channels as $channel) {
                        if ($channel['id'] == $selected_channel_id) {
                            $channel_name = $channel['name'];
                            break;
                        }
                    }
                    echo $channel_name;
                    ?>
                </div>
            </div>
            
            <!-- در قسمت messages-container -->
            <div class="messages-container" id="messages-container">
                <?php foreach($messages as $message): 
                    $message_user = getUser($message['user_id']);
                ?>
                    <div class="message">
                        <img class="message-avatar" src="uploads/<?= $message['avatar'] ?>" alt="<?= $message['username'] ?>"
                            onclick="showUserProfile(<?= $message['user_id'] ?>)"
                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNSIgcj0iNy41IiBmaWxsPSIjZGNkZGRlIi8+CjxwYXRoIGQ9Ik0yMCAyNUMzMCAyNSAzOCAzMCAzOCAzNUgyQzIgMzAgMTAgMjUgMjAgMjVaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-author" onclick="showUserProfile(<?= $message['user_id'] ?>)">
                                    <?= htmlspecialchars($message['username']) ?>
                                    <?php if($message_user['verified'] == 1): ?>
                                        <span class="verified-badge" title="تایید شده">
                                            <svg viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                    
                                </span>
                                <span class="message-time"><?= date('H:i', strtotime($message['created_at'])) ?></span>
                            </div>
                            <div class="message-text"><?= htmlspecialchars($message['content']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="message-input-container">
                <form method="POST" action="send_message.php" id="message-form">
                    <input type="hidden" name="channel_id" value="<?= $selected_channel_id ?>">
                    <div class="input-wrapper">
                        <textarea class="message-input" name="message" placeholder="پیام خود را در #<?= $channel_name ?> بنویسید" rows="1" id="message-textarea"></textarea>
                        <button type="submit" class="send-button hidden" id="send-button">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div style="display: flex; justify-content: center; align-items: center; height: 100%;">
                لطفاً یک کانال را انتخاب کنید
            </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
            <?php if(count($servers) == 0): ?>
                <div style="text-align: center;">
                    <h2>به دیسکورد خوش آمدید!</h2>
                    <p>شما هنوز به هیچ سروری ملحق نشده‌اید.</p>
                    <button class="btn" onclick="openModal('createServerModal')" style="width: auto; padding: 10px 20px; margin-top: 20px;">
                        اولین سرور خود را ایجاد کنید
                    </button>
                </div>
            <?php else: ?>
                <div style="text-align: center;">
                    <h2>سروری را انتخاب کنید</h2>
                    <p>برای شروع چت، یک سرور از لیست سمت چپ انتخاب کنید.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- مدال ایجاد سرور -->
    <div id="createServerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>سرور خود را سفارشی کنید</h3>
                <button type="button" class="back-button" onclick="closeModal('createServerModal')">×</button>
            </div>
            <form method="POST" action="create_server.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="server_name">نام سرور</label>
                        <input type="text" class="form-control" id="server_name" name="server_name" required>
                    </div>
                    <div class="form-group">
                        <label for="server_icon">آیکون سرور (اختیاری)</label>
                        <input type="file" class="form-control" id="server_icon" name="server_icon" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createServerModal')">لغو</button>
                    <button type="submit" class="btn">ایجاد سرور</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- مدال ایجاد کانال -->
    <div id="createChannelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>ایجاد کانال</h3>
                <button type="button" class="back-button" onclick="closeModal('createChannelModal')">×</button>
            </div>
            <form method="POST" action="create_channel.php">
                <input type="hidden" name="server_id" value="<?= $selected_server_id ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="channel_name">نام کانال</label>
                        <input type="text" class="form-control" id="channel_name" name="channel_name" required>
                    </div>
                    <div class="form-group">
                        <label for="channel_type">نوع کانال</label>
                        <select class="form-control" id="channel_type" name="channel_type">
                            <option value="text">متنی</option>
                            <option value="voice">صوتی</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createChannelModal')">لغو</button>
                    <button type="submit" class="btn">ایجاد کانال</button>
                </div>
            </form>
        </div>
    </div>

    <!-- مدال نمایش اعضای سرور -->
    <div id="membersModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>اعضای سرور</h3>
                <button type="button" class="back-button" onclick="closeModal('membersModal')">×</button>
            </div>
            <div class="modal-body">
                <div id="members-list" style="max-height: 400px; overflow-y: auto;">
                    <!-- لیست اعضا اینجا لود می‌شود -->
                </div>
            </div>
        </div>
    </div>

    <!-- مدال مدیریت لینک‌های دعوت -->
    <div id="inviteModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>دعوت به سرور</h3>
                <button type="button" class="back-button" onclick="closeModal('inviteModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>لینک دعوت جدید</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" class="form-control" id="new-invite-link" readonly style="flex-grow: 1;">
                        <button type="button" class="btn" onclick="generateInvite()">ایجاد لینک</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>لینک‌های فعال</label>
                    <div id="active-invites" style="max-height: 200px; overflow-y: auto;">
                        <!-- لینک‌های فعال اینجا نمایش داده می‌شوند -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // مدیریت ارسال پیام در چت و سیستم آپدیت خودکار
        document.addEventListener('DOMContentLoaded', function() {
            // اگر در کانالی هستیم، سیستم پیام را راه‌اندازی کن
            <?php if($selected_channel_id && isset($messages) && !empty($messages)): ?>
                // پیدا کردن آخرین ID پیام
                const lastMessageId = <?= end($messages)['id'] ?>;
                initializeMessageSystem(<?= $selected_channel_id ?>, lastMessageId);
            <?php elseif($selected_channel_id): ?>
                // اگر کانال انتخاب شده اما پیامی نیست
                initializeMessageSystem(<?= $selected_channel_id ?>, 0);
            <?php endif; ?>
            
            // اسکرول به پایین در پیام‌ها
            const messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            // مدیریت ارسال پیام
            const messageTextarea = document.getElementById('message-textarea');
            const messageForm = document.getElementById('message-form');
            const sendButton = document.getElementById('send-button');
            
            if (messageTextarea && messageForm && sendButton) {
                messageTextarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 150) + 'px';
                    
                    if (this.value.trim() !== '') {
                        sendButton.classList.remove('hidden');
                    } else {
                        sendButton.classList.add('hidden');
                    }
                });
                
                messageTextarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        if (this.value.trim() !== '') {
                            // ارسال پیام با AJAX (جدید)
                            sendMessage(this.value.trim());
                            this.value = '';
                            this.style.height = 'auto';
                            sendButton.classList.add('hidden');
                        }
                    }
                });
                
                messageTextarea.style.height = 'auto';
                messageTextarea.style.height = Math.min(messageTextarea.scrollHeight, 150) + 'px';
                sendButton.classList.add('hidden');

                // مدیریت ارسال با کلیک روی دکمه (جدید)
                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (messageTextarea.value.trim() !== '') {
                        sendMessage(messageTextarea.value.trim());
                        messageTextarea.value = '';
                        messageTextarea.style.height = 'auto';
                        sendButton.classList.add('hidden');
                    }
                });
            }
        });

        // ارسال پیام با AJAX (تابع جدید)
        async function sendMessage(content) {
            const channelId = <?= $selected_channel_id ?? 'null' ?>;
            if (!channelId) return;
            
            try {
                const response = await fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `channel_id=${channelId}&message=${encodeURIComponent(content)}`
                });
                
                if (response.ok) {
                    console.log('Message sent successfully');
                    // پیام به صورت خودکار از طریق polling اضافه می‌شود
                } else {
                    throw new Error('Failed to send message');
                }
                
            } catch (error) {
                console.error('Error sending message:', error);
                alert('خطا در ارسال پیام');
            }
        }

        // وقتی کاربر صفحه را ترک می‌کند (جدید)
        window.addEventListener('beforeunload', function() {
            stopMessageSystem();
        });

        // وقتی کاربر به کانال دیگری می‌رود (اگر از طریق JavaScript کانال عوض می‌کنید)
        function switchChannel(newChannelId) {
            stopMessageSystem();
            initializeMessageSystem(newChannelId, 0);
        }

        //چت آپدیت چنل ها

        // متغیرهای global برای مدیریت پیام‌ها
        let currentChannelId = null;
        let lastMessageId = 0;
        let messagePollInterval = null;

        // مقداردهی اولیه سیستم پیام‌ها
        function initializeMessageSystem(channelId, initialLastMessageId = 0) {
            currentChannelId = channelId;
            lastMessageId = initialLastMessageId;
            
            // توقف interval قبلی اگر وجود دارد
            if (messagePollInterval) {
                clearInterval(messagePollInterval);
            }
            
            // شروع polling برای پیام‌های جدید
            messagePollInterval = setInterval(checkForNewMessages, 3000); // هر 3 ثانیه
            
            console.log(`Message system initialized for channel ${channelId}, last message: ${lastMessageId}`);
        }

        // بررسی پیام‌های جدید
        async function checkForNewMessages() {
            if (!currentChannelId) return;
            
            try {
                const response = await fetch(`get_channel_messages.php?channel_id=${currentChannelId}&last_message_id=${lastMessageId}`);
                const data = await response.json();
                
                if (data.error) {
                    console.error('Error fetching new messages:', data.error);
                    return;
                }
                
                if (data.has_new_messages && data.messages.length > 0) {
                    console.log(`Found ${data.messages.length} new messages`);
                    appendNewMessages(data.messages);
                    lastMessageId = data.last_message_id;
                }
            } catch (error) {
                console.error('Error checking for new messages:', error);
            }
        }

        // اضافه کردن پیام‌های جدید به صفحه
        function appendNewMessages(messages) {
            const messagesContainer = document.getElementById('messages-container');
            if (!messagesContainer) return;
            
            const isScrolledToBottom = isMessagesContainerAtBottom();
            
            messages.forEach(message => {
                const messageElement = createMessageElement(message);
                messagesContainer.appendChild(messageElement);
            });
            
            // اگر کاربر در پایین بود یا نزدیک پایین بود، اسکرول کن
            if (isScrolledToBottom || isNearBottom()) {
                scrollToBottom();
            }
            
            // پخش صدای نوتیفیکیشن اگر کاربر در تب دیگر است
            if (!document.hasFocus() && messages.length > 0) {
                playMessageSound();
            }
        }

        // ایجاد المان پیام
        function createMessageElement(message) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message';
            messageDiv.innerHTML = `
                <img class="message-avatar" src="uploads/${message.avatar}" alt="${message.username}"
                    onclick="showUserProfile(${message.user_id})"
                    onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNSIgcj0iNy41IiBmaWxsPSIjZGNkZGRlIi8+CjxwYXRoIGQ9Ik0yMCAyNUMzMCAyNSAzOCAzMCAzOCAzNUgyQzIgMzAgMTAgMjUgMjAgMjVaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-author" onclick="showUserProfile(${message.user_id})">
                            ${message.username}
                             ${message.verified == 1 ? `
                                <span class="verified-badge" title="تایید شده">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                </span>
                            ` : ''}
                        </span>
                        <span class="message-time">${message.time}</span>
                    </div>
                    <div class="message-text">${message.content}</div>
                </div>
            `;
            
            return messageDiv;
        }

        // بررسی آیا کاربر در پایین صفحه است
        function isMessagesContainerAtBottom() {
            const messagesContainer = document.getElementById('messages-container');
            if (!messagesContainer) return false;
            
            const threshold = 100; // 100px از پایین
            return messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < threshold;
        }

        // بررسی آیا کاربر نزدیک پایین صفحه است
        function isNearBottom() {
            const messagesContainer = document.getElementById('messages-container');
            if (!messagesContainer) return false;
            
            const threshold = 300; // 300px از پایین
            return messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < threshold;
        }

        // اسکرول به پایین
        function scrollToBottom() {
            const messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        // پخش صدای نوتیفیکیشن
        function playMessageSound() {
            // ایجاد یک sound notification ساده
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0, audioContext.currentTime);
                gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.01);
                gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.1);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (error) {
                console.log('Audio context not supported');
            }
        }

        // توقف سیستم پیام‌ها وقتی کاربر صفحه را ترک می‌کند
        function stopMessageSystem() {
            if (messagePollInterval) {
                clearInterval(messagePollInterval);
                messagePollInterval = null;
            }
            currentChannelId = null;
            lastMessageId = 0;
            console.log('Message system stopped');
        }

        // وقتی کاربر از کانال خارج می‌شود
        function leaveChannel() {
            stopMessageSystem();
        }

        // وقتی کاربر به کانال جدید می‌رود
        function switchChannel(newChannelId) {
            stopMessageSystem();
            initializeMessageSystem(newChannelId);
        }
    </script>

</body>
</html>