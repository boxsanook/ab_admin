-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 20, 2025 at 04:01 PM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u666915587_line_02`
--

-- --------------------------------------------------------

--
-- Table structure for table `affiliates`
--

CREATE TABLE `affiliates` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 0.00,
  `total_referrals` int(11) DEFAULT 0,
  `total_earnings` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `visits` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `affiliates`
--

INSERT INTO `affiliates` (`id`, `user_id`, `name`, `code`, `description`, `commission_rate`, `total_referrals`, `total_earnings`, `status`, `created_at`, `updated_at`, `visits`, `conversions`) VALUES
(1, 'Uea66fcbd258ee222b18a995c5bdf02a3', '', '69BG7SO7', NULL, 10.00, 0, 0.00, 'inactive', '2025-04-20 06:42:47', '2025-04-20 09:18:07', 0, 0),
(2, 'U7ef1073b19d44141c72201193151d39c', '', 'VSYIH74V', NULL, 10.00, 0, 0.00, 'active', '2025-04-20 06:42:47', '2025-04-20 06:42:47', 0, 0),
(3, 'Udfe24051a6efd4b9ab7cf9e7a1f293d8', 'BoxS', '5RPZTUQV', NULL, 10.00, 5, 7000.00, 'active', '2025-04-20 06:42:47', '2025-04-20 06:51:49', 100, 5),
(4, 'U1fbac1f72716265be6d22fa7c1fa6f02', '', '8A7E347D', NULL, 10.00, 0, 0.00, 'inactive', '2025-04-20 12:56:33', '2025-04-20 13:01:40', 0, 0),
(5, 'Udc17d452a81808d9329ad6902c1b6465', '', 'A81A2FCA', NULL, 10.00, 0, 0.00, 'active', '2025-04-20 15:45:46', '2025-04-20 15:45:46', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `affiliate_payouts`
--

CREATE TABLE `affiliate_payouts` (
  `id` int(11) NOT NULL,
  `affiliate_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_details` text DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `payout_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `affiliate_payouts`
--

INSERT INTO `affiliate_payouts` (`id`, `affiliate_id`, `amount`, `payment_method`, `payment_details`, `status`, `payout_date`, `created_at`, `updated_at`) VALUES
(0, 3, 5000.00, 'bank_transfer', '{\"bank_name\": \"Example Bank\", \"account_number\": \"XXXX-XXXX-XXXX-1234\"}', 'completed', '2025-04-18 06:51:49', '2025-04-20 06:51:49', '2025-04-20 06:51:49');

-- --------------------------------------------------------

--
-- Table structure for table `affiliate_settings`
--

CREATE TABLE `affiliate_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `affiliate_settings`
--

INSERT INTO `affiliate_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'default_commission_rate', '10.00', 'Default commission rate for new affiliates (%)', '2025-04-20 05:46:49', '2025-04-20 05:46:49'),
(2, 'minimum_payout', '1000.00', 'Minimum amount required for payout', '2025-04-20 05:46:49', '2025-04-20 05:46:49'),
(3, 'payout_schedule', 'monthly', 'Frequency of affiliate payouts', '2025-04-20 05:46:49', '2025-04-20 05:46:49'),
(4, 'cookie_duration', '30', 'Duration of affiliate tracking cookie in days', '2025-04-20 05:46:49', '2025-04-20 05:46:49');

-- --------------------------------------------------------

--
-- Table structure for table `affiliate_transactions`
--

