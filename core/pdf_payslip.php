<?php
/**
 * Payslip PDF Generator
 *
 * Individual employee payslip with:
 *   - School header
 *   - Employee information
 *   - Earnings table (basic, allowances, overtime, gross)
 *   - Deductions table (tax, pension, other, total)
 *   - Net pay with amount in words
 *   - Signature lines
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/core/pdf.php';
require_once APP_ROOT . '/core/db.php';
require_once APP_ROOT . '/core/ethiopian_calendar.php';
require_once APP_ROOT . '/core/payroll.php';

/**
 * Generate individual Payslip PDF.
 *
 * @param int    $recordId     hr_payroll_records.id
 * @param string $outputMode   'D' = download, 'I' = inline, 'F' = save
 * @param string $outputPath   Path for 'F' mode
 */
function pdf_payslip(int $recordId, string $outputMode = 'D', string $outputPath = ''): void
{
    // ── Fetch payroll record with joins ──────────────────
    $record = db_fetch_one(
        "SELECT pr.*, pp.month_ec, pp.year_ec, pp.month_name_ec,
                pp.start_date AS period_start, pp.end_date AS period_end,
                e.employee_id AS emp_code, e.first_name, e.father_name,
                e.grandfather_name, e.position, e.tin_number,
                e.bank_name, e.bank_account, e.phone,
                d.name AS department_name
         FROM hr_payroll_records pr
         JOIN hr_payroll_periods pp ON pp.id = pr.payroll_period_id
         JOIN hr_employees e ON e.id = pr.employee_id
         LEFT JOIN hr_departments d ON d.id = e.department_id
         WHERE pr.id = ?",
        [$recordId]
    );
    if (!$record) {
        die('Payroll record not found.');
    }

    $fullName = $record['first_name'] . ' ' . $record['father_name'] . ' ' . $record['grandfather_name'];
    $monthEc  = $record['month_name_ec'] ?? ec_month_name((int)$record['month_ec']);
    $yearEc   = (string)$record['year_ec'];

    // ── Build PDF (Portrait A4) ─────────────────────────
    $pdf = new SchoolPDF('P', 'mm', 'A4');
    $pdf->AddPage('P');
    $pw = 190;
    $lm = 10;
    $y  = 10;

    // ── School Header ───────────────────────────────────
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 7, SCHOOL_NAME, 0, 0, 'C');
    $y += 7;
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, SCHOOL_SUBCITY . ', ' . SCHOOL_CITY . ', ' . SCHOOL_REGION . '  |  Tel: ' . SCHOOL_TELEPHONE, 0, 0, 'C');
    $y += 6;
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, 'TIN: ' . SCHOOL_TIN, 0, 0, 'C');
    $y += 8;

    // Divider
    $pdf->Line($lm, $y, $lm + $pw, $y);
    $y += 4;

    // Title
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 7, 'PAY SLIP', 0, 0, 'C');
    $y += 8;
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, "Payroll Period: {$monthEc} {$yearEc} E.C.", 0, 0, 'C');
    $y += 9;

    // ── Employee Information ────────────────────────────
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, 'Employee Information', 'B');
    $y += 7;

    $labelW = 45;
    $valW   = $pw / 2 - $labelW;
    $lineH  = 5.5;

    $infoLeft = [
        ['Name:', $fullName],
        ['Employee ID:', $record['emp_code']],
        ['Position:', $record['position'] ?? 'N/A'],
        ['Department:', $record['department_name'] ?? 'N/A'],
    ];

    $infoRight = [
        ['TIN:', $record['tin_number'] ?? 'N/A'],
        ['Bank:', $record['bank_name'] ?? 'N/A'],
        ['Account:', $record['bank_account'] ?? 'N/A'],
        ['Phone:', $record['phone'] ?? 'N/A'],
    ];

    $halfW  = $pw / 2;
    for ($i = 0; $i < count($infoLeft); $i++) {
        // Left column
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetXY($lm, $y);
        $pdf->Cell($labelW, $lineH, $infoLeft[$i][0], 0);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell($valW, $lineH, $infoLeft[$i][1], 0);

        // Right column
        if (isset($infoRight[$i])) {
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetXY($lm + $halfW, $y);
            $pdf->Cell($labelW, $lineH, $infoRight[$i][0], 0);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell($valW, $lineH, $infoRight[$i][1], 0);
        }
        $y += $lineH;
    }
    $y += 5;

    // ── Working Days Info ───────────────────────────────
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw / 3, $lineH, 'Working Days: ' . $record['working_days'], 0);
    $pdf->Cell($pw / 3, $lineH, 'Days Worked: ' . $record['days_worked'], 0);
    $pdf->Cell($pw / 3, $lineH, 'Period: ' . ($record['period_start'] ?? '') . ' to ' . ($record['period_end'] ?? ''), 0, 0, 'R');
    $y += 8;

    // ── Earnings & Deductions Tables (side by side) ─────
    $tableW = $pw / 2 - 3;  // each table width
    $colLbl = $tableW * 0.60;
    $colAmt = $tableW * 0.40;
    $rowH   = 7;

    // ─── Earnings (Left) ────
    $ex = $lm;
    $ey = $y;

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetFillColor(34, 139, 34);  // green header
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Rect($ex, $ey, $tableW, $rowH, 'F');
    $pdf->Rect($ex, $ey, $tableW, $rowH);
    $pdf->SetXY($ex + 1, $ey + 2);
    $pdf->Cell($tableW - 2, 3, 'EARNINGS', 0, 0, 'C');
    $pdf->SetTextColor(0);
    $ey += $rowH;

    // Earnings header
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Rect($ex, $ey, $colLbl, $rowH);
    $pdf->Rect($ex + $colLbl, $ey, $colAmt, $rowH);
    $pdf->SetXY($ex + 1, $ey + 2);
    $pdf->Cell($colLbl - 2, 3, 'Description', 0);
    $pdf->SetXY($ex + $colLbl + 1, $ey + 2);
    $pdf->Cell($colAmt - 2, 3, 'Amount (ETB)', 0, 0, 'R');
    $ey += $rowH;

    $earnings = [
        ['Basic Salary',        (float)$record['basic_salary']],
        ['Prorated Salary',     (float)$record['prorated_salary']],
        ['Transport Allowance', (float)$record['transport_allowance']],
        ['Other Allowance',     (float)$record['other_allowance']],
        ['Overtime',            (float)($record['overtime'] ?? 0)],
    ];

    $pdf->SetFont('Helvetica', '', 8);
    foreach ($earnings as $item) {
        $pdf->Rect($ex, $ey, $colLbl, $rowH);
        $pdf->Rect($ex + $colLbl, $ey, $colAmt, $rowH);
        $pdf->SetXY($ex + 1, $ey + 2);
        $pdf->Cell($colLbl - 2, 3, $item[0], 0);
        $pdf->SetXY($ex + $colLbl + 1, $ey + 2);
        $pdf->Cell($colAmt - 2, 3, number_format($item[1], 2), 0, 0, 'R');
        $ey += $rowH;
    }

    // Gross Salary total
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetFillColor(230, 255, 230);
    $pdf->Rect($ex, $ey, $colLbl, $rowH, 'F');
    $pdf->Rect($ex, $ey, $colLbl, $rowH);
    $pdf->Rect($ex + $colLbl, $ey, $colAmt, $rowH, 'F');
    $pdf->Rect($ex + $colLbl, $ey, $colAmt, $rowH);
    $pdf->SetXY($ex + 1, $ey + 2);
    $pdf->Cell($colLbl - 2, 3, 'GROSS SALARY', 0);
    $pdf->SetXY($ex + $colLbl + 1, $ey + 2);
    $pdf->Cell($colAmt - 2, 3, number_format((float)$record['gross_salary'], 2), 0, 0, 'R');
    $ey += $rowH;

    // ─── Deductions (Right) ─────
    $dx = $lm + $pw / 2 + 3;
    $dy = $y;

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetFillColor(220, 53, 69);  // red header
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Rect($dx, $dy, $tableW, $rowH, 'F');
    $pdf->Rect($dx, $dy, $tableW, $rowH);
    $pdf->SetXY($dx + 1, $dy + 2);
    $pdf->Cell($tableW - 2, 3, 'DEDUCTIONS', 0, 0, 'C');
    $pdf->SetTextColor(0);
    $dy += $rowH;

    // Deductions header
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->Rect($dx, $dy, $colLbl, $rowH);
    $pdf->Rect($dx + $colLbl, $dy, $colAmt, $rowH);
    $pdf->SetXY($dx + 1, $dy + 2);
    $pdf->Cell($colLbl - 2, 3, 'Description', 0);
    $pdf->SetXY($dx + $colLbl + 1, $dy + 2);
    $pdf->Cell($colAmt - 2, 3, 'Amount (ETB)', 0, 0, 'R');
    $dy += $rowH;

    $deductions = [
        ['Income Tax',          (float)$record['income_tax']],
        ['Employee Pension 7%', (float)$record['employee_pension']],
        ['Employer Pension 11%',(float)$record['employer_pension']],
        ['Other Deductions',    (float)($record['other_deductions'] ?? 0)],
    ];

    $pdf->SetFont('Helvetica', '', 8);
    foreach ($deductions as $item) {
        $pdf->Rect($dx, $dy, $colLbl, $rowH);
        $pdf->Rect($dx + $colLbl, $dy, $colAmt, $rowH);
        $pdf->SetXY($dx + 1, $dy + 2);
        $pdf->Cell($colLbl - 2, 3, $item[0], 0);
        $pdf->SetXY($dx + $colLbl + 1, $dy + 2);
        $pdf->Cell($colAmt - 2, 3, number_format($item[1], 2), 0, 0, 'R');
        $dy += $rowH;
    }

    // Total Deductions
    $pdf->SetFont('Helvetica', 'B', 8);
    $pdf->SetFillColor(255, 230, 230);
    $pdf->Rect($dx, $dy, $colLbl, $rowH, 'F');
    $pdf->Rect($dx, $dy, $colLbl, $rowH);
    $pdf->Rect($dx + $colLbl, $dy, $colAmt, $rowH, 'F');
    $pdf->Rect($dx + $colLbl, $dy, $colAmt, $rowH);
    $pdf->SetXY($dx + 1, $dy + 2);
    $pdf->Cell($colLbl - 2, 3, 'TOTAL DEDUCTIONS', 0);
    $pdf->SetXY($dx + $colLbl + 1, $dy + 2);
    $pdf->Cell($colAmt - 2, 3, number_format((float)$record['total_deductions'], 2), 0, 0, 'R');

    // ── Net Pay Section ─────────────────────────────────
    $bottomY = max($ey, $dy + $rowH) + 8;

    $netPay = (float)$record['net_salary'];

    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetFillColor(240, 240, 255);
    $netBoxH = 10;
    $pdf->Rect($lm, $bottomY, $pw, $netBoxH, 'F');
    $pdf->Rect($lm, $bottomY, $pw, $netBoxH);
    $pdf->SetXY($lm + 2, $bottomY + 3);
    $pdf->Cell($pw / 2 - 4, 5, 'NET PAY:', 0);
    $pdf->Cell($pw / 2 - 4, 5, 'ETB ' . number_format($netPay, 2), 0, 0, 'R');
    $bottomY += $netBoxH + 2;

    // Amount in words
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetXY($lm, $bottomY);
    $pdf->Cell($pw, 4, 'Amount in words: ' . payroll_amount_in_words($netPay), 0);
    $bottomY += 6;

    // Payment info
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetXY($lm, $bottomY);
    $payMethod = ucfirst(str_replace('_', ' ', $record['payment_method'] ?? 'bank_transfer'));
    $pdf->Cell($pw / 2, 4, 'Payment Method: ' . $payMethod, 0);
    $pdf->Cell($pw / 2, 4, 'Payment Status: ' . ucfirst($record['payment_status']), 0, 0, 'R');
    $bottomY += 12;

    // ── Signatures ──────────────────────────────────────
    $halfW = $pw / 2 - 5;

    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetXY($lm, $bottomY);
    $pdf->Cell($halfW, 5, 'Employer:', 'B');
    $pdf->SetXY($lm + $pw / 2 + 5, $bottomY);
    $pdf->Cell($halfW, 5, 'Employee:', 'B');
    $bottomY += 8;

    $pdf->SetFont('Helvetica', '', 8);
    $sigLines = ['Name: ___________________________', 'Signature: ______________________', 'Date: ___________________________'];
    foreach ($sigLines as $sl) {
        $pdf->SetXY($lm, $bottomY);
        $pdf->Cell($halfW, 5, $sl, 0);
        $pdf->SetXY($lm + $pw / 2 + 5, $bottomY);
        $pdf->Cell($halfW, 5, $sl, 0);
        $bottomY += 6;
    }

    $bottomY += 5;
    $pdf->SetFont('Helvetica', '', 7);
    $pdf->SetXY($lm, $bottomY);
    $pdf->Cell($pw, 4, 'This payslip is computer-generated. For queries, contact the HR & Finance office.', 0, 0, 'C');

    // Page number
    $pdf->drawPageFooter(1, 1);

    // ── Output ──────────────────────────────────────────
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fullName);
    $filename = "Payslip_{$safeName}_{$monthEc}_{$yearEc}.pdf";

    switch ($outputMode) {
        case 'I':
            $pdf->preview($filename);
            break;
        case 'F':
            $pdf->saveTo($outputPath ?: (APP_ROOT . "/storage/uploads/{$filename}"));
            break;
        default:
            $pdf->download($filename);
            break;
    }
}
