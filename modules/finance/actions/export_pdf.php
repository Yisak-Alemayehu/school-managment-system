<?php
/**
 * Finance — Export PDF
 * Generates a simple HTML-based PDF download using print-friendly layout.
 * For production, integrate a library like TCPDF or Dompdf.
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
    // Build data from query params for other export types
    $title   = 'Finance Export';
    $headers = [];
    $rows    = [];

    if ($type === 'payments') {
        $title = 'School Payment History';
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

        $txRows = db_fetch_all("SELECT t.*, s.full_name, s.admission_no, c.name AS class_name, f.description AS fee_desc FROM fin_transactions t $joins WHERE $whereClause ORDER BY t.created_at DESC LIMIT 5000", $params);
        foreach ($txRows as $r) {
            $rows[] = [format_datetime($r['created_at']), $r['full_name'], $r['admission_no'], $r['class_name'] ?? '—', $r['fee_desc'] ?? '—', format_money($r['amount']), ucfirst($r['channel'] ?? '—'), $r['receipt_no'] ?? '—'];
        }

    } elseif ($type === 'supplementary-payments') {
        $title = 'Supplementary Payment History';
        $headers = ['Date', 'Student', 'Code', 'Fee', 'Amount', 'Channel', 'Receipt'];
        $txRows = db_fetch_all("SELECT st.*, s.full_name, s.admission_no, sf.description AS fee_desc FROM fin_supplementary_transactions st JOIN students s ON st.student_id = s.id LEFT JOIN fin_supplementary_fees sf ON st.supplementary_fee_id = sf.id ORDER BY st.created_at DESC LIMIT 5000");
        foreach ($txRows as $r) {
            $rows[] = [format_datetime($r['created_at']), $r['full_name'], $r['admission_no'], $r['fee_desc'] ?? '—', format_money($r['amount']), ucfirst($r['channel'] ?? '—'), $r['receipt_no'] ?? '—'];
        }

    } elseif ($type === 'fees') {
        $title = 'Fee List';
        $headers = ['Description', 'Amount', 'Type', 'Period', 'Status'];
        $feeRows = db_fetch_all("SELECT * FROM fin_fees ORDER BY created_at DESC LIMIT 5000");
        foreach ($feeRows as $f) {
            $rows[] = [$f['description'], format_money($f['amount']) . ' ' . $f['currency'], $f['fee_type'] ? 'Recurrent' : 'One-Time', format_date($f['effective_date']) . ' - ' . format_date($f['end_date']), $f['is_active'] ? 'Active' : 'Inactive'];
        }

    } elseif ($type === 'fee-detail') {
        $feeId = input_int('id');
        $fee = db_fetch_one("SELECT * FROM fin_fees WHERE id = ?", [$feeId]);
        $title = 'Fee Detail: ' . ($fee['description'] ?? 'Unknown');
        $headers = ['Student', 'Code', 'Amount', 'Balance', 'Status'];
        $sfRows = db_fetch_all("SELECT sf.*, s.full_name, s.admission_no FROM fin_student_fees sf JOIN students s ON sf.student_id = s.id WHERE sf.fee_id = ? ORDER BY s.full_name", [$feeId]);
        foreach ($sfRows as $sf) {
            $rows[] = [$sf['full_name'], $sf['admission_no'], format_money($sf['amount']), format_money($sf['balance']), $sf['is_active'] ? 'Active' : 'Removed'];
        }

    } elseif ($type === 'student-detail') {
        $studentId = input_int('id');
        $stu = db_fetch_one("SELECT * FROM students WHERE id = ?", [$studentId]);
        $title = 'Student Finance: ' . ($stu['full_name'] ?? 'Unknown');
        $headers = ['Date', 'Type', 'Amount', 'Balance Before', 'Balance After', 'Description'];
        $txRows = db_fetch_all("SELECT * FROM fin_transactions WHERE student_id = ? ORDER BY created_at DESC LIMIT 5000", [$studentId]);
        foreach ($txRows as $t) {
            $rows[] = [format_datetime($t['created_at']), ucfirst(str_replace('_', ' ', $t['type'])), format_money($t['amount']), format_money($t['balance_before'] ?? 0), format_money($t['balance_after'] ?? 0), $t['description'] ?? '—'];
        }
    }
}

// Output as printable HTML
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= e($title) ?></title>
    <style>
        @media print { @page { size: landscape; margin: 1cm; } }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; color: #333; }
        h1 { text-align: center; font-size: 16px; margin-bottom: 4px; }
        .meta { text-align: center; font-size: 10px; color: #666; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        tr:nth-child(even) { background: #fafafa; }
        .no-print { margin-bottom: 12px; text-align: center; }
        @media print { .no-print { display: none; } }
        .btn { padding: 8px 20px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn" onclick="window.close()" style="background:#6b7280">Close</button>
    </div>
    <h1><?= e($title) ?></h1>
    <div class="meta">Generated: <?= date('Y-m-d H:i:s') ?> | Total Records: <?= count($rows) ?></div>
    <table>
        <thead><tr><th>#</th><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="<?= count($headers) + 1 ?>" style="text-align:center;padding:20px">No records found.</td></tr>
            <?php else: ?>
            <?php foreach ($rows as $i => $row): ?>
            <tr><td><?= $i + 1 ?></td><?php foreach ($row as $cell): ?><td><?= e($cell) ?></td><?php endforeach; ?></tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
exit;
