CREATE TABLE `#pool__system_stats` (
  `count` int(8) NOT NULL PRIMARY KEY,
  `nodes` int(5) default NULL,
  `tcp` int(8) default NULL,
  `udp` int(8) default NULL,
  `cons` int(8) default NULL,
  `tunnel` int(8) default NULL,
  `sas` int(8) default NULL,
  `consistency` decimal(5,4) default NULL,
  `retries` decimal(6,4) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
