ALTER TABLE dashboards ADD COLUMN `group_id` int(11) NOT NULL DEFAULT '1';
ALTER TABLE checks ADD COLUMN `group_id` int(11) NOT NULL DEFAULT '1';
ALTER TABLE lines ADD COLUMN `weight` int(11) NOT NULL DEFAULT '0';

INSERT INTO groups VALUES (1, 'Default group', 'This group is the default group. It can\'t be deleted nor edited.');

ALTER TABLE checks ADD COLUMN `hour_start` varchar(5) DEFAULT NULL;
ALTER TABLE checks ADD COLUMN `hour_end` varchar(5) DEFAULT NULL;
ALTER TABLE checks ADD COLUMN `day_start` varchar(3) DEFAULT NULL;
ALTER TABLE checks ADD COLUMN `day_end` varchar(3) DEFAULT NULL;

ALTER TABLE graphs ADD COLUMN `starts_at_midnight` TINYINT(1) DEFAULT 0;

ALTER TABLE checks MODIFY COLUMN last_check_value bigint;

CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(1000) NOT NULL,
  PRIMARY KEY (`group_id`),
  KEY `name` (`name`)
) CHARSET=utf8;
INSERT INTO groups VALUES (1, 'Default group', 'This group is the default group. It can\'t be deleted nor edited.');

delete a from subscriptions as a, subscriptions as b where a.check_id = b.check_id and a.user_id = b.user_id and a.method = b.method and a.subscription_id < b.subscription_id
alter table subscriptions add constraint unique key user_method (check_id, user_id, method);
