-- Adminer 4.8.1 MySQL 8.0.27-0ubuntu0.20.04.1 dump
INSERT INTO `languages` (`id`, `code`, `_languages`, `_languages_position`) VALUES
(1,	'root',	NULL,	NULL),
(2,	'en',	1,	1);


INSERT INTO `userstypes` (`id`, `type`) VALUES
(3,	'orders administrator'),
(7,	'web administrator'),
(9,	'product administrator'),
(10,	'product seller'),
(11,	'user administrator'),
(12,	'customer'),
(13,	'system administrator');

INSERT INTO `users` (`id`, `username`, `pwd`, `status`, `access`, `_userstypes`) VALUES
(1,	'webadmin',	'$2y$10$NKXLi/BalpfosYj2btEkjO7KZxvIX/bBJx1uPVieALynD/LUEP3pe',	0,	1637161835,	7),
(2,	'ordersadmin',	'$2y$10$PlqpvA9Oafxu9UA6tbF67OL86oqDjFgY9IPUuSHoPXl3LQ12J8wHu',	0,	1603212168,	3),
(4,	'productsadmin',	'$2y$10$gaaoUP8s7iE5QF0HgLTBOut3AL8HhHT4UXhcQ.3mnc42JzM3O/opq',	0,	1626969881,	9),
(6,	'usersadmin',	'$2y$10$W4KkiELlafJWyHHamXko/.lzcc0cvRvYSCpqBNt9sbQXB9NVVq3kq',	0,	1590327417,	11),
(7,	'systemadmin',	'$2y$10$ImHVY1dgkuB4RMWE8PYd0u7Y3S9TO1mwkJUl6rjeMhwuSpRBbjJue',	0,	1637161411,	13);

INSERT INTO `usersdata` (`id`, `fullname`, `emailaddress`, `phonenumber`, `_users`) VALUES
(1,	'',	'',	'0',	1),
(2,	'',	'',	'0',	2),
(3,	'',	'',	'0',	4),
(4,	'',	'',	'0',	6),
(5,	'',	'',	'0',	7);

INSERT INTO `addresses` (`id`, `streetaddress`, `city`, `state`, `zipcode`, `country`, `_users`) VALUES
(23,	'',	'',	'',	'',	NULL,	2),
(24,	'',	'',	'',	'',	NULL,	1),
(25,	'',	'',	'',	'',	NULL,	4),
(26,	'',	'',	'',	'',	NULL,	6),
(27,	'',	'',	'',	'',	NULL,	7);

INSERT INTO `itemcategories` (`id`, `_itemcategories`, `_itemcategories_position`) VALUES
(1,	NULL,	0),
(54,	1,	1),
(55,	54,	1);

DROP TABLE IF EXISTS `itemcategoriesdata`;
CREATE TABLE `itemcategoriesdata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `_itemcategories` int DEFAULT NULL,
  `_languages` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `_languages` (`_languages`),
  KEY `_itemcategories` (`_itemcategories`),
  CONSTRAINT `itemcategoriesdata_ibfk_1` FOREIGN KEY (`_itemcategories`) REFERENCES `itemcategories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `itemcategoriesdata_ibfk_2` FOREIGN KEY (`_languages`) REFERENCES `languages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `itemcategoriesdata` (`id`, `name`, `_itemcategories`, `_languages`) VALUES
(1,	'root',	1,	2),
(62,	'First category',	54,	2),
(63,	'First subcategory',	55,	2);

INSERT INTO `items` (`id`, `image`, `_itemcategories`, `_itemusers`, `_itemcategories_position`) VALUES
(41,	'',	55,	1,	1);

INSERT INTO `itemsdata` (`id`, `name`, `descriptionlarge`, `descriptionshort`, `price`, `_items`, `_languages`) VALUES
(41,	'p1',	'',	'p1 description',	1000,	41,	2);

INSERT INTO `paymenttypes` (`id`, `image`, `vars`, `template`, `active`, `_paymenttypes`, `_paymenttypes_position`) VALUES
(1,	'',	'',	NULL,	0,	NULL,	0),
(2,	'',	'{\"merchantId\":\"test\"}',	'paypal',	1,	1,	1);

INSERT INTO `paymenttypesdata` (`id`, `name`, `description`, `_paymenttypes`, `_languages`) VALUES
(1,	'Paypal',	'Paypal payment system',	2,	2);

INSERT INTO `shippingtypes` (`id`, `image`, `delay_hours`, `price`, `_shippingtypes`, `_shippingtypes_position`) VALUES
(1,	'',	0,	0,	NULL,	0),
(2,	'',	24,	1000,	1,	1),
(3,	'',	72,	300,	1,	2);

INSERT INTO `shippingtypesdata` (`id`, `name`, `description`, `_shippingtypes`, `_languages`) VALUES
(5,	'ship1',	'des ship1',	2,	2),
(6,	'ship2',	'des ship2',	3,	2);


-- 2021-11-17 15:52:23
