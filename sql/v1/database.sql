
-- جدول الشركات
CREATE TABLE IF NOT EXISTS `companies` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `commercial_registration` varchar(50) DEFAULT NULL,
    `city` varchar(100) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `contact_person` varchar(255) DEFAULT NULL,
    `logo` varchar(500) DEFAULT NULL,
    
    -- الاشتراك
    `subscription_start` date DEFAULT NULL,
    `subscription_end` date DEFAULT NULL,
    `subscription_status` enum('active', 'expired', 'suspended', 'pending') DEFAULT 'pending',
    
    -- الإحصائيات
    `total_branches` int(11) DEFAULT 0,
    `overall_score` decimal(5,2) DEFAULT 0.00,
    `category` varchar(255) DEFAULT 'C',
    
    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0: inactive, 1: active',
    `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: not verified, 1: verified',
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_company_email` (`email`),
    UNIQUE KEY `uq_company_commercial_reg` (`commercial_registration`),
    
    INDEX `idx_company_name` (`name`),
    INDEX `idx_company_city` (`city`),
    INDEX `idx_company_status` (`status`),
    INDEX `idx_company_subscription` (`subscription_end`, `subscription_status`),
    INDEX `idx_company_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الفروع
CREATE TABLE IF NOT EXISTS `branches` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `company_id` bigint(20) unsigned NOT NULL,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    
    -- الموقع
    `city` varchar(100) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `latitude` decimal(10,8) DEFAULT NULL,
    `longitude` decimal(11,8) DEFAULT NULL,
    
    -- المدير
    `manager_user_id` bigint(20) unsigned DEFAULT NULL,
    `manager_name` varchar(255) DEFAULT NULL,
    `manager_email` varchar(255) DEFAULT NULL,
    `manager_phone` varchar(20) DEFAULT NULL,
    
    -- الإحصائيات
    `compliance_score` decimal(5,2) DEFAULT 0.00,
    `category` varchar(255) DEFAULT 'C',
    `total_visits` int(11) DEFAULT 0,
    `last_visit_date` date DEFAULT NULL,
    `next_scheduled_visit` date DEFAULT NULL,
    
    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0: inactive, 1: active',
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_branch_email_company` (`company_id`, `email`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_branch_company` (`company_id`),
    INDEX `idx_branch_city` (`city`),
    INDEX `idx_branch_category` (`category`),
    INDEX `idx_branch_score` (`compliance_score`),
    INDEX `idx_branch_status` (`status`),
    INDEX `idx_branch_next_visit` (`next_scheduled_visit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS `users` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    
    -- الأدوار المحسنة
    `role` enum('super_admin', 'auditor', 'company_admin', 'branch_manager') NOT NULL DEFAULT 'auditor',
    `token` VARCHAR(500) NULL DEFAULT NULL,
    
    -- للشركات والفروع
    `company_id` bigint(20) unsigned DEFAULT NULL,
    `branch_id` bigint(20) unsigned DEFAULT NULL,
    `department` varchar(100) DEFAULT NULL,
    `position` varchar(100) DEFAULT NULL,
    
    -- للمراجعين فقط
    `national_number` varchar(50) DEFAULT NULL,
    `municipality_certificate` varchar(500) DEFAULT NULL,
    `driving_license` varchar(500) DEFAULT NULL,
    `personal_photo` varchar(500) DEFAULT NULL,
    
    -- صلاحية الوثائق
    `certificate_expiry` date DEFAULT NULL,
    `license_expiry` date DEFAULT NULL,
    `id_expiry` date DEFAULT NULL,
    `is_certificate_valid` tinyint(1) DEFAULT 1,
    `is_license_valid` tinyint(1) DEFAULT 1,
    `is_id_valid` tinyint(1) DEFAULT 1,
    
    -- معلومات شخصية
    `birth_date` date DEFAULT NULL,
    `gender` enum('male', 'female', 'other') DEFAULT NULL,
    
    -- الحساب
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `remember_token` varchar(100) DEFAULT NULL,
    `last_login_at` timestamp NULL DEFAULT NULL,
    `last_login_ip` varchar(45) DEFAULT NULL,
    
    `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0: inactive, 1: active, 2: suspended',
    `must_change_password` tinyint(1) DEFAULT 0,
    
    `created_by` bigint(20) unsigned DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_email` (`email`),
    UNIQUE KEY `uq_user_national_number` (`national_number`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_user_name` (`name`),
    INDEX `idx_user_role` (`role`),
    INDEX `idx_user_company` (`company_id`),
    INDEX `idx_user_branch` (`branch_id`),
    INDEX `idx_user_status` (`status`),
    INDEX `idx_user_created` (`created_at`),
    INDEX `idx_user_documents` (`certificate_expiry`, `license_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول معايير التقييم
CREATE TABLE IF NOT EXISTS `criterias` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `category` varchar(100) DEFAULT NULL,
    `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    
    `created_by` bigint(20) unsigned DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_criteria_name` (`name`),
    INDEX `idx_criteria_category` (`category`),
    INDEX `idx_criteria_active` (`is_active`),
    INDEX `idx_criteria_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول نطاقات التقييم
CREATE TABLE IF NOT EXISTS `criteria_ranges` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `criteria_id` bigint(20) unsigned NOT NULL,
    `name` varchar(100) NOT NULL,
    `min_score` int(11) NOT NULL,
    `max_score` int(11) NOT NULL,
    `color` varchar(10) DEFAULT NULL,
    `description` text DEFAULT NULL,
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`criteria_id`) REFERENCES `criterias`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_range_criteria` (`criteria_id`),
    UNIQUE KEY `uq_range_criteria_name` (`criteria_id`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول قوائم المراجعة
CREATE TABLE IF NOT EXISTS `checklists` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `code` varchar(50) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `version` varchar(20) DEFAULT '1.0',
    
    -- الجهة المستهدفة
    `company_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL يعني عام',
    `industry_type` varchar(100) DEFAULT NULL,
    
    -- الإعدادات
    `total_score` int(11) NOT NULL DEFAULT 100,
    `passing_score` int(11) NOT NULL DEFAULT 70,
    `time_estimate` int(11) DEFAULT NULL COMMENT 'الوقت المقدر بالدقائق',
    
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `is_template` tinyint(1) DEFAULT 0 COMMENT '0: not template, 1: template',
    
    `created_by` bigint(20) unsigned DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_checklist_name` (`name`),
    INDEX `idx_checklist_code` (`code`),
    INDEX `idx_checklist_company` (`company_id`),
    INDEX `idx_checklist_active` (`is_active`),
    INDEX `idx_checklist_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الأسئلة
CREATE TABLE IF NOT EXISTS `questions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `checklist_id` bigint(20) unsigned NOT NULL,
    `criteria_id` bigint(20) unsigned DEFAULT NULL,
    
    `question_text` text NOT NULL,
    `description` text DEFAULT NULL,
    `help_text` text DEFAULT NULL,
    
    -- نوع السؤال المحسن
    `type` enum('yes_no', 'scale_1_5', 'scale_1_10', 'multiple_choice', 'text', 'file', 'date', 'number') NOT NULL DEFAULT 'yes_no',
    
    -- للاختيارات المتعددة (JSON format)
    `options` json DEFAULT NULL,
    `correct_answer` varchar(255) DEFAULT NULL,
    
    -- الترجيح
    `weight` int(11) NOT NULL DEFAULT 1,
    `max_score` int(11) NOT NULL DEFAULT 10,
    `passing_score` int(11) DEFAULT NULL,
    
    -- الإعدادات
    `is_required` tinyint(1) NOT NULL DEFAULT 1,
    `is_critical` tinyint(1) DEFAULT 0 COMMENT 'خطأ حرج',
    `has_attachment` tinyint(1) DEFAULT 0,
    `attachment_instructions` text DEFAULT NULL,
    
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`checklist_id`) REFERENCES `checklists`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`criteria_id`) REFERENCES `criterias`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_question_checklist` (`checklist_id`),
    INDEX `idx_question_criteria` (`criteria_id`),
    INDEX `idx_question_sort` (`checklist_id`, `sort_order`),
    INDEX `idx_question_critical` (`is_critical`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الزيارات
CREATE TABLE IF NOT EXISTS `visits` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    
    -- الجهات المعنية
    `branch_id` bigint(20) unsigned NOT NULL,
    `auditor_id` bigint(20) unsigned NOT NULL,
    `checklist_id` bigint(20) unsigned NOT NULL,
    
    -- التواريخ
    `scheduled_date` date NOT NULL,
    `scheduled_time` time DEFAULT NULL,
    `actual_start_time` timestamp NULL DEFAULT NULL,
    `actual_end_time` timestamp NULL DEFAULT NULL,
    `visit_date` date DEFAULT NULL,
    `completed_at` timestamp NULL DEFAULT NULL,
    
    -- التوقيت
    `estimated_duration` int(11) DEFAULT NULL COMMENT 'بالدقائق',
    `actual_duration` int(11) DEFAULT NULL COMMENT 'بالدقائق',
    
    `start_latitude` decimal(10,8) DEFAULT NULL COMMENT 'خط العرض عند بدء الزيارة',
    `start_longitude` decimal(11,8) DEFAULT NULL COMMENT 'خط الطول عند بدء الزيارة',
    `end_latitude` decimal(10,8) DEFAULT NULL COMMENT 'خط العرض عند انتهاء الزيارة',
    `end_longitude` decimal(11,8) DEFAULT NULL COMMENT 'خط الطول عند انتهاء الزيارة',
    `location_url` varchar(500) DEFAULT NULL COMMENT 'رابط موقع جوجل ماب',
    `location_address` text DEFAULT NULL COMMENT 'العنوان المستخرج من الموقع',
    

    -- النتائج
    `total_score` decimal(5,2) DEFAULT 0.00,
    `max_possible_score` int(11) DEFAULT 100,
    `percentage` decimal(5,2) DEFAULT 0.00,
    `category` varchar(255) DEFAULT 'C',
    
    -- الحالة المحسنة
    `status` enum('draft', 'scheduled', 'in_progress', 'completed', 'cancelled', 'under_review', 'approved', 'rejected') DEFAULT 'draft',
    
    -- الموافقات
    `approved_by` bigint(20) unsigned DEFAULT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `rejection_reason` text DEFAULT NULL,
    
    -- ملاحظات
    `auditor_notes` text DEFAULT NULL,
    `branch_feedback` text DEFAULT NULL,
    `internal_notes` text DEFAULT NULL,
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`auditor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`checklist_id`) REFERENCES `checklists`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_visit_branch` (`branch_id`),
    INDEX `idx_visit_auditor` (`auditor_id`),
    INDEX `idx_visit_status` (`status`),
    INDEX `idx_visit_scheduled` (`scheduled_date`),
    INDEX `idx_visit_date` (`visit_date`),
    INDEX `idx_visit_approved` (`approved_at`),
    INDEX `idx_visit_category` (`category`),
    INDEX `idx_visit_location` (`start_latitude`, `start_longitude`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الإجابات
CREATE TABLE IF NOT EXISTS `answers` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `visit_id` bigint(20) unsigned NOT NULL,
    `question_id` bigint(20) unsigned NOT NULL,
    
    -- الإجابة المحسنة
    `answer_value` text DEFAULT NULL,
    `score` decimal(5,2) DEFAULT 0.00,
    `max_score` int(11) DEFAULT 10,
    `is_correct` tinyint(1) DEFAULT NULL,
    
    -- ملاحظات
    `comment` text DEFAULT NULL,
    `auditor_comment` text DEFAULT NULL,
    `corrective_action` text DEFAULT NULL,
    `corrective_deadline` date DEFAULT NULL,
    
    -- حالة التصحيح
    `correction_status` enum('pending', 'in_progress', 'completed', 'verified', 'waived') DEFAULT 'pending',
    `corrected_at` timestamp NULL DEFAULT NULL,
    `verified_by` bigint(20) unsigned DEFAULT NULL,
    `verified_at` timestamp NULL DEFAULT NULL,
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_answer_visit_question` (`visit_id`, `question_id`),
    FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_answer_visit` (`visit_id`),
    INDEX `idx_answer_question` (`question_id`),
    INDEX `idx_answer_correction` (`correction_status`, `corrective_deadline`),
    INDEX `idx_answer_score` (`score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول التحليلات
