-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.5.5-10.0.15-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             8.3.0.4694
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table alchemake.combinations
DROP TABLE IF EXISTS `combinations`;
CREATE TABLE IF NOT EXISTS `combinations` (
  `itemid` int(10) unsigned NOT NULL,
  `ingredient1_itemid` int(10) unsigned NOT NULL,
  `ingredient2_itemid` int(10) unsigned NOT NULL,
  `ingredient3_itemid` int(10) unsigned NOT NULL DEFAULT '0',
  `preq_tool_itemid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`ingredient1_itemid`,`ingredient2_itemid`,`ingredient3_itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table alchemake.combo-suggest
DROP TABLE IF EXISTS `combo-suggest`;
CREATE TABLE IF NOT EXISTS `combo-suggest` (
  `userid` varchar(255) NOT NULL,
  `ingredient1_itemid` int(10) unsigned NOT NULL,
  `ingredient2_itemid` int(10) unsigned NOT NULL,
  `ingredient3_itemid` int(10) unsigned NOT NULL DEFAULT '0',
  `suggestion` varchar(255) NOT NULL,
  `suggestionid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`suggestionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table alchemake.inventory
DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `userid` int(10) unsigned NOT NULL,
  `itemid` int(10) unsigned NOT NULL,
  `qty` int(10) unsigned NOT NULL,
  PRIMARY KEY (`userid`,`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table alchemake.items
DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `itemid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `basic` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`itemid`),
  KEY `basic` (`basic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table alchemake.trades
DROP TABLE IF EXISTS `trades`;
CREATE TABLE IF NOT EXISTS `trades` (
  `tradeid` bigint(20) NOT NULL AUTO_INCREMENT,
  `proposer_userid` varchar(255) NOT NULL,
  `proposed_userid` varchar(255) NOT NULL,
  `status` enum('pending','rejected','counteroffered','withdrawn','complete') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tradeid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table alchemake.trade_details
DROP TABLE IF EXISTS `trade_details`;
CREATE TABLE IF NOT EXISTS `trade_details` (
  `tradeid` bigint(20) NOT NULL,
  `direction` enum('TO_PROPOSER','FROM_PROPOSER') NOT NULL,
  `itemid` int(10) unsigned NOT NULL,
  `qty` int(10) unsigned NOT NULL,
  KEY `tradeid` (`tradeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table alchemake.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `userid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `networkid` enum('email') NOT NULL DEFAULT 'email',
  `emailaddress` varchar(255) NOT NULL,
  `networkcredential` varchar(255) NOT NULL,
  `nickname` varchar(60) NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `last_drop` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_allowence` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `main_order` varchar(4) NOT NULL DEFAULT 'TIU' COMMENT 'Order for index.php T=Trade, I=Items, U=Userinfo',
  PRIMARY KEY (`userid`),
  UNIQUE KEY `nickname` (`nickname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
