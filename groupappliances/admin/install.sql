CREATE TABLE `groupappliances_users` (
  `ga_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request` tinyint(1) DEFAULT 0,
  `member` tinyint(1) DEFAULT 0,
  `revoked` tinyint(1) DEFAULT 0,
  `admin` tinyint(1) DEFAULT 0,
  `reason` text DEFAULT NULL,
  `join_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ga_id`, `user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `groupappliances` (
  `group_id` int(11) NOT NULL,
  `ga_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(256) NOT NULL,
  `description` text,
  `create_time` TIMESTAMP DEFAULT '0000-00-00 00:00:00',
  `last_update` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ga_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;

DROP TABLE IF EXISTS `#__linux_passwords`;
CREATE TABLE IF NOT EXISTS `#__linux_passwords` (
  `user_id` int(11) NOT NULL,
  `password` varchar(50) NOT NULL,
  PRIMARY_KEY(`user_id`)
) ENGINE=MyISAM;
