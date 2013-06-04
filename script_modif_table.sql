ALTER TABLE dashboards ADD COLUMN `group_id` int(11) NOT NULL DEFAULT '1';

INSERT INTO groups VALUES (1, 'Default group', 'This group is the default group. It can\'t be deleted nor edited.');

