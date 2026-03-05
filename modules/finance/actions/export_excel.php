<?php
/**
 * Finance — Export Excel (CSV download)
 * Generates a CSV file with appropriate headers for Excel compatibility.
 */

$type = input('type');

// If coming from report generator
if ($type === 'report' && isset($_SESSION['_report_data'])) {
    $data    = $_SESSION['_report_data'];
    $title   = $data['title'];
    $headers = $data['headers'];
    $rows    = $data['rows'];
    unset($_SESSION['_report_data']);
} else {
    $title   = 'Finance Export';
    $headers = [];
    $rows    = [];

    if ($type === 'payments') {
        $title = 'School_Payment_History';
        $headers = ['Date', 'Student', 'Code', 'Class', 'Fee', 'Amount', 'Channel', 'Receipt'];
        $where = ["t.type = 'payment'"];
        $params = [];
        $search = input('search');
        $feeId = input_int('fee_id');
        $classId = input_int('class_id');
        $dateFrom = input('date_from');
        $dateTo = input('date_to');
        $channel = input('channel');

        if ($search) { $where[] = "(s.full_name LIKE ? OR s.admission_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($feeId) { $where[] = "sf.fee_id = ?"; $params[] = $feeId; }
        if ($classId) { $where[] = "e.class_id = ?"; $params[] = $classId; }
        if ($dateFrom) { $where[] = "t.created_at >= ?"; $params[] = $dateFrom . ' 00:00:00'; }
        if ($dateTo) { $where[] = "t.created_at <= ?"; $params[] = $dateTo . ' 23:59:59'; }
        if ($channel) { $where[] = "t.channel = ?"; $params[] = $channel; }

        $joins = "JOIN students s ON t.student_id = s.id LEFT JOIN fin_student_fees sf ON t.student_fee_id = sf.id LEFT JOIN fin_fees f ON sf.fee_id = f.id LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active' LEFT JOIN classes c ON e.class_id = c.id";
        $whereClause = implode(' AND ', $where);

        $txRows = db_fetch_all("SELECT t.*, s.full_name, s.admission_no, c.name AS class_name, f.description AS fee_desc FROM fin_transactions t $joins WHERE $whereClause ORDER BY t.created_at DESC LIMIT 10000", $params);
        foreach ($txRows as $r) {
            $rows[] = [$r['created_at'], $r['full_name'], $r['admission_no'], $r['class_name'] ?? '', $r['fee_desc'] ?? '', $r['amount'], ucfirst($r['channel'] ?? ''), $r['receipt_no'] ?? ''];
        }

    } elseif ($type === 'supplementary-payments') {
        $title = 'Supplementary_Payment_History';
        $headers = ['Date', 'Student', 'Code', 'Fee', 'Amount', 'Channel', 'Receipt'];
        $txRows = db_fetch_all("SELECT st.*, s.full_name, s.admission_no, sf.description AS fee_desc FROM fin_supplementary_transactions st JOIN students s ON st.student_id = s.id LEFT JOIN fin_supplementary_fees sf ON st.supplementary_fee_id = sf.id ORDER BY st.created_at DESC LIMIT 10000");
        foreach ($txRows as $r) {
            $rows[] = [$r['created_at'], $r['full_name'], $r['admission_no'], $r['fee_desc'] ?? '', $r['amount'], ucfirst($r['channel'] ?? ''), $r['receipt_no'] ?? ''];
        }

    } elseif ($type === 'fees') {
        $title = 'Fee_List';
        $headers = ['Description', 'Amount', 'Currency', 'Type', 'Effective Date', 'End Date', 'Status'];
        $feeRows = db_fetch_all("SELECT * FROM fin_fees ORDER BY created_at DESC LIMIT 10000");
        foreach ($feeRows as $f) {
            $rows[] = [$f['description'], $f['amount'], $f['currency'], $f['fee_type'] ? 'Recurrent' : 'One-Time', $f['effective_date'], $f['end_date'], $f['is_active'] ? 'Active' : 'Inactive'];
        }

    } elseif ($type === 'fee-detail') {
        $feeId = input_int('id');
        $fee = db_fetch_one("SELECT * FROM fin_fees WHERE id = ?", [$feeId]);
        $title = 'Fee_' . preg_replace('/[^a-zA-Z0-9]/', '_', $fee['description'] ?? 'Detail');
        $headers = ['Student', 'Code', 'Amount', 'Balance', 'Status', 'Assigned At'];
        $sfRows = db_fetch_all("SELECT sf.*, s.full_name, s.admission_no FROM fin_student_fees sf JOIN students s ON sf.student_id = s.id WHERE sf.fee_id = ? ORDER BY s.full_name", [$feeId]);
        foreach ($sfRows as $sf) {
            $rows[] = [$sf['full_name'], $sf['admission_no'], $sf['amount'], $sf['balance'], $sf['is_active'] ? 'Active' : 'Removed', $sf['assigned_at']];
        }

    } elseif ($type === 'student-detail') {
        $studentId = input_int('id');
        $stu = db_fetch_one("SELECT * FROM students WHERE id = ?", [$studentId]);
        $title = 'Student_' . preg_replace('/[^a-zA-Z0-9]/', '_', $stu['full_name'] ?? 'Detail');
        $headers = ['Date', 'Type', 'Amount', 'Currency', 'Balance Before', 'Balance After', 'Description', 'Channel', 'Receipt'];
        $txRows = db_fetch_all("SELECT * FROM fin_transactions WHERE student_id = ? ORDER BY created_at DESC LIMIT 10000", [$studentId]);
        foreach ($txRows as $t) {
            $rows[] = [$t['created_at'], ucfirst(str_replace('_', ' ', $t['type'])), $t['amount'], $t['currency'], $t['balance_before'] ?? 0, $t['balance_after'] ?? 0, $t['description'] ?? '', $t['channel'] ?? '', $t['receipt_no'] ?? ''];
        }
    }
}

// Output CSV
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
$filename = $safeName . '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, $headers);
foreach ($rows as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
