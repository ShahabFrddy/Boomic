<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);

// دریافت ID کاربر مقابل
$friend_id = isset($_GET['friend_id']) ? $_GET['friend_id'] : null;

if (!$friend_id) {
    header('Location: friends.php');
    exit();
}

// بررسی دوستی
$stmt = $pdo->prepare("
    SELECT * FROM friend_requests 
    WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
    AND status = 'accepted'
");
$stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
$is_friend = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$is_friend) {
    header('Location: friends.php');
    exit();
}

// دریافت اطلاعات کاربر مقابل
$friend = getUser($friend_id);

// دریافت پیام‌ها
$messages = getDirectMessages($user_id, $friend_id);

// علامت گذاری پیام‌ها به عنوان خوانده شده
$stmt = $pdo->prepare("UPDATE direct_messages SET is_read = TRUE WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE");
$stmt->execute([$user_id, $friend_id]);

// ارسال پیام
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO direct_messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        if ($stmt->execute([$user_id, $friend_id, $message])) {
            // رفرش صفحه برای نمایش پیام جدید
            header("Location: dm.php?friend_id=" . $friend_id);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چت خصوصی - <?= htmlspecialchars($friend['username']) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Whitney', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #36393f;
            color: #dcddde;
            height: 100vh;
            overflow: hidden;
        }
        
        .dm-container {
            display: flex;
            height: 100vh;
            width: 100vw;
        }
        
        /* Sidebar */
        .dm-sidebar {
            width: 300px;
            background-color: #2f3136;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        
        .dm-header {
            padding: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.2);
            font-weight: bold;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            flex-shrink: 0;
        }
        
        .back-button {
            cursor: pointer;
            color: #b9bbbe;
            padding: 8px;
            border-radius: 3px;
            background: none;
            border: none;
            font-size: 16px;
        }
        
        .back-button:hover {
            background-color: rgba(79, 84, 92, 0.3);
        }
        
        .friends-list {
            flex-grow: 1;
            overflow-y: auto;
            padding: 8px;
        }
        
        .friend-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 2px;
            transition: background-color 0.2s;
        }
        
        .friend-item:hover {
            background-color: rgba(79, 84, 92, 0.32);
        }
        
        .friend-item.active {
            background-color: rgba(79, 84, 92, 0.6);
        }
        
        .friend-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
        }
        
        .friend-info {
            flex-grow: 1;
        }
        
        .friend-name {
            color: white;
            font-weight: 500;
            font-size: 14px;
        }
        
        .friend-status {
            color: #b9bbbe;
            font-size: 12px;
        }
        
        /* محتوای اصلی چت */
        .dm-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        
        .dm-chat-header {
            padding: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.2);
            color: white;
            font-weight: bold;
            display: flex;
            align-items: center;
            height: 60px;
            flex-shrink: 0;
            background-color: #36393f;
        }
        
        .dm-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
        }
        
        .online-dot {
            width: 8px;
            height: 8px;
            background-color: #3ba55c;
            border-radius: 50%;
            margin-left: 8px;
        }
        
        .messages-container {
            flex-grow: 1;
            overflow-y: auto;
            padding: 16px;
            background-color: #36393f;
        }
        
        .message-input-container {
            padding: 16px;
            flex-shrink: 0;
            background-color: #36393f;
        }
        
        /* استایل‌های پیام‌ها */
        .message {
            display: flex;
            margin-bottom: 16px;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 16px;
            flex-shrink: 0;
            object-fit: cover;
        }
        
        .message-content {
            flex-grow: 1;
            min-width: 0;
        }
        
        .message-header {
            display: flex;
            align-items: baseline;
            margin-bottom: 4px;
        }
        
        .message-author {
            font-weight: 500;
            margin-right: 8px;
            color: white;
            font-size: 16px;
        }
        
        .message-time {
            font-size: 12px;
            color: #72767d;
        }
        
        .message-text {
            color: #dcddde;
            line-height: 1.4;
            word-wrap: break-word;
            font-size: 16px;
        }
        
        /* استایل input */
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        
        .message-input {
            width: 100%;
            background-color: #40444b;
            border: none;
            border-radius: 8px;
            padding: 12px 50px 12px 12px;
            color: #dcddde;
            font-size: 16px;
            resize: none;
            max-height: 150px;
            font-family: inherit;
            line-height: 1.5;
        }
        
        .message-input:focus {
            outline: none;
        }
        
        .message-input::placeholder {
            color: #72767d;
        }
        
        .send-button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #b9bbbe;
            cursor: pointer;
            padding: 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s, background-color 0.2s;
            width: 32px;
            height: 32px;
        }
        
        .send-button:hover:not(:disabled) {
            color: #dcddde;
            background-color: rgba(79, 84, 92, 0.4);
        }
        
        .send-button:disabled {
            color: #72767d;
            cursor: not-allowed;
            background-color: transparent;
        }
        
        .send-button.hidden {
            display: none;
        }
        
        .message-input:not(:placeholder-shown) + .send-button:not(:disabled) {
            color: #5865f2;
        }
        
        /* اسکرول بار */
        .messages-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .messages-container::-webkit-scrollbar-track {
            background: #2e3338;
        }
        
        .messages-container::-webkit-scrollbar-thumb {
            background: #202225;
            border-radius: 4px;
        }
        
        .friends-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .friends-list::-webkit-scrollbar-track {
            background: #2f3136;
        }
        
        .friends-list::-webkit-scrollbar-thumb {
            background: #202225;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="dm-container">
        <!-- Sidebar دوستان -->
        <div class="dm-sidebar">
            <div class="dm-header">
                <span>پیام‌های مستقیم</span>
                <button class="back-button" onclick="location.href='friends.php'" title="بازگشت">←</button>
            </div>
            
            <div class="friends-list">
                <?php
                $friends = getFriends($user_id);
                foreach($friends as $friend_item): 
                    $is_active = $friend_item['id'] == $friend_id;
                ?>
                    <div class="friend-item <?= $is_active ? 'active' : '' ?>" 
                         onclick="location.href='dm.php?friend_id=<?= $friend_item['id'] ?>'">
                        <img class="friend-avatar" src="uploads/<?= $friend_item['avatar'] ?>" alt="<?= $friend_item['username'] ?>"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxMiIgcj0iNiIgZmlsbD0iI2RjZGRkZSIvPgo8cGF0aCBkPSJNMTYgMjBDMjAgMjAgMjQgMjIgMjQgMjZIMThDMTggMjIgMTYgMjAgMTYgMjBaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
                        <div class="friend-info">
                            <div class="friend-name"><?= htmlspecialchars($friend_item['username']) ?></div>
                            <div class="friend-status">آنلاین</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- محتوای اصلی چت -->
        <div class="dm-content">
            <div class="dm-chat-header">
                <img class="dm-user-avatar" src="uploads/<?= $friend['avatar'] ?>" alt="<?= $friend['username'] ?>"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxMiIgcj0iNiIgZmlsbD0iI2RjZGRkZSIvPgo8cGF0aCBkPSJNMTYgMjBDMjAgMjAgMjQgMjIgMjQgMjZIMThDMTggMjIgMTYgMjAgMTYgMjBaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
                <div style="display: flex; align-items: center;">
                    <?= htmlspecialchars($friend['username']) ?>
                    <div class="online-dot"></div>
                </div>
            </div>
            
            <div class="messages-container" id="messages-container">
                <?php if(empty($messages)): ?>
                    <div style="text-align: center; color: #72767d; padding: 40px;">
                        <p>هنوز هیچ پیامی رد و بدل نشده است.</p>
                        <p>شروع کننده مکالمه باشید!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($messages as $message): ?>
                        <div class="message">
                            <img class="message-avatar" src="uploads/<?= $message['avatar'] ?>" alt="<?= $message['username'] ?>"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNSIgcj0iNy41IiBmaWxsPSIjZGNkZGRlIi8+CjxwYXRoIGQ9Ik0yMCAyNUMzMCAyNSAzOCAzMCAzOCAzNUgyQzIgMzAgMTAgMjUgMjAgMjVaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="message-author"><?= htmlspecialchars($message['username']) ?></span>
                                    <span class="message-time"><?= date('H:i', strtotime($message['created_at'])) ?></span>
                                </div>
                                <div class="message-text"><?= htmlspecialchars($message['content']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="message-input-container">
                <form method="POST" action="" id="message-form">
                    <div class="input-wrapper">
                        <textarea class="message-input" name="message" placeholder="پیام خود را به <?= htmlspecialchars($friend['username']) ?> بنویسید..." rows="1" id="message-textarea"></textarea>
                        <button type="submit" class="send-button hidden" id="send-button">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // اسکرول به پایین
        function scrollToBottom() {
            const messagesContainer = document.getElementById('messages-container');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
        
        // بارگذاری پیام‌های جدید
        function loadNewMessages() {
            const messagesContainer = document.getElementById('messages-container');
            const currentMessageCount = messagesContainer.querySelectorAll('.message').length;
            
            fetch(`get_dm_messages.php?friend_id=<?= $friend_id ?>&count=${currentMessageCount}`)
                .then(response => response.json())
                .then(data => {
                    if (data.newMessages && data.newMessages.length > 0) {
                        // اضافه کردن پیام‌های جدید
                        data.newMessages.forEach(message => {
                            const messageElement = `
                                <div class="message">
                                    <img class="message-avatar" src="uploads/${message.avatar}" alt="${message.username}"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNSIgcj0iNy41IiBmaWxsPSIjZGNkZGRlIi8+CjxwYXRoIGQ9Ik0yMCAyNUMzMCAyNSAzOCAzMCAzOCAzNUgyQzIgMzAgMTAgMjUgMjAgMjVaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
                                    <div class="message-content">
                                        <div class="message-header">
                                            <span class="message-author">${message.username}</span>
                                            <span class="message-time">${message.time}</span>
                                        </div>
                                        <div class="message-text">${message.content}</div>
                                    </div>
                                </div>
                            `;
                            messagesContainer.innerHTML += messageElement;
                        });
                        
                        scrollToBottom();
                    }
                })
                .catch(error => console.error('Error loading messages:', error));
        }
        
        // مدیریت ارسال پیام
        const messageTextarea = document.getElementById('message-textarea');
        const messageForm = document.getElementById('message-form');
        const sendButton = document.getElementById('send-button');
        
        if (messageTextarea && messageForm) {
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
                        messageForm.submit();
                    }
                }
            });
            
            messageTextarea.style.height = 'auto';
            messageTextarea.style.height = Math.min(messageTextarea.scrollHeight, 150) + 'px';
            sendButton.classList.add('hidden');
        }
        
        // فوکوس روی input و اسکرول به پایین هنگام لود
        window.onload = function() {
            scrollToBottom();
            
            const messageTextarea = document.getElementById('message-textarea');
            if (messageTextarea) {
                messageTextarea.focus();
            }
            
            // بررسی پیام‌های جدید هر 3 ثانیه
            setInterval(loadNewMessages, 3000);
        };
    </script>
</body>
</html>