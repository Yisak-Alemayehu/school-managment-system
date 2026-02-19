<?php
/**
 * Students — View Profile
 */

$student = db_fetch_one("SELECT * FROM students WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$student) {
    set_flash('error', 'Student not found.');
    redirect(url('students'));
}

// Current enrollment
$enrollment = db_fetch_one(
    "SELECT e.*, c.name as class_name, sec.name as section_name, acs.name as session_name
     FROM enrollments e
     JOIN sections sec ON e.section_id = sec.id
     JOIN classes c ON sec.class_id = c.id
     JOIN academic_sessions acs ON e.session_id = acs.id
     WHERE e.student_id = ? AND e.status = 'active'
     ORDER BY e.enrolled_at DESC LIMIT 1",
    [$id]
);

// Guardians
$guardians = db_fetch_all(
    "SELECT g.*, sg.relationship, sg.is_primary
     FROM guardians g
     JOIN student_guardians sg ON g.id = sg.guardian_id
     WHERE sg.student_id = ?
     ORDER BY sg.is_primary DESC",
    [$id]
);

// Fee summary
$feeData = db_fetch_one(
    "SELECT COALESCE(SUM(total_amount), 0) as total_invoiced,
            COALESCE(SUM(paid_amount), 0) as total_paid,
            COALESCE(SUM(total_amount - paid_amount), 0) as balance
     FROM invoices WHERE student_id = ? AND status != 'cancelled'",
    [$id]
);

// Attendance summary
$attendanceSummary = db_fetch_one(
    "SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
     FROM attendance WHERE student_id = ?",
    [$id]
);

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="<?= url('students') ?>" class="p-1 text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-xl font-bold text-gray-900">Student Profile</h1>
        </div>
        <div class="flex gap-2">
            <?php if (auth_has_permission('students.edit')): ?>
                <a href="<?= url('students', 'edit', $id) ?>" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">Edit</a>
            <?php endif; ?>
            <button onclick="printContent('student-profile')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Print</button>
        </div>
    </div>

    <div id="student-profile">
        <!-- Profile Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <div class="flex flex-col sm:flex-row items-start gap-4">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center text-blue-800 text-3xl font-bold flex-shrink-0">
                    <?= e(strtoupper(substr($student['full_name'], 0, 1))) ?>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-lg font-semibold text-gray-900"><?= e($student['full_name']) ?></h2>
                        <?php
                        $statusColors = ['active' => 'green', 'graduated' => 'blue', 'transferred' => 'yellow', 'withdrawn' => 'red'];
                        $color = $statusColors[$student['status']] ?? 'gray';
                        ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-<?= $color ?>-100 text-<?= $color ?>-800">
                            <?= e(ucfirst($student['status'])) ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        Admission No: <strong><?= e($student['admission_no']) ?></strong>
                        <?php if ($enrollment): ?>
                            &bull; <?= e($enrollment['class_name']) ?> <?= e($enrollment['section_name']) ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-6 text-sm">
                <div><dt class="text-gray-500">Gender</dt><dd class="font-medium text-gray-900"><?= e(ucfirst($student['gender'])) ?></dd></div>
                <div><dt class="text-gray-500">Date of Birth</dt><dd class="font-medium text-gray-900"><?= format_date($student['date_of_birth']) ?></dd></div>
                <div><dt class="text-gray-500">Blood Group</dt><dd class="font-medium text-gray-900"><?= e($student['blood_group'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-gray-500">Phone</dt><dd class="font-medium text-gray-900"><?= e($student['phone'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-gray-500">Email</dt><dd class="font-medium text-gray-900"><?= e($student['email'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-gray-500">Religion</dt><dd class="font-medium text-gray-900"><?= e($student['religion'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-gray-500">Admission Date</dt><dd class="font-medium text-gray-900"><?= format_date($student['admission_date']) ?></dd></div>
                <div><dt class="text-gray-500">Previous School</dt><dd class="font-medium text-gray-900"><?= e($student['previous_school'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-gray-500">Medical</dt><dd class="font-medium text-gray-900"><?= e($student['medical_notes'] ?: 'None') ?></dd></div>
            </dl>

            <?php if ($student['address']): ?>
                <div class="mt-4 text-sm">
                    <dt class="text-gray-500">Address</dt>
                    <dd class="font-medium text-gray-900"><?= e($student['address']) ?></dd>
                </div>
            <?php endif; ?>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-green-600"><?= $attendanceSummary['present'] ?? 0 ?></p>
                <p class="text-xs text-gray-500">Days Present</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-red-600"><?= $attendanceSummary['absent'] ?? 0 ?></p>
                <p class="text-xs text-gray-500">Days Absent</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold text-gray-900"><?= format_money($feeData['total_paid'] ?? 0) ?></p>
                <p class="text-xs text-gray-500">Fees Paid</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <p class="text-2xl font-bold <?= ($feeData['balance'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' ?>"><?= format_money($feeData['balance'] ?? 0) ?></p>
                <p class="text-xs text-gray-500">Fee Balance</p>
            </div>
        </div>

        <!-- Guardians -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Guardian(s)</h3>
            <div class="space-y-4">
                <?php foreach ($guardians as $g): ?>
                    <div class="flex items-start justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?= e($g['full_name']) ?></p>
                            <p class="text-xs text-gray-500"><?= e(ucfirst($g['relationship'])) ?> <?= $g['is_primary'] ? '(Primary)' : '' ?></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= e($g['phone']) ?>
                                <?= $g['email'] ? ' &bull; ' . e($g['email']) : '' ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($guardians)): ?>
                    <p class="text-sm text-gray-400">No guardians linked.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Student Profile — ' . $student['full_name'];
require APP_ROOT . '/templates/layout.php';
