<?php
/**
 * Income Tax Declaration PDF (Schedule A / Form E.P Income Tax)
 * Exact reproduction of Ethiopian government form.
 *
 * Columns (a–n):
 *   a) Seq# | b) TIN | c) Name | d) Start Date | e) Basic Salary
 *   f) Transport Allowance | g) Overtime | h) Other Allowance
 *   i) Gross Salary | j) Taxable Income | k) Income Tax
 *   l) Employee Pension (7%) | m) Total Deductions
 *   n) Net Pay
 *
 * 15 employees per page, with totals, tax calculation lines,
 * employees-removed section, and certification block.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/core/pdf.php';
require_once APP_ROOT . '/core/db.php';
require_once APP_ROOT . '/core/ethiopian_calendar.php';

/**
 * Generate Income Tax Declaration PDF for a payroll period.
 *
 * @param int    $periodId        Payroll period ID
 * @param string $outputMode      'D' = download, 'I' = inline, 'F' = save to file
 * @param string $outputPath      Path for 'F' mode
 */
function pdf_income_tax(int $periodId, string $outputMode = 'D', string $outputPath = ''): void
{
    // ── Fetch payroll period ───────────────────────────────
    $period = db_fetch_one(
        "SELECT * FROM hr_payroll_periods WHERE id = ?",
        [$periodId]
    );
    if (!$period) {
        die('Payroll period not found.');
    }

    $monthEc = $period['month_name_ec'] ?? ec_month_name((int)$period['month_ec']);
    $yearEc  = (string)$period['year_ec'];

    // ── Fetch payroll records with employee data ──────────
    $records = db_fetch_all(
        "SELECT pr.*, e.employee_id AS emp_code, e.first_name, e.father_name,
                e.grandfather_name, e.tin_number, e.start_date_ec,
                e.bank_name, e.bank_account
         FROM hr_payroll_records pr
         JOIN hr_employees e ON e.id = pr.employee_id
         WHERE pr.payroll_period_id = ?
         ORDER BY e.first_name, e.father_name",
        [$periodId]
    );

    $empCount   = count($records);
    $perPage    = 15;
    $totalPages = max(1, (int)ceil($empCount / $perPage));

    // ── Column widths (landscape A4 = ~277 usable) ───────
    // 14 columns
    $pw = 277; // 297 - 20 margins
    $cw = [
        8,   // a  Seq#
        18,  // b  TIN
        40,  // c  Name
        18,  // d  Start Date
        22,  // e  Basic Salary
        18,  // f  Transport
        16,  // g  Overtime
        18,  // h  Other Allowance
        22,  // i  Gross Salary
        22,  // j  Taxable Income
        20,  // k  Income Tax
        18,  // l  Employee Pension
        20,  // m  Total Deductions
        17,  // n  Net Pay
    ];
    // Adjust last column to fill remaining width
    $usedW = array_sum($cw);
    $cw[count($cw) - 1] += ($pw - $usedW);

    // ── Column headers ────────────────────────────────────
    $headers = [
        "Seq\n#\n(a)",
        "Employee\nTIN\n(b)",
        "Employee Name\n(c)",
        "Start\nDate EC\n(d)",
        "Basic\nSalary\n(e)",
        "Transport\nAllow.\n(f)",
        "Overtime\n\n(g)",
        "Other\nAllow.\n(h)",
        "Gross\nSalary\n(i)",
        "Taxable\nIncome\n(j)",
        "Income\nTax\n(k)",
        "Emp.\nPension 7%\n(l)",
        "Total\nDeduct.\n(m)",
        "Net\nPay\n(n)",
    ];

    // ── Compute grand totals ─────────────────────────────
    $totals = array_fill(0, 14, 0); // indices 4..13 = numeric columns
    foreach ($records as $r) {
        $totals[4]  += (float)$r['basic_salary'];
        $totals[5]  += (float)$r['transport_allowance'];
        $totals[6]  += (float)($r['overtime'] ?? 0);
        $totals[7]  += (float)$r['other_allowance'];
        $totals[8]  += (float)$r['gross_salary'];
        $totals[9]  += (float)$r['taxable_income'];
        $totals[10] += (float)$r['income_tax'];
        $totals[11] += (float)$r['employee_pension'];
        $totals[12] += (float)$r['total_deductions'];
        $totals[13] += (float)$r['net_salary'];
    }

    // ── Build PDF ────────────────────────────────────────
    $pdf = new SchoolPDF('L', 'mm', 'A4');

    for ($page = 0; $page < $totalPages; $page++) {
        $pdf->AddPage('L');

        // Header
        $pdf->SetY(8);
        $pdf->drawFormHeader(
            'Income Tax Declaration (Schedule A)',
            'Withholding Tax on Employment Income — Article 64 of the Income Tax Proclamation No. 979/2016',
            'Employees Withholding Tax on Income From Employment'
        );

        // Taxpayer info block
        $pdf->drawTaxpayerInfoTax($monthEc, $yearEc);
        $y = $pdf->GetY() + 2;

        // Schedule title
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetXY(10, $y);
        $pdf->Cell($pw, 4, 'Schedule A: Details of Employees', 0, 0, 'L');
        $y += 5;

        // Column header row
        $headerH = 12;
        $y = $pdf->drawHeaderRow($y, $cw, $headers, $headerH, 5);

        // Data rows
        $rowH    = 5;
        $startIdx = $page * $perPage;
        $endIdx   = min($startIdx + $perPage, $empCount);

        for ($i = $startIdx; $i < $endIdx; $i++) {
            $r   = $records[$i];
            $seq = $i + 1;
            $fullName = $r['first_name'] . ' ' . $r['father_name'] . ' ' . $r['grandfather_name'];

            $rowData = [
                (string)$seq,
                $r['tin_number'] ?? '',
                $fullName,
                $r['start_date_ec'] ?? '',
                $pdf->fmt((float)$r['basic_salary']),
                $pdf->fmt((float)$r['transport_allowance']),
                $pdf->fmt((float)($r['overtime'] ?? 0)),
                $pdf->fmt((float)$r['other_allowance']),
                $pdf->fmt((float)$r['gross_salary']),
                $pdf->fmt((float)$r['taxable_income']),
                $pdf->fmt((float)$r['income_tax']),
                $pdf->fmt((float)$r['employee_pension']),
                $pdf->fmt((float)$r['total_deductions']),
                $pdf->fmt((float)$r['net_salary']),
            ];

            $x = 10;
            foreach ($cw as $ci => $w) {
                $pdf->Rect($x, $y, $w, $rowH);
                $pdf->SetFont('Helvetica', '', 6);
                $align = ($ci <= 3) ? 'L' : 'R';
                if ($ci === 0) $align = 'C';
                $pdf->SetXY($x + 0.5, $y + 1);
                $pdf->Cell($w - 1, 3, $rowData[$ci], 0, 0, $align);
                $x += $w;
            }
            $y += $rowH;
        }

        // Empty rows to fill page
        for ($i = $endIdx - $startIdx; $i < $perPage; $i++) {
            $x = 10;
            foreach ($cw as $w) {
                $pdf->Rect($x, $y, $w, $rowH);
                $x += $w;
            }
            $y += $rowH;
        }

        // Totals row (only on last page)
        if ($page === $totalPages - 1) {
            $x = 10;
            $totalRow = [
                '', '', 'TOTAL', '',
                $pdf->fmt($totals[4]),
                $pdf->fmt($totals[5]),
                $pdf->fmt($totals[6]),
                $pdf->fmt($totals[7]),
                $pdf->fmt($totals[8]),
                $pdf->fmt($totals[9]),
                $pdf->fmt($totals[10]),
                $pdf->fmt($totals[11]),
                $pdf->fmt($totals[12]),
                $pdf->fmt($totals[13]),
            ];
            foreach ($cw as $ci => $w) {
                $pdf->Rect($x, $y, $w, $rowH);
                $pdf->SetFont('Helvetica', 'B', 6);
                $align = ($ci <= 3) ? 'L' : 'R';
                if ($ci === 2) $align = 'R';
                $pdf->SetXY($x + 0.5, $y + 1);
                $pdf->Cell($w - 1, 3, $totalRow[$ci], 0, 0, $align);
                $x += $w;
            }
            $y += $rowH + 2;

            // ── Tax Calculation Section ──
            $calcW  = $pw * 0.6;
            $calcX  = 10;
            $labelW = $calcW * 0.7;
            $valW   = $calcW * 0.3;

            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetXY($calcX, $y);
            $pdf->Cell($calcW, 4, 'Tax Calculation', 0);
            $y += 4;

            $calcLines = [
                ['10. Total Tax Withheld This Period (Sum of Column k)', $pdf->fmt($totals[10])],
                ['20. Penalty / Interest if applicable', '0.00'],
                ['30. Total Amount Due (Line 10 + Line 20)', $pdf->fmt($totals[10])],
            ];

            foreach ($calcLines as $line) {
                $pdf->Rect($calcX, $y, $labelW, $rowH);
                $pdf->Rect($calcX + $labelW, $y, $valW, $rowH);
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetXY($calcX + 1, $y + 1);
                $pdf->Cell($labelW - 2, 3, $line[0], 0);
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetXY($calcX + $labelW + 1, $y + 1);
                $pdf->Cell($valW - 2, 3, $line[1], 0, 0, 'R');
                $y += $rowH;
            }

            $y += 2;

            // Employees Removed section
            $y = $pdf->drawEmployeesRemovedSection($y);

            // Certification
            $pdf->drawCertificationSection($y);
        }

        // Page number
        $pdf->drawPageFooter($page + 1, $totalPages);
    }

    // ── Output ───────────────────────────────────────────
    $filename = "Income_Tax_Declaration_{$monthEc}_{$yearEc}.pdf";

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
