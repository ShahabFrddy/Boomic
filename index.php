<?php
require_once __DIR__ . '/includes/init.php';

// اگر کاربر وارد شده بود ➝ داشبورد
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// اگر وارد نشده ➝ صفحه ورود
header('Location: login.php');
exit;
