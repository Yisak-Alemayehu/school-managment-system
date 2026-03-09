<?php
/**
 * Ethiopian Calendar Service
 * Urji Beri School Management System — HR Module
 *
 * Provides conversion between Ethiopian Calendar (EC) and Gregorian Calendar (GC).
 * Ethiopian calendar has 13 months: 12 months of 30 days + Pagume (5 or 6 days).
 * Leap year in EC: every 4 years (year % 4 == 3).
 *
 * Reference epoch: 1 Meskerem 1 EC = August 29, 8 AD (Julian) ≈ Sep 11 Gregorian.
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Ethiopian month names (1-indexed)
 */
function ec_month_names(): array {
    return [
        1  => ['en' => 'Meskerem',  'am' => 'መስከረም'],
        2  => ['en' => 'Tikimt',    'am' => 'ጥቅምት'],
        3  => ['en' => 'Hidar',     'am' => 'ኅዳር'],
        4  => ['en' => 'Tahsas',    'am' => 'ታኅሣሥ'],
        5  => ['en' => 'Tir',       'am' => 'ጥር'],
        6  => ['en' => 'Yekatit',   'am' => 'የካቲት'],
        7  => ['en' => 'Megabit',   'am' => 'መጋቢት'],
        8  => ['en' => 'Miazia',    'am' => 'ሚያዝያ'],
        9  => ['en' => 'Ginbot',    'am' => 'ግንቦት'],
        10 => ['en' => 'Sene',      'am' => 'ሰኔ'],
        11 => ['en' => 'Hamle',     'am' => 'ሐምሌ'],
        12 => ['en' => 'Nehase',    'am' => 'ነሐሴ'],
        13 => ['en' => 'Pagume',    'am' => 'ጳጉሜ'],
    ];
}

/**
 * Get Ethiopian month name
 */
function ec_month_name(int $month, string $lang = 'en'): string {
    $months = ec_month_names();
    return $months[$month][$lang] ?? '';
}

/**
 * Check if Ethiopian year is a leap year.
 * EC leap year: (year % 4) == 3
 */
function ec_is_leap_year(int $ecYear): bool {
    return ($ecYear % 4) === 3;
}

/**
 * Get number of days in an Ethiopian month.
 * Months 1-12: 30 days each
 * Month 13 (Pagume): 5 days normally, 6 days in leap year
 */
function ec_days_in_month(int $month, int $ecYear): int {
    if ($month >= 1 && $month <= 12) {
        return 30;
    }
    if ($month === 13) {
        return ec_is_leap_year($ecYear) ? 6 : 5;
    }
    return 0;
}

/**
 * Convert Ethiopian Calendar date to Gregorian.
 *
 * Algorithm: Convert EC to Julian Day Number then to Gregorian.
 *
 * @param int $ecDay   Day (1-30, or 1-5/6 for Pagume)
 * @param int $ecMonth Month (1-13)
 * @param int $ecYear  Year
 * @return array ['year' => int, 'month' => int, 'day' => int, 'date' => 'YYYY-MM-DD']
 */
function ec_to_gregorian(int $ecDay, int $ecMonth, int $ecYear): array {
    // Ethiopian calendar epoch in Julian Day Number
    // 1 Meskerem 1 EC = JDN 1724221
    $jdnEpoch = 1724221;

    // Calculate JDN for the given EC date
    $jdn = $jdnEpoch
         + 365 * ($ecYear - 1)
         + intdiv($ecYear, 4)    // leap days (EC leap when year%4==3, so floor(year/4) leap days before this year)
         + 30 * ($ecMonth - 1)
         + $ecDay - 1;

    // Convert JDN to Gregorian
    return jdn_to_gregorian_date($jdn);
}

/**
 * Convert Gregorian date to Ethiopian Calendar.
 *
 * @param int $gYear  Gregorian year
 * @param int $gMonth Gregorian month
 * @param int $gDay   Gregorian day
 * @return array ['day' => int, 'month' => int, 'year' => int, 'date_ec' => 'DD/MM/YYYY']
 */
function gregorian_to_ec(int $gYear, int $gMonth, int $gDay): array {
    $jdn = gregorian_to_jdn_date($gYear, $gMonth, $gDay);

    $jdnEpoch = 1724221;

    // Days from Ethiopian epoch
    $r = ($jdn - $jdnEpoch);

    // 1461 = 4 * 365 + 1  (4-year cycle with one leap)
    $ecYear = intdiv(4 * $r + 3, 1461);
    // days remaining in this year
    $dayOfYear = $r - (365 * $ecYear + intdiv($ecYear, 4));

    $ecYear += 1; // 1-based year

    $ecMonth = intdiv($dayOfYear, 30) + 1;
    $ecDay   = ($dayOfYear % 30) + 1;

    // Clamp month to 13
    if ($ecMonth > 13) {
        $ecMonth = 13;
        $ecDay = $dayOfYear - 360 + 1;
    }

    return [
        'day'     => $ecDay,
        'month'   => $ecMonth,
        'year'    => $ecYear,
        'date_ec' => sprintf('%02d/%02d/%04d', $ecDay, $ecMonth, $ecYear),
    ];
}

/**
 * Convert Julian Day Number to Gregorian date.
 */
function jdn_to_gregorian_date(int $jdn): array {
    // Algorithm from Meeus "Astronomical Algorithms"
    $l = $jdn + 68569;
    $n = intdiv(4 * $l, 146097);
    $l = $l - intdiv(146097 * $n + 3, 4);
    $i = intdiv(4000 * ($l + 1), 1461001);
    $l = $l - intdiv(1461 * $i, 4) + 31;
    $j = intdiv(80 * $l, 2447);
    $day = $l - intdiv(2447 * $j, 80);
    $l = intdiv($j, 11);
    $month = $j + 2 - 12 * $l;
    $year = 100 * ($n - 49) + $i + $l;

    return [
        'year'  => $year,
        'month' => $month,
        'day'   => $day,
        'date'  => sprintf('%04d-%02d-%02d', $year, $month, $day),
    ];
}

