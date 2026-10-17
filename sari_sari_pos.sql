/*
SQLyog Community v13.3.1 (64 bit)
MySQL - 10.4.32-MariaDB : Database - sari_sari_pos
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`sari_sari_pos` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `sari_sari_pos`;

/*Table structure for table `cart` */

DROP TABLE IF EXISTS `cart`;

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`cart_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `cart` */

/*Table structure for table `categories` */

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `categories` */

insert  into `categories`(`category_id`,`category_name`) values 
(1,'Beverages'),
(2,'Snacks'),
(3,'Canned Goods'),
(4,'Rice'),
(5,'Toiletries');

/*Table structure for table `products` */

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `products` */

insert  into `products`(`product_id`,`product_name`,`category_id`,`price`,`stock`) values 
(1,'Coke 1.5L',1,75.00,30),
(2,'Sprite 1.5L',1,75.00,20),
(3,'Royal 1.5L',1,75.00,30),
(4,'Lucky Me Pancit Canton',2,18.00,45),
(5,'Piattos',2,22.00,38),
(6,'SkyFlakes',2,10.00,60),
(7,'555 Sardines',3,30.00,24),
(8,'Argentina Corned Beef',3,45.00,5),
(9,'Rice 1kg',4,55.00,95),
(10,'Palmolive Shampoo',5,8.00,50),
(11,'Safeguard Soap',5,38.00,45),
(12,'Closeup Toothpaste',5,55.00,20);

/*Table structure for table `sale_items` */

DROP TABLE IF EXISTS `sale_items`;

CREATE TABLE `sale_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `sale_items` */

insert  into `sale_items`(`item_id`,`sale_id`,`product_id`,`quantity`,`price`,`subtotal`) values 
(1,1,7,5,30.00,150.00),
(2,2,10,20,8.00,160.00),
(3,3,8,13,45.00,585.00),
(8,8,12,1,55.00,55.00),
(9,9,12,2,55.00,110.00),
(10,10,7,1,30.00,30.00),
(11,12,7,1,30.00,30.00),
(12,13,7,2,30.00,60.00),
(13,14,5,2,22.00,44.00);

/*Table structure for table `sales` */

DROP TABLE IF EXISTS `sales`;

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_date` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `cashier` varchar(50) NOT NULL,
  `cash` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`sale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `sales` */

insert  into `sales`(`sale_id`,`sale_date`,`total`,`cashier`,`cash`,`change_amount`) values 
(1,'2026-07-14 01:55:38',150.00,'admin',200.00,50.00),
(2,'2026-09-16 23:22:20',160.00,'cashier',500.00,340.00),
(3,'2026-09-16 23:23:09',585.00,'cashier',1000.00,415.00),
(11,'2026-09-22 03:18:44',110.00,'admin',200.00,90.00),
(12,'2026-09-22 03:32:07',0.00,'30',50.00,20.00),
(13,'2026-09-22 14:06:26',0.00,'60',70.00,10.00),
(14,'2026-09-22 14:24:35',0.00,'44',50.00,6.00);

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `users` */

insert  into `users`(`user_id`,`username`,`password`,`role`) values 
(1,'admin','$2y$10$7kWWqBQxFwHhyXAQlmY0EuPjSypD1Lcysq1zsJDPPhLCwy8HqBJWe','Admin'),
(2,'cashier','$2y$10$SIbUw15fcdZuicR9PVau1.iCuwjLpBlIjA5JhMXYF7AGp4E6YyySm','Cashier');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
