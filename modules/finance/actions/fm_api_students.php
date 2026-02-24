<?php
/**
 * Fee Management — AJAX: Search students
 * Returns JSON for student search in assignment & group forms
 */

header('Content-Type: application/json');

$q       = trim($_GET['q'] ?? '');
$classId = input_int('class_id');
$limit   = min(50, max(10, input_int('limit') ?: 20));

$sessionId = db_fetch_value("SELECT id FROM academic_sessions WHERE is_active = 1 LIMIT 1");

$where  = ["s.status = 'active'", "s.deleted_at IS NULL"];
$params = [];

if ($q) {
    $where[]  = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ?)";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
    $params[] = "%{$q}%";
}

if ($classId) {
    $where[]  = "e.class_id = ?";
    $params[] = $classId;
}

$whereStr = implode(' AND ', $where);

$students = db_fetch_all("
    SELECT s.id, s.first_name, s.last_name, s.admission_no, s.gender, 
           c.name AS class_name, sec.name AS section_name
    FROM students s
    LEFT JOIN enrollments e ON e.student_id = s.id AND e.session_id = ? AND e.status = 'active'
    LEFT JOIN classes c ON c.id = e.class_id
    LEFT JOIN sections sec ON sec.id = e.section_id
    WHERE {$whereStr}
    ORDER BY s.first_name, s.last_name
    LIMIT {$limit}
", array_merge([$sessionId], $params));

json_response(['students' => $students]);
