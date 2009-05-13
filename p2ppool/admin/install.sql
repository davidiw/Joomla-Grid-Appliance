CREATE TABLE `p2ppools` (
  `pool_id` int(11) NOT NULL auto_increment,
  `pool` varchar(256) NOT NULL,
  `default_pool` tinyint(1) default NULL,
  `user_name` varchar(256) default NULL,
  `install_path` varchar(1024) default NULL,
  `tcpport` int(11) default '0',
  `udpport` int(11) default '0',
  `description` text,
  `rpcport` int(11) default '0',
  `namespace` varchar(256) default NULL,
  `mkbundle` tinyint(1) default '0',
  `running` tinyint(1) default '0',
  `inuse` tinyint(1) default '0',
  `uninstall` tinyint(1) default '0',
  PRIMARY KEY  (`pool_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

CREATE TABLE `p2ppool_tasks` (
  `task_id` int(11) NOT NULL auto_increment,
  `task` varchar(256) NOT NULL,
  `recurring` tinyint(1) NOT NULL,
  `next_run` datetime NOT NULL,
  `period` int(11) NOT NULL,
  PRIMARY KEY (`task_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

INSERT INTO p2ppool_tasks (task, recurring, next_run, period) VALUE ("crawl", 1, NOW(), 1500);
INSERT INTO p2ppool_tasks (task, recurring, next_run, period) VALUE ("check", 1, NOW(), 86100);
INSERT INTO p2ppool_tasks (task, recurring, next_run, period) VALUE ("uninstall", 1, NOW(), 86100);

CREATE TABLE `p2ppool_taskman` (
  `pool` varchar(256) NOT NULL,
  `task` varchar(256) NOT NULL,
  `pid` int(11) NOT NULL,
  `start_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
