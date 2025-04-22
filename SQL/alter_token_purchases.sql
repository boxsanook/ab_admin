-- Add package_id column to token_purchases table
ALTER TABLE `token_purchases`
ADD COLUMN `package_id` int(11) DEFAULT NULL AFTER `user_id`;

-- Add foreign key constraint
ALTER TABLE `token_purchases`
ADD CONSTRAINT `fk_token_purchases_package` 
FOREIGN KEY (`package_id`) 
REFERENCES `token_packages` (`id`) 
ON DELETE SET NULL; 