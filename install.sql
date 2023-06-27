DROP TABLE IF EXISTS `wechat_config`;
CREATE TABLE `wechat_config` (
  `key` varchar(32) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `wechat_config` (`key`, `value`) VALUES
('admin_password', ''),
('admin_username', ''),
('version', '1003'),
('ip_type', '0'),
('syskey', '');

DROP TABLE IF EXISTS `wechat_domain`;
CREATE TABLE `wechat_domain` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `domain` varchar(128) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `addtime` datetime DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `wechat_record`;
CREATE TABLE `wechat_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `did` int(11) NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `appid` varchar(32) NOT NULL,
  `redirect_uri` text NOT NULL,
  `state` text DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `addtime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `did` (`did`),
  KEY `addtime` (`addtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `wechat_token`;
CREATE TABLE `wechat_token` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `name` varchar(30) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `appid` varchar(32) NOT NULL,
  `appsecret` varchar(50) NOT NULL,
  `access_token` varchar(300) DEFAULT NULL,
  `addtime` datetime DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL,
  `expiretime` datetime DEFAULT NULL,
 PRIMARY KEY (`id`),
 KEY `appid` (`appid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `wechat_log`;
CREATE TABLE `wechat_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` tinyint(4) NOT NULL DEFAULT '1',
  `action` varchar(40) NOT NULL,
  `data` varchar(150) DEFAULT NULL,
  `addtime` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `wechat_servergroup`;
CREATE TABLE `wechat_servergroup` (
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

DROP TABLE IF EXISTS `wechat_serveritem`;
CREATE TABLE `wechat_serveritem` (
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