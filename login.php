<?php
require_once 'includes/init.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    $login = trim($_POST['login'] ?? ''); // می‌تونه username یا email باشه
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $errors[] = 'لطفاً همه فیلدها را پر کنید.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, is_active FROM users WHERE username = :l OR email = :l LIMIT 1');
        $stmt->execute(['l' => $login]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            if (empty($user['is_active'])) {
                $errors[] = 'حساب شما فعال نیست.';
            } else {
                // ورود موفق
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $errors[] = 'نام کاربری/ایمیل یا رمز عبور اشتباه است.';
        }
    }
}

$csrf = csrf_token();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8"><title>ورود عزیز</title>
    <link rel="stylesheet" href="login-style.css">
</head>
    <body>
        <div class="login-container">
            <h1>ورود</h1>

            <?php if (!empty($errors)): ?>
            <ul class="error-list" style="color:red;">
                <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <form method="post" action="login.php">
                <input type="hidden" name="_csrf" value="<?php echo e($csrf); ?>">
                <label>نام کاربری یا ایمیل: <input type="text" name="login" value="<?php echo e($_POST['login'] ?? '') ?>"></label>
                <label>رمز عبور: <input type="password" name="password"></label><br>
                <button type="submit">ورود</button>
            </form>

            <p>هنوز عضو نیستی؟ <a href="register.php">ثبت‌نام</a></p>
        </div>
    </body>
</html>