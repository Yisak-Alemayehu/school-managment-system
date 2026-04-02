-- Finance — Batch Payment: Add batch_receipt_no to fin_transactions
-- Run this migration on existing databases to add the batch receipt column.

ALTER TABLE `fin_transactions`
    ADD COLUMN `batch_receipt_no` VARCHAR(100) DEFAULT NULL AFTER `receipt_no`;

SELECT 'fin_transactions.batch_receipt_no column added!' AS result;
