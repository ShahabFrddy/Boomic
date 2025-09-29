<?php
// create_server.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $server_name = trim($_POST['server_name']);
    
    if (!empty($server_name)) {
        try {
            // ایجاد سرور جدید
            $stmt = $pdo->prepare("INSERT INTO servers (name, owner_id) VALUES (?, ?)");
            $stmt->execute([$server_name, $_SESSION['user_id']]);
            
            $server_id = $pdo->lastInsertId();
            
            // ایجاد کانال عمومی پیش‌فرض
            $stmt = $pdo->prepare("INSERT INTO channels (name, server_id) VALUES (?, ?)");
            $stmt->execute(['عمومی', $server_id]);
            
            // اضافه کردن مالک به عنوان عضو سرور
            $stmt = $pdo->prepare("INSERT INTO server_members (server_id, user_id) VALUES (?, ?)");
            $stmt->execute([$server_id, $_SESSION['user_id']]);
            
            $_SESSION['success'] = "سرور با موفقیت ایجاد شد!";
            header("Location: dashboard.php");
            exit();
            
        } catch(PDOException $e) {
            $error = "خطا در ایجاد سرور: " . $e->getMessage();
        }
    } else {
        $error = "نام سرور نمی‌تواند خالی باشد!";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ایجاد سرور - Boomic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        
        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🏗️ ایجاد سرور جدید</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="server_name" placeholder="نام سرور" required>
            </div>
            
            <button type="submit">ایجاد سرور</button>
        </form>
        
        <div class="back-link">
            <a href="dashboard.php">↩ بازگشت به داشبورد</a>
        </div>
    </div>
</body>
</html>