CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request` tinyint(1) DEFAULT 0,
  `member` tinyint(1) DEFAULT 0,
  `revoked` tinyint(1) DEFAULT 0,
  `admin` tinyint(1) DEFAULT 0,
  `reason` text DEFAULT NULL,
  `secret` varchar(32) DEFAULT 0,
  `join_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`, `user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `groupvpn` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(256) NOT NULL UNIQUE,
  `description` text,
  `create_time` TIMESTAMP DEFAULT '0000-00-00 00:00:00',
  `last_update` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;

CREATE TABLE `groupvpn_config` (
  `group_id` int(11) NOT NULL,
  `node_params` text,
  `ipop_params` text,
  `dhcp_params` text,
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
