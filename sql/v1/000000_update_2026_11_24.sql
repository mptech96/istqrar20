-- تحديث 2026-11-24
-- 1) التجديد الآلي للاشتراك (العقد: المادة الثانية - ج: "تحديد مدة الاشتراك لكل منشأة ونظام تجديد آلي")
ALTER TABLE `companies`
  ADD COLUMN `auto_renew` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: تجديد يدوي, 1: تجديد آلي عند الانتهاء' AFTER `subscription_status`;

-- 2) إظهار المنشأة في صفحة "عملاؤنا" بالموقع العام (بموافقة مدير النظام)
ALTER TABLE `companies`
  ADD COLUMN `show_on_website` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: تظهر في صفحة عملاؤنا' AFTER `auto_renew`;

-- 3) قالب إشعار تجديد الاشتراك
INSERT INTO `notification_templates` (`type`, `locale`, `title_template`, `body_template`, `is_active`)
SELECT 'subscription_renewed', 'ar', 'تجديد الاشتراك', 'تم تجديد اشتراك منشأة {company_name} تلقائياً حتى {expiry_date}.', 1
WHERE NOT EXISTS (SELECT 1 FROM `notification_templates` WHERE `type` = 'subscription_renewed' AND `locale` = 'ar');
