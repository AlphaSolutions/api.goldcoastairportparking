CREATE TABLE `api_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(45) NOT NULL,
  `password` varchar(128) DEFAULT NULL,
  `is_active` char(1) NOT NULL DEFAULT 'N' COMMENT 'N = No, Y = Yes',
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
