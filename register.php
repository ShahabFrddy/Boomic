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
<head><meta charset="utf-8"><title>ثبت‌نام</title></head>
<body>
<h1>ثبت‌نام</h1>
<?php if ($msg = flash_get('success')): ?>
  <p style="color:green;"><?php echo e($msg); ?></p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <ul style="color:red;">
    <?php foreach ($errors as $err): ?>
      <li><?php echo e($err); ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post" action="register.php">
  <input type="hidden" name="_csrf" value="<?php echo e($csrf); ?>">
  <label>نام کاربری: <input name="username" value="<?php echo e($_POST['username'] ?? '') ?>"></label><br>
  <label>ایمیل: <input name="email" value="<?php echo e($_POST['email'] ?? '') ?>"></label><br>
  <label>رمز عبور: <input type="password" name="password"></label><br>
  <label>تکرار رمز عبور: <input type="password" name="password2"></label><br>
  <button type="submit">ثبت‌نام</button>
</form>

<p>قبلاً ثبت‌نام کرده‌اید؟ <a href="login.php">ورود</a></p>
</body>
</html>
