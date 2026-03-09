<?php
/**
 * Payroll Calculation Service
 * Urji Beri School Management System — HR Module
 *
 * Ethiopian Income Tax Formula (verified against actual payroll data):
 *   0 - 2,000     → 0%   (exempt)
 *   2,001 - 4,000 → 15%  - 300
 *   4,001 - 7,000 → 20%  - 500.5
 *   7,001 - 10,000→ 25%  - 850.5
 *  10,001 - 14,000→ 30%  - 1,350
 *  Above 14,000   → 35%  - 2,050
 *
 * Pension: Employee 7%, Employer 11%, Total 18%
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Calculate Ethiopian Employment Income Tax.
 *
 * Uses the EXACT formula verified against the Urji Beri School payroll Excel file.
 *
 * @param float $taxableIncome The taxable income (basic salary + allowances)
 * @return float Income tax amount
 */
function payroll_calculate_income_tax(float $taxableIncome): float {
    if ($taxableIncome <= 0) {
        return 0;
    }

    if ($taxableIncome <= 2000) {
        return 0;
    }

    if ($taxableIncome <= 4000) {
        return round($taxableIncome * 0.15 - 300, 2);
    }

    if ($taxableIncome <= 7000) {
        return round($taxableIncome * 0.20 - 500.5, 2);
    }

    if ($taxableIncome <= 10000) {
        return round($taxableIncome * 0.25 - 850.5, 2);
    }

    if ($taxableIncome <= 14000) {
        return round($taxableIncome * 0.30 - 1350, 2);
    }

    return round($taxableIncome * 0.35 - 2050, 2);
}

/**
 * Calculate Employee Pension (7% of basic salary).
 *
 * @param float $basicSalary
 * @return float
 */
function payroll_employee_pension(float $basicSalary): float {
    return round($basicSalary * 0.07, 2);
}

/**
 * Calculate Employer Pension (11% of basic salary).
 *
 * @param float $basicSalary
 * @return float
 */
function payroll_employer_pension(float $basicSalary): float {
    return round($basicSalary * 0.11, 2);
}

/**
 * Calculate Total Pension = Employee (7%) + Employer (11%) = 18%.
 *
 * @param float $basicSalary
 * @return float
 */
function payroll_total_pension(float $basicSalary): float {
    return round($basicSalary * 0.18, 2);
}

/**
 * Calculate pro-rated salary for mid-month joiners/leavers.
 *
 * @param float  $basicSalary   Full monthly salary
 * @param int    $workingDays   Total working days in the month
 * @param int    $daysWorked    Actual days worked
 * @return float Pro-rated salary
 */
function payroll_prorate_salary(float $basicSalary, int $workingDays, int $daysWorked): float {
    if ($workingDays <= 0) return 0;
    if ($daysWorked >= $workingDays) return $basicSalary;
    return round(($basicSalary / $workingDays) * $daysWorked, 2);
}

/**
 * Calculate complete payroll for a single employee.
 *
 * @param float $basicSalary       Full monthly basic salary
 * @param float $transportAllowance
 * @param float $otherAllowance
 * @param int   $workingDays       Total working days in pay period
 * @param int   $daysWorked        Actual days employee worked
 * @param float $overtime          Overtime pay amount
 * @param float $otherDeductions   Additional deductions (loans, penalties, etc.)
 * @return array Payroll breakdown
 */
function payroll_calculate(
    float $basicSalary,
    float $transportAllowance = 0,
    float $otherAllowance = 0,
    int   $workingDays = 30,
    int   $daysWorked = 30,
    float $overtime = 0,
    float $otherDeductions = 0
): array {
    // Pro-rate salary if partial month
    $proratedSalary = payroll_prorate_salary($basicSalary, $workingDays, $daysWorked);

    // Gross salary = prorated basic + allowances + overtime
    $grossSalary = round($proratedSalary + $transportAllowance + $otherAllowance + $overtime, 2);

    // Taxable income = gross salary (all components are taxable in Ethiopia)
    $taxableIncome = $grossSalary;

    // Income tax calculated on taxable income
    $incomeTax = payroll_calculate_income_tax($taxableIncome);

    // Pension calculated on prorated basic salary only (not allowances)
    $employeePension = payroll_employee_pension($proratedSalary);
    $employerPension = payroll_employer_pension($proratedSalary);
    $totalPension = round($employeePension + $employerPension, 2);

    // Total deductions = income tax + employee pension + other deductions
    $totalDeductions = round($incomeTax + $employeePension + $otherDeductions, 2);

    // Net salary = gross - deductions
    $netSalary = round($grossSalary - $totalDeductions, 2);

    return [
        'basic_salary'        => $basicSalary,
        'prorated_salary'     => $proratedSalary,
        'transport_allowance' => $transportAllowance,
        'other_allowance'     => $otherAllowance,
        'overtime'            => $overtime,
        'gross_salary'        => $grossSalary,
        'taxable_income'      => $taxableIncome,
        'income_tax'          => $incomeTax,
        'employee_pension'    => $employeePension,
        'employer_pension'    => $employerPension,
        'total_pension'       => $totalPension,
        'other_deductions'    => $otherDeductions,
        'total_deductions'    => $totalDeductions,
        'net_salary'          => $netSalary,
        'working_days'        => $workingDays,
        'days_worked'         => $daysWorked,
    ];
}

