-- ============================================================
-- Petals & Bloom Flower Shop - Decision Support System
-- Database Schema + Seed Data
-- Author: Mitea Diana-Maria
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `flower_shop_dss`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `flower_shop_dss`;

-- ============================================================
-- Table: admins
-- ============================================================
CREATE TABLE `admins` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)     NOT NULL UNIQUE,
  `email`         VARCHAR(100)    NOT NULL UNIQUE,
  `full_name`     VARCHAR(100)    NOT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: customers
-- ============================================================
CREATE TABLE `customers` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `first_name`    VARCHAR(50)     NOT NULL,
  `last_name`     VARCHAR(50)     NOT NULL,
  `email`         VARCHAR(100)    NOT NULL UNIQUE,
  `phone`         VARCHAR(20)     NOT NULL,
  `address`       VARCHAR(255)    DEFAULT NULL,
  `city`          VARCHAR(100)    DEFAULT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `created_at`    TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: categories
-- ============================================================
CREATE TABLE `categories` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)    NOT NULL,
  `description` TEXT            DEFAULT NULL,
  `image_path`  VARCHAR(255)    DEFAULT NULL,
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: products
-- ============================================================
CREATE TABLE `products` (
  `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `category_id`    INT UNSIGNED    NOT NULL,
  `name`           VARCHAR(150)    NOT NULL,
  `description`    TEXT            DEFAULT NULL,
  `price`          DECIMAL(10,2)   NOT NULL,
  `stock_quantity` INT             NOT NULL DEFAULT 0,
  `image_path`     VARCHAR(255)    DEFAULT NULL,
  `is_active`      TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: orders
-- ============================================================
CREATE TABLE `orders` (
  `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `customer_id`      INT UNSIGNED    DEFAULT NULL,
  `order_code`       VARCHAR(20)     NOT NULL UNIQUE,
  `customer_name`    VARCHAR(100)    NOT NULL,
  `customer_email`   VARCHAR(100)    NOT NULL,
  `customer_phone`   VARCHAR(20)     NOT NULL,
  `delivery_address` VARCHAR(255)    NOT NULL,
  `delivery_city`    VARCHAR(100)    NOT NULL,
  `delivery_date`    DATE            NOT NULL,
  `delivery_time`    VARCHAR(20)     DEFAULT NULL,
  `occasion`         VARCHAR(50)     DEFAULT NULL,
  `card_message`     TEXT            DEFAULT NULL,
  `special_notes`    TEXT            DEFAULT NULL,
  `status`           ENUM('new','pending','confirmed','preparing','out_for_delivery','delivered','cancelled')
                                     NOT NULL DEFAULT 'new',
  `total_price`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `payment_status`   ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `created_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_orders_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: order_items
-- ============================================================
CREATE TABLE `order_items` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `order_id`     INT UNSIGNED    NOT NULL,
  `product_id`   INT UNSIGNED    DEFAULT NULL,
  `product_name` VARCHAR(150)    NOT NULL,
  `quantity`     INT             NOT NULL DEFAULT 1,
  `unit_price`   DECIMAL(10,2)   NOT NULL,
  `subtotal`     DECIMAL(10,2)   NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: uploaded_files
-- ============================================================
CREATE TABLE `uploaded_files` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `order_id`      INT UNSIGNED    DEFAULT NULL,
  `original_name` VARCHAR(255)    NOT NULL,
  `stored_name`   VARCHAR(255)    NOT NULL,
  `file_path`     VARCHAR(500)    NOT NULL,
  `file_type`     VARCHAR(100)    NOT NULL,
  `file_size`     INT             NOT NULL,
  `description`   VARCHAR(255)    DEFAULT NULL,
  `uploaded_at`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_files_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: status_history
-- ============================================================
CREATE TABLE `status_history` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `order_id`    INT UNSIGNED    NOT NULL,
  `old_status`  VARCHAR(50)     DEFAULT NULL,
  `new_status`  VARCHAR(50)     NOT NULL,
  `changed_by`  VARCHAR(100)    DEFAULT NULL,
  `notes`       TEXT            DEFAULT NULL,
  `changed_at`  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_history_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: api_cache
-- ============================================================
CREATE TABLE `api_cache` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `cache_key`     VARCHAR(100)    NOT NULL UNIQUE,
  `response_data` TEXT            NOT NULL,
  `cached_at`     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `expires_at`    TIMESTAMP       NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin users (password: Admin@1234)
INSERT INTO `admins` (`username`, `email`, `full_name`, `password_hash`) VALUES
('admin',   'admin@petalsandbloom.ro',   'Diana Mitea',    '$2y$12$6T8R9zQ5vX2kL7mN1oP3ueKjHgFdCbAw4sYiExWqZpMnVtRlOuSe2'),
('manager', 'manager@petalsandbloom.ro', 'Shop Manager',   '$2y$12$6T8R9zQ5vX2kL7mN1oP3ueKjHgFdCbAw4sYiExWqZpMnVtRlOuSe2');

-- NOTE: The above hashes are placeholders. Run this after import to set real passwords:
-- UPDATE admins SET password_hash = '$2y$12$...' WHERE username = 'admin';
-- Or use the included setup script. Test credentials: admin / Admin@1234

-- Categories
INSERT INTO `categories` (`name`, `description`, `is_active`) VALUES
('Roses',             'Classic and romantic roses in various colours and arrangements', 1),
('Seasonal Bouquets', 'Fresh seasonal flower arrangements for every occasion',          1),
('Wedding Flowers',   'Elegant floral arrangements for weddings and ceremonies',        1),
('Tropical Flowers',  'Exotic tropical flowers and stunning arrangements',              1),
('Potted Plants',     'Beautiful potted plants for home and office decoration',         1),
('Dried Flowers',     'Long-lasting dried flower arrangements and wreaths',             1);

-- Products
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `stock_quantity`) VALUES
(1, 'Classic Red Rose Bouquet (12)',     'Timeless bouquet of 12 fresh red roses, perfect for love and affection', 149.99, 25),
(1, 'Pink Rose Arrangement (24)',        'Stunning arrangement of 24 pink roses with baby breath and greenery',   259.99, 15),
(1, 'White Rose Bridal Bouquet',         'Elegant bridal bouquet with white roses, peonies and eucalyptus',       349.99, 10),
(1, 'Rainbow Rose Collection',           'Colourful mixed rose bouquet in vibrant rainbow colours',                189.99, 20),
(2, 'Spring Garden Mix',                 'Fresh spring bouquet with tulips, daffodils and hyacinths',             129.99, 30),
(2, 'Summer Sunshine Bouquet',           'Bright summer arrangement with sunflowers, daisies and marigolds',      119.99, 35),
(2, 'Autumn Harvest Arrangement',        'Beautiful autumn arrangement with chrysanthemums and seasonal foliage',  159.99, 4),
(2, 'Winter Elegance',                   'Sophisticated winter arrangement with white flowers and pine elements',  179.99, 15),
(3, 'Wedding Arch Decoration',           'Full wedding arch floral decoration with premium flowers',              1499.99,  5),
(3, 'Bridesmaid Bouquets (Set of 4)',     'Matching bridesmaid bouquets in coordinated colours',                   599.99,  8),
(3, 'Wedding Table Centrepiece',         'Elegant table centrepiece for wedding receptions',                       249.99, 20),
(4, 'Bird of Paradise Arrangement',      'Exotic arrangement featuring stunning bird of paradise flowers',         199.99, 12),
(4, 'Tropical Paradise Mix',             'Vibrant tropical arrangement with orchids, heliconias and anthuriums',   229.99,  3),
(5, 'Orchid Potted Plant',               'Beautiful phalaenopsis orchid in a decorative ceramic pot',              89.99,  40),
(5, 'Peace Lily Arrangement',            'Elegant peace lily plant perfect for home or office',                    69.99,  50),
(6, 'Dried Lavender Bundle',             'Fragrant dried lavender bundle, perfect for home decor',                 49.99,  60),
(6, 'Dried Rose Wreath',                 'Beautiful preserved rose wreath for long-lasting elegance',              139.99, 25);

-- Customers (password: Customer@1234)
INSERT INTO `customers` (`first_name`, `last_name`, `email`, `phone`, `address`, `city`, `password_hash`) VALUES
('Ana',    'Popescu',    'ana.popescu@email.com',    '0721234567', 'Str. Florilor nr. 15',      'Bucharest',   '$2y$12$6T8R9zQ5vX2kL7mN1oP3ueKjHgFdCbAw4sYiExWqZpMnVtRlOuSe2'),
('Ion',    'Ionescu',    'ion.ionescu@email.com',    '0731234567', 'Calea Victoriei nr. 42',    'Cluj-Napoca', '$2y$12$6T8R9zQ5vX2kL7mN1oP3ueKjHgFdCbAw4sYiExWqZpMnVtRlOuSe2'),
('Maria',  'Constantin', 'maria.constantin@email.com','0741234567','Str. Independentei nr. 8', 'Timisoara',   '$2y$12$6T8R9zQ5vX2kL7mN1oP3ueKjHgFdCbAw4sYiExWqZpMnVtRlOuSe2'),
('George', 'Dumitrescu', 'george.d@email.com',       '0751234567', 'Bd. Unirii nr. 25',         'Iasi',        '$2y$12$6T8R9zQ5vX2kL7mN1oP3ueKjHgFdCbAw4sYiExWqZpMnVtRlOuSe2'),
('Elena',  'Popa',       'elena.popa@email.com',     '0761234567', 'Str. Libertatii nr. 3',    'Constanta',   '$2y$12$6T8R9zQ5vX2kL7mN1oP3ueKjHgFdCbAw4sYiExWqZpMnVtRlOuSe2');

-- Sample orders
INSERT INTO `orders` (`customer_id`,`order_code`,`customer_name`,`customer_email`,`customer_phone`,
  `delivery_address`,`delivery_city`,`delivery_date`,`delivery_time`,`occasion`,
  `card_message`,`special_notes`,`status`,`total_price`,`payment_status`) VALUES
(1,'ORD-0001','Ana Popescu','ana.popescu@email.com','0721234567',
 'Str. Florilor nr. 15','Bucharest','2026-04-20','morning','birthday',
 'Happy Birthday! Wishing you all the best!','Please leave at the door if no answer',
 'delivered',299.98,'paid'),

(2,'ORD-0002','Ion Ionescu','ion.ionescu@email.com','0731234567',
 'Calea Victoriei nr. 42','Cluj-Napoca','2026-06-15','afternoon','wedding',
 'Congratulations on your special day!',NULL,
 'confirmed',1879.97,'paid'),

(3,'ORD-0003','Maria Constantin','maria.constantin@email.com','0741234567',
 'Str. Independentei nr. 8','Timisoara','2026-05-30','morning','anniversary',
 'Happy Anniversary!','Use the pink ribbon please',
 'preparing',389.98,'unpaid'),

(NULL,'ORD-0004','Mihai Georgescu','mihai.g@email.com','0771234567',
 'Str. Republicii nr. 10','Bucharest','2026-06-01','evening','birthday',
 NULL,NULL,
 'new',119.99,'unpaid'),

(4,'ORD-0005','George Dumitrescu','george.d@email.com','0751234567',
 'Bd. Unirii nr. 25','Iasi','2026-05-28','morning','other',
 'Thank you for everything',NULL,
 'pending',159.99,'unpaid'),

(1,'ORD-0006','Ana Popescu','ana.popescu@email.com','0721234567',
 'Str. Florilor nr. 15','Bucharest','2026-02-14','afternoon','valentine',
 'With love always',NULL,
 'delivered',259.99,'paid'),

(5,'ORD-0007','Elena Popa','elena.popa@email.com','0761234567',
 'Str. Libertatii nr. 3','Constanta','2026-03-08','morning','mothers_day',
 'Happy Mother''s Day!','Add a pink bow',
 'delivered',189.99,'paid'),

(2,'ORD-0008','Ion Ionescu','ion.ionescu@email.com','0731234567',
 'Calea Victoriei nr. 42','Cluj-Napoca','2026-06-20','afternoon','corporate',
 'Best regards',NULL,
 'cancelled',229.99,'refunded');

-- Order items
INSERT INTO `order_items` (`order_id`,`product_id`,`product_name`,`quantity`,`unit_price`,`subtotal`) VALUES
(1, 1,  'Classic Red Rose Bouquet (12)',   2, 149.99, 299.98),
(2, 9,  'Wedding Arch Decoration',         1, 1499.99,1499.99),
(2, 11, 'Wedding Table Centrepiece',       1,  249.99, 249.99),
(2, 5,  'Spring Garden Mix',               1,  129.99, 129.99),
(3, 2,  'Pink Rose Arrangement (24)',      1,  259.99, 259.99),
(3, 5,  'Spring Garden Mix',               1,  129.99, 129.99),
(4, 6,  'Summer Sunshine Bouquet',         1,  119.99, 119.99),
(5, 7,  'Autumn Harvest Arrangement',      1,  159.99, 159.99),
(6, 2,  'Pink Rose Arrangement (24)',      1,  259.99, 259.99),
(7, 4,  'Rainbow Rose Collection',         1,  189.99, 189.99),
(8, 13, 'Tropical Paradise Mix',           1,  229.99, 229.99);

-- Status history
INSERT INTO `status_history` (`order_id`,`old_status`,`new_status`,`changed_by`,`notes`) VALUES
(1, NULL,        'new',         'system',  'Order placed online'),
(1, 'new',       'confirmed',   'admin',   'Order confirmed and accepted'),
(1, 'confirmed', 'preparing',   'admin',   'Florist started preparing the bouquet'),
(1, 'preparing', 'delivered',   'admin',   'Delivered successfully'),
(2, NULL,        'new',         'system',  'Order placed online'),
(2, 'new',       'confirmed',   'admin',   'Order confirmed'),
(3, NULL,        'new',         'system',  'Order placed online'),
(3, 'new',       'pending',     'admin',   'Awaiting stock confirmation'),
(3, 'pending',   'preparing',   'admin',   'Stock confirmed, preparing'),
(4, NULL,        'new',         'system',  'Order placed online'),
(5, NULL,        'new',         'system',  'Order placed online'),
(5, 'new',       'pending',     'admin',   'Pending confirmation'),
(6, NULL,        'new',         'system',  'Order placed online'),
(6, 'new',       'delivered',   'admin',   'Valentine delivery completed'),
(7, NULL,        'new',         'system',  'Order placed online'),
(7, 'new',       'delivered',   'admin',   'Mother''s Day delivery completed'),
(8, NULL,        'new',         'system',  'Order placed online'),
(8, 'new',       'cancelled',   'admin',   'Customer requested cancellation');
