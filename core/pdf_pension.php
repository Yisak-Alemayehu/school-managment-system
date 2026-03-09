<?php
/**
 * Pension Contribution Declaration PDF
 * Exact reproduction of Ethiopian government pension form.
 *
 * Columns (a–h):
 *   a) Seq# | b) TIN | c) Name | d) Pension Number
 *   e) Basic Salary | f) Employee Pension 7%
 *   g) Employer Pension 11% | h) Total Pension 18%
 *
 * 15 employees per page, with totals, calculation lines 10-50,
 * and certification block.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/core/pdf.php';
require_once APP_ROOT . '/core/db.php';
require_once APP_ROOT . '/core/ethiopian_calendar.php';

/**
 * Generate Pension Contribution Declaration PDF.
 *
 * @param int    $periodId
 * @param string $outputMode  'D' = download, 'I' = inline, 'F' = save
 * @param string $outputPath  Path for 'F' mode
 */
function pdf_pension(int $periodId, string $outputMode = 'D', string $outputPath = ''): void
{
    // ── Fetch payroll period ────────────────────────────
    $period = db_fetch_one(
        "SELECT * FROM hr_payroll_periods WHERE id = ?",
        [$periodId]
    );
    if (!$period) {
        die('Payroll period not found.');
    }

    $monthEc = $period['month_name_ec'] ?? ec_month_name((int)$period['month_ec']);
    $yearEc  = (string)$period['year_ec'];

    // ── Fetch payroll records with employee data ────────
    $records = db_fetch_all(
        "SELECT pr.*, e.employee_id AS emp_code, e.first_name, e.father_name,
                e.grandfather_name, e.tin_number, e.national_id
         FROM hr_payroll_records pr
         JOIN hr_employees e ON e.id = pr.employee_id
         WHERE pr.payroll_period_id = ?
         ORDER BY e.first_name, e.father_name",
        [$periodId]
    );

    $empCount   = count($records);
    $perPage    = 15;
    $totalPages = max(1, (int)ceil($empCount / $perPage));

    // ── Column widths (landscape A4 ≈ 277mm usable) ────
    $pw = 277;
    $cw = [
        12,   // a  Seq#
        30,   // b  TIN
        70,   // c  Name
        35,   // d  Pension Number
        35,   // e  Basic Salary
        30,   // f  Employee Pension 7%
        32,   // g  Employer Pension 11%
        33,   // h  Total Pension 18%
    ];
    $usedW = array_sum($cw);
    $cw[count($cw) - 1] += ($pw - $usedW);

    // ── Column headers ──────────────────────────────────
    $headers = [
        "Seq\n#\n(a)",
        "Employee\nTIN\n(b)",
        "Employee Name\n(Father  Grandfather)\n(c)",
        "Pension\nNumber\n(d)",
        "Basic\nSalary\n(e)",
        "Employee\nPension 7%\n(f)",
        "Employer\nPension 11%\n(g)",
        "Total\nPension 18%\n(h)",
    ];

    // ── Grand totals ────────────────────────────────────
    $totals = array_fill(0, 8, 0);
    foreach ($records as $r) {
        $totals[4] += (float)$r['basic_salary'];
        $totals[5] += (float)$r['employee_pension'];
        $totals[6] += (float)$r['employer_pension'];
        $totals[7] += (float)($r['total_pension'] ?? ((float)$r['employee_pension'] + (float)$r['employer_pension']));
    }

    // ── Build PDF ───────────────────────────────────────
    $pdf = new SchoolPDF('L', 'mm', 'A4');

    for ($page = 0; $page < $totalPages; $page++) {
        $pdf->AddPage('L');

        // Header
        $pdf->SetY(8);
        $pdf->drawFormHeader(
            'Pension Contribution Declaration',
            'Private Organization Pension Contribution — Proclamation No. 715/2011',
            'Monthly Pension Withholding Declaration'
        );

        // Taxpayer info block (pension variant)
        $pdf->drawTaxpayerInfoPension($monthEc, $yearEc);
        $y = $pdf->GetY() + 2;

        // Schedule title
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetXY(10, $y);
        $pdf->Cell($pw, 4, 'Schedule: Details of Employees and Pension Contributions', 0, 0, 'L');
        $y += 5;

        // Column header row
        $headerH = 12;
        $y = $pdf->drawHeaderRow($y, $cw, $headers, $headerH, 5.5);

        // Data rows
        $rowH     = 5.5;
        $startIdx = $page * $perPage;
        $endIdx   = min($startIdx + $perPage, $empCount);

        for ($i = $startIdx; $i < $endIdx; $i++) {
            $r   = $records[$i];
            $seq = $i + 1;
            $fullName = $r['first_name'] . ' ' . $r['father_name'] . ' ' . $r['grandfather_name'];
            $totalPen = (float)($r['total_pension'] ?? ((float)$r['employee_pension'] + (float)$r['employer_pension']));

            $rowData = [
                (string)$seq,
                $r['tin_number'] ?? '',
                $fullName,
                $r['national_id'] ?? '', // pension number mapped to national_id if available
                $pdf->fmt((float)$r['basic_salary']),
                $pdf->fmt((float)$r['employee_pension']),
                $pdf->fmt((float)$r['employer_pension']),
                $pdf->fmt($totalPen),
            ];

            $x = 10;
            foreach ($cw as $ci => $w) {
                $pdf->Rect($x, $y, $w, $rowH);
                $pdf->SetFont('Helvetica', '', 6.5);
                $align = ($ci <= 3) ? 'L' : 'R';
                if ($ci === 0) $align = 'C';
                $pdf->SetXY($x + 0.5, $y + 1.2);
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

        // Totals row & calculation section on last page
        if ($page === $totalPages - 1) {
            // Totals row
            $x = 10;
            $totalRow = [
                '', '', 'TOTAL', '',
                $pdf->fmt($totals[4]),
                $pdf->fmt($totals[5]),
                $pdf->fmt($totals[6]),
                $pdf->fmt($totals[7]),
            ];
            foreach ($cw as $ci => $w) {
                $pdf->Rect($x, $y, $w, $rowH);
                $pdf->SetFont('Helvetica', 'B', 6.5);
                $align = ($ci <= 3) ? 'L' : 'R';
                if ($ci === 2) $align = 'R';
                $pdf->SetXY($x + 0.5, $y + 1.2);
                $pdf->Cell($w - 1, 3, $totalRow[$ci], 0, 0, $align);
                $x += $w;
            }
            $y += $rowH + 3;

            // ── Pension Calculation Section ──
            $calcW  = $pw * 0.6;
            $calcX  = 10;
            $labelW = $calcW * 0.65;
            $valW   = $calcW * 0.35;

            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetXY($calcX, $y);
            $pdf->Cell($calcW, 4, 'Pension Calculation', 0);
            $y += 4;

            $lineH = 5;
            $calcLines = [
                ['10. Total Employee Pension (7%) — Sum of Column f',  $pdf->fmt($totals[5])],
                ['20. Total Employer Pension (11%) — Sum of Column g', $pdf->fmt($totals[6])],
                ['30. Total Combined Pension (18%) — Sum of Column h', $pdf->fmt($totals[7])],
                ['40. Penalty / Interest if applicable',               '0.00'],
                ['50. Total Amount Due (Line 30 + Line 40)',           $pdf->fmt($totals[7])],
            ];

            foreach ($calcLines as $line) {
                $pdf->Rect($calcX, $y, $labelW, $lineH);
                $pdf->Rect($calcX + $labelW, $y, $valW, $lineH);
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetXY($calcX + 1, $y + 1);
                $pdf->Cell($labelW - 2, 3, $line[0], 0);
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetXY($calcX + $labelW + 1, $y + 1);
                $pdf->Cell($valW - 2, 3, $line[1], 0, 0, 'R');
                $y += $lineH;
            }

            $y += 2;

            // Certification section
            $pdf->drawCertificationSection($y);
        }

        // Page number
        $pdf->drawPageFooter($page + 1, $totalPages);
    }

    // ── Output ──────────────────────────────────────────
    $filename = "Pension_Contribution_{$monthEc}_{$yearEc}.pdf";

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
