<?php
// create_database.php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // اتصال به MySQL بدون انتخاب دیتابیس
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ایجاد دیتابیس
    $pdo->exec("CREATE DATABASE IF NOT EXISTS boomic_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE boomic_db");
    
    // ایجاد جدول کاربران
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            avatar VARCHAR(255) DEFAULT 'default.png',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // ایجاد جدول سرورها
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS servers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            owner_id INT,
            icon VARCHAR(255) DEFAULT 'default_server.png',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // ایجاد جدول اعضای سرور
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS server_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            server_id INT,
            user_id INT,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_member (server_id, user_id)
        )
    ");
    
    // ایجاد جدول کانال‌ها
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS channels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            server_id INT,
            type ENUM('text', 'voice') DEFAULT 'text',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
        )
    ");
    
    // ایجاد جدول پیام‌ها
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            content TEXT NOT NULL,
            user_id INT,
            channel_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE
        )
    ");
    
    echo "✅ Database and tables created successfully!<br>";
    echo "📊 Database: boomic_db<br>";
    echo "📋 Tables: users, servers, server_members, channels, messages<br>";
    echo "<a href='register.php'>Go to Registration</a>";
    
} catch(PDOException $e) {
    die("Error creating database: " . $e->getMessage());
}
?>