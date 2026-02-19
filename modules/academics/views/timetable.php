<?php
/**
 * Academics — Timetable View (Fixed)
 * - Lowercase day_of_week to match DB ENUM
 * - Teacher query uses user_roles join + CONCAT for name
 * - Includes session_id awareness
 * - Includes academics sub-nav
 */

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$sections = db_fetch_all("SELECT s.id, s.name, s.class_id FROM sections s ORDER BY s.name ASC");
$subjects = db_fetch_all("SELECT id, name, code FROM subjects WHERE is_active = 1 ORDER BY name ASC");
$teachers = db_fetch_all("
    SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) AS full_name
    FROM users u
    JOIN user_roles ur ON ur.user_id = u.id
    JOIN roles r ON r.id = ur.role_id
    WHERE r.slug = 'teacher' AND u.is_active = 1
    ORDER BY full_name
");

$activeSession = get_active_session();
$sessionId     = $activeSession['id'] ?? 0;

$filterClass   = input_int('class_id') ?: ($classes[0]['id'] ?? 0);
$filterSection = input_int('section_id');

$days      = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
$dayLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Fetch timetable entries
$where  = "WHERE t.class_id = ?";
$params = [$filterClass];
if ($filterSection) {
    $where .= " AND t.section_id = ?";
    $params[] = $filterSection;
}
if ($sessionId) {
    $where .= " AND t.session_id = ?";
    $params[] = $sessionId;
}

$entries = db_fetch_all("
    SELECT t.*, sub.name AS subject_name, sub.code AS subject_code,
           CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
    FROM timetables t
    JOIN subjects sub ON sub.id = t.subject_id
    LEFT JOIN users u ON u.id = t.teacher_id
    {$where}
    ORDER BY FIELD(t.day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday'), t.start_time ASC
", $params);

// Group by day
$grid = [];
foreach ($days as $d) $grid[$d] = [];
foreach ($entries as $e) {
    $grid[$e['day_of_week']][] = $e;
}

// Class subjects for modal dropdown
$classSubjects = db_fetch_all("
    SELECT s.id, s.name, s.code
    FROM class_subjects cs
    JOIN subjects s ON s.id = cs.subject_id
    WHERE cs.class_id = ?
    ORDER BY s.name
", [$filterClass]);

ob_start();
?>

<div class="max-w-7xl mx-auto">
    <?php require APP_ROOT . '/templates/partials/academics_nav.php'; ?>

    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Timetable</h1>
            <?php if ($sessionId): ?>
                <p class="text-sm text-gray-600 mt-1">Session: <?= e($activeSession['name'] ?? '') ?></p>
            <?php endif; ?>
        </div>
        <button onclick="document.getElementById('addSlotModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Slot
        </button>
    </div>

    <?php if (!$sessionId): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800 mb-6">
            No active session. Please activate an academic session first.
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select onchange="window.location='<?= url('academics', 'timetable') ?>?class_id='+this.value"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select onchange="window.location='<?= url('academics', 'timetable') ?>?class_id=<?= $filterClass ?>&section_id='+this.value"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="0">All Sections</option>
                    <?php foreach ($sections as $s): ?>
                        <?php if ($s['class_id'] == $filterClass): ?>
                            <option value="<?= $s['id'] ?>" <?= $filterSection == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Weekly Grid -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full min-w-[800px]">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Day</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periods</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($days as $idx => $day): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 text-sm font-semibold text-gray-900 align-top"><?= $dayLabels[$idx] ?></td>
                        <td class="px-4 py-4">
                            <?php if (empty($grid[$day])): ?>
                                <span class="text-xs text-gray-400 italic">No slots scheduled</span>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($grid[$day] as $slot): ?>
                                        <div class="relative group bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-xs min-w-[130px] shadow-sm">
                                            <div class="font-semibold text-blue-900"><?= e($slot['subject_name']) ?></div>
                                            <div class="text-blue-700 font-mono mt-0.5"><?= substr($slot['start_time'],0,5) ?> – <?= substr($slot['end_time'],0,5) ?></div>
                                            <?php if (!empty($slot['teacher_name'])): ?>
                                                <div class="text-blue-600 mt-0.5"><?= e($slot['teacher_name']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($slot['room'])): ?>
                                                <div class="text-blue-500 mt-0.5">Room: <?= e($slot['room']) ?></div>
                                            <?php endif; ?>
                                            <form method="POST" action="<?= url('academics', 'timetable-save') ?>" class="absolute -top-2 -right-2 hidden group-hover:block">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="delete_id" value="<?= $slot['id'] ?>">
                                                <button type="submit" onclick="return confirm('Remove this slot?')"
                                                        class="w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600 shadow">&times;</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Slot Modal -->
<div id="addSlotModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Add Timetable Slot</h3>
            <button type="button" onclick="document.getElementById('addSlotModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="<?= url('academics', 'timetable-save') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="class_id" value="<?= $filterClass ?>">
            <input type="hidden" name="session_id" value="<?= $sessionId ?>">
            <?php if ($filterSection): ?>
                <input type="hidden" name="section_id" value="<?= $filterSection ?>">
            <?php else: ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                    <select name="section_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All / None</option>
                        <?php foreach ($sections as $s): ?>
                            <?php if ($s['class_id'] == $filterClass): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Day <span class="text-red-500">*</span></label>
                <select name="day_of_week" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <?php foreach ($days as $idx => $d): ?>
                        <option value="<?= $d ?>"><?= $dayLabels[$idx] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                <select name="subject_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Subject</option>
                    <?php if (!empty($classSubjects)): ?>
                        <?php foreach ($classSubjects as $cs): ?>
                            <option value="<?= $cs['id'] ?>"><?= e($cs['name']) ?> (<?= e($cs['code']) ?>)</option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>"><?= e($sub['name']) ?> (<?= e($sub['code']) ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                <select name="teacher_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">Select Teacher (optional)</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time <span class="text-red-500">*</span></label>
                    <input type="time" name="start_time" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Time <span class="text-red-500">*</span></label>
                    <input type="time" name="end_time" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Room (optional)</label>
                <input type="text" name="room" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500" placeholder="e.g. Room 101">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    Save Slot
                </button>
                <button type="button" onclick="document.getElementById('addSlotModal').classList.add('hidden')"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
