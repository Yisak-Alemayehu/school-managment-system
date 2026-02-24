<?php
/**
 * Fee Management — Export Report (CSV)
 */

$reportType = $_GET['type'] ?? 'outstanding';
$dateFrom   = $_GET['date_from'] ?? date('Y-01-01');
$dateTo     = $_GET['date_to'] ?? date('Y-m-d');

$filename = "fee_report_{$reportType}_" . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

$output = fopen('php://output', 'w');

switch ($reportType) {
    case 'outstanding':
        fputcsv($output, ['Student', 'Admission No', 'Class', 'Fee', 'Amount', 'Due Date', 'Status', 'Penalties']);
        $rows = db_fetch_all("
            SELECT s.first_name, s.last_name, s.admission_no, c.name AS class_name,
                   f.description AS fee_name, sfc.amount, sfc.due_date, sfc.status,
                   COALESCE(SUM(pc.penalty_amount), 0) AS total_penalties
            FROM student_fee_charges sfc
            JOIN students s ON s.id = sfc.student_id
            JOIN fees f ON f.id = sfc.fee_id
            LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
            LEFT JOIN classes c ON c.id = e.class_id
            LEFT JOIN penalty_charges pc ON pc.charge_id = sfc.id
            WHERE sfc.status IN ('pending','overdue')
            AND sfc.due_date BETWEEN ? AND ?
            GROUP BY sfc.id
            ORDER BY sfc.due_date ASC
        ", [$dateFrom, $dateTo]);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['first_name'] . ' ' . $row['last_name'],
                $row['admission_no'],
                $row['class_name'] ?? 'N/A',
                $row['fee_name'],
                number_format($row['amount'], 2),
                $row['due_date'],
                strtoupper($row['status']),
                number_format($row['total_penalties'], 2),
            ]);
        }
        break;

    case 'penalties':
        fputcsv($output, ['Student', 'Fee', 'Charge Amount', 'Penalty Amount', 'Applied At', 'Reason']);
        $rows = db_fetch_all("
            SELECT s.first_name, s.last_name, f.description AS fee_name,
                   sfc.amount AS charge_amount, pc.penalty_amount,
                   pc.applied_at, pc.reason
            FROM penalty_charges pc
            JOIN student_fee_charges sfc ON sfc.id = pc.charge_id
            JOIN students s ON s.id = sfc.student_id
            JOIN fees f ON f.id = sfc.fee_id
            WHERE pc.applied_at BETWEEN ? AND ?
            ORDER BY pc.applied_at DESC
        ", [$dateFrom, $dateTo . ' 23:59:59']);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['first_name'] . ' ' . $row['last_name'],
                $row['fee_name'],
                number_format($row['charge_amount'], 2),
                number_format($row['penalty_amount'], 2),
                $row['applied_at'],
                $row['reason'],
            ]);
        }
        break;

    case 'revenue':
        fputcsv($output, ['Fee', 'Expected', 'Collected', 'Waived', 'Outstanding', 'Collection Rate']);
        $rows = db_fetch_all("
            SELECT f.description AS fee_name,
                   SUM(sfc.amount) AS expected,
                   SUM(CASE WHEN sfc.status = 'paid' THEN sfc.paid_amount ELSE 0 END) AS collected,
                   SUM(CASE WHEN sfc.status = 'waived' THEN sfc.amount ELSE 0 END) AS waived,
                   SUM(CASE WHEN sfc.status IN ('pending','overdue') THEN sfc.amount ELSE 0 END) AS outstanding
            FROM student_fee_charges sfc
            JOIN fees f ON f.id = sfc.fee_id
            WHERE sfc.created_at BETWEEN ? AND ?
            GROUP BY f.id
            ORDER BY f.description
        ", [$dateFrom, $dateTo . ' 23:59:59']);
        foreach ($rows as $row) {
            $rate = $row['expected'] > 0 ? round(($row['collected'] / $row['expected']) * 100, 1) : 0;
            fputcsv($output, [
                $row['fee_name'],
                number_format($row['expected'], 2),
                number_format($row['collected'], 2),
                number_format($row['waived'], 2),
                number_format($row['outstanding'], 2),
                $rate . '%',
            ]);
        }
        break;

    case 'exemptions':
        fputcsv($output, ['Student', 'Admission No', 'Class', 'Fee', 'Reason', 'Exempted By', 'Date']);
        $rows = db_fetch_all("
            SELECT s.first_name, s.last_name, s.admission_no, c.name AS class_name,
                   f.description AS fee_name, fe.reason,
                   u.full_name AS exempted_by, fe.created_at
            FROM fee_exemptions fe
            JOIN students s ON s.id = fe.student_id
            JOIN fees f ON f.id = fe.fee_id
            LEFT JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
            LEFT JOIN classes c ON c.id = e.class_id
            LEFT JOIN users u ON u.id = fe.created_by
            WHERE fe.created_at BETWEEN ? AND ?
            ORDER BY fe.created_at DESC
        ", [$dateFrom, $dateTo . ' 23:59:59']);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['first_name'] . ' ' . $row['last_name'],
                $row['admission_no'],
                $row['class_name'] ?? 'N/A',
                $row['fee_name'],
                $row['reason'] ?? 'N/A',
                $row['exempted_by'] ?? 'N/A',
                format_date($row['created_at']),
            ]);
        }
        break;
}

fclose($output);
exit;
