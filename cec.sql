-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: cec-comart-web
-- ------------------------------------------------------
-- Server version	8.0.41

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `cart_id` bigint NOT NULL,
  `item_id` bigint NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cart_id` (`cart_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
INSERT INTO `cart_items` VALUES (2,2,12,3.000,1050.00,'2026-03-03 05:37:09','2026-03-11 06:17:36'),(3,2,13,1.000,400.00,'2026-03-03 05:37:17','2026-03-06 10:40:13'),(7,2,14,3.000,240.00,'2026-03-11 06:18:14','2026-03-11 06:18:30'),(8,3,14,1.000,80.00,'2026-03-26 05:27:02','2026-03-26 05:27:02'),(9,3,12,2.000,700.00,'2026-03-26 05:27:26','2026-03-26 05:27:26'),(10,2,15,1.000,499.98,'2026-06-10 09:55:12','2026-06-10 09:55:12'),(11,4,11,2.000,160.00,'2026-06-23 03:51:46','2026-06-23 03:51:46'),(12,5,11,2.000,160.00,'2026-06-23 03:52:09','2026-06-23 03:52:09'),(13,6,12,1.000,350.00,'2026-06-23 03:53:08','2026-06-23 03:53:08'),(14,7,12,1.000,350.00,'2026-06-23 15:02:09','2026-06-23 15:02:09'),(15,8,17,2.000,600.00,'2026-06-24 09:01:46','2026-06-24 09:01:46'),(16,8,13,2.000,800.00,'2026-06-24 09:05:00','2026-06-24 09:06:33'),(17,8,14,2.000,160.00,'2026-06-24 09:06:12','2026-06-24 09:06:20'),(18,8,20,1.000,3399.99,'2026-06-24 09:07:01','2026-06-24 09:07:01'),(19,8,22,1.000,90.00,'2026-06-24 09:08:25','2026-06-24 09:08:25'),(20,13,30,5.000,3000.00,'2026-06-24 11:26:46','2026-06-24 11:26:46'),(22,15,23,1.000,190.00,'2026-06-26 10:31:42','2026-06-26 10:31:42'),(23,16,22,1.000,90.00,'2026-06-26 11:05:28','2026-06-26 11:05:28');
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `status` enum('active','ordered') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES (2,14,'ordered','2026-03-03 05:37:09','2026-06-23 03:52:09'),(3,15,'active','2026-03-26 05:27:02','2026-03-26 05:27:02'),(4,14,'ordered','2026-06-23 03:51:46','2026-06-23 03:52:09'),(5,14,'ordered','2026-06-23 03:52:09','2026-06-23 03:52:09'),(6,14,'ordered','2026-06-23 03:53:08','2026-06-23 04:16:16'),(7,16,'ordered','2026-06-23 15:02:09','2026-06-23 15:03:30'),(8,17,'ordered','2026-06-24 09:01:46','2026-06-25 05:22:21'),(13,14,'ordered','2026-06-24 11:26:46','2026-06-24 11:26:46'),(14,17,'active','2026-06-25 05:24:23','2026-06-25 05:24:23'),(15,18,'active','2026-06-26 10:31:42','2026-06-26 10:31:42'),(16,20,'active','2026-06-26 11:05:28','2026-06-26 11:05:28');
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Vegetables','vegetables','assets/images/shopCategory/vegetable.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(2,'Fruits','fruits','assets/images/shopCategory/fruits.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(3,'Snacks','snacks','assets/images/shopCategory/snack.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(4,'Biscuits','biscuits','assets/images/shopCategory/biscuit.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(5,'Coffee','coffee','assets/images/shopCategory/coffee.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(6,'Eggs','eggs','assets/images/shopCategory/eggs.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(7,'Water','water','assets/images/shopCategory/waterBottle.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(8,'Tea','tea','assets/images/shopCategory/tea-bag.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(9,'Cheese','cheese','assets/images/shopCategory/cheese.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(10,'Yoghurts & Curd','yoghurts-curd','assets/images/shopCategory/yoghurt.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(11,'Desserts','desserts','assets/images/shopCategory/yoghurt.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(12,'Beverages','beverages','assets/images/shopCategory/beverages.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(13,'Bakery','bakery','assets/images/shopCategory/bread.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(14,'Meat & Seafood','meat-seafood','assets/images/shopCategory/meat.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(15,'Pantry Staples','pantry-staples','assets/images/shopCategory/pantry.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(16,'Dairy Staples','dairy-staples','assets/images/shopCategory/dairy.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(17,'Frozen Foods','frozen-foods','assets/images/shopCategory/frozen.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(18,'Household & Cleaning','household-cleaning','assets/images/shopCategory/household.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10'),(19,'Personal Care & Baby Care','personal-care-baby-care','assets/images/shopCategory/personal.png','active','2026-11-11 13:06:10','2026-11-11 13:06:10');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
INSERT INTO `contacts` VALUES (1,'Nimal','nimal@example.com','ABC','aaaa','2026-02-17 11:15:37'),(2,'Nimal','nimal@example.com','ABC','aaaa','2026-02-17 11:15:38'),(3,'Nimal','nimal@example.com','ABC','aaaa','2026-02-17 11:18:18'),(4,'Nimal','nimal@example.com','ABC','aaaa','2026-02-17 11:18:21'),(5,'Nimal','nimal@example.com','ABC','aaaaaaa','2026-02-17 11:18:29'),(6,'Nimal','nimal@example.com','ABC','aaaaaaa','2026-02-17 11:21:30');
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` decimal(10,3) DEFAULT '0.000',
  `category_id` bigint DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (11,'Banana','Admin/ImageUpload/banana_69a509482554a.avif','100 g',80.00,0.000,2,'active','2026-03-02 03:51:36','2026-11-11 13:06:10'),(12,'chocolate Biscuit','Admin/ImageUpload/chocolatebiscuit_69a5099182bec.jpeg','365 g',350.00,0.000,4,'active','2026-03-02 03:52:49','2026-03-02 03:52:49'),(13,'kothmale Chees','Admin/ImageUpload/kothmalechees_69a50aa505c8b.jpeg','120 g',400.00,0.000,9,'active','2026-03-02 03:57:25','2026-03-02 03:57:25'),(14,'Highland','Admin/ImageUpload/highland_69a50abe250d7.jpeg','80 g',80.00,0.000,10,'active','2026-03-02 03:57:50','2026-03-02 03:57:50'),(15,'7 UP','Admin/ImageUpload/7up_69e8a01b265a3.jpeg','2 L',499.98,0.000,12,'active','2026-04-22 10:16:59','2026-06-24 03:43:02'),(16,'7 UP','Admin/ImageUpload/7up_69e8a06056ef6.jpeg','1.5 L',420.00,0.000,12,'active','2026-04-22 10:18:08','2026-06-24 03:43:02'),(17,'7 Up pet Bottle','Admin/ImageUpload/7uppetbottle_69e8a08b7443f.jpeg','1 L',300.00,0.000,12,'active','2026-04-22 10:18:51','2026-06-24 03:43:02'),(18,'Ahmad Tea Bopf Premium','Admin/ImageUpload/ahmadteabopfpremium_69e8a1b610c0b.jpeg','400 g',1206.00,0.000,8,'active','2026-04-22 10:23:50','2026-06-24 03:43:02'),(19,'Ahmad Tea Flavoured Chai','Admin/ImageUpload/ahmadteaflavouredchai_69e8a1f38c2fd.jpeg','100 g',790.00,0.000,8,'active','2026-04-22 10:24:51','2026-06-24 03:43:02'),(20,'Akbar Coffee Gold Jar','Admin/ImageUpload/akbarcoffeegoldjar_69e8a23643f16.jpeg','100 g',3399.99,0.000,5,'active','2026-04-22 10:25:58','2026-06-24 03:43:02'),(21,'American Joozy Mixed Fruit','Admin/ImageUpload/americanjoozymixedfruit_69e8a263281f0.jpeg','180 ml',140.00,0.000,12,'active','2026-04-22 10:26:43','2026-06-24 03:43:02'),(22,'Pure Life Mineral Water','assets/images/shopCategory/waterBottle.png','1 L',90.00,99.000,7,'active','2026-06-24 03:43:02','2026-06-25 05:22:21'),(23,'Fresh Crusty White Bread','assets/images/shopCategory/bread.png','450 g',190.00,50.000,13,'active','2026-06-24 04:00:25','2026-06-24 04:00:25'),(24,'Fresh Chicken Breast Boneless','assets/images/shopCategory/meat.png','500 g',980.00,30.000,14,'active','2026-06-24 04:00:25','2026-06-24 04:00:25'),(25,'Premium Basmati Rice','assets/images/shopCategory/pantry.png','1 kg',750.00,100.000,15,'active','2026-06-24 04:00:25','2026-06-24 04:00:25'),(26,'Fresh Full Cream Milk','assets/images/shopCategory/dairy.png','1 L',290.00,40.000,16,'active','2026-06-24 04:00:25','2026-06-24 04:00:25'),(27,'Rich Chocolate Ice Cream Tub','assets/images/shopCategory/frozen.png','1 L',850.00,25.000,17,'active','2026-06-24 04:00:25','2026-06-24 04:00:25'),(28,'Lemon Liquid Laundry Detergent','assets/images/shopCategory/household.png','1 L',620.00,60.000,18,'active','2026-06-24 04:00:25','2026-06-24 04:00:25'),(29,'Gentle Tear-Free Baby Shampoo','assets/images/shopCategory/personal.png','200 ml',450.00,35.000,19,'active','2026-06-24 04:00:25','2026-06-24 04:00:25'),(30,'Basmathi Rice','Admin/ImageUpload/basmathirice_6a3bb256134b4.webp','1 kg',600.00,74.500,1,'active','2026-06-24 10:32:54','2026-11-11 13:06:10');
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_id` bigint NOT NULL,
  `item_id` bigint NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,12,3.000,350.00,1050.00,'2026-03-26 05:01:43'),(2,1,13,1.000,400.00,400.00,'2026-03-26 05:01:43'),(3,1,14,3.000,80.00,240.00,'2026-03-26 05:01:43'),(4,3,11,2.000,80.00,160.00,'2026-06-23 03:52:09'),(5,4,12,1.000,350.00,350.00,'2026-06-23 04:16:16'),(6,5,12,1.000,350.00,350.00,'2026-06-23 15:03:30'),(7,7,30,5.000,600.00,3000.00,'2026-06-24 11:25:03'),(8,8,30,5.000,600.00,3000.00,'2026-06-24 11:26:46'),(9,9,17,2.000,300.00,600.00,'2026-06-25 05:22:21'),(10,9,13,2.000,400.00,800.00,'2026-06-25 05:22:21'),(11,9,14,2.000,80.00,160.00,'2026-06-25 05:22:21'),(12,9,20,1.000,3399.99,3399.99,'2026-06-25 05:22:21'),(13,9,22,1.000,90.00,90.00,'2026-06-25 05:22:21');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `user_id` bigint NOT NULL,
  `purchase_method` enum('cod','pickup') NOT NULL DEFAULT 'cod',
  `payment_status` enum('unpaid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  `customer_name` varchar(150) NOT NULL DEFAULT '',
  `customer_phone` varchar(30) NOT NULL DEFAULT '',
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT '0.00',
  `town` varchar(255) DEFAULT '',
  `street_address2` varchar(255) DEFAULT '',
  `delivery_notes` text,
  `pickup_date` date DEFAULT NULL,
  `pickup_time_slot` varchar(50) DEFAULT NULL,
  `pickup_code` varchar(20) DEFAULT NULL,
  `street_address1` varchar(255) DEFAULT '',
  `order_status` enum('pending','processing','ready_for_pickup','shipped','delivered','collected','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'00001',14,'cod','unpaid','Achintha Mihiran','0788329266',2140.00,450.00,'Colombo','Apt 4B',NULL,NULL,NULL,NULL,'123 Main St','pending','2026-03-26 05:01:43','2026-11-11 13:06:10'),(3,'00003',14,'cod','unpaid','Achintha Mihiran','0788329266',610.00,450.00,'Colombo','Apt 4B',NULL,NULL,NULL,NULL,'123 Main St','pending','2026-06-23 03:52:09','2026-11-11 13:06:10'),(4,'00004',14,'cod','unpaid','Achintha Mihiran','0788329266',800.00,450.00,'Colombo','Apt 4B',NULL,NULL,NULL,NULL,'123 Main St','processing','2026-06-23 04:16:16','2026-11-11 13:06:10'),(5,'00005',16,'cod','unpaid','John Doe','0771234567',800.00,450.00,'Colombo','Apt 4B',NULL,NULL,NULL,NULL,'123 Main St','pending','2026-06-23 15:03:30','2026-11-11 13:06:10'),(7,'00007',14,'cod','unpaid','Achintha Mihiran','0788329266',3010.00,0.00,'Colombo','Apt 4B',NULL,NULL,NULL,NULL,'123 Main St','pending','2026-06-24 11:25:03','2026-11-11 13:06:10'),(8,'00008',14,'cod','unpaid','Achintha Mihiran','0788329266',3010.00,0.00,'Colombo','Apt 4B',NULL,NULL,NULL,NULL,'123 Main St','pending','2026-06-24 11:26:46','2026-11-11 13:06:10'),(9,'00009',17,'cod','unpaid','Kasun Lakshan','0788329244',5499.99,450.00,'Rajagiriya ','Nawala Road ',NULL,NULL,NULL,NULL,'No 20 ,CEC Building ','pending','2026-06-25 05:22:21','2026-11-11 13:06:10');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_transactions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `item_id` bigint NOT NULL,
  `transaction_type` enum('in','out','adjustment') NOT NULL,
  `quantity` decimal(10,3) NOT NULL,
  `previous_stock` decimal(10,3) NOT NULL,
  `new_stock` decimal(10,3) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transactions`
--

LOCK TABLES `stock_transactions` WRITE;
/*!40000 ALTER TABLE `stock_transactions` DISABLE KEYS */;
INSERT INTO `stock_transactions` VALUES (1,30,'in',80.000,0.000,80.000,'Supplier Delivery (80 kg)','2026-06-24 10:59:13'),(2,30,'out',0.500,80.000,79.500,'Damaged Items (500 g)','2026-06-24 11:02:19'),(3,30,'in',80.000,0.000,80.000,'Supplier Delivery (80 kg)','2026-06-24 11:22:28'),(4,30,'out',0.500,80.000,79.500,'Damaged Items (500 g)','2026-06-24 11:22:28'),(5,30,'in',80.000,0.000,80.000,'Supplier Delivery (80 kg)','2026-06-24 11:23:03'),(6,30,'out',0.500,80.000,79.500,'Damaged Items (500 g)','2026-06-24 11:23:03'),(7,30,'out',5.000,79.500,74.500,'Customer Order #00007','2026-06-24 11:25:03'),(8,30,'out',5.000,79.500,74.500,'Customer Order #00008','2026-06-24 11:26:46'),(9,17,'out',2.000,0.000,0.000,'Customer Order #00009','2026-06-25 05:22:21'),(10,13,'out',2.000,0.000,0.000,'Customer Order #00009','2026-06-25 05:22:21'),(11,14,'out',2.000,0.000,0.000,'Customer Order #00009','2026-06-25 05:22:21'),(12,20,'out',1.000,0.000,0.000,'Customer Order #00009','2026-06-25 05:22:21'),(13,22,'out',1.000,100.000,99.000,'Customer Order #00009','2026-06-25 05:22:21');
/*!40000 ALTER TABLE `stock_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `street_address1` varchar(255) DEFAULT NULL,
  `street_address2` varchar(255) DEFAULT NULL,
  `town` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (13,'Nimal','Perera','nimal11@example.com','0788329244','1',NULL,NULL,NULL,'2026-02-17 09:04:35','2026-02-17 09:04:35',0),(14,'Achintha','Mihiran','achintha@gmail.com','0788329266','$2y$10$xgGdmjtrRWi7OkswENMgJ.XfmOJ39LDc9a0Wz/9.byuZQmN5YyX.m','123 Main St','Apt 4B','Colombo','2026-02-18 09:47:53','2026-06-24 11:25:03',1),(15,'Pasindu ','Lakshan','pasindu@gmail.com','0788329266','$2y$10$1DsXrdFPzG5jVSBdeSPFbe9DE8d51q46kMVCDxrxwtBeEtrxhMd0O',NULL,NULL,NULL,'2026-03-26 05:26:41','2026-06-24 04:23:32',0),(16,'John','Doe','testuser_unique1@example.com','0771234567','$2y$10$v5bQC2tPnh/3/xwBfUDIYO247z7ZudE4ngXW8aWiI07aKT.78iblC','123 Main St','Apt 4B','Colombo','2026-06-23 15:00:26','2026-06-23 15:03:30',0),(17,'Kasun','Lakshan','kasun@gmail.com','0788329244','$2y$10$eOkuJCW59DcVSFNPf7uC1eP.ZA4t5hpUfkwsRCIqXSgcyzWQZzU6e','No 20 ,CEC Building ','Nawala Road ','Rajagiriya ','2026-06-24 08:57:06','2026-06-25 05:22:21',0),(18,'John','Doe','john.doe@example.com','0771112223','$2y$10$gdi/QvCHIVt.WAci28FY5edtdrw2BG3C5GQQxWW49AAGdfPzoqcyK','No. 123, Galle Road','Floor 3','Colombo','2026-06-26 04:18:43','2026-06-26 10:29:07',0),(19,'Jane','Doe','jane.doe@example.com','0773334445','$2y$10$RmL3dHTnGazmxcLQ.cORme6aayCnma3haZLZYJBRw2gh4v9NH8JTq',NULL,NULL,NULL,'2026-06-26 05:59:31','2026-06-26 05:59:31',0),(20,'John','Doe','john.doe.new@example.com','0771234567','$2y$10$5JujRkH2ntXO.z09nHK/mOfxNbl6uBUKY2VfKoEldl4sS/.Bapr42',NULL,NULL,NULL,'2026-06-26 11:04:08','2026-06-26 11:04:08',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'cec-comart-web'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-11 18:47:50
