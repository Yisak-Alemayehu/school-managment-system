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

// Output as proper PDF using FPDF
require_once APP_ROOT . '/vendor/setasign/fpdf/fpdf.php';

$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);

// Title
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, $title, 0, 1, 'C');

// Meta line
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s') . ' | Total Records: ' . count($rows), 0, 1, 'C');
$pdf->Ln(4);

// Column widths: # = 10mm, rest split equally
$numW = 10;
$dataW = (count($headers) > 0) ? ($pdf->GetPageWidth() - 20 - $numW) / count($headers) : 0;

// Helper to print header row
$printHeader = function () use ($pdf, $headers, $numW, $dataW) {
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell($numW, 7, '#', 1, 0, 'L', true);
    foreach ($headers as $idx => $h) {
        $ln = ($idx === count($headers) - 1) ? 1 : 0;
        $pdf->Cell($dataW, 7, $h, 1, $ln, 'L', true);
    }
};

$printHeader();

// Data rows
$pdf->SetFont('Arial', '', 7);
if (empty($rows)) {
    $pdf->Cell($pdf->GetPageWidth() - 20, 10, 'No records found.', 1, 1, 'C');
} else {
    foreach ($rows as $i => $row) {
        $fill = ($i % 2 === 1);
        if ($fill) {
            $pdf->SetFillColor(250, 250, 250);
        }
        $pdf->Cell($numW, 6, $i + 1, 1, 0, 'L', $fill);
        foreach (array_values($row) as $ci => $cell) {
            $ln = ($ci === count($row) - 1) ? 1 : 0;
            $text = mb_strimwidth((string)$cell, 0, 40, '...');
            $pdf->Cell($dataW, 6, $text, 1, $ln, 'L', $fill);
        }

        // Page break if near bottom — reprint header on new page
        if ($pdf->GetY() > $pdf->GetPageHeight() - 20) {
            $pdf->AddPage();
            $printHeader();
            $pdf->SetFont('Arial', '', 7);
        }
    }
}

$pdf->Output('I', $safeName . '.pdf');
exit;
