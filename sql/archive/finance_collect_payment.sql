-- ============================================================
-- Finance — Collect Payment: Add print_count to fin_transactions
-- Run AFTER finance.sql
-- ============================================================

SET NAMES utf8mb4;

-- Track how many times a payment attachment has been printed
ALTER TABLE `fin_transactions`
    ADD COLUMN `print_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `notes`;

SELECT 'fin_transactions.print_count column added!' AS result;
