-- ============================================================
-- Migration 014: Fix duplicate assessments & add unique key
--
-- Root cause: assessments table had no unique constraint on
-- (name, class_id, subject_id, session_id, term_id), so
-- running the seed script multiple times created duplicate rows.
-- Each duplicate caused student_results to be summed multiple
-- times in roster/result queries, producing totals like 600/100.
--
-- This migration:
--   1. Checks the assessments table actually exists
--   2. Finds duplicate assessment groups
--   3. Re-points all student_results to the canonical (lowest) id
--   4. Deletes the orphan duplicate assessments
--   5. Adds UNIQUE KEY to prevent recurrence
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Guard: only proceed if the assessments table exists ───────
SET @assess_exists = (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name   = 'assessments'
);

-- ── Step 1: Dedup — only when table exists ────────────────────
-- Identify canonical row (lowest id) per logical assessment
SET @sql_tmp1 = IF(
    @assess_exists > 0,
    'CREATE TEMPORARY TABLE IF NOT EXISTS _canonical_assess AS
     SELECT MIN(id) AS keep_id, name, class_id, subject_id, session_id, term_id
     FROM assessments
     GROUP BY name, class_id, subject_id, session_id, term_id',
    'SELECT ''assessments table does not exist yet — skipping dedup'' AS info'
);
PREPARE stmt FROM @sql_tmp1; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Build duplicate-id → canonical-id map
SET @sql_tmp2 = IF(
    @assess_exists > 0,
    'CREATE TEMPORARY TABLE IF NOT EXISTS _dupe_map AS
     SELECT a.id AS dupe_id, c.keep_id AS canon_id
     FROM assessments a
     JOIN _canonical_assess c
         ON  a.name       = c.name
         AND a.class_id   = c.class_id
         AND a.subject_id = c.subject_id
         AND a.session_id = c.session_id
         AND (a.term_id   = c.term_id OR (a.term_id IS NULL AND c.term_id IS NULL))
     WHERE a.id <> c.keep_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql_tmp2; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Re-point student_results from duplicate assessment_ids → canonical
-- (skip rows where the canonical already has a result for that student)
SET @sr_exists = (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name   = 'student_results'
);

SET @sql_repoint = IF(
    @assess_exists > 0 AND @sr_exists > 0,
    'UPDATE student_results sr
     JOIN _dupe_map dm ON sr.assessment_id = dm.dupe_id
     LEFT JOIN student_results sr2
         ON sr2.assessment_id = dm.canon_id AND sr2.student_id = sr.student_id
     SET sr.assessment_id = dm.canon_id
     WHERE sr2.id IS NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql_repoint; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Delete orphaned student_results still pointing to duplicate assessments
SET @sql_del_sr = IF(
    @assess_exists > 0 AND @sr_exists > 0,
    'DELETE sr FROM student_results sr
     JOIN _dupe_map dm ON sr.assessment_id = dm.dupe_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql_del_sr; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Delete the duplicate assessment rows
SET @sql_del_a = IF(
    @assess_exists > 0,
    'DELETE a FROM assessments a
     JOIN _dupe_map dm ON a.id = dm.dupe_id',
    'SELECT 1'
);
PREPARE stmt FROM @sql_del_a; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Clean up temp tables (only if they were created)
DROP TEMPORARY TABLE IF EXISTS _dupe_map;
DROP TEMPORARY TABLE IF EXISTS _canonical_assess;

-- ── Step 2: Add unique constraint (idempotent) ────────────────
SET @idx_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name   = 'assessments'
      AND index_name   = 'uk_assessment'
);

SET @sql_uk = IF(
    @assess_exists > 0 AND @idx_exists = 0,
    'ALTER TABLE `assessments` ADD UNIQUE KEY `uk_assessment` (`name`(100), `class_id`, `subject_id`, `session_id`, `term_id`)',
    'SELECT CONCAT(IF(@assess_exists=0, "assessments table not found — run 012_results_module.sql first", "uk_assessment already exists")) AS info'
);
PREPARE stmt FROM @sql_uk; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Summary ───────────────────────────────────────────────────
SELECT
    @assess_exists                                                     AS assessments_table_found,
    IF(@assess_exists > 0,
        (SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'assessments'),
        0)                                                             AS note_run_012_first_if_0,
    IF(@assess_exists > 0 AND @idx_exists = 0, 'ADDED', IF(@idx_exists > 0, 'ALREADY_EXISTS', 'TABLE_MISSING')) AS unique_key_status,
    'Migration 014 complete'                                          AS status;

