<?php
// index.php
require_once __DIR__ . '/includes/init.php';

// اگر کاربر وارد شده، هدایت به داشبورد (اختیاری — می‌تونی این را حذف کنی تا صفحه اصلی ثابت بمونه)
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// نمایش پیام‌ها (flash)
$success = flash_get('success');
$info = flash_get('info');
$error = flash_get('error');
?>
<!doctype html>
<html lang="fa">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>صفحه اصلی</title>
  <style>
    body { font-family: Tahoma, Arial, sans-serif; direction: rtl; padding: 20px; background:#f7f7f7; }
    .card { background: #fff; padding: 18px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); max-width:700px; margin:auto; }
    .nav { text-align: center; margin-bottom: 12px; }
    a { text-decoration: none; color: #2b6cb0; }
    .msg { padding:10px; border-radius:6px; margin-bottom:12px; }
    .success { background:#e6ffed; border:1px solid #b7f2c9; color:#085e2b; }
    .info { background:#eef2ff; border:1px solid #cfd9ff; color:#1e3a8a; }
    .error { background:#ffecec; border:1px solid #ffbcbc; color:#7a1a1a; }
  </style>
</head>
<body>
  <div class="card">
    <h1>خوش آمدید</h1>

    <?php if ($success): ?>
      <div class="msg success"><?php echo e($success); ?></div>
    <?php endif; ?>
    <?php if ($info): ?>
      <div class="msg info"><?php echo e($info); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="msg error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <p>این یک صفحه اصلی ساده برای سامانه کاربران است. اگر حساب داری وارد شو وگرنه ثبت‌نام کن.</p>

    <div class="nav">
      <a href="login.php">ورود</a> |
      <a href="register.php">ثبت‌نام</a> |
      <a href="dashboard.php">داشبورد</a>
    </div>

    <hr>

    <h3>قابلیت‌ها</h3>
    <ul>
      <li>ثبت‌نام با ایمیل و نام کاربری</li>
      <li>ورود امن با password_hash و جلسات امن</li>
      <li>حفاظت CSRF برای فرم‌ها</li>
    </ul>

    <p style="color:#666; font-size:0.9em;">توجه: برای تولیدی شدن، حتماً از HTTPS استفاده کن و تنظیمات session.cookie_secure رو در php.ini فعال کن.</p>
  </div>
</body>
</html>
