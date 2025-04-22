-- Create table for token purchases
CREATE TABLE `token_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tokens` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `referral_code` varchar(8) DEFAULT NULL,
  `commission_paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `referral_code` (`referral_code`),
  CONSTRAINT `fk_token_purchases_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_token_purchases_referral` FOREIGN KEY (`referral_code`) REFERENCES `users` (`affiliate_code`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create view for token purchase summary
CREATE VIEW `token_purchase_summary` AS
SELECT 
    u.name as buyer_name,
    u.email as buyer_email,
    tp.amount,
    tp.tokens,
    tp.payment_method,
    tp.payment_status,
    tp.created_at as purchase_date,
    a.name as affiliate_name,
    a.email as affiliate_email,
    tp.referral_code,
    tp.commission_paid
FROM token_purchases tp
LEFT JOIN users u ON tp.user_id = u.user_id
LEFT JOIN users a ON tp.referral_code = a.affiliate_code
WHERE tp.payment_status = 'completed'; 