CREATE TABLE IF NOT EXISTS `visit_analyses` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `visit_id` bigint(20) unsigned NOT NULL,
    
    -- المعلومات الأساسية
    `branch_id` bigint(20) unsigned NOT NULL,
    `company_id` bigint(20) unsigned NOT NULL,
    
    -- نوع التحليل
    `analysis_type` enum('automatic', 'manual', 'ai', 'comparative') DEFAULT 'automatic',
    `analysis_date` date NOT NULL,
    `generated_by` bigint(20) unsigned DEFAULT NULL,
    
    -- النتائج الكمية
    `total_questions` int(11) DEFAULT 0,
    `answered_questions` int(11) DEFAULT 0,
    `correct_answers` int(11) DEFAULT 0,
    `incorrect_answers` int(11) DEFAULT 0,
    
    -- الأخطاء
    `critical_errors` int(11) DEFAULT 0,
    `major_errors` int(11) DEFAULT 0,
    `minor_errors` int(11) DEFAULT 0,
    
    -- النقاط
    `total_score` decimal(5,2) DEFAULT 0.00,
    `lost_points` decimal(5,2) DEFAULT 0.00,
    `potential_score` decimal(5,2) DEFAULT 0.00,
    
    -- التحليل النصي
    `summary` text DEFAULT NULL,
    `strengths` text DEFAULT NULL,
    `weaknesses` text DEFAULT NULL,
    `opportunities` text DEFAULT NULL,
    `threats` text DEFAULT NULL,
    
    -- التوصيات (JSON format)
    `recommendations` json DEFAULT NULL,
    `priority_recommendations` json DEFAULT NULL,
    `improvement_plan` text DEFAULT NULL,
    
    -- المقارنة
    `previous_visit_id` bigint(20) unsigned DEFAULT NULL,
    `improvement_rate` decimal(5,2) DEFAULT NULL,
    
    -- التصنيف
    `error_categories` json DEFAULT NULL,
    `top_error_category` varchar(100) DEFAULT NULL,
    
    -- الحالة
    `status` enum('generated', 'reviewed', 'approved', 'rejected') DEFAULT 'generated',
    `reviewed_by` bigint(20) unsigned DEFAULT NULL,
    `reviewed_at` timestamp NULL DEFAULT NULL,
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_analysis_visit` (`visit_id`),
    FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`previous_visit_id`) REFERENCES `visits`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_analysis_branch` (`branch_id`),
    INDEX `idx_analysis_company` (`company_id`),
    INDEX `idx_analysis_date` (`analysis_date`),
    INDEX `idx_analysis_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول التذاكر (نظام جديد)
CREATE TABLE IF NOT EXISTS `tickets` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `ticket_number` varchar(50) NOT NULL,
    
    -- الجهة المرسلة
    `created_by` bigint(20) unsigned NOT NULL,
    `user_type` enum('company', 'branch', 'auditor', 'admin', 'quality') NOT NULL,
    `company_id` bigint(20) unsigned DEFAULT NULL,
    `branch_id` bigint(20) unsigned DEFAULT NULL,
    `visit_id` bigint(20) unsigned DEFAULT NULL,
    
    -- محتوى التذكرة
    `subject` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `ticket_type` enum('dispute', 'complaint', 'request', 'inquiry', 'technical', 'feedback') NOT NULL,
    
    -- الأولوية والتتبع
    `priority` enum('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    `department` enum('support', 'technical', 'quality', 'billing', 'admin') DEFAULT 'support',
    
    -- التخصيص والمتابعة
    `assigned_to` bigint(20) unsigned DEFAULT NULL,
    `assigned_at` timestamp NULL DEFAULT NULL,
    
    -- الحالة
    `status` enum('open', 'in_progress', 'pending_customer', 'resolved', 'closed', 'escalated') DEFAULT 'open',
    `resolution` text DEFAULT NULL,
    `resolved_by` bigint(20) unsigned DEFAULT NULL,
    `resolved_at` timestamp NULL DEFAULT NULL,
    
    -- SLA
    `sla_due_date` timestamp NULL DEFAULT NULL,
    `first_response_at` timestamp NULL DEFAULT NULL,
    `resolution_due_date` timestamp NULL DEFAULT NULL,
    
    -- إحصائيات
    `replies_count` int(11) DEFAULT 0,
    `customer_satisfaction` tinyint(1) DEFAULT NULL COMMENT '1-5',
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ticket_number` (`ticket_number`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`visit_id`) REFERENCES `visits`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`resolved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_ticket_number` (`ticket_number`),
    INDEX `idx_ticket_created_by` (`created_by`),
    INDEX `idx_ticket_status` (`status`),
    INDEX `idx_ticket_priority` (`priority`),
    INDEX `idx_ticket_company` (`company_id`),
    INDEX `idx_ticket_branch` (`branch_id`),
    INDEX `idx_ticket_assigned` (`assigned_to`, `status`),
    INDEX `idx_ticket_sla` (`sla_due_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول ردود التذاكر
CREATE TABLE IF NOT EXISTS `ticket_replies` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `ticket_id` bigint(20) unsigned NOT NULL,
    `replied_by` bigint(20) unsigned NOT NULL,
    
    -- المحتوى
    `message` text NOT NULL,
    `is_internal` tinyint(1) DEFAULT 0 COMMENT '0: public, 1: internal',
    
    -- المرفقات
    `has_attachments` tinyint(1) DEFAULT 0,
    
    -- التتبع
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`replied_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_reply_ticket` (`ticket_id`),
    INDEX `idx_reply_author` (`replied_by`),
    INDEX `idx_reply_created` (`created_at`),
    INDEX `idx_reply_internal` (`is_internal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المرفقات المحسن
CREATE TABLE IF NOT EXISTS `attachments` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    
    -- العلاقة الديناميكية
    `entity_type` varchar(255) NOT NULL,
    `entity_id` bigint(20) unsigned NOT NULL,
    
    -- معلومات الملف
    `original_name` varchar(255) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_url` varchar(500) NOT NULL,
    
    -- المواصفات
    `file_type` varchar(100) DEFAULT NULL,
    `mime_type` varchar(100) DEFAULT NULL,
    `file_size` bigint(20) DEFAULT NULL COMMENT 'بالبايت',
    `file_extension` varchar(10) DEFAULT NULL,
    
    -- للملفات المرئية
    `width` int(11) DEFAULT NULL,
    `height` int(11) DEFAULT NULL,
    `duration` int(11) DEFAULT NULL COMMENT 'للفيديو بالثواني',
    
    -- الوصف
    `title` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `alt_text` varchar(255) DEFAULT NULL,
    
    -- التخزين السحابي
    `storage_driver` enum('local', 's3', 'cloudinary', 'digitalocean') DEFAULT 'local',
    `cloud_provider` varchar(50) DEFAULT NULL,
    `cloud_public_id` varchar(255) DEFAULT NULL,
    `cloud_version` varchar(50) DEFAULT NULL,
    
    -- المالك والإذن
    `uploaded_by` bigint(20) unsigned NOT NULL,
    `upload_source` enum('web', 'mobile', 'api') DEFAULT 'web',
    `is_public` tinyint(1) DEFAULT 0,
    `access_token` varchar(100) DEFAULT NULL,
    
    -- الحالة
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `is_deleted` tinyint(1) DEFAULT 0,
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_attachment_entity` (`entity_type`, `entity_id`),
    INDEX `idx_attachment_user` (`uploaded_by`),
    INDEX `idx_attachment_type` (`file_type`),
    INDEX `idx_attachment_storage` (`storage_driver`),
    INDEX `idx_attachment_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجلات التدقيق
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    
    -- المستخدم
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `user_type` varchar(50) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    
    -- الإجراء
    `action` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    
    -- الكيان المتأثر
    `entity_type` varchar(100) DEFAULT NULL,
    `entity_id` bigint(20) unsigned DEFAULT NULL,
    `entity_name` varchar(255) DEFAULT NULL,
    
    -- التغييرات
    `old_values` json DEFAULT NULL,
    `new_values` json DEFAULT NULL,
    `changed_fields` text DEFAULT NULL,
    
    -- الموقع
    `url` varchar(500) DEFAULT NULL,
    `method` varchar(10) DEFAULT NULL,
    
    -- الأمن
    `is_suspicious` tinyint(1) DEFAULT 0,
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    
    PRIMARY KEY (`id`),
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_created` (`created_at`),
    INDEX `idx_audit_suspicious` (`is_suspicious`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الإحصائيات
CREATE TABLE IF NOT EXISTS `statistics` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `record_date` date NOT NULL,
    `metric_name` varchar(100) NOT NULL,
    `metric_value` decimal(15,2) NOT NULL,
    
    -- التجميع
    `period_type` enum('daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'daily',
    `company_id` bigint(20) unsigned DEFAULT NULL,
    `branch_id` bigint(20) unsigned DEFAULT NULL,
    `auditor_id` bigint(20) unsigned DEFAULT NULL,
    
    -- بيانات إضافية
    `additional_data` json DEFAULT NULL,
    `trend_direction` enum('up', 'down', 'stable') DEFAULT 'stable',
    `previous_value` decimal(15,2) DEFAULT NULL,
    `percentage_change` decimal(5,2) DEFAULT NULL,
    
    `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_statistic_record` (`record_date`, `metric_name`, `company_id`, `branch_id`, `auditor_id`, `period_type`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`auditor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_statistic_date` (`record_date`),
    INDEX `idx_statistic_metric` (`metric_name`),
    INDEX `idx_statistic_company` (`company_id`),
    INDEX `idx_statistic_branch` (`branch_id`),
    INDEX `idx_statistic_period` (`period_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الإعدادات الديناميكية
CREATE TABLE IF NOT EXISTS `settings` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `group` varchar(100) NOT NULL DEFAULT 'general',
    `key` varchar(100) NOT NULL,
    `value` text DEFAULT NULL,
    `type` enum('string', 'number', 'boolean', 'json', 'array', 'text') DEFAULT 'string',
    `data_type` varchar(50) DEFAULT NULL,
    
    -- التحقق
    `validation_rules` text DEFAULT NULL,
    `options` json DEFAULT NULL COMMENT 'للاختيارات المحددة',
    
    -- العرض
    `label` varchar(255) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `placeholder` varchar(255) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    
    -- الأذونات
    `is_public` tinyint(1) DEFAULT 0,
    `is_encrypted` tinyint(1) DEFAULT 0,
    `is_required` tinyint(1) DEFAULT 0,
    
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key_group` (`group`, `key`),
    
    INDEX `idx_settings_group` (`group`),
    INDEX `idx_settings_key` (`key`),
    INDEX `idx_settings_sort` (`group`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_suggestions` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,

    -- الربط مع الإحصائيات
    `statistic_id` bigint(20) unsigned NULL,
    `metric_name` varchar(100) NOT NULL,
    `record_date` date NOT NULL,
    `period_type` enum('daily','weekly','monthly','quarterly','yearly') NOT NULL,

    -- نطاق التأثير
    `company_id` bigint(20) unsigned DEFAULT NULL,
    `branch_id` bigint(20) unsigned DEFAULT NULL,

    -- حالة التقييم
    `current_value` decimal(15,2) NOT NULL,
    `previous_value` decimal(15,2) DEFAULT NULL,
    `percentage_change` decimal(5,2) DEFAULT NULL,
    `trend_direction` enum('up','down','stable') NOT NULL,

    -- تصنيف الذكاء الاصطناعي
    `severity` enum('info','warning','critical') NOT NULL,
    `status` enum('excellent','good','average','poor','critical') NOT NULL,

    -- مخرجات الذكاء الاصطناعي
    `suggestion` text NOT NULL,
    `summary` text DEFAULT NULL,
    `confidence` decimal(5,2) DEFAULT NULL COMMENT 'AI confidence %',

    -- بيانات النموذج
    `ai_provider` varchar(50) NOT NULL COMMENT 'openai, azure, local',
    `ai_model` varchar(100) NOT NULL,
    `tokens_used` int DEFAULT NULL,
    `processing_time` int DEFAULT NULL COMMENT 'ms',
    `metadata` json NULL DEFAULT NULL,

    -- متابعة التنفيذ
    `is_applied` tinyint(1) NOT NULL DEFAULT 0,
    `applied_by` bigint(20) unsigned DEFAULT NULL,
    `applied_at` timestamp NULL DEFAULT NULL,

    -- ملاحظات إدارية
    `admin_notes` text DEFAULT NULL,

    -- التتبع الزمني
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
    `deleted_at` timestamp NULL DEFAULT NULL,

    PRIMARY KEY (`id`),

    -- العلاقات
    FOREIGN KEY (`statistic_id`) REFERENCES `statistics`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`applied_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,

    -- الفهارس
    INDEX `idx_ai_statistic` (`statistic_id`),
    INDEX `idx_ai_metric` (`metric_name`),
    INDEX `idx_ai_company` (`company_id`),
    INDEX `idx_ai_branch` (`branch_id`),
    INDEX `idx_ai_severity` (`severity`),
    INDEX `idx_ai_status` (`status`),
    INDEX `idx_ai_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `tokenable_type` (`tokenable_type`),
  KEY `tokenable_id` (`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `queue` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary key',
    `email` VARCHAR(255) NOT NULL COMMENT 'Email address used for login attempt',
    `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP address (supports IPv6 with length 45)',
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of login attempt',
    `successful` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Whether login was successful',
    `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Laravel created_at timestamp',
    `updated_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Laravel updated_at timestamp',
    
    INDEX `login_attempts_email_index` (`email`),
    INDEX `login_attempts_ip_address_index` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
