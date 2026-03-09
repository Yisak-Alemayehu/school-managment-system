<?php
/**
 * HR — Biometric Attendance Devices View
 */

$devices = db_fetch_all(
    "SELECT d.*,
            (SELECT COUNT(*) FROM hr_employee_biometric WHERE device_id = d.id AND is_active = 1) AS enrolled_count,
            (SELECT COUNT(*) FROM hr_attendance_logs WHERE device_id = d.id AND processed = 0) AS pending_logs
     FROM hr_attendance_devices d
     ORDER BY d.device_name"
);

ob_start();
?>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Attendance Devices</h1>
        <button onclick="openDevModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Device
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($devices as $dev): ?>
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-5">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-dark-text"><?= e($dev['device_name']) ?></h3>
                    <p class="text-xs text-gray-400 mt-0.5"><?= e($dev['device_model']) ?> &bull; <?= e($dev['connection_type']) ?></p>
                </div>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-full <?= $dev['status'] === 'active' ? 'bg-green-100 text-green-700' : ($dev['status'] === 'maintenance' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') ?>">
                    <?= ucfirst($dev['status']) ?>
                </span>
            </div>
            <dl class="text-sm space-y-1 text-gray-600 dark:text-dark-muted">
                <div class="flex justify-between"><dt>IP Address</dt><dd class="font-mono"><?= e($dev['ip_address'] ?? '—') ?></dd></div>
                <div class="flex justify-between"><dt>Port</dt><dd class="font-mono"><?= e($dev['port'] ?? '4370') ?></dd></div>
                <div class="flex justify-between"><dt>Location</dt><dd><?= e($dev['location'] ?? '—') ?></dd></div>
                <div class="flex justify-between"><dt>Enrolled</dt><dd><?= (int)$dev['enrolled_count'] ?> employees</dd></div>
                <div class="flex justify-between"><dt>Pending Logs</dt><dd>
                    <?php if ((int)$dev['pending_logs'] > 0): ?>
                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded text-xs font-semibold"><?= (int)$dev['pending_logs'] ?></span>
                    <?php else: ?>
                    <span class="text-gray-400">0</span>
                    <?php endif; ?>
                </dd></div>
                <div class="flex justify-between"><dt>Last Sync</dt><dd><?= $dev['last_sync'] ? date('M d, H:i', strtotime($dev['last_sync'])) : '—' ?></dd></div>
            </dl>
            <div class="flex gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-dark-border">
                <button onclick='editDev(<?= json_encode($dev) ?>)' class="text-xs text-amber-600 hover:text-amber-800 font-medium">Edit</button>
                <?php if ($dev['status'] === 'active'): ?>
                <form method="POST" action="<?= url('hr', 'device-sync') ?>" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="device_id" value="<?= $dev['id'] ?>">
                    <button type="submit" class="text-xs text-primary-600 hover:text-primary-800 font-medium">Sync Now</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($devices)): ?>
        <div class="col-span-full text-center py-8 text-gray-400">No attendance devices registered.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Device Modal -->
<div id="devModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-dark-card rounded-xl w-full max-w-md p-6 m-4">
        <h3 id="devTitle" class="text-lg font-semibold text-gray-900 dark:text-dark-text mb-4">Add Device</h3>
        <form method="POST" action="<?= url('hr', 'device-save') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="dev_id" value="">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Device Name *</label>
                    <input type="text" name="device_name" id="dev_name" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" placeholder="e.g. Main Gate Device">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Model</label>
                        <select name="device_model" id="dev_model" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                            <option value="ZKTeco">ZKTeco</option>
                            <option value="DigitalPersona">DigitalPersona</option>
                            <option value="Suprema">Suprema</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Connection</label>
                        <select name="connection_type" id="dev_conn" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                            <option value="api">API</option>
                            <option value="sdk">SDK</option>
                            <option value="database">Database</option>
                            <option value="csv">CSV Import</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">IP Address</label>
                        <input type="text" name="ip_address" id="dev_ip" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" placeholder="192.168.1.100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Port</label>
                        <input type="number" name="port" id="dev_port" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" placeholder="4370" value="4370">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Location</label>
                    <input type="text" name="location" id="dev_loc" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" placeholder="e.g. Main Entrance">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-dark-muted mb-1">Status</label>
                    <select name="status" id="dev_status" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closeDevModal()" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDevModal() {
    document.getElementById('devTitle').textContent = 'Add Device';
    document.getElementById('dev_id').value = '';
    document.getElementById('dev_name').value = '';
    document.getElementById('dev_model').value = 'ZKTeco';
    document.getElementById('dev_conn').value = 'api';
    document.getElementById('dev_ip').value = '';
    document.getElementById('dev_port').value = '4370';
    document.getElementById('dev_loc').value = '';
    document.getElementById('dev_status').value = 'active';
    document.getElementById('devModal').classList.remove('hidden');
}
function editDev(d) {
    document.getElementById('devTitle').textContent = 'Edit Device';
    document.getElementById('dev_id').value = d.id;
    document.getElementById('dev_name').value = d.device_name;
    document.getElementById('dev_model').value = d.device_model || 'ZKTeco';
    document.getElementById('dev_conn').value = d.connection_type || 'api';
    document.getElementById('dev_ip').value = d.ip_address || '';
    document.getElementById('dev_port').value = d.port || '4370';
    document.getElementById('dev_loc').value = d.location || '';
    document.getElementById('dev_status').value = d.status;
    document.getElementById('devModal').classList.remove('hidden');
}
function closeDevModal() { document.getElementById('devModal').classList.add('hidden'); }
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
