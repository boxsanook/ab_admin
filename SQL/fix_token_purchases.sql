-- Drop existing foreign key constraints if they exist
ALTER TABLE token_purchases 
DROP FOREIGN KEY IF EXISTS fk_token_purchases_user,
DROP FOREIGN KEY IF EXISTS fk_token_purchases_referral;

-- Drop existing indexes if they exist
ALTER TABLE token_purchases 
DROP INDEX IF EXISTS user_id,
DROP INDEX IF EXISTS referral_code;

-- Ensure the referenced columns have the correct indexes in users table
ALTER TABLE users 
MODIFY user_id varchar(255) NOT NULL,
MODIFY affiliate_code varchar(8) DEFAULT NULL,
ADD INDEX IF NOT EXISTS idx_user_id (user_id),
ADD INDEX IF NOT EXISTS idx_affiliate_code (affiliate_code);

-- Modify token_purchases table structure to match referenced columns
ALTER TABLE token_purchases 
MODIFY user_id varchar(255) NOT NULL,
MODIFY referral_code varchar(8) DEFAULT NULL;

-- Add back the indexes and foreign key constraints
ALTER TABLE token_purchases
ADD INDEX idx_user_id (user_id),
ADD INDEX idx_referral_code (referral_code),
ADD CONSTRAINT fk_token_purchases_user 
    FOREIGN KEY (user_id) 
    REFERENCES users(user_id) 
    ON DELETE CASCADE,
ADD CONSTRAINT fk_token_purchases_referral 
    FOREIGN KEY (referral_code) 
    REFERENCES users(affiliate_code) 
    ON DELETE SET NULL; 