/**
 * Get active recurring allowances for an employee for a given payroll period.
 *
 * @param int    $employeeId
 * @param string $periodStart Gregorian start date YYYY-MM-DD
 * @param string $periodEnd   Gregorian end date YYYY-MM-DD
 * @return array ['transport' => float, 'other' => float, 'total' => float, 'items' => array]
 */
function payroll_get_employee_allowances(int $employeeId, string $periodStart, string $periodEnd): array {
    $rows = db_fetch_all(
        "SELECT * FROM hr_employee_allowances
         WHERE employee_id = ? AND status = 'active' AND is_permanent = 1
           AND (start_date IS NULL OR start_date <= ?)
           AND (end_date IS NULL OR end_date >= ?)
         ORDER BY allowance_type",
        [$employeeId, $periodEnd, $periodStart]
    );

    $transport = 0;
    $other = 0;
    $items = [];

    foreach ($rows as $row) {
        $amount = (float)$row['amount'];
        if ($row['allowance_type'] === 'transport') {
            $transport += $amount;
        } else {
            $other += $amount;
        }
        $items[] = $row;
    }

    return [
        'transport' => round($transport, 2),
        'other'     => round($other, 2),
        'total'     => round($transport + $other, 2),
        'items'     => $items,
    ];
}

/**
 * Count working days an employee actually attended (present, late, half_day) in a period.
 *
 * @param int    $employeeId
 * @param string $periodStart YYYY-MM-DD
 * @param string $periodEnd   YYYY-MM-DD
 * @return array ['days_present' => int, 'days_absent' => int, 'days_late' => int, 'days_half' => int, 'days_leave' => int]
 */
function payroll_attendance_summary(int $employeeId, string $periodStart, string $periodEnd): array {
    $rows = db_fetch_all(
        "SELECT status, COUNT(*) as cnt FROM hr_attendance
         WHERE employee_id = ? AND date_gregorian BETWEEN ? AND ?
         GROUP BY status",
        [$employeeId, $periodStart, $periodEnd]
    );

    $summary = [
        'days_present' => 0,
        'days_absent'  => 0,
        'days_late'    => 0,
        'days_half'    => 0,
        'days_leave'   => 0,
        'days_holiday' => 0,
    ];

    foreach ($rows as $row) {
        switch ($row['status']) {
            case 'present': $summary['days_present'] = (int)$row['cnt']; break;
            case 'absent':  $summary['days_absent']  = (int)$row['cnt']; break;
            case 'late':    $summary['days_late']     = (int)$row['cnt']; break;
            case 'half_day':$summary['days_half']     = (int)$row['cnt']; break;
            case 'leave':   $summary['days_leave']    = (int)$row['cnt']; break;
            case 'holiday': $summary['days_holiday']  = (int)$row['cnt']; break;
        }
    }

    return $summary;
}

/**
 * Generate payroll for all active employees for a given period.
 *
 * @param int $periodId  Payroll period ID
 * @return array ['success' => int, 'errors' => array]
 */
