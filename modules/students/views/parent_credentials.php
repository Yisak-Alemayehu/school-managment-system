<?php
/**
 * Parents — Generate Username & Password
 * Generate login credentials for guardian/parent accounts.
 * Admin-only. Guardians without a user account will have one created;
 * those who already have one can be overwritten.
 */

// All guardians for preview (no class filter — parents span classes)
$search    = trim($_GET['search'] ?? '');
$withoutOnly = !empty($_GET['without_only']);

$sql    = "SELECT g.id, g.full_name, g.phone, g.relation, u.username,
                  GROUP_CONCAT(DISTINCT s.full_name ORDER BY s.full_name SEPARATOR ', ') AS children
             FROM guardians g
             LEFT JOIN users u ON u.id = g.user_id
             LEFT JOIN student_guardians sg ON sg.guardian_id = g.id
             LEFT JOIN students s ON s.id = sg.student_id AND s.deleted_at IS NULL";
$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = "(g.first_name LIKE ? OR g.last_name LIKE ? OR g.phone LIKE ?)";
    $like     = "%$search%";
    $params   = [$like, $like, $like];
}
if ($withoutOnly) {
    $where[] = "g.user_id IS NULL";
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' GROUP BY g.id ORDER BY g.full_name';

$guardians = db_fetch_all($sql, $params);

ob_start();
?>

<div class="max-w-4xl mx-auto space-y-6">
    <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Generate Parent Username &amp; Password</h1>

    <?php if ($msg = get_flash('success')): ?>
        <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>

    <!-- Filter bar -->
    <form method="GET" action="<?= url('students', 'parent-credentials') ?>" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs text-gray-500 dark:text-dark-muted mb-1">Search by name or phone</label>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="e.g. Getachew or 091…"
                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-dark-muted pb-0.5">
            <input type="checkbox" name="without_only" value="1" <?= $withoutOnly ? 'checked' : '' ?> onchange="this.form.submit()">
            Show only parents without credentials
        </label>
        <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-dark-card2 border border-gray-300 dark:border-dark-border text-sm rounded-lg hover:bg-gray-200 dark:text-dark-text">Filter</button>
    </form>

    <!-- Credentials table & bulk action -->
    <form method="POST" action="<?= url('students', 'parent-credentials') ?>">
        <?= csrf_field() ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-dark-border flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Username format:
                    <select name="username_format" class="ml-1 px-2 py-1 border border-gray-300 dark:border-dark-border dark:bg-dark-card dark:text-dark-text rounded-md text-sm">
                        <option value="phone">Phone number</option>
                        <option value="firstlast">firstname.lastname</option>
                        <option value="firstname_id">firstname + guardian ID</option>
                    </select>
                </label>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Password:
                    <select id="parentPasswordMode" name="password_mode" class="ml-1 px-2 py-1 border border-gray-300 dark:border-dark-border dark:bg-dark-card dark:text-dark-text rounded-md text-sm">
                        <option value="phone">Phone number</option>
                        <option value="random">Random 8-char</option>
                        <option value="custom">Custom</option>
                    </select>
                </label>
                <div id="parentCustomPasswordSection" class="hidden">
                    <input type="text" name="custom_password" placeholder="Custom password"
                           class="px-2 py-1 border border-gray-300 dark:border-dark-border rounded-md text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-dark-muted">
                    <input type="checkbox" name="overwrite" value="1">
                    Overwrite existing credentials
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-dark-bg border-b border-gray-200 dark:border-dark-border">
                        <tr>
                            <th class="px-4 py-2 text-left w-8">
                                <input type="checkbox" id="checkAllParents"
                                       onchange="document.querySelectorAll('input[name=\'ids[]\']').forEach(c=>c.checked=this.checked)">
                            </th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Parent / Guardian</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Phone</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Relation</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Children</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-700 dark:text-gray-300">Current Username</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                        <?php if (empty($guardians)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-400 dark:text-gray-500 italic">No parents found.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($guardians as $g): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-dark-bg">
                            <td class="px-4 py-2">
                                <input type="checkbox" name="ids[]" value="<?= $g['id'] ?>" checked>
                            </td>
                            <td class="px-4 py-2 font-medium text-gray-900 dark:text-dark-text"><?= e($g['full_name']) ?></td>
                            <td class="px-4 py-2 text-gray-600 dark:text-dark-muted font-mono text-xs"><?= e($g['phone']) ?></td>
                            <td class="px-4 py-2 text-gray-500 dark:text-dark-muted capitalize"><?= e($g['relation']) ?></td>
                            <td class="px-4 py-2 text-gray-500 dark:text-dark-muted text-xs"><?= e($g['children'] ?? '—') ?></td>
                            <td class="px-4 py-2 font-mono text-xs">
                                <?php if ($g['username']): ?>
                                    <span class="text-green-600"><?= e($g['username']) ?></span>
                                <?php else: ?>
                                    <span class="text-yellow-600">Not set</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($guardians)): ?>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-dark-border flex justify-end">
                <button type="submit" class="px-5 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
                    Generate Credentials
                </button>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
(function () {
    var mode    = document.getElementById('parentPasswordMode');
    var section = document.getElementById('parentCustomPasswordSection');
    if (!mode || !section) return;
    function sync() { section.classList.toggle('hidden', mode.value !== 'custom'); }
    mode.addEventListener('change', sync);
    sync();
})();
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
