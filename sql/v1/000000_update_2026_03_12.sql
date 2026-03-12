ALTER TABLE `companies`
ADD COLUMN `max_allowed_users` int(11) DEFAULT 2 AFTER `logo`,
ADD COLUMN `max_allowed_branches` int(11) DEFAULT 1 AFTER `max_allowed_users`;