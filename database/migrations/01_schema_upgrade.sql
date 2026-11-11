-- Migration 01: Schema Upgrade for COD & Store Pickup, Categories Table, and Data Fixes
-- Target DB: cec-comart-web
-- Idempotent script: Safe to run multiple times without errors

-- --------------------------------------------------------------------------------------
-- 1. UPGRADE orders TABLE SCHEMA
-- --------------------------------------------------------------------------------------

-- Drop legacy/transitional columns if they exist under old names
SET @exist_order_method := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'order_method'
);
SET @sql := IF(@exist_order_method > 0, 'ALTER TABLE `orders` DROP COLUMN `order_method`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_payment_method := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_method'
);
SET @sql := IF(@exist_payment_method > 0, 'ALTER TABLE `orders` DROP COLUMN `payment_method`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_pickup_slot := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'pickup_slot'
);
SET @sql := IF(@exist_pickup_slot > 0, 'ALTER TABLE `orders` DROP COLUMN `pickup_slot`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist_delivery_instructions := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_instructions'
);
SET @sql := IF(@exist_delivery_instructions > 0, 'ALTER TABLE `orders` DROP COLUMN `delivery_instructions`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add purchase_method ENUM('cod', 'pickup') NOT NULL DEFAULT 'cod' AFTER user_id
SET @exist_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'purchase_method'
);
SET @sql := IF(@exist_col = 0, 
    'ALTER TABLE `orders` ADD COLUMN `purchase_method` ENUM(\'cod\', \'pickup\') NOT NULL DEFAULT \'cod\' AFTER `user_id`',
    'ALTER TABLE `orders` MODIFY COLUMN `purchase_method` ENUM(\'cod\', \'pickup\') NOT NULL DEFAULT \'cod\' AFTER `user_id`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add payment_status ENUM('unpaid', 'paid', 'cancelled') NOT NULL DEFAULT 'unpaid' AFTER purchase_method
SET @exist_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_status'
);
SET @sql := IF(@exist_col = 0, 
    'ALTER TABLE `orders` ADD COLUMN `payment_status` ENUM(\'unpaid\', \'paid\', \'cancelled\') NOT NULL DEFAULT \'unpaid\' AFTER `purchase_method`',
    'ALTER TABLE `orders` MODIFY COLUMN `payment_status` ENUM(\'unpaid\', \'paid\', \'cancelled\') NOT NULL DEFAULT \'unpaid\' AFTER `purchase_method`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add customer_name VARCHAR(150) NOT NULL DEFAULT '' AFTER payment_status
SET @exist_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_name'
);
SET @sql := IF(@exist_col = 0, 
    'ALTER TABLE `orders` ADD COLUMN `customer_name` VARCHAR(150) NOT NULL DEFAULT \'\' AFTER `payment_status`',
    'ALTER TABLE `orders` MODIFY COLUMN `customer_name` VARCHAR(150) NOT NULL DEFAULT \'\' AFTER `payment_status`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add customer_phone VARCHAR(30) NOT NULL DEFAULT '' AFTER customer_name
SET @exist_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'customer_phone'
);
SET @sql := IF(@exist_col = 0, 
    'ALTER TABLE `orders` ADD COLUMN `customer_phone` VARCHAR(30) NOT NULL DEFAULT \'\' AFTER `customer_name`',
    'ALTER TABLE `orders` MODIFY COLUMN `customer_phone` VARCHAR(30) NOT NULL DEFAULT \'\' AFTER `customer_name`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add delivery_notes TEXT NULL AFTER street_address2
SET @exist_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'delivery_notes'
);
SET @sql := IF(@exist_col = 0, 
    'ALTER TABLE `orders` ADD COLUMN `delivery_notes` TEXT NULL AFTER `street_address2`',
    'ALTER TABLE `orders` MODIFY COLUMN `delivery_notes` TEXT NULL AFTER `street_address2`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add pickup_date DATE NULL AFTER delivery_notes
SET @exist_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'pickup_date'
);
SET @sql := IF(@exist_col = 0, 
    'ALTER TABLE `orders` ADD COLUMN `pickup_date` DATE NULL AFTER `delivery_notes`',
    'ALTER TABLE `orders` MODIFY COLUMN `pickup_date` DATE NULL AFTER `delivery_notes`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add pickup_time_slot VARCHAR(50) NULL AFTER pickup_date
SET @exist_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'pickup_time_slot'
);
SET @sql := IF(@exist_col = 0, 
    'ALTER TABLE `orders` ADD COLUMN `pickup_time_slot` VARCHAR(50) NULL AFTER `pickup_date`',
    'ALTER TABLE `orders` MODIFY COLUMN `pickup_time_slot` VARCHAR(50) NULL AFTER `pickup_date`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add pickup_code VARCHAR(20) NULL AFTER pickup_time_slot
SET @exist_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'pickup_code'
);
SET @sql := IF(@exist_col = 0, 
    'ALTER TABLE `orders` ADD COLUMN `pickup_code` VARCHAR(20) NULL AFTER `pickup_time_slot`',
    'ALTER TABLE `orders` MODIFY COLUMN `pickup_code` VARCHAR(20) NULL AFTER `pickup_time_slot`'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Modify order_status ENUM to: ENUM('pending', 'processing', 'ready_for_pickup', 'shipped', 'delivered', 'collected', 'cancelled') NOT NULL DEFAULT 'pending'
ALTER TABLE `orders` 
    MODIFY COLUMN `order_status` ENUM('pending', 'processing', 'ready_for_pickup', 'shipped', 'delivered', 'collected', 'cancelled') NOT NULL DEFAULT 'pending';


-- --------------------------------------------------------------------------------------
-- 2. CREATE AND SEED categories TABLE
-- --------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `categories` (`id`, `name`, `slug`, `image_path`, `status`) VALUES
(1, 'Vegetables', 'vegetables', 'assets/images/shopCategory/vegetable.png', 'active'),
(2, 'Fruits', 'fruits', 'assets/images/shopCategory/fruits.png', 'active'),
(3, 'Snacks', 'snacks', 'assets/images/shopCategory/snack.png', 'active'),
(4, 'Biscuits', 'biscuits', 'assets/images/shopCategory/biscuit.png', 'active'),
(5, 'Coffee', 'coffee', 'assets/images/shopCategory/coffee.png', 'active'),
(6, 'Eggs', 'eggs', 'assets/images/shopCategory/eggs.png', 'active'),
(7, 'Water', 'water', 'assets/images/shopCategory/waterBottle.png', 'active'),
(8, 'Tea', 'tea', 'assets/images/shopCategory/tea-bag.png', 'active'),
(9, 'Cheese', 'cheese', 'assets/images/shopCategory/cheese.png', 'active'),
(10, 'Yoghurts & Curd', 'yoghurts-curd', 'assets/images/shopCategory/yoghurt.png', 'active'),
(11, 'Desserts', 'desserts', 'assets/images/shopCategory/yoghurt.png', 'active'),
(12, 'Beverages', 'beverages', 'assets/images/shopCategory/beverages.png', 'active'),
(13, 'Bakery', 'bakery', 'assets/images/shopCategory/bread.png', 'active'),
(14, 'Meat & Seafood', 'meat-seafood', 'assets/images/shopCategory/meat.png', 'active'),
(15, 'Pantry Staples', 'pantry-staples', 'assets/images/shopCategory/pantry.png', 'active'),
(16, 'Dairy Staples', 'dairy-staples', 'assets/images/shopCategory/dairy.png', 'active'),
(17, 'Frozen Foods', 'frozen-foods', 'assets/images/shopCategory/frozen.png', 'active'),
(18, 'Household & Cleaning', 'household-cleaning', 'assets/images/shopCategory/household.png', 'active'),
(19, 'Personal Care & Baby Care', 'personal-care-baby-care', 'assets/images/shopCategory/personal.png', 'active')
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `slug` = VALUES(`slug`),
    `image_path` = VALUES(`image_path`),
    `status` = VALUES(`status`);


-- --------------------------------------------------------------------------------------
-- 3. DATA CLEANUP & BACKFILL
-- --------------------------------------------------------------------------------------

-- 3a. Backfill existing orders from users table
UPDATE `orders` o
INNER JOIN `users` u ON o.user_id = u.id
SET 
    o.customer_name = IF(o.customer_name IS NULL OR TRIM(o.customer_name) = '', 
                         TRIM(CONCAT(u.first_name, ' ', COALESCE(u.last_name, ''))), 
                         o.customer_name),
    o.customer_phone = IF(o.customer_phone IS NULL OR TRIM(o.customer_phone) = '', 
                          COALESCE(u.phone_number, ''), 
                          o.customer_phone),
    o.street_address1 = IF(o.street_address1 IS NULL OR TRIM(o.street_address1) = '', 
                           COALESCE(u.street_address1, ''), 
                           o.street_address1),
    o.street_address2 = IF(o.street_address2 IS NULL OR TRIM(o.street_address2) = '', 
                           COALESCE(u.street_address2, ''), 
                           o.street_address2),
    o.town = IF(o.town IS NULL OR TRIM(o.town) = '', 
                COALESCE(u.town, ''), 
                o.town)
WHERE o.customer_name = '' 
   OR o.customer_phone = '' 
   OR o.street_address1 = '' 
   OR o.town = '';

-- Normalize payment_status casing if any legacy values exist
UPDATE `orders` SET `payment_status` = 'unpaid' WHERE `payment_status` NOT IN ('unpaid', 'paid', 'cancelled');

-- 3b. Fix broken image_path values in items
UPDATE `items` 
SET `image_path` = 'Admin/ImageUpload/banana_69a509482554a.avif'
WHERE `id` = 11 AND `image_path` = 'Admin/ImageUpload/banana_69a509482554a';

UPDATE `items` 
SET `image_path` = 'Admin/ImageUpload/basmathirice_6a3bb256134b4.webp'
WHERE `id` = 30 AND `image_path` = 'Admin/ImageUpload/basmathirice_6a3bb256134b4';

