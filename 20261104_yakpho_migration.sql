-- ==============================================================================
-- Yak Pho Aroma / Esther Aroma — Complete Database Migration Script
-- Date: 2026-11-04
-- Target Server Database: woodysay_esther (DirectAdmin)
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. Table: user_tokens (For 30-day VIP links and Remember-Me tokens)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Table: member_points_log (History log for E-Stamp / M-Stamp rewards)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `member_points_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `points_change` INT NOT NULL,
    `balance_after` INT NOT NULL DEFAULT 0,
    `action_type` VARCHAR(50) NOT NULL DEFAULT 'order_reward',
    `reference_id` INT DEFAULT NULL,
    `note` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Tables: user_tags & user_tag_mapping (Admin Customer Segmentation Tags)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_tags` (
    `tag_id` INT AUTO_INCREMENT PRIMARY KEY,
    `tag_name` VARCHAR(100) NOT NULL UNIQUE,
    `tag_color` VARCHAR(20) DEFAULT '#065f46',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_tag_mapping` (
    `mapping_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `tag_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_tag_unique` (`user_id`, `tag_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Table: affiliates (Therapist & Spa Partner Referral System)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `affiliates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `affiliate_code` VARCHAR(50) NOT NULL UNIQUE,
    `total_shops` INT NOT NULL DEFAULT 0,
    `total_commission` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Table: addr (User Shipping Addresses for Domestic & International)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `addr` (
    `addr_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `addr_name` VARCHAR(255) DEFAULT NULL,
    `addr_mobile` VARCHAR(50) DEFAULT NULL,
    `addr_detail` TEXT DEFAULT NULL,
    `addr_detail2` TEXT DEFAULT NULL,
    `addr_postcode` VARCHAR(20) DEFAULT NULL,
    `country_id` INT DEFAULT 171,
    `provinces_id` INT DEFAULT 1,
    `addr_type` INT DEFAULT 1,
    `addr_status` INT DEFAULT 1,
    `addr_del` INT DEFAULT 0,
    `addr_tax_id` VARCHAR(50) DEFAULT NULL,
    `addr_branch` VARCHAR(20) DEFAULT '00000',
    `addr_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `addr_update` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Add Missing Columns to `user` Table (Safe Stored Procedure for any MySQL version)
-- ------------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS `AddColIfNotExist`;
DELIMITER $$
CREATE PROCEDURE `AddColIfNotExist`(
    IN tableName VARCHAR(64),
    IN colName VARCHAR(64),
    IN colDef TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = tableName 
        AND COLUMN_NAME = colName
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', colName, '` ', colDef);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Execute AddColIfNotExist for `user` table
CALL AddColIfNotExist('user', 'role', 'VARCHAR(50) NOT NULL DEFAULT \'customer\'');
CALL AddColIfNotExist('user', 'user_stars', 'INT NOT NULL DEFAULT 0');
CALL AddColIfNotExist('user', 'm_stamp_points', 'INT NOT NULL DEFAULT 0');
CALL AddColIfNotExist('user', 'member_tier', 'VARCHAR(50) NOT NULL DEFAULT \'general\'');
CALL AddColIfNotExist('user', 'user_nickname', 'VARCHAR(100) DEFAULT NULL');
CALL AddColIfNotExist('user', 'user_birthdate', 'VARCHAR(50) DEFAULT NULL');
CALL AddColIfNotExist('user', 'line_user_id', 'VARCHAR(100) DEFAULT NULL');
CALL AddColIfNotExist('user', 'line_display_name', 'VARCHAR(150) DEFAULT NULL');
CALL AddColIfNotExist('user', 'line_picture_url', 'VARCHAR(500) DEFAULT NULL');
CALL AddColIfNotExist('user', 'affiliate_code', 'VARCHAR(50) DEFAULT NULL');
CALL AddColIfNotExist('user', 'email_verified', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL AddColIfNotExist('user', 'user_key', 'VARCHAR(100) DEFAULT NULL');
CALL AddColIfNotExist('user', 'user_google_map', 'TEXT DEFAULT NULL');
CALL AddColIfNotExist('user', 'user_website', 'VARCHAR(255) DEFAULT NULL');
CALL AddColIfNotExist('user', 'user_picture', 'VARCHAR(255) DEFAULT NULL');
CALL AddColIfNotExist('user', 'user_note', 'TEXT DEFAULT NULL');
CALL AddColIfNotExist('user', 'user_del', 'INT NOT NULL DEFAULT 0');
CALL AddColIfNotExist('user', 'user_status', 'INT NOT NULL DEFAULT 1');

-- Execute AddColIfNotExist for `orders` table
CALL AddColIfNotExist('orders', 'orders_del', 'INT NOT NULL DEFAULT 0');
CALL AddColIfNotExist('orders', 'orders_delicosttrue', 'DECIMAL(10,2) DEFAULT 0.00');
CALL AddColIfNotExist('orders', 'orders_country_id', 'INT DEFAULT 171');
CALL AddColIfNotExist('orders', 'orders_destination', 'VARCHAR(50) DEFAULT \'domestic\'');
CALL AddColIfNotExist('orders', 'orders_tracking', 'VARCHAR(100) DEFAULT NULL');
CALL AddColIfNotExist('orders', 'track_token', 'VARCHAR(64) DEFAULT NULL');

-- Clean up helper procedure
DROP PROCEDURE IF EXISTS `AddColIfNotExist`;

-- ------------------------------------------------------------------------------
-- 7. Normalize default values & sync points
-- ------------------------------------------------------------------------------
UPDATE `user` SET `m_stamp_points` = `user_stars` WHERE `user_stars` > 0 AND (`m_stamp_points` = 0 OR `m_stamp_points` IS NULL);
UPDATE `user` SET `role` = 'customer' WHERE `role` = '' OR `role` IS NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- Migration Completed Successfully!
-- ==============================================================================
