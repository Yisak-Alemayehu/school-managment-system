<?php
/**
 * Fee Management — Assign Fees
 * Select fee -> Choose assignment method -> Manage exemptions -> Confirm
 */
$feeId = input_int('fee_id');

// Load active fees for dropdown
$activeFees = db_fetch_all(
    "SELECT id, description, amount, currency, fee_type FROM fees WHERE status = 'active' AND deleted_at IS NULL ORDER BY description"
);

// Load selected fee details
$selectedFee = null;
if ($feeId) {
    $selectedFee = db_fetch_one("SELECT * FROM fees WHERE id = ? AND status = 'active' AND deleted_at IS NULL", [$feeId]);
}

// Load data for assignment targets
$classes = db_fetch_all("SELECT c.id, c.name, s.id AS section_id, s.name AS section_name 
    FROM classes c LEFT JOIN sections s ON s.class_id = c.id 
    ORDER BY c.name, s.name");

$grades = db_fetch_all("SELECT DISTINCT id, name FROM classes ORDER BY name");

$groups = db_fetch_all("SELECT id, name, (SELECT COUNT(*) FROM student_group_members WHERE group_id = student_groups.id AND deleted_at IS NULL) AS member_count 
    FROM student_groups WHERE status = 'active' AND deleted_at IS NULL ORDER BY name");

// Get existing assignments for the selected fee
$existingAssignments = [];
$existingExemptions  = [];
if ($selectedFee) {
    $existingAssignments = db_fetch_all(
        "SELECT fa.*, 
                CASE fa.assignment_type 
                    WHEN 'class' THEN (SELECT c.name FROM classes c WHERE c.id = fa.target_id LIMIT 1)
                    WHEN 'grade' THEN (SELECT name FROM classes WHERE id = fa.target_id LIMIT 1)
                    WHEN 'group' THEN (SELECT name FROM student_groups WHERE id = fa.target_id LIMIT 1)
                    WHEN 'individual' THEN (SELECT CONCAT(u.first_name, ' ', u.last_name) FROM students st JOIN users u ON u.id = st.user_id WHERE st.id = fa.target_id LIMIT 1)
                    ELSE 'Unknown'
                END AS target_label
         FROM fee_assignments fa WHERE fa.fee_id = ? AND fa.deleted_at IS NULL ORDER BY fa.created_at DESC",
        [$feeId]
    );
    $existingExemptions = db_fetch_all(
        "SELECT fe.*, CONCAT(u.first_name, ' ', u.last_name) AS student_name, st.admission_no
         FROM fee_exemptions fe
         JOIN students st ON st.id = fe.student_id
         JOIN users u ON u.id = st.user_id
         WHERE fe.fee_id = ? AND fe.deleted_at IS NULL",
        [$feeId]
    );
}

ob_start();
?>
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Assign Fees</h1>
        <a href="<?= url('finance', 'fm-manage-fees') ?>" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Fees</a>
    </div>

    <!-- Step 1: Select Fee -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Step 1: Select Fee</h2>
        <form method="GET" action="<?= url('finance', 'fm-assign-fees') ?>" class="flex gap-3 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Active Fee</label>
                <select name="fee_id" required class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Select a fee --</option>
                    <?php foreach ($activeFees as $af): ?>
                        <option value="<?= $af['id'] ?>" <?= $feeId == $af['id'] ? 'selected' : '' ?>>
                            <?= e($af['description']) ?> — <?= CURRENCY_SYMBOL ?> <?= number_format($af['amount'], 2) ?> (<?= ucfirst($af['fee_type']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Load Fee</button>
        </form>
    </div>

    <?php if ($selectedFee): ?>
    <!-- Selected Fee Info -->
    <div class="bg-gradient-to-r from-primary-50 to-blue-50 rounded-xl border border-primary-200 p-4 flex items-center justify-between">
        <div>
            <p class="font-semibold text-primary-900"><?= e($selectedFee['description']) ?></p>
            <p class="text-sm text-primary-700"><?= CURRENCY_SYMBOL ?> <?= number_format($selectedFee['amount'], 2) ?> &middot; <?= ucfirst($selectedFee['fee_type']) ?> &middot; <?= format_date($selectedFee['effective_date']) ?> to <?= format_date($selectedFee['end_date']) ?></p>
        </div>
        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Active</span>
    </div>

    <!-- Existing Assignments -->
    <?php if (!empty($existingAssignments)): ?>
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="font-semibold text-gray-900">Current Assignments (<?= count($existingAssignments) ?>)</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-2 font-medium text-gray-600">Type</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-600">Target</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-600">Assigned On</th>
                    <th class="text-right px-4 py-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach ($existingAssignments as $ea): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 capitalize"><?= e($ea['assignment_type']) ?></td>
                    <td class="px-4 py-2 font-medium"><?= e($ea['target_label']) ?></td>
                    <td class="px-4 py-2 text-gray-500"><?= format_date($ea['created_at']) ?></td>
                    <td class="px-4 py-2 text-right">
                        <form method="POST" action="<?= url('finance', 'fm-assignment-delete') ?>" class="inline"
                              onsubmit="return confirm('Remove assignment? Pending charges will be cancelled.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $ea['id'] ?>">
                            <input type="hidden" name="fee_id" value="<?= $feeId ?>">
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Step 2: New Assignment -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Step 2: Add New Assignment</h2>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Assignment Method</label>
            <div class="flex flex-wrap gap-3" id="assignMethodBtns">
                <button type="button" onclick="showAssignPanel('class')" class="assign-method-btn px-4 py-2 rounded-lg border text-sm font-medium hover:bg-gray-50" data-method="class">
                    By Class/Section
                </button>
                <button type="button" onclick="showAssignPanel('grade')" class="assign-method-btn px-4 py-2 rounded-lg border text-sm font-medium hover:bg-gray-50" data-method="grade">
                    By Grade
                </button>
                <button type="button" onclick="showAssignPanel('individual')" class="assign-method-btn px-4 py-2 rounded-lg border text-sm font-medium hover:bg-gray-50" data-method="individual">
                    Individual Student
                </button>
                <button type="button" onclick="showAssignPanel('group')" class="assign-method-btn px-4 py-2 rounded-lg border text-sm font-medium hover:bg-gray-50" data-method="group">
                    Student Group
                </button>
            </div>
        </div>

        <!-- Class Assignment -->
        <div id="panel_class" class="assign-panel hidden">
            <form method="POST" action="<?= url('finance', 'fm-assignment-save') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="fee_id" value="<?= $feeId ?>">
                <input type="hidden" name="assignment_type" value="class">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Class/Section</label>
                        <select name="target_id" required class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            <option value="">-- Select --</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['section_id'] ?: $cl['id'] ?>"><?= e($cl['name']) ?><?= $cl['section_name'] ? ' — ' . e($cl['section_name']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Assign to Class</button>
            </form>
        </div>

        <!-- Grade Assignment -->
        <div id="panel_grade" class="assign-panel hidden">
            <form method="POST" action="<?= url('finance', 'fm-assignment-save') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="fee_id" value="<?= $feeId ?>">
                <input type="hidden" name="assignment_type" value="grade">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Grade</label>
                        <select name="target_id" required class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            <option value="">-- Select --</option>
                            <?php foreach ($grades as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= e($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Assign to Grade</button>
            </form>
        </div>

        <!-- Individual Assignment -->
        <div id="panel_individual" class="assign-panel hidden">
            <form method="POST" action="<?= url('finance', 'fm-assignment-save') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="fee_id" value="<?= $feeId ?>">
                <input type="hidden" name="assignment_type" value="individual">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Student</label>
                    <div class="relative">
                        <input type="text" id="studentSearch" placeholder="Type student name or admission number..."
                               onkeyup="searchStudents(this.value)"
                               class="w-full rounded-lg border-gray-300 shadow-sm text-sm" autocomplete="off">
                        <div id="studentResults" class="absolute z-10 w-full bg-white border rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto hidden"></div>
                    </div>
                    <input type="hidden" name="target_id" id="selectedStudentId" required>
                    <p class="text-sm text-gray-500 mt-1" id="selectedStudentLabel"></p>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Assign to Student</button>
            </form>
        </div>

        <!-- Group Assignment -->
        <div id="panel_group" class="assign-panel hidden">
            <form method="POST" action="<?= url('finance', 'fm-assignment-save') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="fee_id" value="<?= $feeId ?>">
                <input type="hidden" name="assignment_type" value="group">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Group</label>
                        <select name="target_id" required class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                            <option value="">-- Select --</option>
                            <?php foreach ($groups as $grp): ?>
                                <option value="<?= $grp['id'] ?>"><?= e($grp['name']) ?> (<?= $grp['member_count'] ?> members)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mb-4">Don't see your group? <a href="<?= url('finance', 'fm-groups') ?>" class="text-primary-600 hover:underline">Manage Groups</a></p>
                <button type="submit" class="px-6 py-2.5 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Assign to Group</button>
            </form>
        </div>
    </div>

    <!-- Step 3: Exemptions -->
    <div class="bg-white rounded-xl shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Step 3: Manage Exemptions</h2>
        <p class="text-sm text-gray-500 mb-4">Exempt specific students from this fee. Exempted students' pending charges will be waived.</p>

        <!-- Add Exemption -->
        <form method="POST" action="<?= url('finance', 'fm-exemption-save') ?>" class="flex flex-col sm:flex-row gap-3 mb-4 bg-gray-50 rounded-lg p-4">
            <?= csrf_field() ?>
            <input type="hidden" name="fee_id" value="<?= $feeId ?>">
            <div class="flex-1">
                <input type="text" id="exemptionStudentSearch" placeholder="Search student..."
                       onkeyup="searchExemptionStudents(this.value)" autocomplete="off"
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                <div id="exemptionStudentResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto hidden" style="position:absolute;"></div>
                <input type="hidden" name="student_id" id="exemptionStudentId">
            </div>
            <div class="flex-1">
                <input type="text" name="reason" placeholder="Reason for exemption..." maxlength="255"
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
            </div>
            <button type="submit" class="px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 whitespace-nowrap">Add Exemption</button>
        </form>

        <!-- Existing Exemptions -->
        <?php if (!empty($existingExemptions)): ?>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-2 font-medium text-gray-600">Student</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-600">Admission #</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-600">Reason</th>
                    <th class="text-right px-4 py-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <?php foreach ($existingExemptions as $ex): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium"><?= e($ex['student_name']) ?></td>
                    <td class="px-4 py-2 text-gray-500"><?= e($ex['admission_no']) ?></td>
                    <td class="px-4 py-2"><?= e($ex['reason'] ?? '-') ?></td>
                    <td class="px-4 py-2 text-right">
                        <form method="POST" action="<?= url('finance', 'fm-exemption-delete') ?>" class="inline"
                              onsubmit="return confirm('Remove exemption?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $ex['id'] ?>">
                            <input type="hidden" name="fee_id" value="<?= $feeId ?>">
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="text-center text-gray-400 text-sm py-4">No exemptions for this fee.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function showAssignPanel(method) {
    document.querySelectorAll('.assign-panel').forEach(function(el) { el.classList.add('hidden'); });
    document.querySelectorAll('.assign-method-btn').forEach(function(btn) {
        btn.classList.remove('bg-primary-100', 'border-primary-500', 'text-primary-700');
    });
    var panel = document.getElementById('panel_' + method);
    if (panel) panel.classList.remove('hidden');
    var btn = document.querySelector('[data-method="' + method + '"]');
    if (btn) btn.classList.add('bg-primary-100', 'border-primary-500', 'text-primary-700');
}

var searchTimer;
function searchStudents(q) {
    clearTimeout(searchTimer);
    if (q.length < 2) { document.getElementById('studentResults').classList.add('hidden'); return; }
    searchTimer = setTimeout(function() {
        fetch('<?= url('finance', 'fm-api-students') ?>&q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var el = document.getElementById('studentResults');
                if (!data.students || data.students.length === 0) {
                    el.innerHTML = '<div class="p-3 text-sm text-gray-400">No results</div>';
                } else {
                    el.innerHTML = data.students.map(function(s) {
                        return '<div class="p-3 hover:bg-gray-50 cursor-pointer text-sm" onclick="selectStudent(' + s.id + ',\'' + s.name.replace(/'/g, "\\'") + '\',\'' + (s.admission_no || '') + '\')">' +
                            '<span class="font-medium">' + s.name + '</span> <span class="text-gray-400">' + (s.admission_no || '') + '</span></div>';
                    }).join('');
                }
                el.classList.remove('hidden');
            });
    }, 300);
}

function selectStudent(id, name, admNo) {
    document.getElementById('selectedStudentId').value = id;
    document.getElementById('selectedStudentLabel').textContent = 'Selected: ' + name + ' (' + admNo + ')';
    document.getElementById('studentSearch').value = name;
    document.getElementById('studentResults').classList.add('hidden');
}

function searchExemptionStudents(q) {
    clearTimeout(searchTimer);
    if (q.length < 2) { document.getElementById('exemptionStudentResults').classList.add('hidden'); return; }
    searchTimer = setTimeout(function() {
        fetch('<?= url('finance', 'fm-api-students') ?>&q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var el = document.getElementById('exemptionStudentResults');
                if (!data.students || data.students.length === 0) {
                    el.innerHTML = '<div class="p-3 text-sm text-gray-400">No results</div>';
                } else {
                    el.innerHTML = data.students.map(function(s) {
                        return '<div class="p-3 hover:bg-gray-50 cursor-pointer text-sm" onclick="selectExemptionStudent(' + s.id + ',\'' + s.name.replace(/'/g, "\\'") + '\')">' +
                            s.name + ' <span class="text-gray-400">' + (s.admission_no || '') + '</span></div>';
                    }).join('');
                }
                el.classList.remove('hidden');
            });
    }, 300);
}

function selectExemptionStudent(id, name) {
    document.getElementById('exemptionStudentId').value = id;
    document.getElementById('exemptionStudentSearch').value = name;
    document.getElementById('exemptionStudentResults').classList.add('hidden');
}

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#studentSearch') && !e.target.closest('#studentResults')) {
        document.getElementById('studentResults').classList.add('hidden');
    }
    if (!e.target.closest('#exemptionStudentSearch') && !e.target.closest('#exemptionStudentResults')) {
        document.getElementById('exemptionStudentResults').classList.add('hidden');
    }
});
</script>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
