-- Database setup for Romy and Allit's Food Haven
-- Import this file with phpMyAdmin or the MySQL command line.

CREATE DATABASE IF NOT EXISTS `db_karenderya`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `db_karenderya`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `username` VARCHAR(80) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
  `institution_id` VARCHAR(80) NOT NULL DEFAULT '',
  `email` VARCHAR(160) NOT NULL DEFAULT '',
  `email_verified` TINYINT(1) NOT NULL DEFAULT 1,
  `phone` VARCHAR(40) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `email_verification_codes` (
  `user_id` INT NOT NULL,
  `code_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_email_verification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_reset_token_hash` (`token_hash`),
  KEY `idx_password_reset_user` (`user_id`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Initial administrator account. Change this password after the first login.
-- The hash is compatible with PHP password_verify() for the password admin123.
INSERT INTO `users` (`full_name`, `username`, `password_hash`, `role`)
SELECT 'Canteen Administrator', 'admin', '$2y$10$Un4AeHoMICmo5UiHOszTcOxbd0/1DjXOtHZRL1zPPDwIgZb7ZI.Z6', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NULL,
  `order_number` VARCHAR(30) NOT NULL,
  `student_name` VARCHAR(120) NOT NULL,
  `student_id` VARCHAR(60) NOT NULL,
  `payment_method` ENUM('cash', 'gcash', 'bank_transfer') NOT NULL,
  `payment_status` ENUM('pay_on_pickup', 'paid') NOT NULL DEFAULT 'pay_on_pickup',
  `fulfillment_type` ENUM('pickup', 'dine_in') NOT NULL DEFAULT 'pickup',
  `preferred_time` VARCHAR(40) NOT NULL DEFAULT '',
  `special_instructions` VARCHAR(500) NOT NULL DEFAULT '',
  `order_status` ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `items_json` LONGTEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_order_number` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `menu_products` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `category` VARCHAR(80) NOT NULL,
  `description` VARCHAR(255) NOT NULL DEFAULT '',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `image_path` VARCHAR(255) NOT NULL DEFAULT '',
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `stock_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `canteen_profile` (
  `id` TINYINT NOT NULL PRIMARY KEY,
  `canteen_name` VARCHAR(160) NOT NULL DEFAULT 'Romy and Allit Food Haven',
  `operating_hours` VARCHAR(160) NOT NULL DEFAULT 'Monday - Saturday, 6:00 AM - 5:00 PM',
  `contact_info` VARCHAR(255) NOT NULL DEFAULT '',
  `pickup_location` VARCHAR(255) NOT NULL DEFAULT 'Main pickup counter'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT IGNORE INTO `canteen_profile` (`id`) VALUES (1);

CREATE TABLE IF NOT EXISTS `product_categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL UNIQUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT IGNORE INTO `product_categories` (`name`) VALUES ('mains'), ('breakfast'), ('snacks'), ('drinks');

INSERT INTO `menu_products` (`name`, `category`, `description`, `price`, `image_path`)
SELECT seed.`name`, seed.`category`, seed.`description`, seed.`price`, seed.`image_path`
FROM (
  SELECT 'Pork Adobo' AS `name`, 'mains' AS `category`, 'Filipino-style pork braised in savory sauce' AS `description`, 40.00 AS `price`, 'Dishes/Pork Adobo.jpeg' AS `image_path`
  UNION ALL SELECT 'Menudo', 'mains', 'Classic Filipino pork and tomato stew', 40.00, 'Dishes/Menudo.jpeg'
  UNION ALL SELECT 'Pork Sinigang', 'mains', 'Sour, comforting soup with gabi', 40.00, 'Dishes/Pork Sinigang.jpeg'
  UNION ALL SELECT 'Fried Chicken', 'mains', 'Crispy, golden and freshly cooked', 40.00, 'Dishes/Fried Chicken.jpeg'
  UNION ALL SELECT 'Egado', 'mains', 'Savory Ilocano pork stew', 40.00, 'Dishes/Egado.jpeg'
  UNION ALL SELECT 'Sweet and Sour', 'mains', 'Tangy sweet and sour favorite', 40.00, 'Dishes/Sweet and Sour.jpeg'
  UNION ALL SELECT 'Chicken Adobo', 'mains', 'Tender chicken simmered in savory adobo sauce', 40.00, 'Dishes/Chicken Adobo.jpeg'
  UNION ALL SELECT 'Longganisa, Hotdog and Egg', 'breakfast', 'Breakfast favorites served together', 40.00, 'Breakfast/eac20c86-2f3f-44f6-9459-b30a5f51ee4f.jpeg'
  UNION ALL SELECT 'Luncheon Meat and Egg', 'breakfast', 'Seared luncheon meat with egg', 40.00, 'Breakfast/95e538ce-91f3-4275-bfc2-e197714c097a.jpeg'
  UNION ALL SELECT 'Siomai', 'breakfast', 'Steamed savory siomai', 40.00, 'Breakfast/eac20c86-2f3f-44f6-9459-b30a5f51ee4f.jpeg'
  UNION ALL SELECT 'Coke', 'drinks', 'Chilled Coca-Cola', 40.00, 'Drink/Coke.jpeg'
  UNION ALL SELECT 'Mountain Dew', 'drinks', 'Chilled Mountain Dew', 40.00, 'Drink/Mountain Dew.jpeg'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `menu_products`);
