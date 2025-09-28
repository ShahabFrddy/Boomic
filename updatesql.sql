CREATE DATABASE boomic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE boomic;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_active TINYINT(1) DEFAULT 1 -- برای فعال/غیرفعال یا تایید ایمیل
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
