-- إدراج أو تحديث الإعدادات العامة
INSERT INTO `settings` (`group`, `key`, `value`, `type`, `label`, `description`, `is_public`, `sort_order`, `created_at`, `updated_at`)
VALUES
('general', 'site_name', 'نظام التقييم', 'string', 'اسم الموقع', 'اسم الموقع الذي يظهر في الصفحة الرئيسية', 1, 1, NOW(), NOW()),
('general', 'site_description', 'نظام متكامل لتقييم الفروع والشركات', 'text', 'وصف الموقع', NULL, 1, 2, NOW(), NOW()),
('general', 'contact_email', 'info@example.com', 'string', 'البريد الإلكتروني للتواصل', NULL, 1, 3, NOW(), NOW()),
('general', 'contact_phone', '+966500000000', 'string', 'رقم الهاتف للتواصل', NULL, 1, 4, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`),
    `label` = VALUES(`label`),
    `description` = VALUES(`description`),
    `is_public` = VALUES(`is_public`),
    `sort_order` = VALUES(`sort_order`),
    `updated_at` = NOW();

-- إدراج أو تحديث إعدادات البريد الإلكتروني
INSERT INTO `settings` (`group`, `key`, `value`, `type`, `label`, `is_encrypted`, `created_at`, `updated_at`)
VALUES
('email', 'mail_host', 'smtp.gmail.com', 'string', 'خادم البريد', 0, NOW(), NOW()),
('email', 'mail_port', '587', 'number', 'منفذ البريد', 0, NOW(), NOW()),
('email', 'mail_username', 'noreply@example.com', 'string', 'اسم المستخدم', 0, NOW(), NOW()),
('email', 'mail_password', 'password', 'string', 'كلمة المرور', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`),
    `label` = VALUES(`label`),
    `is_encrypted` = VALUES(`is_encrypted`),
    `updated_at` = NOW();

-- إدراج أو تحديث إعدادات النظام
INSERT INTO `settings` (`group`, `key`, `value`, `type`, `label`, `validation_rules`, `created_at`, `updated_at`)
VALUES
('system', 'items_per_page', '15', 'number', 'عدد العناصر في الصفحة', 'integer|min:5|max:100', NOW(), NOW()),
('system', 'enable_registration', 'true', 'boolean', 'تفعيل التسجيل', NULL, NOW(), NOW()),
('system', 'default_language', 'ar', 'string', 'اللغة الافتراضية', NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`),
    `label` = VALUES(`label`),
    `validation_rules` = VALUES(`validation_rules`),
    `updated_at` = NOW();

-- إدراج أو تحديث إعدادات الإشعارات
INSERT INTO `settings` (`group`, `key`, `value`, `type`, `label`, `created_at`, `updated_at`)
VALUES
('notifications', 'email_notifications', 'true', 'boolean', 'إشعارات البريد الإلكتروني', NOW(), NOW()),
('notifications', 'sms_notifications', 'false', 'boolean', 'إشعارات الرسائل القصيرة', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`),
    `label` = VALUES(`label`),
    `updated_at` = NOW();

-- إدراج أو تحديث إعدادات الأمان
INSERT INTO `settings` (`group`, `key`, `value`, `type`, `label`, `created_at`, `updated_at`)
VALUES
('security', 'max_login_attempts', '5', 'number', 'الحد الأقصى لمحاولات تسجيل الدخول', NOW(), NOW()),
('security', 'password_expiry_days', '90', 'number', 'صلاحية كلمة المرور (بالأيام)', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`),
    `label` = VALUES(`label`),
    `updated_at` = NOW();

-- إدراج أو تحديث إعدادات الميزات
INSERT INTO `settings` (`group`, `key`, `value`, `type`, `label`, `description`, `created_at`, `updated_at`)
VALUES
('features', 'enable_tickets', 'true', 'boolean', 'تفعيل نظام التذاكر', NULL, NOW(), NOW()),
('features', 'enable_attachments', 'true', 'boolean', 'تفعيل المرفقات', NULL, NOW(), NOW()),
('features', 'max_file_size', '10240', 'number', 'الحد الأقصى لحجم الملف (كيلوبايت)', 'الحد الأقصى لحجم الملفات المرفوعة بالكيلوبايت', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`),
    `label` = VALUES(`label`),
    `description` = VALUES(`description`),
    `updated_at` = NOW();

-- إدراج أو تحديث إعدادات المظهر
INSERT INTO `settings` (`group`, `key`, `value`, `type`, `label`, `is_public`, `created_at`, `updated_at`)
VALUES
('appearance', 'primary_color', '#3490dc', 'string', 'اللون الأساسي', 1, NOW(), NOW()),
('appearance', 'logo_url', '/images/logo.png', 'string', 'رابط الشعار', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `value` = VALUES(`value`),
    `type` = VALUES(`type`),
    `label` = VALUES(`label`),
    `is_public` = VALUES(`is_public`),
    `updated_at` = NOW();

-- تحديث إعداد اللغة الافتراضية مع خيارات JSON (إذا كان موجوداً)
UPDATE `settings` 
SET `options` = JSON_OBJECT('ar', 'العربية', 'en', 'English'),
    `updated_at` = NOW()
WHERE `group` = 'system' AND `key` = 'default_language';

-- تحديث validation_rules للبريد الإلكتروني (إذا كان موجوداً)
UPDATE `settings` 
SET `validation_rules` = 'email',
    `updated_at` = NOW()
WHERE `group` = 'general' AND `key` = 'contact_email';