function payroll_generate(int $periodId): array {
    $period = db_fetch_one("SELECT * FROM hr_payroll_periods WHERE id = ?", [$periodId]);
    if (!$period) {
        return ['success' => 0, 'errors' => ['Payroll period not found.']];
    }

    if ($period['status'] !== 'draft') {
        return ['success' => 0, 'errors' => ['Payroll already generated for this period.']];
    }

    // Get period dates for pro-rating
    $periodStart = $period['start_date'];
    $periodEnd   = $period['end_date'];

    // Calculate total working days in period
    $totalWorkingDays = ec_working_days($periodStart, $periodEnd);
    if ($totalWorkingDays <= 0) $totalWorkingDays = 30; // fallback

    // Get all active employees (not soft-deleted, include those who joined or left during period)
    $employees = db_fetch_all(
        "SELECT * FROM hr_employees
         WHERE deleted_at IS NULL
           AND status = 'active'
           AND start_date_gregorian <= ?
           AND (end_date_gregorian IS NULL OR end_date_gregorian >= ?)
         ORDER BY employee_id",
        [$periodEnd, $periodStart]
    );

    $success = 0;
    $errors = [];

    db_transaction(function() use ($employees, $periodId, $periodStart, $periodEnd, $totalWorkingDays, &$success, &$errors) {
        foreach ($employees as $emp) {
            // Check if already processed
            $existing = db_fetch_one(
                "SELECT id FROM hr_payroll_records WHERE payroll_period_id = ? AND employee_id = ?",
                [$periodId, $emp['id']]
            );
            if ($existing) {
                continue;
            }

            // Calculate days worked (pro-rating for mid-month joiners/leavers)
            $empStart = max($periodStart, $emp['start_date_gregorian']);
            $empEnd = $emp['end_date_gregorian']
                ? min($periodEnd, $emp['end_date_gregorian'])
                : $periodEnd;

            $daysWorked = ec_working_days($empStart, $empEnd);
            if ($daysWorked <= 0) continue;

            // Get recurring allowances from hr_employee_allowances table
            $allowances = payroll_get_employee_allowances((int)$emp['id'], $periodStart, $periodEnd);

            // Use allowances table totals; fall back to fixed employee fields if no records
            $transportAllowance = $allowances['transport'] > 0
                ? $allowances['transport']
                : (float)$emp['transport_allowance'];
            $otherAllowance = $allowances['other'] > 0
                ? $allowances['other']
                : (float)$emp['other_allowance'];

            // Calculate payroll with overtime and deductions
            $payroll = payroll_calculate(
                (float)$emp['basic_salary'],
                $transportAllowance,
                $otherAllowance,
                $totalWorkingDays,
                $daysWorked
            );

            // Determine payment method from employee bank info
            $paymentMethod = !empty($emp['bank_account']) ? 'bank_transfer' : 'cash';

            // Insert payroll record
            db_insert('hr_payroll_records', [
                'payroll_period_id'   => $periodId,
                'employee_id'         => $emp['id'],
                'working_days'        => $totalWorkingDays,
                'days_worked'         => $daysWorked,
                'basic_salary'        => $payroll['basic_salary'],
                'prorated_salary'     => $payroll['prorated_salary'],
                'transport_allowance' => $payroll['transport_allowance'],
                'other_allowance'     => $payroll['other_allowance'],
                'overtime'            => $payroll['overtime'],
                'gross_salary'        => $payroll['gross_salary'],
                'taxable_income'      => $payroll['taxable_income'],
                'income_tax'          => $payroll['income_tax'],
                'employee_pension'    => $payroll['employee_pension'],
                'employer_pension'    => $payroll['employer_pension'],
                'other_deductions'    => $payroll['other_deductions'],
                'total_pension'       => $payroll['total_pension'],
                'total_deductions'    => $payroll['total_deductions'],
                'net_salary'          => $payroll['net_salary'],
                'payment_method'      => $paymentMethod,
                'created_by'          => auth_user_id(),
            ]);

            $success++;
        }

        // Update period status and set EC date fields
        $monthNameEc = ec_month_name($GLOBALS['_period_month_ec'] ?? 0);
        db_update('hr_payroll_periods', [
            'status'       => 'generated',
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => auth_user_id(),
        ], 'id = ?', [$periodId]);
    });

    // Update EC date columns on the period (outside transaction for safety)
    payroll_update_period_ec_fields($periodId);

    return ['success' => $success, 'errors' => $errors];
}

/**
 * Populate month_name_ec, start_date_ec, end_date_ec on a payroll period.
 */
function payroll_update_period_ec_fields(int $periodId): void {
    $period = db_fetch_one("SELECT * FROM hr_payroll_periods WHERE id = ?", [$periodId]);
    if (!$period) return;

    $monthNameEc = ec_month_name((int)$period['month_ec']);
    $startDateEc = !empty($period['start_date']) ? gregorian_str_to_ec($period['start_date']) : null;
    $endDateEc   = !empty($period['end_date']) ? gregorian_str_to_ec($period['end_date']) : null;

    db_update('hr_payroll_periods', [
        'month_name_ec' => $monthNameEc,
        'start_date_ec' => $startDateEc,
        'end_date_ec'   => $endDateEc,
    ], 'id = ?', [$periodId]);
}

