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
            // دریافت پیام‌های کانال
            $stmt = $pdo->prepare("
                SELECT m.*, u.username, u.avatar 
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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Sidebar سرورها -->
    <div class="servers-sidebar">
        <?php foreach($servers as $server): ?>
            <div class="server-icon" onclick="location.href='index.php?server=<?= $server['id'] ?>'">
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
        
        <div class="server-icon" onclick="location.href='profile.php'">
            <img src="uploads/<?= $user['avatar'] ?>" alt="پروفایل">
        </div>
    </div>
    
    <?php if($selected_server_id): ?>
    <!-- Sidebar کانال‌ها -->
    <div class="channels-sidebar">
        <div class="server-header">
            <?= $selected_server['name'] ?>
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
        
        <div class="user-menu">
            <img class="user-avatar" src="uploads/<?= $user['avatar'] ?>" alt="<?= $user['username'] ?>">
            <div class="user-info">
                <div class="username"><?= $user['username'] ?></div>
                <div class="user-tag">#<?= $user_id ?></div>
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
            
            <div class="messages-container" id="messages-container">
                <?php foreach($messages as $message): ?>
                    <div class="message">
                        <img class="message-avatar" src="uploads/<?= $message['avatar'] ?>" alt="<?= $message['username'] ?>">
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-author"><?= $message['username'] ?></span>
                                <span class="message-time"><?= date('H:i', strtotime($message['created_at'])) ?></span>
                            </div>
                            <div class="message-text"><?= htmlspecialchars($message['content']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="message-input-container">
                <form method="POST" action="send_message.php">
                    <input type="hidden" name="channel_id" value="<?= $selected_channel_id ?>">
                    <textarea class="message-input" name="message" placeholder="پیام خود را در #<?= $channel_name ?> بنویسید" rows="1"></textarea>
                    <button type="submit" style="display: none;">ارسال</button>
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
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // بستن مدال با کلیک خارج از آن
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // اسکرول به پایین در پیام‌ها
        window.onload = function() {
            const messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
    </script>
</body>
</html>