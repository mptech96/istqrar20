ALTER TABLE `visits`
ADD COLUMN `start_latitude` decimal(10,8) DEFAULT NULL COMMENT 'خط العرض عند بدء الزيارة' AFTER `actual_duration`,
ADD COLUMN `start_longitude` decimal(11,8) DEFAULT NULL COMMENT 'خط الطول عند بدء الزيارة' AFTER `start_latitude`,
ADD COLUMN `end_latitude` decimal(10,8) DEFAULT NULL COMMENT 'خط العرض عند انتهاء الزيارة' AFTER `start_longitude`,
ADD COLUMN `end_longitude` decimal(11,8) DEFAULT NULL COMMENT 'خط الطول عند انتهاء الزيارة' AFTER `end_latitude`,
ADD COLUMN `location_url` varchar(500) DEFAULT NULL COMMENT 'رابط موقع جوجل ماب' AFTER `end_longitude`,
ADD COLUMN `location_address` text DEFAULT NULL COMMENT 'العنوان المستخرج من الموقع' AFTER `location_url`,
ADD INDEX `idx_visit_location` (`start_latitude`, `start_longitude`);