/**
 * Convert Gregorian date to Julian Day Number.
 */
function gregorian_to_jdn_date(int $year, int $month, int $day): int {
    // Algorithm from Meeus
    $a = intdiv(14 - $month, 12);
    $y = $year + 4800 - $a;
    $m = $month + 12 * $a - 3;

    return $day
         + intdiv(153 * $m + 2, 5)
         + 365 * $y
         + intdiv($y, 4)
         - intdiv($y, 100)
         + intdiv($y, 400)
         - 32045;
}

/**
 * Convert a date string "YYYY-MM-DD" (Gregorian) to Ethiopian "DD/MM/YYYY" format.
 */
function gregorian_str_to_ec(string $gregorianDate): string {
    $parts = explode('-', $gregorianDate);
    if (count($parts) !== 3) return '';
    $ec = gregorian_to_ec((int)$parts[0], (int)$parts[1], (int)$parts[2]);
    return $ec['date_ec'];
}

/**
 * Convert an Ethiopian date string "DD/MM/YYYY" to Gregorian "YYYY-MM-DD".
 */
function ec_str_to_gregorian(string $ecDate): string {
    $parts = explode('/', $ecDate);
    if (count($parts) !== 3) return '';
    $gc = ec_to_gregorian((int)$parts[0], (int)$parts[1], (int)$parts[2]);
    return $gc['date'];
}

/**
 * Format Ethiopian date for display.
 * Input: "DD/MM/YYYY" EC string
 * Output: "DD MonthName YYYY" (e.g., "01 Meskerem 2018")
 */
function ec_format_display(string $ecDate, string $lang = 'en'): string {
    $parts = explode('/', $ecDate);
    if (count($parts) !== 3) return $ecDate;
    $day   = (int)$parts[0];
    $month = (int)$parts[1];
    $year  = (int)$parts[2];
    $monthName = ec_month_name($month, $lang);
    return sprintf('%02d %s %04d', $day, $monthName, $year);
}

/**
 * Get current date in Ethiopian calendar.
 * Returns array with day, month, year, date_ec.
 */
function ec_today(): array {
    $now = new DateTime('now', new DateTimeZone('Africa/Addis_Ababa'));
    return gregorian_to_ec(
        (int)$now->format('Y'),
        (int)$now->format('n'),
        (int)$now->format('j')
    );
}

/**
 * Get the current Ethiopian year.
 */
function ec_current_year(): int {
    return ec_today()['year'];
}

/**
 * Get the current Ethiopian month.
 */
function ec_current_month(): int {
    return ec_today()['month'];
}

/**
 * Calculate working days between two Gregorian dates (excluding weekends and holidays).
 *
 * @param string $startDate 'YYYY-MM-DD'
 * @param string $endDate   'YYYY-MM-DD'
 * @return int
 */
function ec_working_days(string $startDate, string $endDate): int {
    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);

    if ($start > $end) return 0;

    // Load holidays for the date range
    $holidays = [];
    $rows = db_fetch_all(
        "SELECT date_gregorian FROM hr_holidays WHERE date_gregorian BETWEEN ? AND ?",
        [$startDate, $endDate]
    );
    foreach ($rows as $row) {
        $holidays[] = $row['date_gregorian'];
    }

    $workingDays = 0;
    $current = clone $start;
    while ($current <= $end) {
        $dayOfWeek = (int)$current->format('N'); // 1=Mon, 7=Sun
        $dateStr = $current->format('Y-m-d');
        // Exclude Saturday (6) and Sunday (7)
        if ($dayOfWeek < 6 && !in_array($dateStr, $holidays, true)) {
            $workingDays++;
        }
        $current->modify('+1 day');
    }

    return $workingDays;
}

/**
 * Get number of days in an Ethiopian month for a given EC year.
 * Returns 30 for months 1-12, 5 or 6 for Pagume.
 */
function ec_month_days(int $month, int $year): int {
    return ec_days_in_month($month, $year);
}

/**
 * Validate an Ethiopian date string "DD/MM/YYYY".
 */
function ec_validate_date(string $ecDate): bool {
    $parts = explode('/', $ecDate);
    if (count($parts) !== 3) return false;

    $day   = (int)$parts[0];
    $month = (int)$parts[1];
    $year  = (int)$parts[2];

    if ($year < 1 || $month < 1 || $month > 13 || $day < 1) return false;

    $maxDays = ec_days_in_month($month, $year);
    return $day <= $maxDays;
}

/**
 * Get start and end Gregorian dates for an Ethiopian month.
 *
 * @param int $month EC month (1-13)
 * @param int $year  EC year
 * @return array ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']
 */
function ec_month_range(int $month, int $year): array {
    $startEc = ec_to_gregorian(1, $month, $year);
    $daysInMonth = ec_days_in_month($month, $year);
    $endEc = ec_to_gregorian($daysInMonth, $month, $year);

    return [
        'start' => $startEc['date'],
        'end'   => $endEc['date'],
    ];
}

/**
 * Check if a Gregorian date falls on a holiday.
 */
function ec_is_holiday(string $gregorianDate): bool {
    return (bool)db_fetch_value(
        "SELECT COUNT(*) FROM hr_holidays WHERE date_gregorian = ?",
        [$gregorianDate]
    );
}
