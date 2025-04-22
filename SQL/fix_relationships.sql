-- Drop existing foreign key constraints
ALTER TABLE affiliate_transactions DROP FOREIGN KEY IF EXISTS fk_affiliate_transactions;
ALTER TABLE affiliate_payouts DROP FOREIGN KEY IF EXISTS fk_affiliate_payouts;
ALTER TABLE users DROP FOREIGN KEY IF EXISTS fk_user_referral;

-- Drop existing indexes if they exist
ALTER TABLE affiliates DROP INDEX IF EXISTS user_id;
ALTER TABLE affiliates DROP INDEX IF EXISTS code;
ALTER TABLE affiliate_transactions DROP INDEX IF EXISTS affiliate_id;
ALTER TABLE affiliate_transactions DROP INDEX IF EXISTS referral_id;
ALTER TABLE users DROP INDEX IF EXISTS referral_by;

-- First, ensure primary keys are set up correctly
ALTER TABLE affiliates
  MODIFY id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  MODIFY user_id varchar(255) NOT NULL,
  MODIFY code varchar(20) NOT NULL;

ALTER TABLE users
  MODIFY id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  MODIFY user_id varchar(255) NOT NULL;

ALTER TABLE affiliate_transactions
  MODIFY id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  MODIFY affiliate_id int(11) NOT NULL,
  MODIFY referral_id varchar(255) NOT NULL;

-- Create affiliates records for existing referrers
INSERT IGNORE INTO affiliates (user_id, name, code, commission_rate, status, created_at)
SELECT DISTINCT 
    u1.user_id,
    COALESCE(u1.display_name, u1.name) as name,
    u1.affiliate_code as code,
    10.00 as commission_rate,
    'active' as status,
    NOW() as created_at
FROM users u1
WHERE u1.affiliate_code IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM affiliates a 
    WHERE a.code = u1.affiliate_code
);

-- Update or remove invalid referral_by values
UPDATE users 
SET referral_by = NULL 
WHERE referral_by IS NOT NULL 
AND referral_by NOT IN (SELECT code FROM affiliates);

-- Add required indexes
ALTER TABLE affiliates
  ADD UNIQUE INDEX idx_affiliate_code (code),
  ADD INDEX idx_affiliate_user (user_id);

ALTER TABLE users
  ADD UNIQUE INDEX idx_user_id (user_id),
  ADD INDEX idx_referral_by (referral_by);

ALTER TABLE affiliate_transactions
  ADD INDEX idx_affiliate_id (affiliate_id),
  ADD INDEX idx_referral_id (referral_id);

-- Now add foreign key constraints
ALTER TABLE affiliate_transactions
  ADD CONSTRAINT fk_affiliate_transactions FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_affiliate_transactions_referral FOREIGN KEY (referral_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Modify affiliate_payouts table
ALTER TABLE affiliate_payouts
  ADD CONSTRAINT fk_affiliate_payouts FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE ON UPDATE CASCADE;

-- Add additional indexes for performance
ALTER TABLE affiliates
  ADD INDEX idx_affiliate_status (status),
  ADD INDEX idx_affiliate_created (created_at);

ALTER TABLE affiliate_transactions
  ADD INDEX idx_transaction_status (status),
  ADD INDEX idx_transaction_created (created_at);

ALTER TABLE affiliate_payouts
  ADD INDEX idx_payout_status (status),
  ADD INDEX idx_payout_created (created_at);

-- Update visits and conversions columns in affiliates table if they don't exist
ALTER TABLE affiliates
  ADD COLUMN IF NOT EXISTS visits INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS conversions INT DEFAULT 0;

-- Update affiliate statistics
UPDATE affiliates a
SET 
    total_referrals = (
        SELECT COUNT(*) 
        FROM users u 
        WHERE u.referral_by = a.code
    ),
    total_earnings = COALESCE(
        (SELECT SUM(amount) 
         FROM affiliate_transactions 
         WHERE affiliate_id = a.id 
         AND status = 'completed'),
        0
    ); 