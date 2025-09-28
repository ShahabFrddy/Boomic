<?php
// functions.php

// ساده‌سازی sanitize خروجی HTML
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// CSRF token
function csrf_token() {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_check($token) {
    return hash_equals($_SESSION['_csrf_token'] ?? '', $token ?? '');
}

// کار با کاربر وارد شده
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// ساده‌سازی flash message
function flash_set($key, $message) {
    $_SESSION['flash'][$key] = $message;
}
function flash_get($key) {
    if (!empty($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}
