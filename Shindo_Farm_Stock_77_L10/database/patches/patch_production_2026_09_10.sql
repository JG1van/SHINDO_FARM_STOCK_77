-- ============================================================
-- Patch: Update Database Production (byethost32)
-- Jalankan di phpMyAdmin SQL tab, satu blok saja
-- Aman dijalankan berulang (jika sudah ada skip otomatis)
-- ============================================================

-- 1. Tambah kolom role ke tabel users (nullable, default 'admin')
ALTER TABLE `users`
  ADD COLUMN `role` ENUM('super_admin','admin','staf_ayam','staf_keuangan') NULL DEFAULT 'admin'
  AFTER `password`;

-- 2. Set role user existing
UPDATE `users` SET `role` = 'super_admin' WHERE `email` = 'gian123ivan@gmail.com';
UPDATE `users` SET `role` = 'admin' WHERE `role` IS NULL AND `email` != 'gian123ivan@gmail.com';

-- 3. Buat tabel activity_logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `logable_type` varchar(255) NOT NULL,
  `logable_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_logable_type_logable_id_index` (`logable_type`,`logable_id`),
  KEY `activity_logs_user_id_index` (`user_id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Update password admin default (ADMIN77) — biarkan asli, user bisa reset sendiri dari panel

-- Selesai! Cek:
--   SELECT role FROM users;
--   DESCRIBE activity_logs;
