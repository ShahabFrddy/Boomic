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
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود - Discord Clone</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>
    <div class="auth-container">
        <form class="auth-form" method="POST" action="">
            <h2>خوش آمدید!</h2>
            
            <?php if($error): ?>
                <div style="color: #ed4245; margin-bottom: 15px; text-align: center;"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">ایمیل یا نام کاربری</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">رمز عبور</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">ورود</button>
            
            <div class="auth-link">
                <a href="register.php">نیاز به حساب کاربری دارید؟</a>
            </div>
        </form>
    </div>
</body>
</html>