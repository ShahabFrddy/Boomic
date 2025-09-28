<?php
require_once 'includes/init.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!csrf_check($_POST['_csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // ولیدیشن ساده
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'نام کاربری باید بین 3 تا 50 کاراکتر باشد.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'ایمیل نامعتبر است.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'رمز عبور حداقل باید 8 کاراکتر باشد.';
    }
    if ($password !== $password2) {
        $errors[] = 'تکرار رمز عبور مطابقت ندارد.';
    }

    if (empty($errors)) {
        // بررسی یکتایی username/email
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1');
        $stmt->execute(['u' => $username, 'e' => $email]);
        $exists = $stmt->fetch();
        if ($exists) {
            $errors[] = 'نام کاربری یا ایمیل قبلاً ثبت شده است.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :p)');
            $stmt->execute([
                'u' => $username,
                'e' => $email,
                'p' => $password_hash,
            ]);
            flash_set('success', 'ثبت‌نام با موفقیت انجام شد. اکنون وارد شوید.');
            header('Location: login.php');
            exit;
        }
    }
}
$csrf = csrf_token();
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <title>ثبت‌نام</title>
    <link rel="stylesheet" href="login-style.css">
</head>
<body>
<div class="login-container">
    <h1>ثبت‌نام</h1>
    <p style="text-align: center; color: var(--discord-text-muted); margin-bottom: 20px;">برای پیوستن به گپ‌وگفت، یک حساب کاربری بسازید.</p>

    <?php if ($msg = flash_get('success')): ?>
        <p class="success-message" style="color:green; text-align: center; background-color: #3e5a3e; padding: 10px; border-radius: 4px; margin-bottom: 20px;"><?php echo e($msg); ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="register.php">
        <input type="hidden" name="_csrf" value="<?php echo e($csrf); ?>">

        <label>ایمیل: <input name="email" type="email" value="<?php echo e($_POST['email'] ?? '') ?>" required></label>
        <label>نام کاربری: <input name="username" type="text" value="<?php echo e($_POST['username'] ?? '') ?>" required></label>
        <label>رمز عبور: <input type="password" name="password" required></label>
        <label>تکرار رمز عبور: <input type="password" name="password2" required></label>

        <button type="submit">ادامه</button>
    </form>

    <p style="text-align: center;">با ثبت‌نام، موافقت خود را با <a href="#">شرایط خدمات</a> و <a href="#">سیاست حفظ حریم خصوصی</a> ما اعلام می‌کنید.</p>
    <p style="text-align: center; margin-top: 10px;">قبلاً ثبت‌نام کرده‌اید؟ <a href="login.php">ورود</a></p>
</div>
</body>
</html>