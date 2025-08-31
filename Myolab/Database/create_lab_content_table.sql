-- Laboratuvar içerik tablosu oluştur
CREATE TABLE IF NOT EXISTS `lab_content_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lab_id` int(11) NOT NULL,
  `lab_title` text DEFAULT NULL,
  `main_image` varchar(500) DEFAULT NULL,
  `catalog_info` text DEFAULT NULL,
  `detail_page_info` text DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `added_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lab_id_unique` (`lab_id`),
  KEY `lab_id_index` (`lab_id`),
  CONSTRAINT `fk_lab_content_lab` FOREIGN KEY (`lab_id`) REFERENCES `laboratories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek veri ekle (opsiyonel)
-- INSERT INTO `lab_content_new` (`lab_id`, `lab_title`, `catalog_info`, `detail_page_info`) VALUES 
-- (1, 'Genişletilmiş Gerçeklik Laboratuvarı', 'Bu laboratuvar AR/VR teknolojileri ile çalışır', 'Detaylı bilgi burada yer alacak');

