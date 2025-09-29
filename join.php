<?php
require_once 'config.php';

$code = isset($_GET['code']) ? $_GET['code'] : '';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];
}

if (!empty($code)) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = "join.php?code=$code";
        header('Location: login.php');
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $result = useInvite($code, $user_id);
    
    if ($result) {
        $success = 'با موفقیت به سرور پیوستید!';
        header("Location: index.php?server=$result");
        exit();
    } else {
        $error = 'لینک دعوت معتبر نیست یا منقضی شده است';
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پیوستن به سرور - Discord Clone</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .join-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #36393f;
        }
        
        .join-form {
            background-color: #2f3136;
            padding: 32px;
            border-radius: 8px;
            width: 480px;
            box-shadow: 0 2px 10px 0 rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .join-form h2 {
            color: white;
            margin-bottom: 20px;
        }
        
        .join-form p {
            color: #b9bbbe;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="join-container">
        <div class="join-form">
            <h2>پیوستن به سرور</h2>
            
            <?php if($success): ?>
                <div style="color: #3ba55c; margin-bottom: 15px; padding: 10px; background-color: rgba(59, 165, 92, 0.1); border-radius: 4px;">
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div style="color: #ed4245; margin-bottom: 15px; padding: 10px; background-color: rgba(237, 66, 69, 0.1); border-radius: 4px;">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if(isLoggedIn()): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="code">کد دعوت</label>
                        <input type="text" class="form-control" id="code" name="code" value="<?= htmlspecialchars($code) ?>" placeholder="کد دعوت را وارد کنید" required>
                    </div>
                    <button type="submit" class="btn">پیوستن به سرور</button>
                </form>
            <?php else: ?>
                <p>برای پیوستن به سرور باید وارد حساب کاربری خود شوید.</p>
                <a href="login.php" class="btn" style="display: inline-block; width: auto; padding: 10px 20px;">ورود به حساب</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>