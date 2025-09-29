<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);

$friends = getFriends($user_id);
$pending_requests = getPendingRequests($user_id);
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

// پاک کردن پیام‌های session
unset($_SESSION['success'], $_SESSION['error']);

// ارسال درخواست دوستی
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_friend'])) {
    $username = trim($_POST['username']);
    
    if (empty($username)) {
        $error = 'لطفاً نام کاربری را وارد کنید';
    } else {
        // پیدا کردن کاربر
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$target_user) {
            $error = 'کاربر یافت نشد';
        } else {
            // بررسی وجود درخواست قبلی
            $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
            $stmt->execute([$user_id, $target_user['id'], $target_user['id'], $user_id]);
            $existing_request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_request) {
                if ($existing_request['status'] == 'pending') {
                    $error = 'درخواست دوستی قبلاً ارسال شده است';
                } else {
                    $error = 'این کاربر در لیست دوستان شماست';
                }
            } else {
                // ارسال درخواست جدید
                $stmt = $pdo->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
                if ($stmt->execute([$user_id, $target_user['id']])) {
                    $success = 'درخواست دوستی ارسال شد';
                } else {
                    $error = 'خطا در ارسال درخواست';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دوستان - Discord Clone</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .friends-container {
            display: flex;
            height: 100vh;
            background-color: #36393f;
        }
        
        .friends-sidebar {
            width: 300px;
            background-color: #2f3136;
            display: flex;
            flex-direction: column;
        }
        
        .friends-header {
            padding: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.2);
            font-weight: bold;
            color: white;
        }
        
        .friends-search {
            padding: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.2);
        }
        
        .search-input {
            width: 100%;
            padding: 8px 12px;
            background-color: #40444b;
            border: none;
            border-radius: 4px;
            color: #dcddde;
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
        }
        
        .friend-item:hover {
            background-color: rgba(79, 84, 92, 0.32);
        }
        
        .friend-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-left: 12px;
        }
        
        .friend-info {
            flex-grow: 1;
        }
        
        .friend-name {
            color: white;
            font-weight: 500;
        }
        
        .friend-status {
            color: #b9bbbe;
            font-size: 12px;
        }
        
        .friends-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .content-header {
            padding: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.2);
            color: white;
            font-weight: bold;
        }
        
        .add-friend-form {
            padding: 20px;
            max-width: 500px;
        }
        
        .pending-requests {
            padding: 20px;
        }
        
        .request-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            background-color: #2f3136;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .request-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-accept {
            background-color: #3ba55c;
            padding: 6px 12px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-reject {
            background-color: #ed4245;
            padding: 6px 12px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        
        .online {
            color: #3ba55c;
        }
        
        .offline {
            color: #747f8d;
        }
    </style>
</head>
<body>
    <div class="servers-sidebar">
        <div class="server-icon" onclick="location.href='index.php'" title="سرورها">
            ←
        </div>
        <div class="server-icon" onclick="location.href='friends.php'" title="دوستان" style="background-color: #5865f2;">
            👥
        </div>
    </div>
    
    <div class="friends-container">
        <div class="friends-sidebar">
            <div class="friends-header">
                دوستان
            </div>
            
            <div class="friends-search">
                <input type="text" class="search-input" placeholder="جستجو...">
            </div>
            
            <div class="friends-list">
                <div class="friend-item" onclick="showTab('all')">
                    <div class="friend-name">همه دوستان</div>
                </div>
                <div class="friend-item" onclick="showTab('pending')">
                    <div class="friend-name">درخواست‌های pending</div>
                    <?php if(count($pending_requests) > 0): ?>
                        <span style="background: #ed4245; color: white; padding: 2px 6px; border-radius: 10px; font-size: 12px;">
                            <?= count($pending_requests) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div style="padding: 16px 8px; color: #b9bbbe; font-size: 12px; text-transform: uppercase;">
                    دوستان - <?= count($friends) ?>
                </div>
                
                <?php foreach($friends as $friend): ?>
                    <div class="friend-item" onclick="openDM(<?= $friend['id'] ?>, '<?= $friend['username'] ?>')">
                        <img class="friend-avatar" src="uploads/<?= $friend['avatar'] ?>" alt="<?= $friend['username'] ?>"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxMiIgcj0iNiIgZmlsbD0iI2RjZGRkZSIvPgo8cGF0aCBkPSJNMTYgMjBDMjAgMjAgMjQgMjIgMjQgMjZIMThDMTggMjIgMTYgMjAgMTYgMjBaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
                        <div class="friend-info">
                            <div class="friend-name"><?= htmlspecialchars($friend['username']) ?></div>
                            <div class="friend-status online">آنلاین</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="friends-content">
            <div class="content-header">
                <span id="tab-title">افزودن دوست</span>
            </div>
            
            <?php if($success): ?>
                <div style="color: #3ba55c; margin: 15px; padding: 10px; background-color: rgba(59, 165, 92, 0.1); border-radius: 4px;">
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div style="color: #ed4245; margin: 15px; padding: 10px; background-color: rgba(237, 66, 69, 0.1); border-radius: 4px;">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <!-- تب افزودن دوست -->
            <div id="add-friend-tab" class="tab-content">
                <div class="add-friend-form">
                    <h3>افزودن دوست</h3>
                    <p style="color: #b9bbbe; margin-bottom: 20px;">شما می‌توانید با نام کاربری دوست خود را اضافه کنید.</p>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <input type="text" class="form-control" name="username" placeholder="نام کاربری#0000" required>
                        </div>
                        <button type="submit" name="add_friend" class="btn">ارسال درخواست دوستی</button>
                    </form>
                </div>
            </div>
            
            <!-- تب درخواست‌های pending -->
            <div id="pending-tab" class="tab-content" style="display: none;">
                <div class="pending-requests">
                    <h3>درخواست‌های دوستی</h3>
                    
                    <?php if(count($pending_requests) > 0): ?>
                        <?php foreach($pending_requests as $request): ?>
                            <div class="request-item">
                                <div style="display: flex; align-items: center;">
                                    <img class="friend-avatar" src="uploads/<?= $request['avatar'] ?>" alt="<?= $request['username'] ?>"
                                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxNiIgcj0iMTYiIGZpbGw9IiM1ODY1RjIiLz4KPGNpcmNsZSBjeD0iMTYiIGN5PSIxMiIgcj0iNiIgZmlsbD0iI2RjZGRkZSIvPgo8cGF0aCBkPSJNMTYgMjBDMjAgMjAgMjQgMjIgMjQgMjZIMThDMTggMjIgMTYgMjAgMTYgMjBaIiBmaWxsPSIjZGNkZGRlIi8+Cjwvc3ZnPgo='">
                                    <div style="margin-right: 12px;">
                                        <div class="friend-name"><?= htmlspecialchars($request['username']) ?></div>
                                        <div class="friend-status">می‌خواهد با شما دوست شود</div>
                                    </div>
                                </div>
                                <div class="request-actions">
                                    <a href="accept_request.php?request_id=<?= $request['id'] ?>" class="btn-accept">پذیرش</a>
                                    <a href="reject_request.php?request_id=<?= $request['id'] ?>" class="btn-reject">رد</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #b9bbbe; text-align: center; padding: 40px;">هیچ درخواست دوستی pending ندارید</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- تب چت خصوصی -->
            <div id="dm-tab" class="tab-content" style="display: none;">
                <div class="chat-area">
                    <div class="chat-header">
                        <div class="channel-name" id="dm-user-name"></div>
                    </div>
                    
                    <div class="messages-container" id="dm-messages-container">
                        <!-- پیام‌ها اینجا نمایش داده می‌شوند -->
                    </div>
                    
                    <div class="message-input-container">
                        <form method="POST" action="send_dm.php" id="dm-form">
                            <input type="hidden" name="receiver_id" id="dm-receiver-id">
                            <div class="input-wrapper">
                                <textarea class="message-input" name="message" placeholder="پیام خود را بنویسید..." rows="1" id="dm-textarea"></textarea>
                                <button type="submit" class="send-button hidden" id="dm-send-button">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // مخفی کردن همه تب‌ها
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // نمایش تب انتخاب شده
            if (tabName === 'all') {
                document.getElementById('add-friend-tab').style.display = 'block';
                document.getElementById('tab-title').textContent = 'افزودن دوست';
            } else if (tabName === 'pending') {
                document.getElementById('pending-tab').style.display = 'block';
                document.getElementById('tab-title').textContent = 'درخواست‌های دوستی';
            }
        }
        
        function openDM(userId, userName) {
            window.location.href = 'dm.php?friend_id=' + userId;
        }
        
        function loadDMMessages(userId) {
            // در اینجا باید با AJAX پیام‌ها را از سرور بگیریم
            // برای سادگی، فعلاً خالی می‌گذاریم
            document.getElementById('dm-messages-container').innerHTML = '<p style="text-align: center; color: #b9bbbe; padding: 20px;">در حال بارگذاری پیام‌ها...</p>';
            
            // بعداً با AJAX پیام‌ها را پر می‌کنیم
        }
        
        // مدیریت ارسال پیام در چت خصوصی
        const dmTextarea = document.getElementById('dm-textarea');
        const dmForm = document.getElementById('dm-form');
        const dmSendButton = document.getElementById('dm-send-button');
        
        if (dmTextarea && dmForm) {
            dmTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 150) + 'px';
                
                if (this.value.trim() !== '') {
                    dmSendButton.classList.remove('hidden');
                } else {
                    dmSendButton.classList.add('hidden');
                }
            });
            
            dmTextarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (this.value.trim() !== '') {
                        dmForm.submit();
                    }
                }
            });
            
            dmTextarea.style.height = 'auto';
            dmTextarea.style.height = Math.min(dmTextarea.scrollHeight, 150) + 'px';
            dmSendButton.classList.add('hidden');
        }
    </script>
</body>
</html>