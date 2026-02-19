<?php
/**
 * Academics — Class-Subject Assignment View (Fixed)
 * Now session-aware and supports elective toggle per subject.
 */

$classes  = db_fetch_all("SELECT id, name FROM classes ORDER BY sort_order ASC");
$subjects = db_fetch_all("SELECT id, name, code FROM subjects WHERE is_active = 1 ORDER BY name ASC");
$sessions = db_fetch_all("SELECT id, name FROM academic_sessions ORDER BY start_date DESC");

$activeSession = get_active_session();
$filterSession = input_int('session_id') ?: ($activeSession['id'] ?? 0);
$filterClass   = input_int('class_id');

if (!$filterClass && !empty($classes)) {
    $filterClass = $classes[0]['id'];
}

// Current assignments for selected class + session
$assigned    = [];
$electiveMap = [];
if ($filterClass && $filterSession) {
    $rows = db_fetch_all(
        "SELECT subject_id, is_elective FROM class_subjects WHERE class_id = ? AND session_id = ?",
        [$filterClass, $filterSession]
    );
    foreach ($rows as $r) {
        $assigned[$r['subject_id']] = true;
        if ($r['is_elective']) {
            $electiveMap[$r['subject_id']] = true;
        }
    }
} elseif ($filterClass) {
    // Fallback: no session filter, show all
    $rows = db_fetch_all("SELECT subject_id, is_elective FROM class_subjects WHERE class_id = ?", [$filterClass]);
    foreach ($rows as $r) {
        $assigned[$r['subject_id']] = true;
        if ($r['is_elective']) $electiveMap[$r['subject_id']] = true;
    }
}

ob_start();
?>

<div class="max-w-5xl mx-auto">
    <?php require APP_ROOT . '/templates/partials/academics_nav.php'; ?>

    <h1 class="text-xl font-bold text-gray-900 mb-6">Assign Subjects to Classes</h1>

    <!-- Selectors -->
    <div class="flex flex-wrap items-end gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Session</label>
            <select onchange="updateCSFilter()" id="csSession" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <?php foreach ($sessions as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filterSession == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
            <select onchange="updateCSFilter()" id="csClass" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClass == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($filterClass): ?>
    <form method="POST" action="<?= url('academics', 'class-subjects-save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="class_id" value="<?= $filterClass ?>">
        <input type="hidden" name="session_id" value="<?= $filterSession ?>">

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" onclick="toggleSelectAll(this, 'subjects[]')" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="font-medium text-gray-700">Select All Subjects</span>
                </label>
                <span class="text-xs text-gray-500">Check "Elective" for optional subjects students can choose</span>
            </div>

            <div class="divide-y divide-gray-100">
                <?php foreach ($subjects as $s): ?>
                    <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                        <label class="flex items-center gap-3 cursor-pointer flex-1">
                            <input type="checkbox" name="subjects[]" value="<?= $s['id'] ?>"
                                   <?= isset($assigned[$s['id']]) ? 'checked' : '' ?>
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900"><?= e($s['name']) ?></span>
                                <span class="text-xs text-gray-500 ml-1">(<?= e($s['code']) ?>)</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="electives[]" value="<?= $s['id'] ?>"
                                   <?= isset($electiveMap[$s['id']]) ? 'checked' : '' ?>
                                   class="rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                            <span class="text-xs text-gray-500">Elective</span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="px-4 py-3 bg-gray-50 border-t">
                <button type="submit" class="px-4 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                    Save Assignments
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
function updateCSFilter() {
    const sessionId = document.getElementById('csSession').value;
    const classId = document.getElementById('csClass').value;
    window.location = '<?= url('academics', 'class-subjects') ?>&session_id=' + sessionId + '&class_id=' + classId;
}
function toggleSelectAll(master, name) {
    document.querySelectorAll('input[name="' + name + '"]').forEach(cb => cb.checked = master.checked);
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
