<?php
/**
 * Fee Management — Group Members Management
 * Two-panel layout: search + bulk add | current members list
 */
$groupId = route_id();
if (!$groupId) { redirect('finance', 'fm-groups'); }

$group = db_fetch_one("SELECT * FROM student_groups WHERE id = ? AND deleted_at IS NULL", [$groupId]);
if (!$group) {
    set_flash('error', 'Group not found.');
    redirect('finance', 'fm-groups');
}

// Current members with joins
$members = db_fetch_all(
    "SELECT sgm.*, st.admission_no, CONCAT(u.first_name, ' ', u.last_name) AS student_name,
            c.name AS class_name, sec.name AS section_name
     FROM student_group_members sgm
     JOIN students st ON st.id = sgm.student_id
     JOIN users u ON u.id = st.user_id
     LEFT JOIN enrollments e ON e.student_id = st.id AND e.status = 'active'
     LEFT JOIN classes c ON c.id = e.class_id
     LEFT JOIN sections sec ON sec.id = e.section_id
     WHERE sgm.group_id = ? AND sgm.deleted_at IS NULL
     ORDER BY u.first_name, u.last_name",
    [$groupId]
);

// Load classes for filter dropdown
$classes = db_fetch_all("SELECT id, name FROM classes ORDER BY name");

ob_start();
?>
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="<?= url('finance', 'fm-groups') ?>" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900"><?= e($group['name']) ?> — Members</h1>
            </div>
            <p class="text-sm text-gray-500"><?= count($members) ?> member<?= count($members) != 1 ? 's' : '' ?></p>
        </div>
        <a href="<?= url('finance', 'fm-group-form', $groupId) ?>" class="text-sm text-primary-600 hover:underline">Edit Group</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- Left: Add Members (2 cols) -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Search & Add Individual -->
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Add Student</h3>
                <div class="relative mb-3">
                    <input type="text" id="memberSearch" placeholder="Search by name or admission #..."
                           onkeyup="searchMember(this.value)" autocomplete="off"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                    <div id="memberResults" class="absolute z-10 w-full bg-white border rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden"></div>
                </div>

                <!-- Class filter -->
                <div class="mb-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Filter by Class</label>
                    <select id="classFilter" onchange="searchMember(document.getElementById('memberSearch').value)"
                            class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $cl): ?>
                            <option value="<?= $cl['id'] ?>"><?= e($cl['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Bulk Add via Admission Codes -->
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Bulk Add by Admission Codes</h3>
                <form method="POST" action="<?= url('finance', 'fm-group-member-add') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <input type="hidden" name="bulk" value="1">
                    <textarea name="admission_codes" rows="5" required
                              class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono"
                              placeholder="Paste admission codes, one per line&#10;e.g.&#10;STU-2026-001&#10;STU-2026-002&#10;STU-2026-003"></textarea>
                    <p class="text-xs text-gray-400 mt-1 mb-3">One admission code per line. Duplicates will be skipped.</p>
                    <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                        Bulk Add Members
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Current Members (3 cols) -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Current Members (<?= count($members) ?>)</h3>
                    <?php if (count($members) > 0): ?>
                    <form method="POST" action="<?= url('finance', 'fm-group-member-remove') ?>" id="bulkRemoveForm"
                          onsubmit="return confirm('Remove selected members?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="group_id" value="<?= $groupId ?>">
                        <input type="hidden" name="bulk_remove" value="1">
                        <button type="submit" id="bulkRemoveBtn" disabled
                                class="text-xs text-red-500 hover:text-red-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                            Remove Selected
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <?php if (empty($members)): ?>
                    <div class="p-8 text-center text-gray-400 text-sm">
                        <p>No members yet. Search or bulk add students from the left panel.</p>
                    </div>
                <?php else: ?>
                    <div class="max-h-[500px] overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b sticky top-0">
                                <tr>
                                    <th class="text-left px-4 py-2 w-8">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="rounded">
                                    </th>
                                    <th class="text-left px-4 py-2 font-medium text-gray-600">Student</th>
                                    <th class="text-left px-4 py-2 font-medium text-gray-600">Admission #</th>
                                    <th class="text-left px-4 py-2 font-medium text-gray-600">Class</th>
                                    <th class="text-right px-4 py-2 font-medium text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($members as $m): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">
                                        <input type="checkbox" name="member_ids[]" value="<?= $m['id'] ?>" form="bulkRemoveForm"
                                               onchange="updateBulkBtn()" class="rounded member-checkbox">
                                    </td>
                                    <td class="px-4 py-2 font-medium text-gray-900"><?= e($m['student_name']) ?></td>
                                    <td class="px-4 py-2 text-gray-500 font-mono text-xs"><?= e($m['admission_no']) ?></td>
                                    <td class="px-4 py-2 text-gray-500"><?= e($m['class_name'] ?? '-') ?><?= $m['section_name'] ? ' - ' . e($m['section_name']) : '' ?></td>
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST" action="<?= url('finance', 'fm-group-member-remove') ?>" class="inline"
                                              onsubmit="return confirm('Remove this member?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="group_id" value="<?= $groupId ?>">
                                            <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
var searchTimer;
function searchMember(q) {
    clearTimeout(searchTimer);
    if (q.length < 2) { document.getElementById('memberResults').classList.add('hidden'); return; }
    var classId = document.getElementById('classFilter').value;
    var url = '<?= url('finance', 'fm-api-students') ?>&q=' + encodeURIComponent(q);
    if (classId) url += '&class_id=' + classId;

    searchTimer = setTimeout(function() {
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var el = document.getElementById('memberResults');
                if (!data.students || data.students.length === 0) {
                    el.innerHTML = '<div class="p-3 text-sm text-gray-400">No students found</div>';
                } else {
                    el.innerHTML = data.students.map(function(s) {
                        return '<div class="p-3 hover:bg-gray-50 cursor-pointer text-sm flex justify-between items-center" onclick="addMember(' + s.id + ')">' +
                            '<span><span class="font-medium">' + s.name + '</span> <span class="text-gray-400">' + (s.admission_no || '') + '</span></span>' +
                            '<span class="text-primary-600 text-xs font-medium">+ Add</span></div>';
                    }).join('');
                }
                el.classList.remove('hidden');
            });
    }, 300);
}

function addMember(studentId) {
    // Submit via hidden form
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('finance', 'fm-group-member-add') ?>';

    var csrf = document.createElement('input');
    csrf.type = 'hidden'; csrf.name = '_token';
    csrf.value = '<?= csrf_token() ?>';
    form.appendChild(csrf);

    var gid = document.createElement('input');
    gid.type = 'hidden'; gid.name = 'group_id'; gid.value = '<?= $groupId ?>';
    form.appendChild(gid);

    var sid = document.createElement('input');
    sid.type = 'hidden'; sid.name = 'student_id'; sid.value = studentId;
    form.appendChild(sid);

    document.body.appendChild(form);
    form.submit();
}

function toggleSelectAll() {
    var checked = document.getElementById('selectAll').checked;
    document.querySelectorAll('.member-checkbox').forEach(function(cb) { cb.checked = checked; });
    updateBulkBtn();
}

function updateBulkBtn() {
    var btn = document.getElementById('bulkRemoveBtn');
    if (!btn) return;
    var count = document.querySelectorAll('.member-checkbox:checked').length;
    btn.disabled = count === 0;
    btn.textContent = count > 0 ? 'Remove Selected (' + count + ')' : 'Remove Selected';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#memberSearch') && !e.target.closest('#memberResults')) {
        document.getElementById('memberResults').classList.add('hidden');
    }
});
</script>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/layout.php';