/**
 * Get payroll summary for a period.
 *
 * @param int $periodId
 * @return array Summary totals
 */
function payroll_period_summary(int $periodId): array {
    return db_fetch_one(
        "SELECT
            COUNT(*) as employee_count,
            SUM(basic_salary) as total_basic,
            SUM(prorated_salary) as total_prorated,
            SUM(transport_allowance) as total_transport,
            SUM(other_allowance) as total_other_allowance,
            SUM(overtime) as total_overtime,
            SUM(gross_salary) as total_gross,
            SUM(taxable_income) as total_taxable,
            SUM(income_tax) as total_tax,
            SUM(employee_pension) as total_emp_pension,
            SUM(employer_pension) as total_emr_pension,
            SUM(total_pension) as total_pension,
            SUM(other_deductions) as total_other_deductions,
            SUM(total_deductions) as total_deductions,
            SUM(net_salary) as total_net
         FROM hr_payroll_records
         WHERE payroll_period_id = ?",
        [$periodId]
    ) ?: [];
}

/**
 * Get the tax bracket info for a given income (for display/verification).
 */
function payroll_tax_bracket(float $income): array {
    $brackets = [
        ['min' => 0,     'max' => 2000,  'rate' => 0,    'deduction' => 0,     'label' => '0%'],
        ['min' => 2001,  'max' => 4000,  'rate' => 0.15, 'deduction' => 300,   'label' => '15%'],
        ['min' => 4001,  'max' => 7000,  'rate' => 0.20, 'deduction' => 500.5, 'label' => '20%'],
        ['min' => 7001,  'max' => 10000, 'rate' => 0.25, 'deduction' => 850.5, 'label' => '25%'],
        ['min' => 10001, 'max' => 14000, 'rate' => 0.30, 'deduction' => 1350,  'label' => '30%'],
        ['min' => 14001, 'max' => PHP_FLOAT_MAX, 'rate' => 0.35, 'deduction' => 2050,  'label' => '35%'],
    ];

    foreach ($brackets as $b) {
        if ($income >= $b['min'] && $income <= $b['max']) {
            return $b;
        }
    }

    return $brackets[0];
}

/**
 * Generate next employee ID in format EMP-YYYY-XXXX.
 */
function payroll_next_employee_id(): string {
    $year = date('Y');
    $prefix = "EMP-{$year}-";
    $last = db_fetch_value(
        "SELECT employee_id FROM hr_employees WHERE employee_id LIKE ? ORDER BY employee_id DESC LIMIT 1",
        [$prefix . '%']
    );

    if ($last) {
        $seq = (int)substr($last, -4) + 1;
    } else {
        $seq = 1;
    }

    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

/**
 * Convert number to words (for bank transfer sheet amount in words).
 * Supports Ethiopian Birr.
 */
function payroll_amount_in_words(float $amount): string {
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
             'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
             'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $amount = abs($amount);
    $whole = (int)$amount;
    $cents = round(($amount - $whole) * 100);

    if ($whole === 0) return 'Zero Birr Only';

    $words = '';

    $millions = intdiv($whole, 1000000);
    $whole %= 1000000;
    $thousands = intdiv($whole, 1000);
    $whole %= 1000;
    $hundreds = intdiv($whole, 100);
    $remainder = $whole % 100;

    $convertBelow100 = function($n) use ($ones, $tens) {
        if ($n < 20) return $ones[$n];
        return $tens[intdiv($n, 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
    };

    $convertBelow1000 = function($n) use ($ones, $convertBelow100) {
        if ($n === 0) return '';
        $h = intdiv($n, 100);
        $r = $n % 100;
        $result = '';
        if ($h > 0) $result = $ones[$h] . ' Hundred';
        if ($r > 0) $result .= ($result ? ' ' : '') . $convertBelow100($r);
        return $result;
    };

    if ($millions > 0) {
        $words .= $convertBelow1000($millions) . ' Million';
    }
    if ($thousands > 0) {
        $words .= ($words ? ' ' : '') . $convertBelow1000($thousands) . ' Thousand';
    }
    if ($hundreds > 0 || $remainder > 0) {
        $combined = $hundreds * 100 + $remainder;
        $words .= ($words ? ' ' : '') . $convertBelow1000($combined);
    }

    $words .= ' Birr';
    if ($cents > 0) {
        $words .= ' and ' . $convertBelow100((int)$cents) . ' Cents';
    }
    $words .= ' Only';

    return $words;
}