CREATE TABLE `affiliate_transactions` (
  `id` int(11) NOT NULL,
  `affiliate_id` int(11) NOT NULL,
  `referral_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('commission','bonus','refund') DEFAULT 'commission',
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `affiliate_transactions`
--

INSERT INTO `affiliate_transactions` (`id`, `affiliate_id`, `referral_id`, `amount`, `type`, `status`, `description`, `created_at`, `updated_at`) VALUES
(5, 3, 'U7ef1073b19d44141c72201193151d39c', 1500.00, 'commission', 'completed', 'Commission from new user registration and first purchase', '2025-04-05 06:51:49', '2025-04-20 06:51:49'),
(6, 3, 'U1fbac1f72716265be6d22fa7c1fa6f02', 2000.00, 'commission', 'completed', 'Commission from premium package purchase', '2025-04-10 06:51:49', '2025-04-20 06:51:49'),
(7, 3, 'U2f09473869e84ae4b813c1b0b8d4b4bd', 1000.00, 'commission', 'pending', 'Commission from basic package purchase', '2025-04-15 06:51:49', '2025-04-20 06:51:49'),
(8, 3, 'Ud6365725ee5a00b6e73444a0a79d1ab0', 3000.00, 'commission', 'completed', 'Commission from enterprise package purchase', '2025-04-17 06:51:49', '2025-04-20 06:51:49'),
(9, 3, 'U4344ce6371339bff46ccabdef0f7496d', 500.00, 'bonus', 'completed', 'Performance bonus for reaching 5 referrals', '2025-04-19 06:51:49', '2025-04-20 06:51:49');

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `promptpay_id` varchar(15) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `promptpay_id`, `bank_name`, `bank_account_name`, `bank_account_number`, `status`, `created_at`, `updated_at`) VALUES
(1, '0812345678', 'Kasikorn Bank', 'Your Company Name', '123-4-56789-0', 'active', '2025-04-20 07:13:50', '2025-04-20 07:13:50');

-- --------------------------------------------------------

--
-- Table structure for table `registration_tokens`
--

CREATE TABLE `registration_tokens` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `token` varchar(4000) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_tokens`
--

INSERT INTO `registration_tokens` (`id`, `user_id`, `token`, `expires_at`, `used`, `used_at`, `created_at`) VALUES
(1, 'Uea66fcbd258ee222b18a995c5bdf02a3', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDQwMDY1NjUsImV4cCI6MTc3NTU0MjU2NSwiZGF0YSI6eyJpZCI6MSwidXNlcl9pZCI6IlVlYTY2ZmNiZDI1OGVlMjIyYjE4YTk5NWM1YmRmMDJhMyIsImRpc3BsYXlfbmFtZSI6IiIsImVtYWlsIjoiYm94c2Fub29rMUBnbWFpbC5jb20iLCJwaWN0dXJlX3VybCI6IiIsInN0YXR1c19tZXNzYWdlIjoiIiwiYWNjZXNzX3Rva2VuIjoiIiwicmVmcmVzaF90b2tlbiI6IiIsInRva2VuX2V4cGlyZXNfYXQiOiIwMDAwLTAwLTAwIDAwOjAwOjAwIiwibmFtZSI6Ik1lLkJveHMiLCJhY3RpdmUiOjEsImlzX2FkbWluIjoxLCJjcmVhdGVkX2F0IjoiMjAyNS0wMy0wNyAwNjo1NDoxMyIsInVwZGF0ZWRfYXQiOiIyMDI1LTA0LTA3IDA2OjE1OjE4Iiwibm90aWZ5X2J5IjoidGVsZWdyYW0iLCJ0ZWxlZ3JhbV90b2tlbl9pZCI6Ijc5MzMwMDU4ODE6QUFHSHpscmZhZlAwSmtINXJ2MmJ4RWtpLVExbkVLVkp3MlUiLCJ0ZWxlZ3JhbV9jaGF0X2lkIjoiNzI4NTQ4MTUzMiIsIm1heF9wcm9maWxlIjo5OTksIkNvbXB1dGVyX0lEIjoiMTc4QkZCRkYwMEE1MEYwMC1FODIzOEZBNkJGNTMwMDAxMDAxQjQ0NEE0NjBFMUU2RC1GQzM0OTcwMjUzNTAifX0.6k8y-5ZX_DTSJF8jtrFe-PY00GSKCFUU8sm3sCZB1AM', '2026-04-07 06:16:05', 0, NULL, '2025-04-07 06:16:05'),
(4, 'U7ef1073b19d44141c72201193151d39c', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM5OTEzMTAsImV4cCI6MTc0NjU4MzMxMCwiZGF0YSI6eyJpZCI6MiwidXNlcl9pZCI6IlU3ZWYxMDczYjE5ZDQ0MTQxYzcyMjAxMTkzMTUxZDM5YyIsImRpc3BsYXlfbmFtZSI6bnVsbCwiZW1haWwiOiJib3NzY2x1YjU0MUBnbWFpbC5jb20iLCJwaWN0dXJlX3VybCI6bnVsbCwic3RhdHVzX21lc3NhZ2UiOm51bGwsImFjY2Vzc190b2tlbiI6bnVsbCwicmVmcmVzaF90b2tlbiI6bnVsbCwidG9rZW5fZXhwaXJlc19hdCI6bnVsbCwibmFtZSI6Ik5hdHRhcG9uZy5LIiwiYWN0aXZlIjoxLCJpc19hZG1pbiI6MCwiY3JlYXRlZF9hdCI6IjIwMjUtMDMtMTAgMDE6Mjc6MTgiLCJ1cGRhdGVkX2F0IjoiMjAyNS0wNC0wMyAxMzo1NzowNSIsIm5vdGlmeV9ieSI6InRlbGVncmFtIiwidGVsZWdyYW1fdG9rZW5faWQiOiI3OTMzMDA1ODgxOkFBR0h6bHJmYWZQMEprSDVydjJieEVraS1RMW5FS1ZKdzJVIiwidGVsZWdyYW1fY2hhdF9pZCI6Ijc1MjE5MzkzODUiLCJtYXhfcHJvZmlsZSI6NTAsIkNvbXB1dGVyX0lEIjoiMTc4QkZCRkYwMEE3MEY1Mi1XWDMxQUM3RkswOTUtMjgyRTg5RTExQkI3In19.HjqItUBP-dwzu4I12UMViaCHVzcWVfC-Nr1-k_RZcuw', '2025-05-07 02:01:50', 0, NULL, '2025-04-07 02:01:50'),
(5, 'Uec8d93c4e01f5194dd7a3501be0818f4', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM3NzU4MzQsImV4cCI6MTc3NTMxMTgzNCwiZGF0YSI6eyJpZCI6MywidXNlcl9pZCI6IlVlYzhkOTNjNGUwMWY1MTk0ZGQ3YTM1MDFiZTA4MThmNCIsImRpc3BsYXlfbmFtZSI6bnVsbCwiZW1haWwiOiJhbmlydWRAZW1haWwuY29tIiwicGljdHVyZV91cmwiOm51bGwsInN0YXR1c19tZXNzYWdlIjpudWxsLCJhY2Nlc3NfdG9rZW4iOm51bGwsInJlZnJlc2hfdG9rZW4iOm51bGwsInRva2VuX2V4cGlyZXNfYXQiOm51bGwsIm5hbWUiOiJhbmlydWQiLCJhY3RpdmUiOjEsImlzX2FkbWluIjoxLCJjcmVhdGVkX2F0IjoiMjAyNS0wMy0xMCAwMzo0OTo1NyIsInVwZGF0ZWRfYXQiOiIyMDI1LTAzLTI2IDE0OjAzOjM2Iiwibm90aWZ5X2J5IjoidGVsZWdyYW0iLCJ0ZWxlZ3JhbV90b2tlbl9pZCI6Ijc5MzMwMDU4ODE6QUFHSHpscmZhZlAwSmtINXJ2MmJ4RWtpLVExbkVLVkp3MlUiLCJ0ZWxlZ3JhbV9jaGF0X2lkIjoiNjU3OTA0NzYxMSIsIm1heF9wcm9maWxlIjo5OTksIkNvbXB1dGVyX0lEIjoiIn19.EQO3__hw2DmxahbnUJxw9saM2g6bUqWdg--bZct-DEc', '2026-04-04 14:10:34', 0, NULL, '2025-04-04 14:10:34'),
(6, 'Udc17d452a81808d9329ad6902c1b6465', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDUwNjIxOTcsImV4cCI6MTc0NTkyNjE5NywiZGF0YSI6eyJpZCI6MTcsInVzZXJfaWQiOiJVZGMxN2Q0NTJhODE4MDhkOTMyOWFkNjkwMmMxYjY0NjUiLCJkaXNwbGF5X25hbWUiOiJcdWQ4M2VcdWRlYjhCSUcgQm9TU1x1ZDgzZFx1ZGMxYSIsImVtYWlsIjoic3V0dGF2YXRAaG90bWFpbC5jb20iLCJwaWN0dXJlX3VybCI6Imh0dHBzOi8vcHJvZmlsZS5saW5lLXNjZG4ubmV0LzBoQkVlRWlDR1NIV3BJUEE0NG8tQmpGVGhzSGdCclRVUjRNdzBHREhrOFFRa2lCRmc2WndoVlh5aG9SbHNtWDFnMU4xTlhYSGs0UXdsRUwyb01WbXJoWGs4TVFGdDBDbGs5WVY1UWhBIiwic3RhdHVzX21lc3NhZ2UiOiIiLCJhY2Nlc3NfdG9rZW4iOiJleUpoYkdjaU9pSklVekkxTmlKOS5NSXR0bU1lUmo5d2kzbHZfYW1YbkFKdnNfX29pcHowckcyV0NQX2pmbWpMYWlleTV1cTZmUXhyc2VjTjNjTDJza1dacWZtaGN3ZG5xMFFmTWJDa1FaUVVhUnpoQ050RHlSOXBSV2l4cEFfdk9aYzBVcHpKbkFub1g1dU9Fb1dNQWF2VnhiRTJEWkpIbVJmN1lPT3hZTjgyOEhDUWkxMjNUNlZfaWZWYi1rcFkuWVVHeDNwb3lBeGJOd2RYb3VxM3E3bjlGWFBpaEpHUWJ4bzdIRmRua2s0QSIsInJlZnJlc2hfdG9rZW4iOiJWZGNwUzgxb0NBS284ckRHSU05SSIsInRva2VuX2V4cGlyZXNfYXQiOiIyMDI1LTA1LTA0IDEyOjU0OjAwIiwibmFtZSI6Ilx1ZDgzZVx1ZGViOEJJRyBCb1NTXHVkODNkXHVkYzFhIiwiYWN0aXZlIjoxLCJpc19hZG1pbiI6MCwiY3JlYXRlZF9hdCI6IjIwMjUtMDQtMDQgMTI6NTQ6MzMiLCJ1cGRhdGVkX2F0IjoiMjAyNS0wNC0xOSAxMToyODo1OSIsIm5vdGlmeV9ieSI6InRlbGVncmFtIiwidGVsZWdyYW1fdG9rZW5faWQiOiI3NzA1NTM5NDM1OkFBRXpMc2tTSzRKTUV4YTdvbE90a29QLVZOY1lrS0xPNkNFIiwidGVsZWdyYW1fY2hhdF9pZCI6Ii0xMDAyMzc2ODQwNDQ3IiwibWF4X3Byb2ZpbGUiOjIwLCJDb21wdXRlcl9JRCI6IkJGRUJGQkZGMDAwNDA2RjEtV1gyMkQ4MEgyRDhWLUIwQTdCOUMwQjkwNSIsImxpbmVfbWVzc2FnaW5nX3VzZXJfaWQiOiIifX0.QfqhgIUWljrg-EXW3X48zNyk0gUfVgMGSpkAozWYxcc', '2025-04-29 11:29:57', 0, NULL, '2025-04-19 11:29:57'),
(7, 'U7f17afa4be019f96b4961257aae7fc13', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDUwMzA1MTksImV4cCI6MTc0NTg5NDUxOSwiZGF0YSI6eyJpZCI6NSwidXNlcl9pZCI6IlU3ZjE3YWZhNGJlMDE5Zjk2YjQ5NjEyNTdhYWU3ZmMxMyIsImRpc3BsYXlfbmFtZSI6IiIsImVtYWlsIjoid2F0ZXJtYW42MjYyLTFAZ21haWwuY29tIiwicGljdHVyZV91cmwiOiIiLCJzdGF0dXNfbWVzc2FnZSI6IiIsImFjY2Vzc190b2tlbiI6IiIsInJlZnJlc2hfdG9rZW4iOiIiLCJ0b2tlbl9leHBpcmVzX2F0IjoiMDAwMC0wMC0wMCAwMDowMDowMCIsIm5hbWUiOiJLLndhdGVybWFuIiwiYWN0aXZlIjoxLCJpc19hZG1pbiI6MCwiY3JlYXRlZF9hdCI6bnVsbCwidXBkYXRlZF9hdCI6IjIwMjUtMDQtMDkgMDI6MDY6MzciLCJub3RpZnlfYnkiOiJ0ZWxlZ3JhbSIsInRlbGVncmFtX3Rva2VuX2lkIjoiNzkzMzAwNTg4MTpBQUdIemxyZmFmUDBKa0g1cnYyYnhFa2ktUTFuRUtWSncyVSIsInRlbGVncmFtX2NoYXRfaWQiOiI2MTI3MTc5OTY4IiwibWF4X3Byb2ZpbGUiOjEwLCJDb21wdXRlcl9JRCI6IkJGRUJGQkZGMDAwQjA2RjItRTgyMzhGQTZCRjUzMDAwMTAwMUI0NDhCNEQ4MkFBRDEtQ0MyOEFBODNFQTJGLTAwMTU1RDA1N0VENCIsImxpbmVfbWVzc2FnaW5nX3VzZXJfaWQiOiIifX0.RJhDaOXVSCeOvxmklvbinrvrkuB6-iK9-KpENw6eqz8', '2025-04-29 02:41:59', 0, NULL, '2025-04-19 02:41:59'),
(8, 'Uea66fcbd258ee222b18a995c5bdf02a3_2', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM0MDE2MTEsImV4cCI6MTc3NDkzNzYxMSwiZGF0YSI6eyJpZCI6MTMsInVzZXJfaWQiOiJVZWE2NmZjYmQyNThlZTIyMmIxOGE5OTVjNWJkZjAyYTNfMiIsImVtYWlsIjoiYm94c2Fub29rMkBnbWFpbC5jb20iLCJuYW1lIjoiTWUuQm94cyIsImFjdGl2ZSI6MSwiaXNfYWRtaW4iOjEsImNyZWF0ZWRfYXQiOiIyMDI1LTAzLTA3IDA2OjU0OjEzIiwidXBkYXRlZF9hdCI6IjIwMjUtMDMtMzAgMTU6NTA6MTYiLCJub3RpZnlfYnkiOiJ0ZWxlZ3JhbSIsInRlbGVncmFtX3Rva2VuX2lkIjoiNzkzMzAwNTg4MTpBQUdIemxyZmFmUDBKa0g1cnYyYnhFa2ktUTFuRUtWSncyVSIsInRlbGVncmFtX2NoYXRfaWQiOiI3Mjg1NDgxNTMyIiwibWF4X3Byb2ZpbGUiOjk5OSwiQ29tcHV0ZXJfSUQiOiIifX0.xvTgov8Gzz4TN-ksJw67I5xzN8_hLbr2gZfWw-rqcgA', '2026-03-31 06:13:31', 0, NULL, '2025-03-31 06:13:31'),
(9, 'U1fbac1f72716265be6d22fa7c1fa6f02', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM1MTIyMjQsImV4cCI6MTc0NDcyMTgyNCwiZGF0YSI6eyJpZCI6MTQsInVzZXJfaWQiOiJVMWZiYWMxZjcyNzE2MjY1YmU2ZDIyZmE3YzFmYTZmMDIiLCJlbWFpbCI6InN1YWhlZXdAZ21haWwuY29tIiwibmFtZSI6InN1YWhlZXciLCJhY3RpdmUiOjEsImlzX2FkbWluIjowLCJjcmVhdGVkX2F0IjoiMjAyNS0wNC0wMSAwMjozMToyMCIsInVwZGF0ZWRfYXQiOiIyMDI1LTA0LTAxIDEyOjU2OjUwIiwibm90aWZ5X2J5IjoidGVsZWdyYW0iLCJ0ZWxlZ3JhbV90b2tlbl9pZCI6Ijc5MzMwMDU4ODE6QUFHSHpscmZhZlAwSmtINXJ2MmJ4RWtpLVExbkVLVkp3MlUiLCJ0ZWxlZ3JhbV9jaGF0X2lkIjoiNDUzMzc1NDMwIiwibWF4X3Byb2ZpbGUiOjUsIkNvbXB1dGVyX0lEIjoiQkZFQkZCRkYwMDAzMDZBOS0yMDQ5Nlg4MDI0NjItNTBBRjczMjU1MTQwIn19.A3gUeeyxWZMxSMngFlPhRjAOiQPF4cmoJSDclbMPCEM', '2025-04-15 12:57:04', 0, NULL, '2025-04-01 12:57:04'),
(10, 'U9c75e69eb1a3328c6b2bc30c09b16ba3', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM1NzMyNDQsImV4cCI6MTc0NDQzNzI0NCwiZGF0YSI6eyJpZCI6MTYsInVzZXJfaWQiOiJVOWM3NWU2OWViMWEzMzI4YzZiMmJjMzBjMDliMTZiYTMiLCJlbWFpbCI6InNhcnVueXVAa2t1bWFpbC5jb20iLCJuYW1lIjoidHVteiIsImFjdGl2ZSI6MSwiaXNfYWRtaW4iOjAsImNyZWF0ZWRfYXQiOiIyMDI1LTA0LTAyIDA1OjQwOjE1IiwidXBkYXRlZF9hdCI6IjIwMjUtMDQtMDIgMDU6NTM6NTYiLCJub3RpZnlfYnkiOiJ0ZWxlZ3JhbSIsInRlbGVncmFtX3Rva2VuX2lkIjoiNzkzMzAwNTg4MTpBQUdIemxyZmFmUDBKa0g1cnYyYnhFa2ktUTFuRUtWSncyVSIsInRlbGVncmFtX2NoYXRfaWQiOiI2MDA4MjMwMjg5IiwibWF4X3Byb2ZpbGUiOjUsIkNvbXB1dGVyX0lEIjoiIn19.zLXUr1fT9m1rqSzcm1U7nON7tue9QULh6tR6CyPW6Wc', '2025-04-12 05:54:04', 0, NULL, '2025-04-02 05:54:04'),
(11, 'U2f09473869e84ae4b813c1b0b8d4b4bd', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM5MDkxODIsImV4cCI6MTc0NDc3MzE4MiwiZGF0YSI6eyJpZCI6OSwidXNlcl9pZCI6IlUyZjA5NDczODY5ZTg0YWU0YjgxM2MxYjBiOGQ0YjRiZCIsImRpc3BsYXlfbmFtZSI6IiIsImVtYWlsIjoidHJpbnRoYUBnbWFpbC5jb20iLCJwaWN0dXJlX3VybCI6IiIsInN0YXR1c19tZXNzYWdlIjoiIiwiYWNjZXNzX3Rva2VuIjoiIiwicmVmcmVzaF90b2tlbiI6IiIsInRva2VuX2V4cGlyZXNfYXQiOiIwMDAwLTAwLTAwIDAwOjAwOjAwIiwibmFtZSI6IlRhd2F0IiwiYWN0aXZlIjoxLCJpc19hZG1pbiI6MCwiY3JlYXRlZF9hdCI6IjIwMjUtMDQtMDIgMDY6MTk6NDQiLCJ1cGRhdGVkX2F0IjoiMjAyNS0wNC0wNSAxMzoxNjoyNCIsIm5vdGlmeV9ieSI6InRlbGVncmFtIiwidGVsZWdyYW1fdG9rZW5faWQiOiI3OTMzMDA1ODgxOkFBR0h6bHJmYWZQMEprSDVydjJieEVraS1RMW5FS1ZKdzJVIiwidGVsZWdyYW1fY2hhdF9pZCI6IjIxMDU4MzgxMjkiLCJtYXhfcHJvZmlsZSI6NSwiQ29tcHV0ZXJfSUQiOiJCRkVCRkJGRjAwMEIwNkYyLUU4MjM4RkE2QkY1MzAwMDEwMDFCNDQ4QjREODJBQUQxLUNDMjhBQTgzRUEyRiJ9fQ.Vd2VAB3XeqfspVYYR9z234kv3aGnkY6bDbsc4uiBe4s', '2025-04-16 03:13:02', 0, NULL, '2025-04-06 03:13:02'),
(12, 'Uec8d93c4e01f5194dd7a3501be0818f4_1', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM3Njc5MjQsImV4cCI6MTgzMDA4MTUyNCwiZGF0YSI6eyJpZCI6MTAsInVzZXJfaWQiOiJVZWM4ZDkzYzRlMDFmNTE5NGRkN2EzNTAxYmUwODE4ZjRfMSIsImRpc3BsYXlfbmFtZSI6IiIsImVtYWlsIjoiYW5pcnVkQGdtYWlsLmNvbSIsInBpY3R1cmVfdXJsIjoiIiwic3RhdHVzX21lc3NhZ2UiOiIiLCJhY2Nlc3NfdG9rZW4iOiIiLCJyZWZyZXNoX3Rva2VuIjoiIiwidG9rZW5fZXhwaXJlc19hdCI6IjAwMDAtMDAtMDAgMDA6MDA6MDAiLCJuYW1lIjoiYW5pcnVkIiwiYWN0aXZlIjoxLCJpc19hZG1pbiI6MSwiY3JlYXRlZF9hdCI6IjIwMjUtMDQtMDIgMTI6MTc6MTkiLCJ1cGRhdGVkX2F0IjoiMjAyNS0wNC0wNCAwNjozNDoxMiIsIm5vdGlmeV9ieSI6InRlbGVncmFtIiwidGVsZWdyYW1fdG9rZW5faWQiOiI3OTMzMDA1ODgxOkFBR0h6bHJmYWZQMEprSDVydjJieEVraS1RMW5FS1ZKdzJVIiwidGVsZWdyYW1fY2hhdF9pZCI6IjY1NzkwNDc2MTEiLCJtYXhfcHJvZmlsZSI6OTk5LCJDb21wdXRlcl9JRCI6IjA3OEJGQkZEMDAwNjBGQjEtRjg0QjYzQkE3MzEzIn19.TOekThjWBhP2DD9MMdWF-dkjtFooQJY6c6qo9kNpv9c', '2027-12-29 11:58:44', 0, NULL, '2025-04-04 11:58:44'),
(13, 'Udfe24051a6efd4b9ab7cf9e7a1f293d8', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM3NTc4ODUsImV4cCI6MTc1NDM4NTA4NSwiZGF0YSI6eyJpZCI6MTMsInVzZXJfaWQiOiJVZGZlMjQwNTFhNmVmZDRiOWFiN2NmOWU3YTFmMjkzZDgiLCJkaXNwbGF5X25hbWUiOiJCb3hTIiwiZW1haWwiOiJib3hzYW5vb2tAaG90bWFpbC5jb20iLCJwaWN0dXJlX3VybCI6Imh0dHBzOi8vcHJvZmlsZS5saW5lLXNjZG4ubmV0LzBod0dnNnFZdHNLR2hLVHdCWUJBdFdGem9mS3dKcFBuRjZNbnRqV3kxR2RWOGtmR3RyWkgweUNYZEdkMXNsZTJnMk5YeGtYaTlHZFE5R1hGOE9WQm5VWEUxX2RWbDJlV3dfWXkxbGhnIiwic3RhdHVzX21lc3NhZ2UiOiJGcmVlZG9tcyIsImFjY2Vzc190b2tlbiI6ImV5SmhiR2NpT2lKSVV6STFOaUo5LnFnXzFoTDlrVXVGbFVtRTFXbDhhZFptNmRHSjhJWTkzRkI1UmVzWGIzZTJjQ09Jc2pUZWJxUkhBbzh2dTI5em5xYWFQMk1wTzkyOFM1aGFqSklQTmU3LTRUb1p5RGpsaUd1Z3BoMWtEOXlXMElRMHJmdGI4SVp4RmRNc2tPbk00bmpVVmYzSER2enFwREREQmloeWw5OGJxN1J6MTcyMG5RbldQNUU5ZHZray5vOVVtaFhBVE1pLVBrQy1kSjlIYW5TM0RuZDhuVFNNMzNJQmR1RWVuZ0JnIiwicmVmcmVzaF90b2tlbiI6IklRSHJGN0RCNG1lVFNJelJ6SzNpIiwidG9rZW5fZXhwaXJlc19hdCI6IjAwMDAtMDAtMDAgMDA6MDA6MDAiLCJuYW1lIjoiQm94UyIsImFjdGl2ZSI6MSwiaXNfYWRtaW4iOjEsImNyZWF0ZWRfYXQiOiIyMDI1LTA0LTA0IDA3OjAxOjMxIiwidXBkYXRlZF9hdCI6IjIwMjUtMDQtMDQgMDg6NTY6NTgiLCJub3RpZnlfYnkiOiJ0ZWxlZ3JhbSIsInRlbGVncmFtX3Rva2VuX2lkIjoiNzcwNTUzOTQzNTpBQUV6THNrU0s0Sk1FeGE3b2xPdGtvUC1WTmNZa0tMTzZDRSIsInRlbGVncmFtX2NoYXRfaWQiOiIwIiwibWF4X3Byb2ZpbGUiOjEwLCJDb21wdXRlcl9JRCI6IiJ9fQ.MoyWU-G8Qivl34G2zXcO9FZE890ECc3zCs0j6-H17VU', '2025-08-05 09:11:25', 0, NULL, '2025-04-04 09:11:25'),
(14, 'U690ee81add1c9defa8a386a251dd09f9', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM4Mzg0ODYsImV4cCI6MTc0NDQ0MzI4NiwiZGF0YSI6eyJ1c2VyX2lkIjpudWxsLCJkaXNwbGF5X25hbWUiOiJVbmtub3duIiwiZW1haWwiOiJib3hzYW5vb2s4QGdtYWlsLmNvbSIsImlzX2FkbWluIjowLCJpc19hY3RpdmUiOjF9fQ.qGtuk4K7UA2B0BDbe6I9vzDXvIFl5X5eNjEifa4MwWY', '2025-04-12 07:34:46', 0, NULL, '2025-04-05 07:34:46'),
(15, 'Ud6365725ee5a00b6e73444a0a79d1ab0', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDM5NDk3ODYsImV4cCI6MTc0NDU1NDU4NiwiZGF0YSI6eyJpZCI6MzEsInVzZXJfaWQiOiJVZDYzNjU3MjVlZTVhMDBiNmU3MzQ0NGEwYTc5ZDFhYjAiLCJkaXNwbGF5X25hbWUiOiJJJ20gVG9uIFx1MjY3ZSAyNDY5IiwiZW1haWwiOiJ0ZWVuX3JzdUBob3RtYWlsLmNvbSIsInBpY3R1cmVfdXJsIjoiaHR0cHM6Ly9wcm9maWxlLmxpbmUtc2Nkbi5uZXQvMGhPN2lfVVRSWEVCaEVKamtQSnY1dVp6UjJFM0puVjBrS09oZFdmeVVoR1NGNUhsQkxiMGNQZG5NZ0dTNTZSRlZPT0JSWGRuZHhTeTVJTldkLVduRHNMRU1XVFNsNEVGUlBiVVJkOWciLCJzdGF0dXNfbWVzc2FnZSI6IkFsb25lIiwiYWNjZXNzX3Rva2VuIjoiZXlKaGJHY2lPaUpJVXpJMU5pSjkuZWFoUzRqMHR4eFR2QXA4RmZtc203TV9jQ2JfRXR5Q094VGZ5Y1BNUExFUEM0UHpLTWhxNDNvNEYxZTV1eWlraEJQSkZib0VWdU9vNVVncWVmeUo3Ukxtczg4dkhfM3BHeGluUGpxVzU3SVRWckJWV0Rsc1IyUFQtdk1YalRKVEo2WnM2bWJVNjRMMWFiMUc3c09jZzdCVGV1VTc2dzhHYzNKLU90Y0VzbHNjLkgwdE9EckM1dE9uY1hxSjhZYUpPdHZjeExiZmFIVDZqNTVvd21JaFZnUHciLCJyZWZyZXNoX3Rva2VuIjoiTkk0ZlBMU2E3TWtoZEV0OTRyR2giLCJ0b2tlbl9leHBpcmVzX2F0IjoiMjAyNS0wNS0wNiAxNDoyODozOSIsIm5hbWUiOiJJJ20gVG9uIFx1MjY3ZSAyNDY5IiwiYWN0aXZlIjoxLCJpc19hZG1pbiI6MCwiY3JlYXRlZF9hdCI6IjIwMjUtMDQtMDYgMTQ6Mjg6MzkiLCJ1cGRhdGVkX2F0IjpudWxsLCJub3RpZnlfYnkiOiJ0ZWxlZ3JhbSIsInRlbGVncmFtX3Rva2VuX2lkIjoiIiwidGVsZWdyYW1fY2hhdF9pZCI6IjAiLCJtYXhfcHJvZmlsZSI6MywiQ29tcHV0ZXJfSUQiOiIifX0.M-bOcQzfFkwML0GAHfE8dtAWSYdNp6ph-8FRFHE7Gl8', '2025-04-13 14:29:46', 0, NULL, '2025-04-06 14:29:46'),
(16, 'Udfadc9635836c459d64f49219d923535', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDUwMzA1NDgsImV4cCI6MTc0NTg5NDU0OCwiZGF0YSI6eyJpZCI6MjksInVzZXJfaWQiOiJVZGZhZGM5NjM1ODM2YzQ1OWQ2NGY0OTIxOWQ5MjM1MzUiLCJkaXNwbGF5X25hbWUiOiJcdTI3MjhcdTBlMmJcdTBlMTlcdTBlMzkhISFcdTI3MjgiLCJlbWFpbCI6IndhX3Rlcl9tYW5fNjJAaG90bWFpbC5jb20iLCJwaWN0dXJlX3VybCI6Imh0dHBzOi8vcHJvZmlsZS5saW5lLXNjZG4ubmV0LzBoSEFPWHZySXdGMjVpSVFSOHJ5WnBFUkp4RkFSQlVFNThTaFFJV0ZCeEcxeFpRbE50VGtjSURWTW1UVnBiUlZCcVJrY0xDbFp4R1FsdU1tQUlmSGZyV21VUlNsOWVGMU01UzBOYWdBIiwic3RhdHVzX21lc3NhZ2UiOiIiLCJhY2Nlc3NfdG9rZW4iOiJleUpoYkdjaU9pSklVekkxTmlKOS5FSFMybklXVXdxRThGbTZLaUl3bnQ0cllUTlFZS2ZLeElNbklqd19zUlBPRUExTkstZ3RQdmVrZm1XODFLVDdhWWZuYTZfZk9JLTczelRJMDl0M0twWUxIakRmbzhpQkFLcEdhdWpGVndueW42alB3dzFCRk1EUGcxbDhzeHBZU1JPZ2tCUDNucWNLaGZETnUxLXpiWFExVUcyM3FNSThMLVVNaVRUSGdmR0EuRWlqV09GSks1cmxVZlhmSmE0RV9WOE1rYnBtajhkTldwVUZISHpQcUJkVSIsInJlZnJlc2hfdG9rZW4iOiJ6Z0FwT25VYzlvZUdOMFB5OVlncyIsInRva2VuX2V4cGlyZXNfYXQiOiIyMDI1LTA1LTA2IDAzOjI4OjUwIiwibmFtZSI6Ilx1MjcyOFx1MGUyYlx1MGUxOVx1MGUzOSEhIVx1MjcyOCIsImFjdGl2ZSI6MSwiaXNfYWRtaW4iOjAsImNyZWF0ZWRfYXQiOiIyMDI1LTA0LTA2IDAzOjI4OjUwIiwidXBkYXRlZF9hdCI6IjIwMjUtMDQtMTkgMDI6MjI6MzEiLCJub3RpZnlfYnkiOiJ0ZWxlZ3JhbSIsInRlbGVncmFtX3Rva2VuX2lkIjoiIiwidGVsZWdyYW1fY2hhdF9pZCI6IjAiLCJtYXhfcHJvZmlsZSI6MTAsIkNvbXB1dGVyX0lEIjoiIiwibGluZV9tZXNzYWdpbmdfdXNlcl9pZCI6IiJ9fQ.cASoiNYKttkL468VSn7Q_hH6RdQLgsxW5XFXg6HMdK4', '2025-04-29 02:42:28', 0, NULL, '2025-04-19 02:42:28'),
(17, 'U4344ce6371339bff46ccabdef0f7496d', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDQ0NDQxNTYsImV4cCI6MTc0NTc0MDE1NiwiZGF0YSI6eyJpZCI6MzAsInVzZXJfaWQiOiJVNDM0NGNlNjM3MTMzOWJmZjQ2Y2NhYmRlZjBmNzQ5NmQiLCJkaXNwbGF5X25hbWUiOiIhIFR1TXoiLCJlbWFpbCI6InZvbHRzX292ZXJsb2FkQGxpdmUuY29tIiwicGljdHVyZV91cmwiOiJodHRwczovL3Byb2ZpbGUubGluZS1zY2RuLm5ldC8waDV1ZHVmQTVZYWxwSkRIa2NCcmdVSlRsY2FUQnFmVE5JTVQwa1BYUUVZR053UDMwS01UZ2hQQ2dLTjI0Z09pc01OV290TlhVTE4ydEZIeDA4VjFxV2JrNDhOMnQxT2k0TllHNG50QSIsInN0YXR1c19tZXNzYWdlIjoiXHUwZTFiXHUwZTIzXHUwZTMxXHUwZTFhXHUwZTQwXHUwZTFiXHUwZTI1XHUwZTM1XHUwZTQ4XHUwZTIyXHUwZTE5IFx1MGU0MFx1MGUyM1x1MGUzNVx1MGUyMlx1MGUxOVx1MGUyM1x1MGUzOVx1MGU0OSBcdTBlMjJcdTBlMzdcdTBlMTRcdTBlMmJcdTBlMjJcdTBlMzhcdTBlNDhcdTBlMTkgXHUwZTFlXHUwZTI1XHUwZTM0XHUwZTAxXHUwZTQxXHUwZTFlXHUwZTI1XHUwZTA3IiwiYWNjZXNzX3Rva2VuIjoiZXlKaGJHY2lPaUpJVXpJMU5pSjkuOGpIV2hZb3pvR2dTZlhpcnZPa0x3ejdSb2JMRk1IVjhmQno1YlB0dXNVcURTemd6bnhYWHMxT1R1bGxZeFhKNXcySVVIbWVJbHFLaldpUVlrNEx5VGhkVzRzUVJsNHF0cnRONUVQaGFxR3h2aDkyOEZxZXBsZk8yUWVuajRoSDVjYTdsUjNhUFRHQU1zVjF2TEFMTzcxbVFlekpWdENiQnFhaXllUEoySmxjLkE2LW03eFJyZi1HU1I4ZWhUOVFlMmR2RUY0eV96NlBadXNPb0NHSEVsZTAiLCJyZWZyZXNoX3Rva2VuIjoiRUZLR2Q3UFFkNlo5anVlcUxzYlciLCJ0b2tlbl9leHBpcmVzX2F0IjoiMjAyNS0wNS0wNiAwNjo0MDowNCIsIm5hbWUiOiIhIFR1TXoiLCJhY3RpdmUiOjEsImlzX2FkbWluIjowLCJjcmVhdGVkX2F0IjoiMjAyNS0wNC0wNiAwNjo0MDowNCIsInVwZGF0ZWRfYXQiOm51bGwsIm5vdGlmeV9ieSI6InRlbGVncmFtIiwidGVsZWdyYW1fdG9rZW5faWQiOiIiLCJ0ZWxlZ3JhbV9jaGF0X2lkIjoiMCIsIm1heF9wcm9maWxlIjoxMCwiQ29tcHV0ZXJfSUQiOiIiLCJsaW5lX21lc3NhZ2luZ191c2VyX2lkIjoiIn19.xNpcrti6mJgjBU1KbO3JLXTkERXFqXeNz4jGKOw5T-8', '2025-04-27 07:49:16', 0, NULL, '2025-04-12 07:49:16'),
(18, 'U1bc6fbdfa565cb53f9d0241ad3bb4726', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDQzMDAyMzgsImV4cCI6MTgzMDYxMzgzOCwiZGF0YSI6eyJpZCI6MTYsInVzZXJfaWQiOiJVMWJjNmZiZGZhNTY1Y2I1M2Y5ZDAyNDFhZDNiYjQ3MjYiLCJkaXNwbGF5X25hbWUiOiJtYXJ3aW4iLCJlbWFpbCI6ImtpdGlqcDMyMjNAZ21haWwuY29tIiwicGljdHVyZV91cmwiOiJodHRwczovL3Byb2ZpbGUubGluZS1zY2RuLm5ldC8waGlQQ1E3SFc5Tm45dUtDSXc0Tk5JQUI1NE5SVk5XVzl0UWtaOUgxOHFhazRBSEhoOEZVcDhFUThvT2s5UUczUXVSazk1VGc4cVBSMWlPMEVaY0g3S1Mya1lhMDVTSG5Jb1IwcDdrUSIsInN0YXR1c19tZXNzYWdlIjoiIiwiYWNjZXNzX3Rva2VuIjoiZXlKaGJHY2lPaUpJVXpJMU5pSjkuVEhTeUhob3NIOEpHa2pPX3hSTDBCZmxkX2dqSWFHcFFxRE1VMTlCbHN5cW5HcERnSDR1ZTZibDg2TEROelZCN2VFaVBUaFo4OWpydmhBZ2VCdjZZelRseEVNQXl2ZkJyLWgxOGpmOU1wSnQ3WEdrb0RjcnM0bDh4a3N0S3Z5aElDaXNlQnBHMVhrTVhYMkw3cmU3U3R4a2Nqd3dmRnFZUzU1VElvRmpSeE1NLnVYOFZxcEI2VmlDREhiV2c1Ri1HdGFpcWE4RVdzMVJlMjI0VC1BTnlsWFEiLCJyZWZyZXNoX3Rva2VuIjoiUExSSGs2V1o5a0daSjExdXBYNXciLCJ0b2tlbl9leHBpcmVzX2F0IjoiMjAyNS0wNS0wNCAxMToxNTowMCIsIm5hbWUiOiJtYXJ3aW4iLCJhY3RpdmUiOjEsImlzX2FkbWluIjoxLCJjcmVhdGVkX2F0IjoiMjAyNS0wNC0wNCAxMToxNTowMCIsInVwZGF0ZWRfYXQiOiIyMDI1LTA0LTEwIDE1OjUwOjIyIiwibm90aWZ5X2J5IjoidGVsZWdyYW0iLCJ0ZWxlZ3JhbV90b2tlbl9pZCI6Ijc5MzMwMDU4ODE6QUFHSHpscmZhZlAwSmtINXJ2MmJ4RWtpLVExbkVLVkp3MlUiLCJ0ZWxlZ3JhbV9jaGF0X2lkIjoiNjU3OTA0NzYxMSIsIm1heF9wcm9maWxlIjo5OTksIkNvbXB1dGVyX0lEIjoiIiwibGluZV9tZXNzYWdpbmdfdXNlcl9pZCI6IiJ9fQ.52p5BeaGR6DR1hhH7iSWF37GXiXWu0BqO3hJqBXwyq0', '2028-01-04 15:50:38', 0, NULL, '2025-04-10 15:50:38'),
(19, 'Ua524bd62ca3131fc78edc26a1826144a', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDUxMjIxNTEsImV4cCI6MTc0NTk4NjE1MSwiZGF0YSI6eyJpZCI6MzQsInVzZXJfaWQiOiJVYTUyNGJkNjJjYTMxMzFmYzc4ZWRjMjZhMTgyNjE0NGEiLCJkaXNwbGF5X25hbWUiOiJCSUcgQm9zcyAyIiwiZW1haWwiOiJzdXR0YXZhdC0yQGhvdG1haWwuY29tIiwicGljdHVyZV91cmwiOiIiLCJzdGF0dXNfbWVzc2FnZSI6IiIsImFjY2Vzc190b2tlbiI6ImV5SmhiR2NpT2lKSVV6STFOaUo5Li1zOVlreVVwUUEwMFA5ZnJFX0FJSU9sVjZFY2gwTXo0RHB3eFlFN1IzWHhKQTZwSDFQajM5RTFoT29EWFA3dGVBcUw0LUJiRDUtQnA5SEl3cVozR2lCMzMwYi1jblRBZllaNmxucndvNjU5UlBSMWFBOWw2QU04czU0Vi1DMkc1aXRGZG1rVUlvM0xVN1FQZ1E2LVZlRGRwUEM3WWVhRzdwTFVHOUtZS0IxYy5rZE41bUg4ekVaN0ozd3RfYjlWQVNrNXlXR0tNYkxVT0VDNzdlNWVrSXFBIiwicmVmcmVzaF90b2tlbiI6ImE4dUFtZ09KVXV5VWtXMlNWemw3IiwidG9rZW5fZXhwaXJlc19hdCI6IjAwMDAtMDAtMDAgMDA6MDA6MDAiLCJuYW1lIjoiQklHIEJvc3MgMiIsImFjdGl2ZSI6MSwiaXNfYWRtaW4iOjAsImNyZWF0ZWRfYXQiOiIyMDI1LTA0LTIwIDA0OjA2OjE2IiwidXBkYXRlZF9hdCI6IjIwMjUtMDQtMjAgMDQ6MDk6MDQiLCJub3RpZnlfYnkiOiJ0ZWxlZ3JhbSIsInRlbGVncmFtX3Rva2VuX2lkIjoiNzcwNTUzOTQzNTpBQUV6THNrU0s0Sk1FeGE3b2xPdGtvUC1WTmNZa0tMTzZDRSIsInRlbGVncmFtX2NoYXRfaWQiOiItMTAwMjM3Njg0MDQ0NyIsIm1heF9wcm9maWxlIjoxMCwiQ29tcHV0ZXJfSUQiOiIiLCJsaW5lX21lc3NhZ2luZ191c2VyX2lkIjoiIn19.zHtfvNPmLoITHl2P05BuT4db8oh9J2D0z-j53fckwRA', '2025-04-30 04:09:11', 0, NULL, '2025-04-20 04:09:11'),
(0, 'Uea66fcbd258ee222b18a995c5bdf02a3', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NDUxNDY2MzMsImV4cCI6MTgwMTIyMDIzMywiZGF0YSI6eyJpZCI6MSwidXNlcl9pZCI6IlVlYTY2ZmNiZDI1OGVlMjIyYjE4YTk5NWM1YmRmMDJhMyIsImRpc3BsYXlfbmFtZSI6IiIsImVtYWlsIjoiYm94c2Fub29rMUBnbWFpbC5jb20iLCJwaWN0dXJlX3VybCI6IiIsInN0YXR1c19tZXNzYWdlIjoiIiwiYWNjZXNzX3Rva2VuIjoiIiwicmVmcmVzaF90b2tlbiI6IiIsInRva2VuX2V4cGlyZXNfYXQiOiIwMDAwLTAwLTAwIDAwOjAwOjAwIiwibmFtZSI6Ik1lLkJveHMiLCJhY3RpdmUiOjEsImlzX2FkbWluIjoxLCJjcmVhdGVkX2F0IjoiMjAyNS0wMy0wNyAwNjo1NDoxMyIsInVwZGF0ZWRfYXQiOiIyMDI1LTA0LTIwIDA2OjQyOjAzIiwibm90aWZ5X2J5IjoidGVsZWdyYW0iLCJ0ZWxlZ3JhbV90b2tlbl9pZCI6Ijc5MzMwMDU4ODE6QUFHSHpscmZhZlAwSmtINXJ2MmJ4RWtpLVExbkVLVkp3MlUiLCJ0ZWxlZ3JhbV9jaGF0X2lkIjoiNzI4NTQ4MTUzMiIsIm1heF9wcm9maWxlIjo5OTksIkNvbXB1dGVyX0lEIjoiMTc4QkZCRkYwMEE1MEYwMC1FODIzOEZBNkJGNTMwMDAxMDAxQjQ0NEE0NjBFMUU2RC1GQzM0OTcwMjUzNTAtRUMyRTk4NjE3RUI5LTAwMTU1RDY5MEQxOCIsImxpbmVfbWVzc2FnaW5nX3VzZXJfaWQiOiIiLCJhZmZpbGlhdGUiOjAsImFmZmlsaWF0ZV9jb2RlIjoiNjlCRzdTTzciLCJyZWZlcnJhbF9ieSI6IjVSUFpUVVFWIiwiYmFsYW5jZSI6MH19.JiK4pVwqif2-AQgCnZ-Rv1HoNmeJEAhLcwLmuUIpK2A', '2027-01-29 12:32:00', 0, NULL, '2025-04-20 10:32:05');

-- --------------------------------------------------------

--
-- Table structure for table `token_packages`
--

CREATE TABLE `token_packages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `tokens` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `bonus_tokens` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `token_packages`
--

INSERT INTO `token_packages` (`id`, `name`, `tokens`, `price`, `bonus_tokens`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Starter Pack', 100, 299.00, 0, 'Perfect for beginners', 'active', '2025-04-20 07:13:20', '2025-04-20 07:13:20'),
(2, 'Popular Pack', 500, 1299.00, 50, 'Most popular choice', 'active', '2025-04-20 07:13:20', '2025-04-20 07:13:20'),
(3, 'Pro Pack', 1000, 2499.00, 150, 'Best value for professionals', 'active', '2025-04-20 07:13:20', '2025-04-20 07:13:20'),
(4, 'Ultimate Pack', 2500, 5999.00, 500, 'Maximum value for power users', 'active', '2025-04-20 07:13:20', '2025-04-20 07:13:20');

-- --------------------------------------------------------

--
-- Table structure for table `token_purchases`
--

CREATE TABLE `token_purchases` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tokens` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded','Canceled') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `referral_code` varchar(8) DEFAULT NULL,
  `commission_paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `token_purchases`
--

INSERT INTO `token_purchases` (`id`, `user_id`, `package_id`, `amount`, `tokens`, `payment_method`, `payment_status`, `transaction_id`, `referral_code`, `commission_paid`, `created_at`, `updated_at`) VALUES
(1, 'Udfe24051a6efd4b9ab7cf9e7a1f293d8', 4, 5999.00, 2500, 'bank_transfer', 'completed', NULL, NULL, 0, '2025-04-20 11:55:17', '2025-04-20 12:03:02'),
(2, 'Udfe24051a6efd4b9ab7cf9e7a1f293d8', 1, 299.00, 100, 'bank_transfer', 'Canceled', NULL, NULL, 0, '2025-04-20 11:55:39', '2025-04-20 12:09:45'),
(3, 'Udfe24051a6efd4b9ab7cf9e7a1f293d8', 1, 299.00, 100, 'bank_transfer', 'completed', NULL, NULL, 0, '2025-04-20 12:10:46', '2025-04-20 12:10:51');

-- --------------------------------------------------------

--
-- Stand-in structure for view `token_purchase_summary`
-- (See below for the actual view)
--
CREATE TABLE `token_purchase_summary` (
`buyer_name` varchar(255)
,`buyer_email` varchar(255)
,`amount` decimal(10,2)
,`tokens` int(11)
,`payment_method` varchar(50)
,`payment_status` enum('pending','completed','failed','refunded','Canceled')
,`purchase_date` timestamp
,`affiliate_name` varchar(255)
,`affiliate_email` varchar(255)
,`referral_code` varchar(8)
,`commission_paid` tinyint(1)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `picture_url` text DEFAULT NULL,
  `status_message` text DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `notify_by` enum('line','telegram') DEFAULT NULL,
  `telegram_token_id` varchar(255) DEFAULT NULL,
  `telegram_chat_id` varchar(255) DEFAULT NULL,
  `max_profile` int(10) DEFAULT NULL,
  `Computer_ID` varchar(500) NOT NULL,
  `line_messaging_user_id` varchar(500) NOT NULL,
  `affiliate` tinyint(1) DEFAULT 0,
  `affiliate_code` varchar(8) DEFAULT NULL,
  `referral_by` varchar(8) DEFAULT NULL,
  `balance` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `display_name`, `email`, `picture_url`, `status_message`, `access_token`, `refresh_token`, `token_expires_at`, `name`, `active`, `is_admin`, `created_at`, `updated_at`, `notify_by`, `telegram_token_id`, `telegram_chat_id`, `max_profile`, `Computer_ID`, `line_messaging_user_id`, `affiliate`, `affiliate_code`, `referral_by`, `balance`) VALUES
(1, 'Uea66fcbd258ee222b18a995c5bdf02a3', '', 'boxsanook1@gmail.com', '', '', '', '', '0000-00-00 00:00:00', 'Me.Boxs', 1, 1, '2025-03-07 06:54:13', '2025-04-20 06:42:03', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '7285481532', 999, '178BFBFF00A50F00-E8238FA6BF530001001B444A460E1E6D-FC3497025350-EC2E98617EB9-00155D690D18', '', 0, '69BG7SO7', '5RPZTUQV', 0),
(2, 'U7ef1073b19d44141c72201193151d39c', '', 'bossclub541-123@gmail.com', '', '', '', '', '0000-00-00 00:00:00', 'Nattapong.K', 1, 0, '2025-03-10 01:27:18', '2025-04-20 05:31:47', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '7521939385', 50, '178BFBFF00A70F52-WX31AC7FK095-282E89E11BB7', '', 0, 'VSYIH74V', '5RPZTUQV', 0),
(3, 'Uec8d93c4e01f5194dd7a3501be0818f4', NULL, 'anirud@email.com', NULL, NULL, NULL, NULL, NULL, 'anirud', 1, 1, '2025-03-10 03:49:57', '2025-04-04 14:10:40', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '6579047611', 999, '078BFBFD00060FB1-247DB89DF7F2', '', 0, NULL, NULL, 0),
(5, 'U7f17afa4be019f96b4961257aae7fc13', '', 'waterman6262-1@gmail.com', '', '', '', '', '0000-00-00 00:00:00', 'K.waterman', 1, 0, NULL, '2025-04-09 02:06:37', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '6127179968', 10, 'BFEBFBFF000B06F2-E8238FA6BF530001001B448B4D82AAD1-CC28AA83EA2F-00155D057ED4', '', 0, NULL, NULL, 0),
(6, 'Uea66fcbd258ee222b18a995c5bdf02a3_2', '', 'boxsanook2@gmail.com', '', '', '', '', '0000-00-00 00:00:00', 'Me.Boxs', 1, 1, '2025-03-07 06:54:13', '2025-04-10 03:37:06', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '7285481532', 999, '00660F01178BFBFF-ZN13V6AB-B883034BF065-00155DBFA518', '', 0, NULL, NULL, 0),
(7, 'U1fbac1f72716265be6d22fa7c1fa6f02', NULL, 'suaheew@gmail.com', NULL, NULL, NULL, NULL, NULL, 'suaheew', 1, 0, '2025-04-01 02:31:20', '2025-04-01 12:56:50', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '453375430', 10, 'BFEBFBFF000306A9-20496X802462-50AF73255140', '', 0, '8A7E347D', NULL, 0),
(9, 'U2f09473869e84ae4b813c1b0b8d4b4bd', '', 'trintha@gmail.com', '', '', '', '', '0000-00-00 00:00:00', 'Tawat', 1, 0, '2025-04-02 06:19:44', '2025-04-07 07:03:45', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '2105838129', 10, 'BFEBFBFF000B06F2-F57021011401202-CC28AA83EA2F', '', 0, NULL, NULL, 0),
(10, 'Uec8d93c4e01f5194dd7a3501be0818f4_1', '', 'anirud@gmail.com', '', '', '', '', '0000-00-00 00:00:00', 'anirud', 1, 1, '2025-04-02 12:17:19', '2025-04-04 06:34:12', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '6579047611', 999, '078BFBFD00060FB1-F84B63BA7313', '', 0, NULL, NULL, 0),
(13, 'Udfe24051a6efd4b9ab7cf9e7a1f293d8', 'BoxS', 'boxsanook@hotmail.com', 'https://profile.line-scdn.net/0hwGg6qYtsKGhKTwBYBAtWFzofKwJpPnF6MntjWy1GdV8kfGtrZH0yCXdGd1sle2g2NXxkXi9GdQ9GXF8OVBnUXE1_dVl2eWw_Yy1lhg', 'Freedoms', 'eyJhbGciOiJIUzI1NiJ9.qg_1hL9kUuFlUmE1Wl8adZm6dGJ8IY93FB5ResXb3e2cCOIsjTebqRHAo8vu29znqaaP2MpO928S5hajJIPNe7-4ToZyDjliGugph1kD9yW0IQ0rftb8IZxFdMskOnM4njUVf3HDvzqpDDDBihyl98bq7Rz1720nQnWP5E9dvkk.o9UmhXATMi-PkC-dJ9HanS3Dnd8nTSM33IBduEengBg', 'IQHrF7DB4meTSIzRzK3i', '0000-00-00 00:00:00', 'BoxS', 1, 1, '2025-04-04 07:01:31', '2025-04-20 08:25:47', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '7285481532', 10, '', '', 1, '5RPZTUQV', NULL, 2600),
(16, 'U1bc6fbdfa565cb53f9d0241ad3bb4726', 'marwin', 'kitijp3223@gmail.com', 'https://profile.line-scdn.net/0hiPCQ7HW9Nn9uKCIw4NNIAB54NRVNWW9tQkZ9H18qak4AHHh8FUp8EQ8oOk9QG3QuRk95Tg8qPR1iO0EZcH7KS2kYa05SHnIoR0p7kQ', '', 'eyJhbGciOiJIUzI1NiJ9.THSyHhosH8JGkjO_xRL0Bfld_gjIaGpQqDMU19BlsyqnGpDgH4ue6bl86LDNzVB7eEiPThZ89jrvhAgeBv6YzTlxEMAyvfBr-h18jf9MpJt7XGkoDcrs4l8xkstKvyhICiseBpG1XkMXX2L7re7StxkcjwwfFqYS55TIoFjRxMM.uX8VqpB6ViCDHbWg5F-Gtaiqa8EWs1Re224T-ANylXQ', 'PLRHk6WZ9kGZJ11upX5w', '2025-05-04 11:15:00', 'marwin', 1, 1, '2025-04-04 11:15:00', '2025-04-10 15:50:58', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '6579047611', 999, '078BFBFD00060FB1-BC241148A9E6', '', 0, NULL, NULL, 0),
(17, 'Udc17d452a81808d9329ad6902c1b6465', '🪸BIG BoSS🐚', 'suttavat@hotmail.com', 'https://profile.line-scdn.net/0hBEeEiCGSHWpIPA44o-BjFThsHgBrTUR4Mw0GDHk8QQkiBFg6ZwhVXyhoRlsmX1g1N1NXXHk4QwlEL2oMVmrhXk8MQFt0Clk9YV5QhA', '', 'eyJhbGciOiJIUzI1NiJ9.MIttmMeRj9wi3lv_amXnAJvs__oipz0rG2WCP_jfmjLaiey5uq6fQxrsecN3cL2skWZqfmhcwdnq0QfMbCkQZQUaRzhCNtDyR9pRWixpA_vOZc0UpzJnAnoX5uOEoWMAavVxbE2DZJHmRf7YOOxYN828HCQi123T6V_ifVb-kpY.YUGx3poyAxbNwdXouq3q7n9FXPihJGQbxo7HFdnkk4A', 'VdcpS81oCAKo8rDGIM9I', '2025-05-04 12:54:00', '🪸BIG BoSS🐚', 1, 0, '2025-04-04 12:54:33', '2025-04-19 15:25:47', 'telegram', '7705539435:AAEzLskSK4JMExa7olOtkoP-VNcYkKLO6CE', '-1002376840447', 20, 'BFEBFBFF000406F1-WX22D80H2D8V-B0A7B9C0B905', '', 1, 'A81A2FCA', NULL, 0),
(27, 'U690ee81add1c9defa8a386a251dd09f9', 'ยังไม่ได้ตั้งค่า', 'boxsanook8@gmail.com', 'https://profile.line-scdn.net/0h52T5Xf6Vah15THlJxlgUYgkcaXdaPTMPVn53fRkZZypGenlKAi91eUQbNX4Wf39IAS0jfhkbY391Xx17ZxqWKX58NyxFei5KUC4n8w', 'เพื่อน  999+', 'eyJhbGciOiJIUzI1NiJ9.394txnlccDKpH93S-2Pm51-4DGbf4LIPZP0aGiNJceeSHl8kbGSesQNeb2fCUnQGhAtaFayvirZb6yoxa7iny58OpBRiEVS6jmqL2frTTUDgmcwu-BOwBrPj9s9pSaRaHAxXDpIQWca39wLceJRzq37MoGWS2wx_fS-usUnwfLE.Df1kajx43sVzUZx07PNklup09v08WKDmTymYYneEvvA', 'txzPkUVe54808ZNPX8hL', '2025-05-05 07:34:46', 'ยังไม่ได้ตั้งค่า', 1, 0, '2025-04-05 07:34:46', '2025-04-20 11:37:30', 'telegram', '', '0', 10, '', '', 0, NULL, NULL, 0),
(29, 'Udfadc9635836c459d64f49219d923535', '✨หนู!!!✨', 'wa_ter_man_62@hotmail.com', 'https://profile.line-scdn.net/0hHAOXvrIwF25iIQR8ryZpERJxFARBUE58ShQIWFBxG1xZQlNtTkcIDVMmTVpbRVBqRkcLClZxGQluMmAIfHfrWmURSl9eF1M5S0NagA', '', 'eyJhbGciOiJIUzI1NiJ9.EHS2nIWUwqE8Fm6KiIwnt4rYTNQYKfKxIMnIjw_sRPOEA1NK-gtPvekfmW81KT7aYfna6_fOI-73zTI09t3KpYLHjDfo8iBAKpGaujFVwnyn6jPww1BFMDPg1l8sxpYSROgkBP3nqcKhfDNu1-zbXQ1UG23qMI8L-UMiTTHgfGA.EijWOFJK5rlUfXfJa4E_V8Mkbpmj8dNWpUFHHzPqBdU', 'zgApOnUc9oeGN0Py9Ygs', '2025-05-06 03:28:50', '✨หนู!!!✨', 1, 0, '2025-04-06 03:28:50', '2025-04-19 02:46:42', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '6127179968', 10, 'BFEBFBFF000B06F2-E8238FA6BF530001001B448B4D82AAD1-CC28AA83EA2F', '', 0, NULL, NULL, 0),
(30, 'U4344ce6371339bff46ccabdef0f7496d', '! TuMz', 'volts_overload@live.com', 'https://profile.line-scdn.net/0h5udufA5YalpJDHkcBrgUJTlcaTBqfTNIMT0kPXQEYGNwP30KMTghPCgKN24gOisMNWotNXULN2tFHx08V1qWbk48N2t1Oi4NYG4ntA', 'ปรับเปลี่ยน เรียนรู้ ยืดหยุ่น พลิกแพลง', 'eyJhbGciOiJIUzI1NiJ9.8jHWhYozoGgSfXirvOkLwz7RobLFMHV8fBz5bPtusUqDSzgznxXXs1OTullYxXJ5w2IUHmeIlqKjWiQYk4LyThdW4sQRl4qtrtN5EPhaqGxvh928FqeplfO2Qenj4hH5ca7lR3aPTGAMsV1vLALO71mQezJVtCbBqaiyePJ2Jlc.A6-m7xRrf-GSR8ehT9Qe2dvEF4y_z6PZusOoCGHEle0', 'EFKGd7PQd6Z9jueqLsbW', '2025-05-06 06:40:04', '! TuMz', 1, 0, '2025-04-06 06:40:04', '2025-04-17 01:34:49', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '6008230289', 10, 'BFEBFBFF000506E3-30149665632-F0D5BF82BEA5', '', 0, NULL, NULL, 0),
(31, 'Ud6365725ee5a00b6e73444a0a79d1ab0', 'I\'m Ton ♾ 2469', 'teen_rsu@hotmail.com', 'https://profile.line-scdn.net/0hO7i_UTRXEBhEJjkPJv5uZzR2E3JnV0kKOhdWfyUhGSF5HlBLb0cPdnMgGS56RFVOOBRXdndxSy5INWd-WnDsLEMWTSl4EFRPbURd9g', 'Alone', 'eyJhbGciOiJIUzI1NiJ9.eahS4j0txxTvAp8Ffmsm7M_cCb_EtyCOxTfycPMPLEPC4PzKMhq43o4F1e5uyikhBPJFboEVuOo5UgqefyJ7RLms88vH_3pGxinPjqW57ITVrBVWDlsR2PT-vMXjTJTJ6Zs6mbU64L1ab1G7sOcg7BTeuU76w8Gc3J-OtcEslsc.H0tODrC5tOncXqJ8YaJOtvcxLbfaHT6j55owmIhVgPw', 'NI4fPLSa7MkhdEt94rGh', '2025-05-06 14:28:39', 'I\'m Ton ♾ 2469', 1, 0, '2025-04-06 14:28:39', '2025-04-06 14:29:53', 'telegram', '7933005881:AAGHzlrfafP0JkH5rv2bxEki-Q1nEKVJw2U', '7177823073', 10, 'BFEBFBFF00090672-00000000000000000026B7382C8C2345-D843AE5271D3', '', 0, NULL, NULL, 0),
(34, 'Ua524bd62ca3131fc78edc26a1826144a', 'BIG Boss 2', 'suttavat-2@hotmail.com', '', '', 'eyJhbGciOiJIUzI1NiJ9.-s9YkyUpQA00P9frE_AIIOlV6Ech0Mz4DpwxYE7R3XxJA6pH1Pj39E1hOoDXP7teAqL4-BbD5-Bp9HIwqZ3GiB330b-cnTAfYZ6lnrwo659RPR1aA9l6AM8s54V-C2G5itFdmkUIo3LU7QPgQ6-VeDdpPC7YeaG7pLUG9KYKB1c.kdN5mH8zEZ7J3wt_b9VASk5yWGKMbLUOEC77e5ekIqA', 'a8uAmgOJUuyUkW2SVzl7', '0000-00-00 00:00:00', 'BIG Boss 2', 1, 0, '2025-04-20 04:06:16', '2025-04-20 04:09:24', 'telegram', '7705539435:AAEzLskSK4JMExa7olOtkoP-VNcYkKLO6CE', '-1002376840447', 10, 'BFEBFBFF000A0660-MP38B90800269-B0A460777167', '', 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_registration_view`
-- (See below for the actual view)
--
CREATE TABLE `user_registration_view` (
`id` int(11)
,`user_id` varchar(255)
,`display_name` varchar(255)
,`email` varchar(255)
,`picture_url` text
,`status_message` text
,`access_token` text
,`refresh_token` text
,`token_expires_at` datetime
,`name` varchar(255)
,`active` tinyint(1)
,`is_admin` tinyint(1)
,`created_at` timestamp
,`updated_at` datetime
,`notify_by` enum('line','telegram')
,`telegram_token_id` varchar(255)
,`telegram_chat_id` varchar(255)
,`max_profile` int(10)
,`Computer_ID` varchar(500)
,`registration_token_id` int(11)
,`token` varchar(4000)
,`expires_at` datetime
,`used` tinyint(1)
,`used_at` datetime
,`registration_created_at` datetime
);

-- --------------------------------------------------------

--
-- Structure for view `token_purchase_summary`
--
DROP TABLE IF EXISTS `token_purchase_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u666915587_line_02`@`127.0.0.1` SQL SECURITY DEFINER VIEW `token_purchase_summary`  AS SELECT `u`.`name` AS `buyer_name`, `u`.`email` AS `buyer_email`, `tp`.`amount` AS `amount`, `tp`.`tokens` AS `tokens`, `tp`.`payment_method` AS `payment_method`, `tp`.`payment_status` AS `payment_status`, `tp`.`created_at` AS `purchase_date`, `a`.`name` AS `affiliate_name`, `a`.`email` AS `affiliate_email`, `tp`.`referral_code` AS `referral_code`, `tp`.`commission_paid` AS `commission_paid` FROM ((`token_purchases` `tp` left join `users` `u` on(`tp`.`user_id` = `u`.`user_id`)) left join `users` `a` on(`tp`.`referral_code` = `a`.`affiliate_code`)) WHERE `tp`.`payment_status` = 'completed' ;

-- --------------------------------------------------------

--
-- Structure for view `user_registration_view`
--
DROP TABLE IF EXISTS `user_registration_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u666915587_line_02`@`127.0.0.1` SQL SECURITY DEFINER VIEW `user_registration_view`  AS SELECT `u`.`id` AS `id`, `u`.`user_id` AS `user_id`, `u`.`display_name` AS `display_name`, `u`.`email` AS `email`, `u`.`picture_url` AS `picture_url`, `u`.`status_message` AS `status_message`, `u`.`access_token` AS `access_token`, `u`.`refresh_token` AS `refresh_token`, `u`.`token_expires_at` AS `token_expires_at`, `u`.`name` AS `name`, `u`.`active` AS `active`, `u`.`is_admin` AS `is_admin`, `u`.`created_at` AS `created_at`, `u`.`updated_at` AS `updated_at`, `u`.`notify_by` AS `notify_by`, `u`.`telegram_token_id` AS `telegram_token_id`, `u`.`telegram_chat_id` AS `telegram_chat_id`, `u`.`max_profile` AS `max_profile`, `u`.`Computer_ID` AS `Computer_ID`, `r`.`id` AS `registration_token_id`, `r`.`token` AS `token`, `r`.`expires_at` AS `expires_at`, `r`.`used` AS `used`, `r`.`used_at` AS `used_at`, `r`.`created_at` AS `registration_created_at` FROM (`users` `u` left join `registration_tokens` `r` on(`u`.`user_id` = `r`.`user_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `affiliates`
--
ALTER TABLE `affiliates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_affiliate_code` (`code`),
  ADD KEY `idx_affiliate_user` (`user_id`),
  ADD KEY `idx_affiliate_status` (`status`),
  ADD KEY `idx_affiliate_created` (`created_at`);

--
-- Indexes for table `affiliate_payouts`
--
ALTER TABLE `affiliate_payouts`
  ADD KEY `fk_affiliate_payouts` (`affiliate_id`),
  ADD KEY `idx_payout_status` (`status`),
  ADD KEY `idx_payout_created` (`created_at`);

--
-- Indexes for table `affiliate_transactions`
--
ALTER TABLE `affiliate_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_affiliate_id` (`affiliate_id`),
  ADD KEY `idx_referral_id` (`referral_id`),
  ADD KEY `idx_transaction_status` (`status`),
  ADD KEY `idx_transaction_created` (`created_at`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `token_packages`
--
ALTER TABLE `token_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `token_purchases`
--
ALTER TABLE `token_purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_referral_code` (`referral_code`),
  ADD KEY `fk_token_purchases_package` (`package_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_referral_by` (`referral_by`),
  ADD KEY `idx_affiliate_code` (`affiliate_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `affiliates`
--
ALTER TABLE `affiliates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `affiliate_transactions`
--
ALTER TABLE `affiliate_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `token_packages`
--
ALTER TABLE `token_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `token_purchases`
--
ALTER TABLE `token_purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `affiliate_transactions`
--
ALTER TABLE `affiliate_transactions`
  ADD CONSTRAINT `fk_affiliate_transactions_referral` FOREIGN KEY (`referral_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `token_purchases`
--
ALTER TABLE `token_purchases`
  ADD CONSTRAINT `fk_token_purchases_package` FOREIGN KEY (`package_id`) REFERENCES `token_packages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_token_purchases_referral` FOREIGN KEY (`referral_code`) REFERENCES `users` (`affiliate_code`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_token_purchases_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
