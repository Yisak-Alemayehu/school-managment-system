<?php
/**
 * Finance — Report Generator
 * Processes report forms and renders results on screen or redirects to export.
 */
csrf_protect();

$reportType = input('report_type');
$output     = input('output') ?: 'screen';
$fields     = input_array('fields');

if (empty($fields)) {
    set_flash('error', 'Please select at least one field.');
    redirect(url('finance', 'report-' . ($reportType ?: 'students')));
}

// Collect filter values
$classId   = input_int('class_id');
$gender    = input('gender');
$feeId     = input_int('fee_id');
$sfeeId    = input_int('sfee_id');
$feeStatus = input('fee_status');
$dateFrom  = input('date_from');
$dateTo    = input('date_to');
$channel   = input('channel');

$rows = [];
$headers = [];

// ──────────────────────────────────────────────
// STUDENT INFO REPORT
// ──────────────────────────────────────────────
if ($reportType === 'students') {
    $where  = ["s.deleted_at IS NULL", "s.status = 'active'"];
    $params = [];

    $joins = "LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
              LEFT JOIN classes c ON e.class_id = c.id
              LEFT JOIN sections sec ON e.section_id = sec.id";

    if ($classId)  { $where[] = "e.class_id = ?"; $params[] = $classId; }
    if ($gender)   { $where[] = "s.gender = ?";    $params[] = $gender; }

    if ($feeId) {
        $joins .= " JOIN fin_student_fees sf_filter ON s.id = sf_filter.student_id AND sf_filter.fee_id = " . (int)$feeId;
        if ($feeStatus === 'active')   $where[] = "sf_filter.is_active = 1";
        if ($feeStatus === 'inactive') $where[] = "sf_filter.is_active = 0";
    }

    $whereClause = implode(' AND ', $where);

    $students = db_fetch_all(
        "SELECT DISTINCT s.id, s.admission_no, s.full_name, s.gender, s.date_of_birth,
                s.email, s.phone, s.nationality, s.status,
                c.name AS class_name, sec.name AS section_name, e.created_at AS enrollment_date
           FROM students s $joins
          WHERE $whereClause
          ORDER BY s.full_name
          LIMIT 5000",
        $params
    );

    // Build rows
    $fieldMap = [
        'student_code' => 'Student Code', 'full_name' => 'Full Name', 'gender' => 'Gender',
        'dob' => 'Date of Birth', 'email' => 'Email', 'phone' => 'Phone',
        'nationality' => 'Nationality', 'class' => 'Class', 'section' => 'Section',
        'enrollment_date' => 'Enrollment Date', 'status' => 'Status',
        'total_fees' => 'Total Fees', 'total_paid' => 'Total Paid',
        'balance' => 'Balance', 'active_fees_count' => 'Active Fees',
        'last_payment_date' => 'Last Payment', 'total_penalty' => 'Total Penalty',
    ];

    foreach ($fields as $f) {
        if (isset($fieldMap[$f])) $headers[] = $fieldMap[$f];
    }

    foreach ($students as $stu) {
        // Fetch finance data if needed
        $finData = null;
        $needsFin = array_intersect($fields, ['total_fees', 'total_paid', 'balance', 'active_fees_count', 'last_payment_date', 'total_penalty']);
        if (!empty($needsFin)) {
            $finData = db_fetch_one(
                "SELECT COUNT(*) AS active_count,
                        COALESCE(SUM(amount), 0) AS total_fees,
                        COALESCE(SUM(balance), 0) AS total_balance
                   FROM fin_student_fees WHERE student_id = ? AND is_active = 1",
                [$stu['id']]
            );
            $totalPaid = (float) db_fetch_value(
                "SELECT COALESCE(SUM(amount), 0) FROM fin_transactions WHERE student_id = ? AND type = 'payment'",
                [$stu['id']]
            );
            $lastPayment = db_fetch_value(
                "SELECT MAX(created_at) FROM fin_transactions WHERE student_id = ? AND type = 'payment'",
                [$stu['id']]
            );
            $totalPenalty = (float) db_fetch_value(
                "SELECT COALESCE(SUM(amount), 0) FROM fin_transactions WHERE student_id = ? AND type = 'penalty'",
                [$stu['id']]
            );
        }

        $row = [];
        foreach ($fields as $f) {
            switch ($f) {
                case 'student_code':     $row[] = $stu['admission_no']; break;
                case 'full_name':        $row[] = $stu['full_name']; break;
                case 'gender':           $row[] = ucfirst($stu['gender']); break;
                case 'dob':              $row[] = $stu['date_of_birth'] ? format_date($stu['date_of_birth']) : '—'; break;
                case 'email':            $row[] = $stu['email'] ?? '—'; break;
                case 'phone':            $row[] = $stu['phone'] ?? '—'; break;
                case 'nationality':      $row[] = $stu['nationality'] ?? '—'; break;
                case 'class':            $row[] = $stu['class_name'] ?? '—'; break;
                case 'section':          $row[] = $stu['section_name'] ?? '—'; break;
                case 'enrollment_date':  $row[] = $stu['enrollment_date'] ? format_date($stu['enrollment_date']) : '—'; break;
                case 'status':           $row[] = ucfirst($stu['status']); break;
                case 'total_fees':       $row[] = format_money($finData['total_fees'] ?? 0); break;
                case 'total_paid':       $row[] = format_money($totalPaid ?? 0); break;
                case 'balance':          $row[] = format_money($finData['total_balance'] ?? 0); break;
                case 'active_fees_count': $row[] = (int)($finData['active_count'] ?? 0); break;
                case 'last_payment_date': $row[] = $lastPayment ? format_date($lastPayment) : '—'; break;
                case 'total_penalty':    $row[] = format_money($totalPenalty ?? 0); break;
                default:                 $row[] = '—';
            }
        }
        $rows[] = $row;
    }

    $reportTitle = 'Student Info Report';

// ──────────────────────────────────────────────
// PENALTY REPORT
// ──────────────────────────────────────────────
} elseif ($reportType === 'penalty') {
    $where  = ["t.type = 'penalty'"];
    $params = [];

    $joins = "JOIN students s ON t.student_id = s.id
              JOIN fin_student_fees sf ON t.student_fee_id = sf.id
              JOIN fin_fees f ON sf.fee_id = f.id
              LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
              LEFT JOIN classes c ON e.class_id = c.id";

    if ($classId)  { $where[] = "e.class_id = ?"; $params[] = $classId; }
    if ($feeId)    { $where[] = "f.id = ?";        $params[] = $feeId; }
    if ($dateFrom) { $where[] = "t.created_at >= ?"; $params[] = $dateFrom . ' 00:00:00'; }
    if ($dateTo)   { $where[] = "t.created_at <= ?"; $params[] = $dateTo   . ' 23:59:59'; }

    $whereClause = implode(' AND ', $where);

    $penaltiesRaw = db_fetch_all(
        "SELECT t.*, s.admission_no, s.full_name, c.name AS class_name,
                f.description AS fee_desc, f.amount AS fee_amount, sf.balance,
                f.penalty_type
           FROM fin_transactions t $joins
          WHERE $whereClause
          ORDER BY t.created_at DESC
          LIMIT 5000",
        $params
    );

    $fieldMap = [
        'student_code' => 'Student Code', 'full_name' => 'Full Name', 'class' => 'Class',
        'fee_description' => 'Fee', 'fee_amount' => 'Fee Amount', 'balance' => 'Balance',
        'penalty_count' => 'Penalty Count', 'penalty_total' => 'Penalty Total',
        'last_penalty_date' => 'Last Penalty Date', 'penalty_type' => 'Penalty Type',
    ];

    foreach ($fields as $f) {
        if (isset($fieldMap[$f])) $headers[] = $fieldMap[$f];
    }

    foreach ($penaltiesRaw as $p) {
        $row = [];
        foreach ($fields as $f) {
            switch ($f) {
                case 'student_code':     $row[] = $p['admission_no']; break;
                case 'full_name':        $row[] = $p['full_name']; break;
                case 'class':            $row[] = $p['class_name'] ?? '—'; break;
                case 'fee_description':  $row[] = $p['fee_desc']; break;
                case 'fee_amount':       $row[] = format_money($p['fee_amount']); break;
                case 'balance':          $row[] = format_money($p['balance']); break;
                case 'penalty_count':    $row[] = 1; break;
                case 'penalty_total':    $row[] = format_money($p['amount']); break;
                case 'last_penalty_date': $row[] = format_datetime($p['created_at']); break;
                case 'penalty_type':     $row[] = ucfirst(str_replace('_', ' ', $p['penalty_type'] ?? '—')); break;
                default:                 $row[] = '—';
            }
        }
        $rows[] = $row;
    }

    $reportTitle = 'Penalty Report';

// ──────────────────────────────────────────────
// SUPPLEMENTARY TRANSACTION REPORT
// ──────────────────────────────────────────────
} elseif ($reportType === 'supplementary') {
    $where  = ["1=1"];
    $params = [];

    $joins = "JOIN students s ON st.student_id = s.id
              LEFT JOIN fin_supplementary_fees sf ON st.supplementary_fee_id = sf.id
              LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
              LEFT JOIN classes c ON e.class_id = c.id
              LEFT JOIN users u ON st.processed_by = u.id";

    if ($sfeeId)   { $where[] = "st.supplementary_fee_id = ?"; $params[] = $sfeeId; }
    if ($classId)  { $where[] = "e.class_id = ?";               $params[] = $classId; }
    if ($channel)  { $where[] = "st.channel = ?";               $params[] = $channel; }
    if ($dateFrom) { $where[] = "st.created_at >= ?";           $params[] = $dateFrom . ' 00:00:00'; }
    if ($dateTo)   { $where[] = "st.created_at <= ?";           $params[] = $dateTo   . ' 23:59:59'; }

    $whereClause = implode(' AND ', $where);

    $txRows = db_fetch_all(
        "SELECT st.*, s.admission_no, s.full_name, s.gender, c.name AS class_name,
                sf.description AS fee_desc, sf.amount AS fee_amount, sf.currency AS fee_currency,
                u.full_name AS processed_by_name
           FROM fin_supplementary_transactions st $joins
          WHERE $whereClause
          ORDER BY st.created_at DESC
          LIMIT 5000",
        $params
    );

    $fieldMap = [
        'student_code' => 'Student Code', 'full_name' => 'Full Name', 'class' => 'Class',
        'gender' => 'Gender', 'fee_description' => 'Fee', 'fee_amount' => 'Fee Amount',
        'currency' => 'Currency', 'tx_amount' => 'Tx Amount', 'tx_date' => 'Tx Date',
        'channel' => 'Channel', 'receipt_no' => 'Receipt No', 'depositor_name' => 'Depositor',
        'depositor_branch' => 'Branch', 'tx_id' => 'Transaction ID', 'processed_by' => 'Processed By',
    ];

    foreach ($fields as $f) {
        if (isset($fieldMap[$f])) $headers[] = $fieldMap[$f];
    }

    foreach ($txRows as $t) {
        $row = [];
        foreach ($fields as $f) {
            switch ($f) {
                case 'student_code':     $row[] = $t['admission_no']; break;
                case 'full_name':        $row[] = $t['full_name']; break;
                case 'class':            $row[] = $t['class_name'] ?? '—'; break;
                case 'gender':           $row[] = ucfirst($t['gender']); break;
                case 'fee_description':  $row[] = $t['fee_desc'] ?? '—'; break;
                case 'fee_amount':       $row[] = format_money($t['fee_amount'] ?? 0); break;
                case 'currency':         $row[] = $t['fee_currency'] ?? $t['currency']; break;
                case 'tx_amount':        $row[] = format_money($t['amount']); break;
                case 'tx_date':          $row[] = format_datetime($t['created_at']); break;
                case 'channel':          $row[] = ucfirst($t['channel'] ?? '—'); break;
                case 'receipt_no':       $row[] = $t['receipt_no'] ?? '—'; break;
                case 'depositor_name':   $row[] = $t['channel_depositor_name'] ?? '—'; break;
                case 'depositor_branch': $row[] = $t['channel_depositor_branch'] ?? '—'; break;
                case 'tx_id':            $row[] = $t['channel_transaction_id'] ?? '—'; break;
                case 'processed_by':     $row[] = $t['processed_by_name'] ?? '—'; break;
                default:                 $row[] = '—';
            }
        }
        $rows[] = $row;
    }

    $reportTitle = 'Supplementary Transaction Report';

} else {
    set_flash('error', 'Invalid report type.');
    redirect(url('finance', 'report-students'));
}

// ──────────────────────────────────────────────
// Handle output format
// ──────────────────────────────────────────────
if ($output === 'pdf' || $output === 'excel') {
    $_SESSION['_report_data'] = [
        'title'   => $reportTitle,
        'headers' => $headers,
        'rows'    => $rows,
        'type'    => $reportType,
    ];
    $exportAction = $output === 'pdf' ? 'export-pdf' : 'export-excel';
    redirect(url('finance', $exportAction) . '&type=report');
}

// Screen output
$pageTitle = $reportTitle;
ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-3">
            <a href="<?= url('finance', 'report-' . e($reportType)) ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-xl font-bold text-gray-900"><?= e($reportTitle) ?></h1>
            <span class="text-sm text-gray-500">(<?= count($rows) ?> records)</span>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 responsive-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">#</th>
                        <?php foreach ($headers as $h): ?>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase"><?= e($h) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= count($headers) + 1 ?>" class="px-4 py-8 text-center text-gray-400">No records found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($rows as $i => $row): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-400"><?= $i + 1 ?></td>
                        <?php foreach ($row as $cell): ?>
                        <td class="px-4 py-3 text-sm text-gray-700"><?= e($cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
