<?php
require_once 'includes/init.php';
require_login();

$username = $_SESSION['username'] ?? 'کاربر';
?>
<!doctype html>
<html lang="fa">
<head><meta charset="utf-8"><title>داشبورد</title></head>
<body>
<h1>خوش آمدی، <?php echo e($username); ?></h1>
<p><a href="logout.php">خروج</a></p>
</body>
</html>
