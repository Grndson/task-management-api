-- ============================================================
-- Task Management API — SQL Dump
-- Database: MySQL 8.x
-- Generated for: task_management database
-- ============================================================

CREATE DATABASE IF NOT EXISTS `task_management`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `task_management`;

-- -----------------------------------------------------------
-- Table: tasks
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `tasks`;

CREATE TABLE `tasks` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(255)    NOT NULL,
  `due_date`   DATE            NOT NULL,
  `priority`   ENUM('low','medium','high') NOT NULL,
  `status`     ENUM('pending','in_progress','done') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP       NULL DEFAULT NULL,
  `updated_at` TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tasks_title_due_date_unique` (`title`, `due_date`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Laravel migrations tracking table
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` VARCHAR(255) NOT NULL,
  `batch`     INT          NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2024_01_01_000000_create_tasks_table', 1);

-- -----------------------------------------------------------
-- Sample seed data (matches TaskSeeder.php)
-- Dates are relative; adjust CURDATE() offsets as needed.
-- -----------------------------------------------------------
INSERT INTO `tasks` (`title`, `due_date`, `priority`, `status`, `created_at`, `updated_at`) VALUES
('Fix critical login bug',          DATE_ADD(CURDATE(), INTERVAL 1 DAY),  'high',   'in_progress', NOW(), NOW()),
('Deploy hotfix to production',     DATE_ADD(CURDATE(), INTERVAL 1 DAY),  'high',   'pending',     NOW(), NOW()),
('Write unit tests for auth module',DATE_ADD(CURDATE(), INTERVAL 3 DAY),  'medium', 'pending',     NOW(), NOW()),
('Update API documentation',        DATE_ADD(CURDATE(), INTERVAL 5 DAY),  'medium', 'done',        NOW(), NOW()),
('Clean up unused CSS files',       DATE_ADD(CURDATE(), INTERVAL 7 DAY),  'low',    'done',        NOW(), NOW()),
('Code review for pull request #42',DATE_ADD(CURDATE(), INTERVAL 2 DAY),  'high',   'pending',     NOW(), NOW()),
('Set up CI/CD pipeline',           DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'medium', 'in_progress', NOW(), NOW());
