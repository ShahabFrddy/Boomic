<?php
// init.php

// تنظیمات امنیتی جلسه (باید قبل از session_start باشه)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// حالا سشن رو شروع کن
session_start();

// load config
$config = require __DIR__ . '/config.php';

// PDO connection ...


// PDO connection
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], $options);
} catch (Exception $e) {
    // در محیط تولید خطای کامل نشان نده
    die('Database connection failed.');
}

require_once __DIR__ . '/functions.php';
