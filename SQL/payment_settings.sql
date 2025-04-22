-- Create payment settings table
CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `promptpay_id` varchar(15) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default payment settings
INSERT INTO `payment_settings` (
  `promptpay_id`, 
  `bank_name`, 
  `bank_account_name`, 
  `bank_account_number`, 
  `status`
) VALUES (
  '0812345678',
  'Kasikorn Bank',
  'Your Company Name',
  '123-4-56789-0',
  'active'
); 