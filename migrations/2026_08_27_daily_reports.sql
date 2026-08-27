-- Create daily_reports table for worker self-reported summaries
CREATE TABLE IF NOT EXISTS `daily_reports` (
    `report_id` int(11) NOT NULL AUTO_INCREMENT,
    `worker_id` int(11) NOT NULL,
    `report_date` date NOT NULL,
    `collections_count` int(11) NOT NULL DEFAULT 0,
    `total_weight` decimal(8,2) NOT NULL DEFAULT 0.00,
    `route_efficiency` varchar(50) NOT NULL DEFAULT 'good',
    `notes` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`report_id`),
    UNIQUE KEY `worker_date` (`worker_id`, `report_date`),
    KEY `worker_id` (`worker_id`),
    CONSTRAINT `daily_reports_worker_fk` FOREIGN KEY (`worker_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
