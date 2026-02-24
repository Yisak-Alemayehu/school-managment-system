<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=urjiberi_school;charset=utf8mb4','root','0000',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
echo 'Students:    ' . $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn() . PHP_EOL;
echo 'Results:     ' . $pdo->query('SELECT COUNT(*) FROM student_results')->fetchColumn() . PHP_EOL;
echo 'Attend:      ' . $pdo->query('SELECT COUNT(*) FROM attendance')->fetchColumn() . PHP_EOL;
echo 'Assess:      ' . $pdo->query('SELECT COUNT(*) FROM assessments')->fetchColumn() . PHP_EOL;
echo 'Teachers:    ' . $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id=(SELECT id FROM roles WHERE slug='teacher')")->fetchColumn() . PHP_EOL;
echo 'Enrollments: ' . $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn() . PHP_EOL;

// ── Sanity checks ─────────────────────────────────────────────────────────────
echo PHP_EOL . '── Sanity Checks ──' . PHP_EOL;

// 1. Check for duplicate assessments (same name/class/subject/session/term)
$dupes = $pdo->query(
    "SELECT name, class_id, subject_id, session_id, term_id, COUNT(*) AS cnt
     FROM assessments
     GROUP BY name, class_id, subject_id, session_id, term_id
     HAVING cnt > 1"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($dupes)) {
    echo '  ✓ No duplicate assessments found' . PHP_EOL;
} else {
    echo '  ✗ DUPLICATE ASSESSMENTS DETECTED (' . count($dupes) . ' groups):' . PHP_EOL;
    foreach ($dupes as $d) {
        echo "      name={$d['name']} class_id={$d['class_id']} subject_id={$d['subject_id']}"
           . " session_id={$d['session_id']} term_id={$d['term_id']} count={$d['cnt']}" . PHP_EOL;
    }
    echo '  → Run sql/014_fix_assessment_unique.sql to fix.' . PHP_EOL;
}

// 2. Check assessment total_marks sum per subject/term equals 100
$badSums = $pdo->query(
    "SELECT class_id, subject_id, session_id, term_id,
            SUM(total_marks) AS marks_sum, COUNT(*) AS rows_count
     FROM assessments
     GROUP BY class_id, subject_id, session_id, term_id
     HAVING marks_sum <> 100"
)->fetchAll(PDO::FETCH_ASSOC);

if (empty($badSums)) {
    echo '  ✓ All assessment groups sum to 100 marks' . PHP_EOL;
} else {
    echo '  ✗ ASSESSMENT GROUPS WITH WRONG SUM (' . count($badSums) . '):' . PHP_EOL;
    foreach ($badSums as $b) {
        echo "      class_id={$b['class_id']} subject_id={$b['subject_id']}"
           . " term_id={$b['term_id']} sum={$b['marks_sum']} rows={$b['rows_count']}" . PHP_EOL;
    }
}

// 3. Check unique key exists on assessments
$ukExists = $pdo->query(
    "SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema=DATABASE() AND table_name='assessments' AND index_name='uk_assessment'"
)->fetchColumn();
echo '  ' . ($ukExists ? '✓' : '✗') . ' assessments.uk_assessment unique key '
   . ($ukExists ? 'exists' : 'MISSING — run 014_fix_assessment_unique.sql') . PHP_EOL;
