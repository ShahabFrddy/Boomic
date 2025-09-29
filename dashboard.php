<?php
// dashboard.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// بررسی وجود جداول
try {
    // دریافت سرورهایی که کاربر مالک آن‌ها است
    $stmt = $pdo->prepare("SELECT * FROM servers WHERE owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $owned_servers = $stmt->fetchAll();
    
    // دریافت سرورهایی که کاربر عضو آن‌ها است
    $stmt = $pdo->prepare("
        SELECT s.* FROM servers s 
        INNER JOIN server_members sm ON s.id = sm.server_id 
        WHERE sm.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $member_servers = $stmt->fetchAll();
    
    $all_servers = array_merge($owned_servers, $member_servers);
    
} catch(PDOException $e) {
    // اگر جدول server_members وجود ندارد، فقط سرورهای مالکیت را نشان بده
    if (strpos($e->getMessage(), 'server_members') !== false) {
        $stmt = $pdo->prepare("SELECT * FROM servers WHERE owner_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $all_servers = $stmt->fetchAll();
    } else {
        die("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد - Boomic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .welcome {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .server-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .server-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .server-card:hover {
            transform: translateY(-5px);
        }
        
        .server-name {
            font-size: 20px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .server-actions {
            margin-top: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: opacity 0.3s;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .create-server {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .actions {
            margin-top: 20px;
        }
        
        .logout {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="welcome">👋 خوش آمدید, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>این داشبورد اصلی Boomic شما است</p>
        </div>
        
        <?php if (empty($all_servers)): ?>
            <div class="server-card">
                <h3>🚫 هنوز هیچ سروری ندارید!</h3>
                <p>اولین سرور خود را ایجاد کنید یا به یک سرور بپیوندید.</p>
            </div>
        <?php else: ?>
            <h2 style="color: white; margin-bottom: 15px;">سرورهای شما</h2>
            <div class="server-list">
                <?php foreach ($all_servers as $server): ?>
                    <div class="server-card">
                        <h3 class="server-name">🏠 <?php echo htmlspecialchars($server['name']); ?></h3>
                        <p>🆔 شناسه: <?php echo $server['id']; ?></p>
                        <p>📅 ایجاد شده در: <?php echo $server['created_at']; ?></p>
                        <div class="server-actions">
                            <a href="server.php?id=<?php echo $server['id']; ?>" class="btn">ورود به سرور</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="create-server">
            <h2>🎯 ایجاد سرور جدید</h2>
            <p>یک سرور جدید ایجاد کنید و با دوستان خود ارتباط برقرار کنید</p>
            <div class="actions">
                <a href="create_server.php" class="btn">ایجاد سرور جدید</a>
                <a href="logout.php" class="btn logout">خروج</a>
            </div>
        </div>
    </div>
</body>
</html>