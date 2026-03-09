<?php
/**
 * Employment Contract PDF Generator
 *
 * Generates a formal employment contract in Ethiopian format.
 * Auto-generated when a new employee is created.
 *
 * Sections:
 *   1. School & Employee identification
 *   2. Position and Department
 *   3. Start Date and Employment Type
 *   4. Salary & Allowances breakdown
 *   5. Terms & Conditions
 *   6. Signature blocks (employer + employee)
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/core/pdf.php';
require_once APP_ROOT . '/core/db.php';
require_once APP_ROOT . '/core/ethiopian_calendar.php';
require_once APP_ROOT . '/core/payroll.php';

/**
 * Generate Employment Contract PDF for an employee.
 *
 * @param int    $employeeId   hr_employees.id
 * @param string $outputMode   'D' = download, 'I' = inline, 'F' = save
 * @param string $outputPath   Path for 'F' mode
 */
function pdf_contract(int $employeeId, string $outputMode = 'D', string $outputPath = ''): void
{
    // ── Fetch employee ──────────────────────────────────
    $emp = db_fetch_one(
        "SELECT e.*, d.name AS department_name
         FROM hr_employees e
         LEFT JOIN hr_departments d ON d.id = e.department_id
         WHERE e.id = ?",
        [$employeeId]
    );
    if (!$emp) {
        die('Employee not found.');
    }

    $fullName    = $emp['first_name'] . ' ' . $emp['father_name'] . ' ' . $emp['grandfather_name'];
    $basic       = (float)$emp['basic_salary'];
    $transport   = (float)$emp['transport_allowance'];
    $other       = (float)$emp['other_allowance'];
    $totalSalary = $basic + $transport + $other;

    // ── Build PDF (Portrait A4) ─────────────────────────
    $pdf = new SchoolPDF('P', 'mm', 'A4');
    $pdf->AddPage('P');
    $pw = 190; // usable width
    $y  = 12;
    $lm = 10;

    // ── School Header ───────────────────────────────────
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 7, SCHOOL_NAME, 0, 0, 'C');
    $y += 8;

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, SCHOOL_SUBCITY . ', ' . SCHOOL_CITY . ', ' . SCHOOL_REGION . '  |  Tel: ' . SCHOOL_TELEPHONE, 0, 0, 'C');
    $y += 6;
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, 'TIN: ' . SCHOOL_TIN . '  |  Woreda: ' . SCHOOL_WOREDA, 0, 0, 'C');
    $y += 8;

    // Divider
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Line($lm, $y, $lm + $pw, $y);
    $y += 1;
    $pdf->Line($lm, $y, $lm + $pw, $y);
    $y += 5;

    // ── Title ───────────────────────────────────────────
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 7, 'EMPLOYMENT CONTRACT', 0, 0, 'C');
    $y += 8;
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, '(Executed in accordance with Ethiopian Labour Proclamation No. 1156/2019)', 0, 0, 'C');
    $y += 10;

    // ── Contract Reference & Date ───────────────────────
    $contractRef = 'CTR-' . $emp['employee_id'];
    $contractDate = $emp['start_date_ec'] ?: date('d/m/Y');

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw / 2, 5, 'Contract Ref: ' . $contractRef, 0);
    $pdf->Cell($pw / 2, 5, 'Date: ' . $contractDate, 0, 0, 'R');
    $y += 8;

    // ── Section 1: Parties ──────────────────────────────
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, '1. PARTIES TO THE CONTRACT', 0);
    $y += 7;

    $pdf->SetFont('Helvetica', '', 9);
    $lineH = 5;

    $pdf->SetXY($lm, $y);
    $pdf->MultiCell($pw, $lineH,
        "This Employment Contract is entered into between:\n\n"
        . "EMPLOYER: " . SCHOOL_NAME . "\n"
        . "Address: " . SCHOOL_WOREDA . ", " . SCHOOL_SUBCITY . ", " . SCHOOL_CITY . ", " . SCHOOL_REGION . "\n"
        . "TIN: " . SCHOOL_TIN . "  |  Telephone: " . SCHOOL_TELEPHONE . "\n\n"
        . "EMPLOYEE: {$fullName}\n"
        . "Employee ID: " . $emp['employee_id'] . "\n"
        . "TIN: " . ($emp['tin_number'] ?: 'N/A') . "  |  Phone: " . ($emp['phone'] ?: 'N/A') . "\n"
        . "Address: " . ($emp['address'] ?: 'N/A'),
        0, 'L');
    $y = $pdf->GetY() + 5;

    // ── Section 2: Position ─────────────────────────────
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, '2. POSITION AND DUTIES', 0);
    $y += 7;

    $empType = ucfirst(str_replace('_', ' ', $emp['employment_type']));

    $pdf->SetFont('Helvetica', '', 9);
    $labelW = 45;
    $valW   = $pw - $labelW;

    $positionFields = [
        ['Position / Title:', $emp['position'] ?? 'N/A'],
        ['Department:', $emp['department_name'] ?? 'N/A'],
        ['Role Category:', ucfirst($emp['role'])],
        ['Employment Type:', $empType],
    ];

    foreach ($positionFields as $f) {
        $pdf->SetXY($lm, $y);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($labelW, $lineH, $f[0], 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell($valW, $lineH, $f[1], 0);
        $y += $lineH + 1;
    }
    $y += 3;

    // ── Section 3: Commencement & Duration ──────────────
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, '3. COMMENCEMENT AND DURATION', 0);
    $y += 7;

    $pdf->SetFont('Helvetica', '', 9);
    $dateFields = [
        ['Start Date (E.C.):', $emp['start_date_ec'] ?: 'N/A'],
        ['Start Date (G.C.):', $emp['start_date_gregorian'] ?? 'N/A'],
    ];
    if ($emp['end_date_ec'] || $emp['end_date_gregorian']) {
        $dateFields[] = ['End Date (E.C.):', $emp['end_date_ec'] ?: 'N/A'];
        $dateFields[] = ['End Date (G.C.):', $emp['end_date_gregorian'] ?? 'N/A'];
    }

    foreach ($dateFields as $f) {
        $pdf->SetXY($lm, $y);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($labelW, $lineH, $f[0], 0);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell($valW, $lineH, $f[1], 0);
        $y += $lineH + 1;
    }
    $y += 3;

    // ── Section 4: Compensation ─────────────────────────
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, '4. COMPENSATION AND BENEFITS', 0);
    $y += 7;

    // Salary table
    $salaryItems = [
        ['Basic Salary',         $basic],
        ['Transport Allowance',  $transport],
        ['Other Allowance',      $other],
    ];

    $colLbl = $pw * 0.55;
    $colAmt = $pw * 0.45;

    // Table header
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Rect($lm, $y, $colLbl, 6);
    $pdf->Rect($lm + $colLbl, $y, $colAmt, 6);
    $pdf->SetXY($lm + 1, $y + 1.5);
    $pdf->Cell($colLbl - 2, 3, 'Component', 0);
    $pdf->SetXY($lm + $colLbl + 1, $y + 1.5);
    $pdf->Cell($colAmt - 2, 3, 'Amount (ETB)', 0, 0, 'R');
    $y += 6;

    $pdf->SetFont('Helvetica', '', 9);
    foreach ($salaryItems as $item) {
        $pdf->Rect($lm, $y, $colLbl, 6);
        $pdf->Rect($lm + $colLbl, $y, $colAmt, 6);
        $pdf->SetXY($lm + 1, $y + 1.5);
        $pdf->Cell($colLbl - 2, 3, $item[0], 0);
        $pdf->SetXY($lm + $colLbl + 1, $y + 1.5);
        $pdf->Cell($colAmt - 2, 3, number_format($item[1], 2), 0, 0, 'R');
        $y += 6;
    }

    // Total row
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Rect($lm, $y, $colLbl, 6);
    $pdf->Rect($lm + $colLbl, $y, $colAmt, 6);
    $pdf->SetXY($lm + 1, $y + 1.5);
    $pdf->Cell($colLbl - 2, 3, 'Total Monthly Compensation', 0);
    $pdf->SetXY($lm + $colLbl + 1, $y + 1.5);
    $pdf->Cell($colAmt - 2, 3, number_format($totalSalary, 2), 0, 0, 'R');
    $y += 6;

    // Amount in words
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetXY($lm, $y + 1);
    $pdf->Cell($pw, 4, 'Total in words: ' . payroll_amount_in_words($totalSalary), 0);
    $y += 8;

    // Deductions note
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetXY($lm, $y);
    $pdf->MultiCell($pw, 4,
        'Statutory deductions (Income Tax and Pension Contribution) shall be withheld '
        . 'from the above compensation in accordance with Ethiopian tax laws and the '
        . 'Private Organization Employees Pension Proclamation No. 715/2011.');
    $y = $pdf->GetY() + 5;

    // ── Section 5: Terms & Conditions ────────────────────
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, '5. TERMS AND CONDITIONS', 0);
    $y += 7;

    $pdf->SetFont('Helvetica', '', 8.5);
    $terms = [
        'a) The Employee shall perform duties assigned by the Employer related to the position described above.',
        'b) Working hours shall be in accordance with Ethiopian Labour Law (8 hours/day, 48 hours/week).',
        'c) The Employee is entitled to annual leave, sick leave, and other leave as per Labour Proclamation No. 1156/2019.',
        'd) Either party may terminate this contract with 30 days written notice as per the Labour Law.',
        'e) The Employee shall maintain confidentiality of school records and student information.',
        'f) Any disputes shall be resolved through the procedures established by the Labour Law.',
    ];

    foreach ($terms as $term) {
        $pdf->SetXY($lm + 3, $y);
        $pdf->MultiCell($pw - 6, 4, $term, 0, 'L');
        $y = $pdf->GetY() + 1;
    }
    $y += 5;

    // ── Check if signatures fit, add new page if needed ──
    if ($y > 240) {
        $pdf->AddPage('P');
        $y = 15;
    }

    // ── Section 6: Signatures ────────────────────────────
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 5, '6. SIGNATURES', 0);
    $y += 7;

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY($lm, $y);
    $pdf->MultiCell($pw, 4,
        'Both parties have read and understood the terms of this contract and agree to abide by them.');
    $y = $pdf->GetY() + 8;

    // Two-column signature blocks
    $halfW = $pw / 2 - 5;

    // Employer column
    $ex = $lm;
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetXY($ex, $y);
    $pdf->Cell($halfW, 5, 'FOR THE EMPLOYER', 'B');

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY($ex, $y + 8);
    $pdf->Cell($halfW, 5, 'Name: ________________________________');
    $pdf->SetXY($ex, $y + 16);
    $pdf->Cell($halfW, 5, 'Title: ________________________________');
    $pdf->SetXY($ex, $y + 24);
    $pdf->Cell($halfW, 5, 'Signature: ____________________________');
    $pdf->SetXY($ex, $y + 32);
    $pdf->Cell($halfW, 5, 'Date: _________________________________');
    $pdf->SetXY($ex, $y + 42);
    $pdf->Cell($halfW, 5, 'Seal:');
    $pdf->Rect($ex + 15, $y + 40, 25, 15); // Seal box

    // Employee column
    $empX = $lm + $pw / 2 + 5;
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetXY($empX, $y);
    $pdf->Cell($halfW, 5, 'THE EMPLOYEE', 'B');

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY($empX, $y + 8);
    $pdf->Cell($halfW, 5, 'Name: ' . $fullName);
    $pdf->SetXY($empX, $y + 16);
    $pdf->Cell($halfW, 5, 'Employee ID: ' . $emp['employee_id']);
    $pdf->SetXY($empX, $y + 24);
    $pdf->Cell($halfW, 5, 'Signature: ____________________________');
    $pdf->SetXY($empX, $y + 32);
    $pdf->Cell($halfW, 5, 'Date: _________________________________');
    $pdf->SetXY($empX, $y + 42);
    $pdf->Cell($halfW, 5, 'Fingerprint:');
    $pdf->Rect($empX + 25, $y + 40, 20, 15); // fingerprint box

    // Page number
    $pdf->drawPageFooter(1, 1);

    // ── Output ──────────────────────────────────────────
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fullName);
    $filename = "Contract_{$safeName}_{$emp['employee_id']}.pdf";

    switch ($outputMode) {
        case 'I':
            $pdf->preview($filename);
            break;
        case 'F':
            $pdf->saveTo($outputPath ?: (APP_ROOT . "/storage/uploads/contracts/{$filename}"));
            break;
        default:
            $pdf->download($filename);
            break;
    }
}
