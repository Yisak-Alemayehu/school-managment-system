<?php
/**
 * Payroll Tax Verification Test Script
 * Urji Beri School Management System — HR Module
 *
 * Verifies the payroll calculation engine against expected values
 * from the school's actual payroll Excel file.
 *
 * Usage: php tests/payroll_test.php
 *
 * Ethiopian Income Tax Brackets:
 *   0 - 2,000     → 0%   (exempt)
 *   2,001 - 4,000 → 15%  - 300
 *   4,001 - 7,000 → 20%  - 500.5
 *   7,001 - 10,000→ 25%  - 850.5
 *  10,001 - 14,000→ 30%  - 1,350
 *  Above 14,000   → 35%  - 2,050
 *
 * Pension: Employee 7%, Employer 11%, Total 18%
 * School TIN: 0008845599
 */

// Bootstrap just the payroll module (no DB needed for tax calculation)
define('APP_ROOT', dirname(__DIR__));

// Load only the payroll calculation functions
require_once APP_ROOT . '/core/payroll.php';

// ═══════════════════════════════════════════════════════════════
// Test Framework
// ═══════════════════════════════════════════════════════════════

$testsPassed = 0;
$testsFailed = 0;
$testResults = [];

function assert_equals($expected, $actual, string $testName): void {
    global $testsPassed, $testsFailed, $testResults;

    if (is_numeric($expected) && is_numeric($actual)) {
        $pass = abs($expected - $actual) < 0.01;
    } else {
        $pass = ($expected === $actual);
    }

    if ($pass) {
        $testsPassed++;
        $testResults[] = ['status' => 'PASS', 'name' => $testName, 'expected' => $expected, 'actual' => $actual];
    } else {
        $testsFailed++;
        $testResults[] = ['status' => 'FAIL', 'name' => $testName, 'expected' => $expected, 'actual' => $actual];
    }
}

// ═══════════════════════════════════════════════════════════════
// TEST 1: Income Tax Calculation
// Verified against Urji Beri School payroll Excel data
// ═══════════════════════════════════════════════════════════════

echo "═══════════════════════════════════════════════════════════\n";
echo " PAYROLL TAX VERIFICATION — Urji Beri School\n";
echo " TIN: 0008845599\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "── TEST 1: Income Tax Brackets ──────────────────────────\n\n";

// Exact test cases from the user's specification
$taxTestCases = [
    // [salary, expected_tax, bracket_label]
    [1500,  0,       '0% (exempt)'],
    [2000,  0,       '0% (exempt upper bound)'],
    [2001,  0.15,    '15% (lower bound)'],
    [3000,  150,     '15% (mid)'],
    [4000,  300,     '15% (upper bound)'],
    [5000,  499.5,   '20% (mid)'],
    [5200,  539.5,   '20%'],
    [5400,  579.5,   '20%'],
    [5500,  599.5,   '20%'],
    [6000,  699.5,   '20%'],
    [6500,  799.5,   '20%'],
    [7000,  899.5,   '20% (upper bound)'],
    [7200,  949.5,   '25%'],
    [7500,  1024.5,  '25%'],
    [8500,  1274.5,  '25%'],
    [9000,  1399.5,  '25%'],
    [9500,  1524.5,  '25%'],
    [10000, 1649.5,  '25% (upper bound)'],
    [11500, 2100,    '30%'],
    [12000, 2250,    '30%'],
    [14000, 2850,    '30% (upper bound)'],
    [14500, 3025,    '35%'],
    [20000, 4950,    '35%'],
    [0,     0,       '0 salary'],
];

foreach ($taxTestCases as $tc) {
    $salary = $tc[0];
    $expectedTax = $tc[1];
    $label = $tc[2];
    $actualTax = payroll_calculate_income_tax((float)$salary);
    assert_equals($expectedTax, $actualTax, "Tax on {$salary} ({$label})");
}

// ═══════════════════════════════════════════════════════════════
// TEST 2: Pension Calculations
// ═══════════════════════════════════════════════════════════════

echo "── TEST 2: Pension Calculations ─────────────────────────\n\n";

$pensionTestCases = [
    // [basic_salary, expected_employee (7%), expected_employer (11%), expected_total (18%)]
    [5000,  350,  550,  900],
    [7000,  490,  770,  1260],
    [10000, 700,  1100, 1800],
    [12000, 840,  1320, 2160],
    [15000, 1050, 1650, 2700],
];

foreach ($pensionTestCases as $tc) {
    $salary = $tc[0];
    assert_equals($tc[1], payroll_employee_pension((float)$salary), "Employee pension (7%) on {$salary}");
    assert_equals($tc[2], payroll_employer_pension((float)$salary), "Employer pension (11%) on {$salary}");
    assert_equals($tc[3], payroll_total_pension((float)$salary),    "Total pension (18%) on {$salary}");
}

// ═══════════════════════════════════════════════════════════════
// TEST 3: Pro-Rated Salary
// ═══════════════════════════════════════════════════════════════

echo "── TEST 3: Pro-Rated Salary ─────────────────────────────\n\n";

$prorateTestCases = [
    // [basic, working_days, days_worked, expected_prorated]
    [10000, 26, 26, 10000],     // Full month
    [10000, 26, 13, 5000],      // Half month
    [10000, 26, 1,  384.62],    // 1 day
    [10000, 30, 15, 5000],      // Half of 30 days
    [6000,  26, 20, 4615.38],   // Partial month
];

