<?php
/**
 * Fee Management — AJAX: Get students for a specific fee's assignments
 */

header('Content-Type: application/json');

$feeId = input_int('fee_id');
if (!$feeId) {
    json_response(['error' => 'Fee ID required'], 400);
}

// Get all students assigned to this fee
$charges = db_fetch_all("
    SELECT sfc.*, s.first_name, s.last_name, s.admission_no, c.name AS class_name,
           COALESCE(SUM(pc.penalty_amount), 0) AS total_penalties
    FROM student_fee_charges sfc
    JOIN students s ON s.id = sfc.student_id
    LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
    LEFT JOIN classes c ON c.id = e.class_id
    LEFT JOIN penalty_charges pc ON pc.charge_id = sfc.id
    WHERE sfc.fee_id = ?
    GROUP BY sfc.id
    ORDER BY s.first_name, s.last_name, sfc.occurrence_number
", [$feeId]);

// Get exemptions
$exemptions = db_fetch_all("
    SELECT fe.*, s.first_name, s.last_name, s.admission_no
    FROM fee_exemptions fe
    JOIN students s ON s.id = fe.student_id
    WHERE fe.fee_id = ?
", [$feeId]);

json_response(['charges' => $charges, 'exemptions' => $exemptions]);
