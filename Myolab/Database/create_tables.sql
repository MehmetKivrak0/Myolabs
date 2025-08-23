-- MyoLab Veritabanı Tabloları
-- Bu dosyayı phpMyAdmin'de veya MySQL komut satırında çalıştırın

-- Users tablosu
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `mail` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `authority` enum('user','admin','moderator') DEFAULT 'user',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_mail` (`mail`),
  KEY `idx_authority` (`authority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Örnek admin kullanıcısı (şifre: Admin123)
INSERT INTO `users` (`full_name`, `mail`, `password`, `authority`) VALUES 
('Admin User', 'admin@myolab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Tabloları kontrol et
SHOW TABLES;
DESCRIBE users;
