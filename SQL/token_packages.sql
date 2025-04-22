-- Create token packages table
CREATE TABLE IF NOT EXISTS `token_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `tokens` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `bonus_tokens` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default token packages
INSERT INTO `token_packages` (`name`, `tokens`, `price`, `bonus_tokens`, `description`, `status`) VALUES
('Starter Pack', 100, 299.00, 0, 'Perfect for beginners', 'active'),
('Popular Pack', 500, 1299.00, 50, 'Most popular choice', 'active'),
('Pro Pack', 1000, 2499.00, 150, 'Best value for professionals', 'active'),
('Ultimate Pack', 2500, 5999.00, 500, 'Maximum value for power users', 'active'); 