-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 25 Ağu 2025, 06:20:11
-- Sunucu sürümü: 9.1.0
-- PHP Sürümü: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `myolab`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Bilgisayar');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `devices`
--

DROP TABLE IF EXISTS `devices`;
CREATE TABLE IF NOT EXISTS `devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lab_id` int NOT NULL,
  `device_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_count` int DEFAULT '1',
  `purpose` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_num` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lab_id` (`lab_id`),
  KEY `idx_order_num` (`order_num`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `devices`
--

INSERT INTO `devices` (`id`, `lab_id`, `device_name`, `device_model`, `device_count`, `purpose`, `order_num`, `created_at`) VALUES
(9, 9, 'Tuf Gmaings', 'A15', 20, 'Bu bilgisayarlar photoshop eğitimi için kullanılıyor', 2, '2025-08-24 13:15:22'),
(10, 10, 'Msı', 'ConfegarA45', 5, 'Bu bilgisayar VR Oyunlarıını oynatmak için kullanılıyor', 3, '2025-08-24 14:09:06'),
(11, 9, 'Tuf Gmaing', 'asdasd', 4, 'asdasdasds', 2, '2025-08-24 14:34:59');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `equipment_images`
--

DROP TABLE IF EXISTS `equipment_images`;
CREATE TABLE IF NOT EXISTS `equipment_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_num` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `added_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'unknown',
  PRIMARY KEY (`id`),
  KEY `idx_equipment_id` (`equipment_id`),
  KEY `idx_order_num` (`order_num`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `equipment_images`
--

INSERT INTO `equipment_images` (`id`, `equipment_id`, `url`, `alt_text`, `order_num`, `created_at`, `added_by`) VALUES
(9, 9, 'image/uploads/Bilgisayar_Bil_lab1/device_1756041317_68ab1065261de.jpg', 'Tuf Gmaings', 2, '2025-08-24 13:15:22', 'Mehmet Kıvrak'),
(10, 10, 'image/uploads/Bilgisayar_Bil_Lab2/device_1756044542_68ab1cfe3819a.png', 'Msı', 3, '2025-08-24 14:09:06', 'Mehmet Kıvrak'),
(11, 11, 'image/uploads/Bilgisayar_Bil_lab1/device_1756046096_68ab23102d01b.png', 'Tuf Gmaing', 2, '2025-08-24 14:34:59', 'Mehmet Kıvrak');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `images`
--

DROP TABLE IF EXISTS `images`;
CREATE TABLE IF NOT EXISTS `images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lab_id` int DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `order_num` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lab_id` (`lab_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `laboratories`
--

DROP TABLE IF EXISTS `laboratories`;
CREATE TABLE IF NOT EXISTS `laboratories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category_id` int NOT NULL,
  `redirect_url` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `laboratories`
--

INSERT INTO `laboratories` (`id`, `name`, `category_id`, `redirect_url`) VALUES
(9, 'Bil_lab1', 1, ''),
(10, 'Bil_Lab2', 1, ''),
(11, 'fdsfsd', 1, '');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `lab_contents`
--

DROP TABLE IF EXISTS `lab_contents`;
CREATE TABLE IF NOT EXISTS `lab_contents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lab_id` int NOT NULL,
  `content_type` enum('main_image','about_text','lab_title') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `added_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'unknown',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_lab_content` (`lab_id`,`content_type`),
  KEY `idx_lab_id` (`lab_id`),
  KEY `idx_content_type` (`content_type`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `lab_contents`
--

INSERT INTO `lab_contents` (`id`, `lab_id`, `content_type`, `content_value`, `alt_text`, `created_at`, `updated_at`, `added_by`) VALUES
(7, 9, 'main_image', 'image/uploads/Bilgisayar_Bil_lab1/device_1756040522_68ab0d4a3caf6.jpg', '', '2025-08-24 13:02:03', '2025-08-24 13:02:03', 'Mehmet Kıvrak'),
(8, 10, 'main_image', 'image/uploads/Bilgisayar_Bil_Lab2/device_1756044432_68ab1c901461b.jpg', '', '2025-08-24 14:07:13', '2025-08-24 14:07:13', 'Mehmet Kıvrak'),
(9, 9, 'about_text', 'zaaaaaaaaaaaaaaa', '', '2025-08-24 14:14:42', '2025-08-24 14:22:17', 'Mehmet Kıvrak'),
(10, 9, '', 'asdasdsdaasd', '', '2025-08-24 14:40:35', '2025-08-24 14:40:35', 'Mehmet Kıvrak');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `mail` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `authority` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mail` (`mail`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `full_name`, `mail`, `password`, `authority`) VALUES
(1, 'Mehmet Kıvrak', 'selam77@gmail.com', '$2y$10$VSF.ASSdL6kBtOydCOV9febwQCcVc/bi4MarkDXFCqYTji7SVoGMi', 'user'),
(2, 'Mehmet Kıvrak', 'selam76@gmail.com', '$2y$10$xzN4v0Pgrsh1fpeaB6ektewTNajkS4CbaIGKHTAJsr7kK6RgrYVsm', 'user'),
(3, 'Mehmet Kıvrakx', 'selam79@gmail.com', '$2y$10$qDK02pKYRFP1lTXpnExFjuXrPlMl2pQCJuliNDb1FcqZqiwXaBMVe', 'user'),
(4, 'Mehmet Kıvrak', 'selam71@gmail.com', '$2y$10$BXtwZa.vxKeLvAkrIT0Qk.t1FYqRps3.Y2tqNf0tP1I0F5MnR1EQO', 'user'),
(5, 'Mehmet Kıvrak', 'selam899@gmail.com', '$2y$10$otKtxmjjKn7/aGpD5mpSIOXAQXy7vggbSFY5Eo8TYqk32CnRcUrDO', 'user'),
(6, 'Mehmet Kıvrak', 'kivr.mehmet@gmail.com', '$2y$10$1dQoM.8DBoLuusRMhdiNUukc1d621xYiUg2/lvfzcplqwjIRF003S', 'user'),
(7, 'Admin User', 'admin@myolab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`lab_id`) REFERENCES `laboratories` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `equipment_images`
--
ALTER TABLE `equipment_images`
  ADD CONSTRAINT `equipment_images_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `lab_contents`
--
ALTER TABLE `lab_contents`
  ADD CONSTRAINT `lab_contents_ibfk_1` FOREIGN KEY (`lab_id`) REFERENCES `laboratories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
