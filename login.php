<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$qrCodeUrl = '';

// تولید QR Code برای اسکن
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $qrToken = generateToken();
    $_SESSION['qr_token'] = $qrToken;
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode(json_encode([
        'token' => $qrToken,
        'action' => 'login',
        'timestamp' => time()
    ]));
}

// پردازش فرم لاگین معمولی
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
            
            // اگر کاربر از موبایل لاگین کرده، session ایجاد کن
            if (isMobileDevice()) {
                $sessionToken = generateToken();
                $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, device_type, expires_at) VALUES (?, ?, 'mobile', DATE_ADD(NOW(), INTERVAL 1 HOUR))");
                $stmt->execute([$user['id'], $sessionToken]);
            }
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'نام کاربری یا رمز عبور نادرست است';
        }
    }
}

// API برای بررسی وضعیت QR Code
if (isset($_GET['check_qr']) && isset($_SESSION['qr_token'])) {
    header('Content-Type: application/json');
    
    $stmt = $pdo->prepare("SELECT u.* FROM users u 
                          JOIN user_sessions us ON u.id = us.user_id 
                          WHERE us.session_token = ? AND us.is_active = TRUE AND us.expires_at > NOW()");
    $stmt->execute([$_SESSION['qr_token']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // لاگین موفق
        $_SESSION['user_id'] = $user['id'];
        
        // غیرفعال کردن session موبایل
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = FALSE WHERE session_token = ?");
        $stmt->execute([$_SESSION['qr_token']]);
        
        echo json_encode(['status' => 'success', 'user_id' => $user['id']]);
        exit();
    }
    
    echo json_encode(['status' => 'pending']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به دیسکورد</title>
    <link rel="stylesheet" href="loginstyle.css?v=5">
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
                    <?php if($qrCodeUrl): ?>
                        <img src="<?= $qrCodeUrl ?>" alt="کد QR برای ورود" id="qrCode">
                    <?php else: ?>
                        <div class="qr-loading">در حال تولید QR Code...</div>
                    <?php endif; ?>
                </div>
                <h3>ورود با QR Code</h3>
                <p>اگر در <span class="mobile-app-text">برنامه موبایل دیسکورد</span> وارد شده‌اید، این کد را اسکن کنید تا فورا وارد شوید.</p>
                <div id="qrStatus" class="qr-status"></div>
            </div>
            
        </div>
    </div>

    <script>
        // بررسی وضعیت QR Code هر 2 ثانیه
        function checkQRStatus() {
            fetch('?check_qr=1')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('qrStatus').innerHTML = 
                            '<div style="color: #3ba55c;">✓ ورود موفق! در حال انتقال...</div>';
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 1000);
                    } else if (data.status === 'pending') {
                        setTimeout(checkQRStatus, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    setTimeout(checkQRStatus, 2000);
                });
        }

        // شروع بررسی QR Code
        <?php if(isset($_SESSION['qr_token'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            checkQRStatus();
        });
        <?php endif; ?>
    </script>
</body>
</html>