SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- TODO: Possibly have combos and suggestions look to 
-- recipe table, with the recipe ID moved from suggestions
-- to combos when accepted

--
-- Table structure for table `combinations`
--

CREATE TABLE IF NOT EXISTS `combinations` (
  `itemid` int(10) unsigned NOT NULL,
  `ingredient1_itemid` int(10) unsigned NOT NULL,
  `ingredient2_itemid` int(10) unsigned NOT NULL,
  `ingredient3_itemid` int(10) unsigned NOT NULL DEFAULT '0',
  `preq_tool_itemid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`ingredient1_itemid`,`ingredient2_itemid`,`ingredient3_itemid`)
);

-- --------------------------------------------------------

--
-- Table structure for table `combo-suggest`
--

CREATE TABLE IF NOT EXISTS `combo-suggest` (
  `userid` varchar(255) NOT NULL,
  `ingredient1_itemid` int(10) unsigned NOT NULL,
  `ingredient2_itemid` int(10) unsigned NOT NULL,
  `ingredient3_itemid` int(10) unsigned NOT NULL DEFAULT '0',
  `suggestion` varchar(255) NOT NULL,
  `suggestionid` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`suggestionid`)
);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE IF NOT EXISTS `inventory` (
  `userid` varchar(255) NOT NULL,
  `itemid` int(10) unsigned NOT NULL,
  `qty` int(10) unsigned NOT NULL,
  PRIMARY KEY (`userid`,`itemid`)
);

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE IF NOT EXISTS `items` (
  `itemid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `basic` tinyint(1) NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`itemid`),
  KEY `basic` (`basic`)
);

-- --------------------------------------------------------

--
-- Table structure for table `tradedetails`
--

CREATE TABLE IF NOT EXISTS `tradedetails` (
  `tradeid` bigint(20) NOT NULL,
  `proposer_itemid` int(10) unsigned NOT NULL,
  `proposer_qty` int(10) unsigned NOT NULL,
  `proposed_itemid` int(10) unsigned NOT NULL,
  `proposed_qty` int(10) unsigned NOT NULL,
  KEY `tradeid` (`tradeid`)
);

-- --------------------------------------------------------

--
-- Table structure for table `trades`
--

CREATE TABLE IF NOT EXISTS `trades` (
  `tradeid` bigint(20) NOT NULL AUTO_INCREMENT,
  `proposer_userid` varchar(255) NOT NULL,
  `proposed_userid` varchar(255) NOT NULL,
  `status` enum('pending','rejected','counteroffered','withdrawn','complete') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tradeid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=64 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userid` varchar(255) NOT NULL,
  `networkid` varchar(255) NOT NULL,
  `nickname` varchar(60) NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL,
  `last_drop` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_allowence` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `main_order` varchar(4) NOT NULL DEFAULT 'TIU' COMMENT 'Order for index.php T=Trade, I=Items, U=Userinfo',
  PRIMARY KEY (`userid`),
  UNIQUE KEY `nickname` (`nickname`)
);
