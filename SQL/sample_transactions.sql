-- Update these users to be referred by BoxS
UPDATE users 
SET referral_by = (SELECT code FROM affiliates WHERE code = '5RPZTUQV')
WHERE affiliate_code  !='5RPZTUQV' ;

-- Insert sample transactions for BoxS (5RPZTUQV)
INSERT INTO affiliate_transactions 
(affiliate_id, referral_id, amount, type, status, description, created_at)
SELECT 
    (SELECT id FROM affiliates WHERE code = '5RPZTUQV'),
    'U7ef1073b19d44141c72201193151d39c', -- Nattapong.K
    1500.00,
    'commission',
    'completed',
    'Commission from new user registration and first purchase',
    DATE_SUB(NOW(), INTERVAL 15 DAY)
UNION ALL
SELECT 
    (SELECT id FROM affiliates WHERE code = '5RPZTUQV'),
    'U1fbac1f72716265be6d22fa7c1fa6f02', -- suaheew
    2000.00,
    'commission',
    'completed',
    'Commission from premium package purchase',
    DATE_SUB(NOW(), INTERVAL 10 DAY)
UNION ALL
SELECT 
    (SELECT id FROM affiliates WHERE code = '5RPZTUQV'),
    'U2f09473869e84ae4b813c1b0b8d4b4bd', -- Tawat
    1000.00,
    'commission',
    'pending',
    'Commission from basic package purchase',
    DATE_SUB(NOW(), INTERVAL 5 DAY)
UNION ALL
SELECT 
    (SELECT id FROM affiliates WHERE code = '5RPZTUQV'),
    'Ud6365725ee5a00b6e73444a0a79d1ab0', -- I'm Ton
    3000.00,
    'commission',
    'completed',
    'Commission from enterprise package purchase',
    DATE_SUB(NOW(), INTERVAL 3 DAY)
UNION ALL
SELECT 
    (SELECT id FROM affiliates WHERE code = '5RPZTUQV'),
    'U4344ce6371339bff46ccabdef0f7496d', -- ! TuMz
    500.00,
    'bonus',
    'completed',
    'Performance bonus for reaching 5 referrals',
    DATE_SUB(NOW(), INTERVAL 1 DAY);

-- Update affiliate statistics after adding transactions
UPDATE affiliates 
SET 
    total_referrals = (
        SELECT COUNT(DISTINCT referral_id) 
        FROM affiliate_transactions 
        WHERE affiliate_id = (SELECT id FROM affiliates WHERE code = '5RPZTUQV')
    ),
    total_earnings = (
        SELECT COALESCE(SUM(amount), 0)
        FROM affiliate_transactions 
        WHERE affiliate_id = (SELECT id FROM affiliates WHERE code = '5RPZTUQV')
        AND status = 'completed'
    ),
    visits = 100,
    conversions = 5
WHERE code = '5RPZTUQV';

-- Insert a sample payout record
INSERT INTO affiliate_payouts 
(affiliate_id, amount, payment_method, payment_details, status, payout_date, created_at)
SELECT 
    (SELECT id FROM affiliates WHERE code = '5RPZTUQV'),
    5000.00,
    'bank_transfer',
    '{"bank_name": "Example Bank", "account_number": "XXXX-XXXX-XXXX-1234"}',
    'completed',
    DATE_SUB(NOW(), INTERVAL 2 DAY),
    NOW(); 