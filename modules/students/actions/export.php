<?php
/**
 * Students — CSV Export
 */

$classFilter   = input_int('class_id');
$sectionFilter = input_int('section_id');

$where  = ["s.deleted_at IS NULL", "s.status = 'active'"];
$params = [];

if ($classFilter) {
    $where[]  = "c.id = ?";
    $params[] = $classFilter;
}
if ($sectionFilter) {
    $where[]  = "sec.id = ?";
    $params[] = $sectionFilter;
}

$whereClause = implode(' AND ', $where);

$students = db_fetch_all(
    "SELECT s.admission_no, s.full_name, s.gender, s.date_of_birth, s.phone, s.email,
            s.address, s.status, c.name as class_name, sec.name as section_name
     FROM students s
     LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
     LEFT JOIN sections sec ON e.section_id = sec.id
     LEFT JOIN classes c ON sec.class_id = c.id
     WHERE $whereClause
     ORDER BY s.full_name",
    $params
);

$filename = 'students_export_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Admission No', 'Full Name', 'Gender', 'Date of Birth', 'Phone', 'Email', 'Address', 'Class', 'Section', 'Status']);

foreach ($students as $s) {
    fputcsv($output, [
        $s['admission_no'],
        $s['full_name'],
        ucfirst($s['gender']),
        $s['date_of_birth'],
        $s['phone'],
        $s['email'],
        $s['address'],
        $s['class_name'],
        $s['section_name'],
        ucfirst($s['status']),
    ]);
}

fclose($output);
exit;
