-- Phase 1 database repair migration for BoteBank
-- Backup created at: backups/bote_backup_2026_08_27.sql

-- 1. Widen columns that are truncating real data
ALTER TABLE `recyclabletypes`
    MODIFY `name` varchar(100) NOT NULL;

ALTER TABLE `users`
    MODIFY `first_name` varchar(50) NOT NULL,
    MODIFY `last_name` varchar(50) NOT NULL,
    MODIFY `email` varchar(100) NOT NULL;

-- 2. Add missing status value used by cancel_request.php
ALTER TABLE `pickuprequests`
    MODIFY `status` enum('Pending','Scheduled','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending';

-- 3. Drop unused duplicate column
ALTER TABLE `rewards`
    DROP COLUMN `image_url`;

-- 4. Add missing smsnotifications table (referenced by includes/sms_notifications.php)
CREATE TABLE IF NOT EXISTS `smsnotifications` (
    `notification_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `message` text NOT NULL,
    `type` varchar(50) NOT NULL,
    `sms_id` varchar(255) DEFAULT NULL,
    `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`notification_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `smsnotifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Add description context to points ledger (fixes admin_pickup_requests.php INSERT)
ALTER TABLE `pointsledger`
    ADD COLUMN `description` text DEFAULT NULL AFTER `transaction_type`;

-- 6. Add useful indexes for frequently filtered queries
ALTER TABLE `users`
    ADD KEY `idx_users_role` (`role`);

ALTER TABLE `pickuprequests`
    ADD KEY `idx_pickuprequests_status` (`status`),
    ADD KEY `idx_pickuprequests_resident_status` (`resident_id`, `status`),
    ADD KEY `idx_pickuprequests_worker_status` (`assigned_worker_id`, `status`),
    ADD KEY `idx_pickuprequests_worker_date` (`assigned_worker_id`, `preferred_date`);

ALTER TABLE `direct_dropoffs`
    ADD KEY `idx_direct_dropoffs_status` (`status`),
    ADD KEY `idx_direct_dropoffs_resident_status` (`resident_id`, `status`);

ALTER TABLE `collectionroutes`
    ADD KEY `idx_collectionroutes_worker_generated` (`worker_id`, `generated_on`);

ALTER TABLE `pointsledger`
    ADD KEY `idx_pointsledger_user_type` (`user_id`, `transaction_type`);
