CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(200) NOT NULL,
  `role` varchar(100) NOT NULL DEFAULT 'user',
  `password` varchar(100) NOT NULL COMMENT 'This hash is generated using fCryptography::hashPassword()',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) CHARSET=utf8;

CREATE TABLE `settings` (
  `name` varchar(100) NOT NULL,
  `friendly_name` varchar(200) NOT NULL,
  `value` varchar(500) NOT NULL,
  `value_type` varchar(100) NOT NULL DEFAULT 'string',
  `plugin` varchar(200) NOT NULL,
  `type` varchar(200) NOT NULL DEFAULT 'system' COMMENT 'So far this can be user or system',
  `owner_id` int(11) NOT NULL COMMENT 'Can be used by plugins to associated the value with a user or other object like a dashboard',
  PRIMARY KEY (`name`,`owner_id`)
) CHARSET=utf8;

CREATE TABLE `dashboards` (
  `dashboard_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(1000) NOT NULL DEFAULT '',
  `columns` int(11) NOT NULL DEFAULT '2',
  `background_color` varchar(15) NOT NULL DEFAULT '000000',
  `graph_height` int(11) NOT NULL DEFAULT '300',
  `graph_width` int(11) NOT NULL DEFAULT '300',
  `refresh_rate` int(11) NOT NULL DEFAULT 0,
  `group_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`dashboard_id`),
  UNIQUE KEY `user_id` (`user_id`,`name`)
) CHARSET=utf8;

CREATE TABLE `checks` (
  `check_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `target` varchar(1000) NOT NULL,
  `error` decimal(20,3) NOT NULL,
  `warn` decimal(20,3) NOT NULL,
  `sample` varchar(255) NOT NULL DEFAULT '10',
  `baseline` varchar(255) NOT NULL DEFAULT 'average',
  `visibility` int(11) NOT NULL DEFAULT '0',
  `over_under` int(11) NOT NULL DEFAULT '0',
  `enabled` varchar(45) NOT NULL DEFAULT '1',
  `last_check_status` int(11) DEFAULT NULL,
  `last_check_value` bigint(20) DEFAULT NULL,
  `last_check_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `repeat_delay` int(11) NOT NULL DEFAULT '60',
  `type` varchar(255) NOT NULL,
  `regression_type` varchar(255) DEFAULT NULL,
  `number_of_regressions` int(11) DEFAULT NULL,
  `group_id` int(11) NOT NULL DEFAULT '1',
  `hour_start` varchar(5) DEFAULT NULL,
  `hour_end` varchar(5) DEFAULT NULL,
  `day_start` varchar(3) DEFAULT NULL,
  `day_end` varchar(3) DEFAULT NULL,
  PRIMARY KEY (`check_id`),
  UNIQUE KEY `user_id` (`user_id`,`name`)
) CHARSET=utf8;

CREATE TABLE `check_results` (
  `result_id` int(11) NOT NULL AUTO_INCREMENT,
  `check_id` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `value` decimal(20,3) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `acknowledged` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`result_id`),
  KEY `check_id` (`check_id`)
) CHARSET=utf8;

CREATE TABLE `graphs` (
  `graph_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `area` varchar(45) NOT NULL,
  `vtitle` varchar(45) DEFAULT NULL,
  `description` varchar(1000) NOT NULL DEFAULT '',
  `dashboard_id` int(11) NOT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  `time_value` int(11) NOT NULL DEFAULT '2',
  `unit` varchar(10) NOT NULL DEFAULT 'hours',
  `custom_opts` varchar(1000) NULL,
  `starts_at_midnight` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`graph_id`),
  UNIQUE KEY `dashboard_id` (`dashboard_id`,`name`)
) CHARSET=utf8;

CREATE TABLE `lines` (
  `line_id` int(11) NOT NULL AUTO_INCREMENT,
  `color` varchar(45) DEFAULT NULL,
  `target` varchar(1000) NOT NULL DEFAULT '',
  `alias` varchar(255) DEFAULT NULL,
  `graph_id` int(11) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`line_id`)
) CHARSET=utf8;

CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL AUTO_INCREMENT,
  `check_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `threshold` int(11) NOT NULL DEFAULT '0',
  `method` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `frequency` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`subscription_id`),
  UNIQUE KEY `user_method` (`check_id`, `user_id`, `method`),
  KEY `user_id` (`user_id`)
) CHARSET=utf8;

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(1000) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `name` (`name`)
) CHARSET=utf8;
INSERT INTO groups VALUES (1, 'Default group', 'This group is the default group. It can\'t be deleted nor edited.');
