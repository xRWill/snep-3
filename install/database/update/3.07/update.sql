--
-- Table structure for table `logs_users`
--
CREATE TABLE IF NOT EXISTS `logs_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `ip` varchar(15) NOT NULL,
  `user` varchar(30) NOT NULL,
  `action` varchar(30) NOT NULL,
  `description` varchar(255) NOT NULL,
  `table` varchar(30) NOT NULL,
  `registerid` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `peers` ADD COLUMN `disabled` BOOLEAN DEFAULT false;
ALTER TABLE `trunks` ADD COLUMN `disabled` BOOLEAN DEFAULT false;