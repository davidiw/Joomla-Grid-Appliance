CREATE TABLE `#pool__stats` (
  `count` int(8) NOT NULL,
  `brunet_address` varchar(32) NOT NULL,
  `neighbor` varchar(32) default NULL,
  `retries` smallint(2) default NULL,
  `consistency` tinyint(1) default NULL,
  `ip` varchar(15) default NULL,
  `type` varchar(10) default NULL,
  `virtual_ip` varchar(15) default NULL,
  `namespace` varchar(256) default NULL,
  `geo_loc` varchar(20) default NULL,
  `tcp` int(4) default NULL,
  `udp` int(4) default NULL,
  `cons` int(4) default NULL,
  `tunnel` int(4) default NULL,
  `sas` int(4) default NULL,
   PRIMARY KEY(brunet_address, count)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
