ALTER TABLE dashboards ADD COLUMN `group_id` int(11) NOT NULL DEFAULT '1';
ALTER TABLE checks ADD COLUMN `group_id` int(11) NOT NULL DEFAULT '1';
ALTER TABLE lines ADD COLUMN `weight` int(11) NOT NULL DEFAULT '0';

INSERT INTO groups VALUES (1, 'Default group', 'This group is the default group. It can\'t be deleted nor edited.');

ALTER TABLE checks ADD COLUMN `hour_start` varchar(5) DEFAULT NULL;
ALTER TABLE checks ADD COLUMN `hour_end` varchar(5) DEFAULT NULL;
ALTER TABLE checks ADD COLUMN `day_start` varchar(3) DEFAULT NULL;
ALTER TABLE checks ADD COLUMN `day_end` varchar(3) DEFAULT NULL;

ALTER TABLE graphs ADD COLUMN `starts_at_midnight` TINYINT(1) DEFAULT 0;

