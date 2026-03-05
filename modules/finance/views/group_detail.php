<?php
/**
 * Finance — Group Detail
 * Group Information, Assign Students, Take Action
 */

$group = db_fetch_one("SELECT * FROM fin_groups WHERE id = ?", [$id]);
if (!$group) { set_flash('error', 'Group not found.'); redirect(url('finance', 'groups')); }

$tab = input('tab') ?: 'info';

// Group members
$members = db_fetch_all(
    "SELECT gm.id AS gm_id, gm.added_at, s.id AS student_id, s.full_name, s.admission_no, s.gender, s.phone,
            c.name AS class_name
       FROM fin_group_members gm
       JOIN students s ON gm.student_id = s.id
       LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
       LEFT JOIN classes c ON e.class_id = c.id
      WHERE gm.group_id = ?
      ORDER BY s.full_name",
    [$id]
);
$memberIds = array_column($members, 'student_id');

// For assign students tab: available students not in group
$assignClassId  = input_int('assign_class_id');
$assignGender   = input('assign_gender');
$assignSearch   = input('assign_search');

$classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");

// Available fees for group action
$allFees = db_fetch_all("SELECT id, description, amount, currency FROM fin_fees WHERE is_active = 1 ORDER BY description");

ob_start();
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <a href="<?= url('finance', 'groups') ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-dark-muted hover:text-primary-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Groups
        </a>
    </div>

    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text"><?= e($group['name']) ?></h1>

    <!-- Tab Nav -->
    <div class="flex flex-wrap gap-1 border-b border-gray-200 dark:border-dark-border">
        <?php
        $tabs = ['info' => 'Group Information', 'assign' => 'Assign Students', 'action' => 'Take Action'];
        foreach ($tabs as $key => $label): ?>
        <a href="<?= url('finance', 'group-detail', $id) ?>&tab=<?= $key ?>"
           class="px-4 py-2 text-sm font-medium border-b-2 transition-colors <?= $tab === $key ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 dark:text-dark-muted hover:text-gray-700 dark:text-gray-300' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ═══ TAB: GROUP INFORMATION ═══ -->
    <?php if ($tab === 'info'): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Group Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-4">
            <div>
                <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Group Name</p>
                <p class="text-sm font-medium text-gray-900 dark:text-dark-text"><?= e($group['name']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Group Description</p>
                <p class="text-sm text-gray-900 dark:text-dark-text"><?= e($group['description'] ?? '—') ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Date Created</p>
                <p class="text-sm text-gray-900 dark:text-dark-text"><?= format_datetime($group['created_at']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Date Modified</p>
                <p class="text-sm text-gray-900 dark:text-dark-text"><?= format_datetime($group['updated_at']) ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-dark-muted uppercase font-semibold">Active</p>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $group['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 dark:bg-dark-card2 text-gray-500 dark:text-dark-muted' ?>">
                    <?= $group['is_active'] ? 'Yes' : 'No' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Group Members -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text">Group Members (<?= count($members) ?>)</h2>
            <div class="flex gap-2">
                <a href="<?= url('finance', 'export-pdf') ?>&group_id=<?= $id ?>" target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-50 text-red-700 text-xs rounded-lg hover:bg-red-100 font-medium border border-red-200">Download PDF</a>
                <a href="<?= url('finance', 'export-excel') ?>&group_id=<?= $id ?>" target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-50 text-green-700 text-xs rounded-lg hover:bg-green-100 font-medium border border-green-200">Download Excel</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Student Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Gender</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Added</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($members)): ?>
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No members in this group.</td></tr>
                    <?php else: foreach ($members as $m): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                        <td class="px-4 py-3 text-sm" data-label="Code">
                            <a href="<?= url('finance', 'student-detail', $m['student_id']) ?>" class="text-primary-600 hover:text-primary-800 font-semibold underline"><?= e($m['admission_no']) ?></a>
                        </td>
                        <td class="px-4 py-3 text-sm" data-label="Name"><?= e($m['full_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Class"><?= e($m['class_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted" data-label="Gender"><?= ucfirst(e($m['gender'])) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-dark-muted" data-label="Added"><?= format_date($m['added_at']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ TAB: ASSIGN STUDENTS ═══ -->
    <?php elseif ($tab === 'assign'): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Assign Students to Group</h2>

        <!-- Filters -->
        <form method="GET" action="<?= url('finance', 'group-detail', $id) ?>" class="mb-4">
            <input type="hidden" name="tab" value="assign">
            <div class="flex flex-wrap gap-3">
                <input type="text" name="assign_search" value="<?= e($assignSearch) ?>" placeholder="Search by name…"
                       class="flex-1 min-w-48 px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                <select name="assign_class_id" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $assignClassId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="assign_gender" class="px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm">
                    <option value="">All Genders</option>
                    <option value="male" <?= $assignGender === 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= $assignGender === 'female' ? 'selected' : '' ?>>Female</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Filter</button>
            </div>
        </form>

        <!-- Available Students (not in group) -->
        <?php
        $avWhere = ["s.deleted_at IS NULL", "s.status = 'active'"];
        $avParams = [];
        if (!empty($memberIds)) {
            $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
            $avWhere[] = "s.id NOT IN ($placeholders)";
            $avParams = array_merge($avParams, $memberIds);
        }
        if ($assignSearch) {
            $avWhere[] = "s.full_name LIKE ?";
            $avParams[] = "%$assignSearch%";
        }
        if ($assignClassId) {
            $avWhere[] = "e.class_id = ?";
            $avParams[] = $assignClassId;
        }
        if ($assignGender) {
            $avWhere[] = "s.gender = ?";
            $avParams[] = $assignGender;
        }
        $avWhereClause = implode(' AND ', $avWhere);
        $available = db_fetch_all(
            "SELECT DISTINCT s.id, s.full_name, s.admission_no, s.gender,
                    c.name AS class_name
               FROM students s
               LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
               LEFT JOIN classes c ON e.class_id = c.id
              WHERE $avWhereClause
              ORDER BY s.full_name LIMIT 100", $avParams
        );
        ?>

        <form method="POST" action="<?= url('finance', 'group-assign-members') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="group_id" value="<?= $id ?>">
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border">
                    <thead class="bg-gray-50 dark:bg-dark-bg">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted"><input type="checkbox" id="checkAllAssign" onchange="toggleAll(this, 'student_ids[]')"></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Student Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Class</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Gender</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                        <?php if (empty($available)): ?>
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No students available to assign.</td></tr>
                        <?php else: foreach ($available as $av): ?>
                        <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                            <td class="px-4 py-3"><input type="checkbox" name="student_ids[]" value="<?= $av['id'] ?>"></td>
                            <td class="px-4 py-3 text-sm"><?= e($av['admission_no']) ?></td>
                            <td class="px-4 py-3 text-sm"><?= e($av['full_name']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= e($av['class_name'] ?? '—') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= ucfirst(e($av['gender'])) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($available)): ?>
            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Assign Members</button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Current group members in assign tab -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Current Group Members (<?= count($members) ?>)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border responsive-table">
                <thead class="bg-gray-50 dark:bg-dark-bg">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Student Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Gender</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                    <?php if (empty($members)): ?>
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No members yet.</td></tr>
                    <?php else: foreach ($members as $m): ?>
                    <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                        <td class="px-4 py-3 text-sm"><?= e($m['admission_no']) ?></td>
                        <td class="px-4 py-3 text-sm"><?= e($m['full_name']) ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= e($m['class_name'] ?? '—') ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= ucfirst(e($m['gender'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ TAB: TAKE ACTION ═══ -->
    <?php elseif ($tab === 'action'): ?>
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Take Action on Group</h2>

        <form method="POST" action="<?= url('finance', 'group-action') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="group_id" value="<?= $id ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action *</label>
                    <select name="action_type" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm">
                        <option value="">— Select Action —</option>
                        <option value="assign_fee">Assign Fee</option>
                        <option value="remove_fee">Remove Fee</option>
                        <option value="adjust_balance">Adjust Balance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fee *</label>
                    <select name="fee_id" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm">
                        <option value="">— Select Fee —</option>
                        <?php foreach ($allFees as $f): ?>
                        <option value="<?= $f['id'] ?>"><?= e($f['description']) ?> (<?= format_money($f['amount']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adjustment Amount</label>
                    <input type="number" name="amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm" placeholder="For adjustments only">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" name="part_payments" value="1" id="partPay" class="rounded">
                    <label for="partPay" class="text-sm text-gray-700 dark:text-gray-300">Allow Part Payments</label>
                </div>
            </div>

            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm hover:bg-primary-700 font-medium">Apply to All Members</button>
        </form>
    </div>

    <!-- Remove Members Section -->
    <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-dark-text mb-4">Remove Members</h2>
        <form method="POST" action="<?= url('finance', 'group-remove-members') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="group_id" value="<?= $id ?>">
            <div class="overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-dark-border">
                    <thead class="bg-gray-50 dark:bg-dark-bg">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted"><input type="checkbox" id="checkAllRemove" onchange="toggleAll(this, 'remove_ids[]')"></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Student Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-dark-muted uppercase">Class</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                        <?php if (empty($members)): ?>
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">No members to remove.</td></tr>
                        <?php else: foreach ($members as $m): ?>
                        <tr class="hover:bg-gray-50 dark:bg-dark-bg">
                            <td class="px-4 py-3"><input type="checkbox" name="remove_ids[]" value="<?= $m['gm_id'] ?>"></td>
                            <td class="px-4 py-3 text-sm"><?= e($m['admission_no']) ?></td>
                            <td class="px-4 py-3 text-sm"><?= e($m['full_name']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-dark-muted"><?= e($m['class_name'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($members)): ?>
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 font-medium">Remove Selected Members</button>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleAll(source, name) {
    var checkboxes = document.querySelectorAll('input[name="' + name + '"]');
    checkboxes.forEach(function(cb) { cb.checked = source.checked; });
}
</script>

<?php
$content = ob_get_clean();
$page_title = $pageTitle;
include TEMPLATES_PATH . '/layout.php';
