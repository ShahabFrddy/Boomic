<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'لطفاً تمام فیلدها را پر کنید';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit();
        } else {
            $error = 'نام کاربری یا رمز عبور نادرست است';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به دیسکورد</title>
    <link rel="stylesheet" href="loginstyle.css?v=3">
</head>
<body>
    <div class="login-container">
        <div class="login-box"> 
            
            <div class="form-section">
                <form class="auth-form" method="POST" action="">
                    <h1>خوش آمدید!</h1>
                    <p class="subtitle">از دیدن دوباره شما خوشحالیم!</p>
                    
                    <?php if($error): ?>
                        <div class="error-message" style="color: #ed4245; margin-bottom: 15px; text-align: center;"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <div class="input-group">
                        <label for="username">ایمیل یا نام کاربری</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                    </div>
                    
                    <div class="input-group">
                        <label for="password">رمز عبور</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <a href="#" class="forgot-password">رمز عبور خود را فراموش کرده‌اید؟</a>
                    
                    <button type="submit" class="login-button">ورود</button>
                    
                    <div class="register-link">
                        نیاز به حساب کاربری دارید؟ <a href="register.php">ثبت نام</a>
                    </div>
                </form>
            </div>
            
            <div class="qr-section">
                <div class="qr-code-placeholder">
                    <img src="https://via.placeholder.com/150/202225/ffffff?text=QR+Code" alt="کد QR برای ورود">
                </div>
                <h3>ورود با QR Code</h3>
                <p>این کد را با <span class="mobile-app-text">برنامه موبایل دیسکورد</span> اسکن کنید تا فورا وارد شوید.</p>
            </div>
            
        </div>
    </div>
</body>
</html>