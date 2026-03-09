<?php
/**
 * Bank Salary Transfer Sheet PDF
 *
 * Columns:
 *   Seq# | Employee Name | Bank Name | Account Number | Net Salary
 *
 * With school header, period info, grand total, and bank officer signature section.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/core/pdf.php';
require_once APP_ROOT . '/core/db.php';
require_once APP_ROOT . '/core/ethiopian_calendar.php';
require_once APP_ROOT . '/core/payroll.php';

/**
 * Generate Bank Salary Transfer Sheet PDF.
 *
 * @param int    $periodId
 * @param string $outputMode  'D' = download, 'I' = inline, 'F' = save
 * @param string $outputPath  Path for 'F' mode
 */
function pdf_bank_transfer(int $periodId, string $outputMode = 'D', string $outputPath = ''): void
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
                e.grandfather_name, e.bank_name, e.bank_account
         FROM hr_payroll_records pr
         JOIN hr_employees e ON e.id = pr.employee_id
         WHERE pr.payroll_period_id = ?
         ORDER BY e.first_name, e.father_name",
        [$periodId]
    );

    $empCount   = count($records);
    $perPage    = 25;
    $totalPages = max(1, (int)ceil($empCount / $perPage));

    // ── Column widths (portrait A4 ≈ 190mm usable) ─────
    $pw = 190;
    $cw = [
        12,  // Seq#
        60,  // Employee Name
        35,  // Bank Name
        45,  // Account Number
        38,  // Net Salary
    ];
    $usedW = array_sum($cw);
    $cw[count($cw) - 1] += ($pw - $usedW);

    $headers = [
        "No.",
        "Employee Name",
        "Bank Name",
        "Account Number",
        "Net Salary (ETB)",
    ];

    // Grand total
    $grandTotal = 0;
    foreach ($records as $r) {
        $grandTotal += (float)$r['net_salary'];
    }

    // ── Build PDF ───────────────────────────────────────
    $pdf = new SchoolPDF('P', 'mm', 'A4');

    for ($page = 0; $page < $totalPages; $page++) {
        $pdf->AddPage('P');
        $y = 10;

        // ── School Header ──
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetXY(10, $y);
        $pdf->Cell($pw, 6, SCHOOL_NAME, 0, 0, 'C');
        $y += 7;
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(10, $y);
        $pdf->Cell($pw, 5, 'TIN: ' . SCHOOL_TIN . '  |  Tel: ' . SCHOOL_TELEPHONE, 0, 0, 'C');
        $y += 6;
        $pdf->SetXY(10, $y);
        $pdf->Cell($pw, 5, SCHOOL_SUBCITY . ', ' . SCHOOL_CITY . ', ' . SCHOOL_REGION, 0, 0, 'C');
        $y += 8;

        // Divider line
        $pdf->Line(10, $y, 10 + $pw, $y);
        $y += 3;

        // Title
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetXY(10, $y);
        $pdf->Cell($pw, 6, 'BANK SALARY TRANSFER SHEET', 0, 0, 'C');
        $y += 8;

        // Period info
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(10, $y);
        $pdf->Cell($pw / 2, 5, "Payroll Period: {$monthEc} {$yearEc} E.C.", 0, 0, 'L');
        $pdf->Cell($pw / 2, 5, "Date: " . date('d/m/Y'), 0, 0, 'R');
        $y += 7;

        // Column header row
        $headerH = 7;
        $x = 10;
        $pdf->SetFont('Helvetica', 'B', 8);
        foreach ($cw as $ci => $w) {
            $pdf->Rect($x, $y, $w, $headerH);
            $pdf->SetXY($x + 0.5, $y + 2);
            $pdf->Cell($w - 1, 3, $headers[$ci], 0, 0, 'C');
            $x += $w;
        }
        $y += $headerH;

        // Data rows
        $rowH     = 6;
        $startIdx = $page * $perPage;
        $endIdx   = min($startIdx + $perPage, $empCount);

        for ($i = $startIdx; $i < $endIdx; $i++) {
            $r   = $records[$i];
            $seq = $i + 1;
            $fullName = $r['first_name'] . ' ' . $r['father_name'] . ' ' . $r['grandfather_name'];

            $rowData = [
                (string)$seq,
                $fullName,
                $r['bank_name'] ?? '',
                $r['bank_account'] ?? '',
                $pdf->fmt((float)$r['net_salary']),
            ];

            // Zebra striping
            $x = 10;
            if (($i - $startIdx) % 2 === 1) {
                $pdf->SetFillColor(245, 245, 245);
                $pdf->Rect($x, $y, $pw, $rowH, 'F');
            }

            foreach ($cw as $ci => $w) {
                $pdf->Rect($x, $y, $w, $rowH);
                $pdf->SetFont('Helvetica', '', 7.5);
                $align = ($ci === 4) ? 'R' : 'L';
                if ($ci === 0) $align = 'C';
                $pdf->SetXY($x + 1, $y + 1.5);
                $pdf->Cell($w - 2, 3, $rowData[$ci], 0, 0, $align);
                $x += $w;
            }
            $y += $rowH;
        }

        // Totals row on last page
        if ($page === $totalPages - 1) {
            $x = 10;
            $pdf->SetFont('Helvetica', 'B', 8);
            // Merge first 4 cols for "TOTAL"
            $mergedW = $cw[0] + $cw[1] + $cw[2] + $cw[3];
            $pdf->Rect($x, $y, $mergedW, $rowH);
            $pdf->SetXY($x + 1, $y + 1.5);
            $pdf->Cell($mergedW - 2, 3, 'GRAND TOTAL', 0, 0, 'R');
            $x += $mergedW;
            $pdf->Rect($x, $y, $cw[4], $rowH);
            $pdf->SetXY($x + 1, $y + 1.5);
            $pdf->Cell($cw[4] - 2, 3, $pdf->fmt($grandTotal), 0, 0, 'R');
            $y += $rowH + 2;

            // Amount in words
            $pdf->SetFont('Helvetica', 'I', 8);
            $pdf->SetXY(10, $y);
            $pdf->Cell($pw, 5, 'Amount in Words: ' . payroll_amount_in_words($grandTotal), 0);
            $y += 8;

            // Total employees
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY(10, $y);
            $pdf->Cell($pw, 5, "Total Number of Employees: {$empCount}", 0);
            $y += 12;

            // ── Signature Section ──
            $colW = $pw / 3;
            $sigLabels = [
                ['Prepared By:', '', 'Name: ___________________', 'Signature: ______________', 'Date: ___________________'],
                ['Checked By:', '', 'Name: ___________________', 'Signature: ______________', 'Date: ___________________'],
                ['Approved By:', '', 'Name: ___________________', 'Signature: ______________', 'Date: ___________________'],
            ];

            $pdf->SetFont('Helvetica', '', 8);
            foreach ([0, 1, 2] as $col) {
                $sx = 10 + $col * $colW;
                $sy = $y;
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetXY($sx, $sy);
                $pdf->Cell($colW, 4, $sigLabels[$col][0], 0);
                $pdf->SetFont('Helvetica', '', 8);
                for ($li = 2; $li <= 4; $li++) {
                    $sy += 6;
                    $pdf->SetXY($sx, $sy);
                    $pdf->Cell($colW, 4, $sigLabels[$col][$li], 0);
                }
            }
        }

        // Page number
        $pdf->drawPageFooter($page + 1, $totalPages);
    }

    // ── Output ──────────────────────────────────────────
    $filename = "Bank_Transfer_Sheet_{$monthEc}_{$yearEc}.pdf";

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