foreach ($prorateTestCases as $tc) {
    $result = payroll_prorate_salary((float)$tc[0], $tc[1], $tc[2]);
    assert_equals($tc[3], $result, "Prorate {$tc[0]} for {$tc[2]}/{$tc[1]} days");
}

// ═══════════════════════════════════════════════════════════════
// TEST 4: Full Payroll Calculation
// ═══════════════════════════════════════════════════════════════

echo "── TEST 4: Full Payroll Calculation ─────────────────────\n\n";

// Test case: basic 7000, transport 500, other 0, full month
$result = payroll_calculate(7000, 500, 0, 26, 26);
assert_equals(7000,   $result['basic_salary'],        'Full calc: basic salary');
assert_equals(7000,   $result['prorated_salary'],     'Full calc: prorated (full month)');
assert_equals(500,    $result['transport_allowance'],  'Full calc: transport');
assert_equals(7500,   $result['gross_salary'],        'Full calc: gross');
assert_equals(7500,   $result['taxable_income'],      'Full calc: taxable income');
assert_equals(1024.5, $result['income_tax'],          'Full calc: income tax');
assert_equals(490,    $result['employee_pension'],     'Full calc: employee pension');
assert_equals(770,    $result['employer_pension'],     'Full calc: employer pension');
assert_equals(1260,   $result['total_pension'],        'Full calc: total pension');
assert_equals(1514.5, $result['total_deductions'],     'Full calc: total deductions');
assert_equals(5985.5, $result['net_salary'],           'Full calc: net salary');

// Test case: basic 10000, no allowances, half-month
$result2 = payroll_calculate(10000, 0, 0, 26, 13);
assert_equals(10000, $result2['basic_salary'],     'Half month: basic salary');
assert_equals(5000,  $result2['prorated_salary'],  'Half month: prorated');
assert_equals(5000,  $result2['gross_salary'],     'Half month: gross');
assert_equals(499.5, $result2['income_tax'],       'Half month: income tax');
assert_equals(350,   $result2['employee_pension'],  'Half month: employee pension');
assert_equals(550,   $result2['employer_pension'],  'Half month: employer pension');

// Test case: with overtime and other deductions
$result3 = payroll_calculate(8000, 500, 0, 26, 26, 1500, 200);
assert_equals(10000,  $result3['gross_salary'],       'Overtime calc: gross');
assert_equals(10000,  $result3['taxable_income'],     'Overtime calc: taxable');
assert_equals(1649.5, $result3['income_tax'],         'Overtime calc: tax');
assert_equals(560,    $result3['employee_pension'],    'Overtime calc: emp pension');
assert_equals(880,    $result3['employer_pension'],    'Overtime calc: emr pension');
assert_equals(200,    $result3['other_deductions'],    'Overtime calc: other deductions');
assert_equals(2409.5, $result3['total_deductions'],    'Overtime calc: total deductions');
assert_equals(7590.5, $result3['net_salary'],          'Overtime calc: net salary');

// ═══════════════════════════════════════════════════════════════
// TEST 5: Tax Bracket Identification
// ═══════════════════════════════════════════════════════════════

echo "── TEST 5: Tax Bracket Identification ───────────────────\n\n";

$bracketTests = [
    [1500,  '0%'],
    [3000,  '15%'],
    [5000,  '20%'],
    [8000,  '25%'],
    [12000, '30%'],
    [20000, '35%'],
];

foreach ($bracketTests as $bt) {
    $bracket = payroll_tax_bracket((float)$bt[0]);
    assert_equals($bt[1], $bracket['label'], "Bracket for {$bt[0]}");
}

// ═══════════════════════════════════════════════════════════════
// TEST 6: Amount In Words
// ═══════════════════════════════════════════════════════════════

echo "── TEST 6: Amount In Words ──────────────────────────────\n\n";

$wordsTests = [
    [0,        'Zero Birr Only'],
    [100,      'One Hundred Birr Only'],
    [1500.50,  'One Thousand Five Hundred Birr and Fifty Cents Only'],
    [25000,    'Twenty Five Thousand Birr Only'],
];

foreach ($wordsTests as $wt) {
    $words = payroll_amount_in_words($wt[0]);
    $pass = ($words === $wt[1]);
    if ($pass) {
        $testsPassed++;
        $testResults[] = ['status' => 'PASS', 'name' => "Words for {$wt[0]}", 'expected' => $wt[1], 'actual' => $words];
    } else {
        $testsFailed++;
        $testResults[] = ['status' => 'FAIL', 'name' => "Words for {$wt[0]}", 'expected' => $wt[1], 'actual' => $words];
    }
}

// ═══════════════════════════════════════════════════════════════
// RESULTS
// ═══════════════════════════════════════════════════════════════

echo "\n═══════════════════════════════════════════════════════════\n";
echo " TEST RESULTS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

foreach ($testResults as $r) {
    $icon = $r['status'] === 'PASS' ? '✓' : '✗';
    $line = " {$icon} [{$r['status']}] {$r['name']}";
    if ($r['status'] === 'FAIL') {
        $line .= " — Expected: {$r['expected']}, Got: {$r['actual']}";
    }
    echo $line . "\n";
}

echo "\n───────────────────────────────────────────────────────────\n";
echo " Total: " . ($testsPassed + $testsFailed) . " | Passed: {$testsPassed} | Failed: {$testsFailed}\n";

if ($testsFailed === 0) {
    echo " ✓ ALL TESTS PASSED\n";
} else {
    echo " ✗ SOME TESTS FAILED\n";
}
echo "───────────────────────────────────────────────────────────\n";

exit($testsFailed > 0 ? 1 : 0);
