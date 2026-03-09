<?php
/**
 * HR — Reports Hub View
 */

ob_start();
?>

<div class="space-y-6">
    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">HR Reports</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Attendance Report -->
        <a href="<?= url('hr', 'attendance-report') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5 hover:shadow-md transition-shadow group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-dark-text group-hover:text-primary-600">Attendance Report</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-dark-muted">View attendance summary by date range and department with presence rate.</p>
        </a>

        <!-- Payroll Summary -->
        <a href="<?= url('hr', 'payroll') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5 hover:shadow-md transition-shadow group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-dark-text group-hover:text-primary-600">Payroll Summary</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-dark-muted">Monthly payroll periods with generate, approve, and payment tracking.</p>
        </a>

        <!-- Employee Directory -->
        <a href="<?= url('hr', 'employees') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5 hover:shadow-md transition-shadow group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-dark-text group-hover:text-primary-600">Employee Directory</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-dark-muted">Full employee list with search, filter by department and status.</p>
        </a>

        <!-- Leave Requests -->
        <a href="<?= url('hr', 'leave-requests') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5 hover:shadow-md transition-shadow group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-dark-text group-hover:text-primary-600">Leave Reports</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-dark-muted">Leave request history, pending approvals, and balance tracking.</p>
        </a>

        <!-- Department Summary -->
        <a href="<?= url('hr', 'departments') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5 hover:shadow-md transition-shadow group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-dark-text group-hover:text-primary-600">Departments</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-dark-muted">Department overview with employee counts and heads.</p>
        </a>

        <!-- Holidays -->
        <a href="<?= url('hr', 'holidays') ?>" class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5 hover:shadow-md transition-shadow group">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-dark-text group-hover:text-primary-600">Public Holidays</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-dark-muted">Ethiopian public holidays calendar with EC and GC dates.</p>
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
