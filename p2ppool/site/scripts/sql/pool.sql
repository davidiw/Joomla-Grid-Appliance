CREATE TABLE `#pool__pool` (
  `ip` varchar(15) NOT NULL PRIMARY KEY,
  `name` varchar(256) default NULL,
  `coordinates` varchar(20) default NULL,
  `installed` tinyint(1) default 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
