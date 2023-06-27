CREATE TABLE IF NOT EXISTS `wechat_servergroup` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(128) NOT NULL,
  `token` varchar(32) NOT NULL,
  `enckey` varchar(64) DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `mode` tinyint(1) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  `addtime` datetime DEFAULT NULL,
  `usetime` datetime DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wechat_serveritem` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `gid` int(11) unsigned NOT NULL,
  `sort` int(11) unsigned NOT NULL DEFAULT '0',
  `url` varchar(500) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `addtime` datetime DEFAULT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT '0',
 PRIMARY KEY (`id`),
 KEY `gid` (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;