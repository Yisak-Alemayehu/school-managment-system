<?php
/**
 * Students — Bulk Import
 * Upload a CSV file to import multiple students at once.
 * Photos can be added later by editing individual student profiles.
 */

ob_start();
?>

<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Add Bulk Data</h1>
        <a href="<?= url('students', 'sample-csv') ?>"
           class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-sm rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download Sample Excel / CSV
        </a>
    </div>

    <?php if ($msg = get_flash('success')): ?>
        <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($results = get_flash('import_results')): ?>
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
        <?= $results ?>
    </div>
    <?php endif; ?>

    <!-- Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800 space-y-2">
        <p class="font-semibold">Instructions</p>
        <ul class="list-disc list-inside space-y-1 text-xs">
            <li>Download the sample CSV file above and fill in student data.</li>
            <li>Required columns: <code class="bg-blue-100 px-1 rounded">first_name, last_name, gender, date_of_birth, class_name, section_name</code></li>
            <li>Optional columns: <code class="bg-blue-100 px-1 rounded">admission_no, phone, email, religion, blood_group, guardian_name, guardian_phone, address</code></li>
            <li>Date format: <strong>M/D/YYYY</strong> (e.g. 3/15/2010) or YYYY-MM-DD — both are accepted.</li>
            <li>Phone numbers without leading zero (e.g. <code class="bg-blue-100 px-1 rounded">911223344</code>).</li>
            <li>Photos can be added later by editing each student's profile.</li>
            <li>Rows with missing required fields will be skipped and reported.</li>
        </ul>
    </div>

    <!-- Upload Form -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="<?= url('students', 'bulk-import') ?>" enctype="multipart/form-data" id="importForm">
            <?= csrf_field() ?>

            <!-- File drop zone -->
            <div class="border-2 border-dashed border-gray-300 rounded-xl p-10 text-center mb-6 hover:border-primary-400 transition-colors"
                 id="dropZone"
                 ondragover="event.preventDefault();this.classList.add('border-primary-500','bg-primary-50')"
                 ondragleave="this.classList.remove('border-primary-500','bg-primary-50')"
                 ondrop="handleDrop(event)">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-sm text-gray-500 mb-1">Drag &amp; drop your CSV file here, or</p>
                <label class="cursor-pointer text-primary-600 hover:text-primary-700 text-sm font-medium underline">
                    browse to upload
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,.xlsx,.xls" class="hidden" onchange="showFile(this)">
                </label>
                <p id="fileName" class="mt-2 text-xs text-gray-400"></p>
            </div>

            <!-- Options -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default Class (if not in CSV)</label>
                    <select name="default_class_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">— from CSV column —</option>
                        <?php
                        $classes = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
                        foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">On Duplicate Admission No.</label>
                    <select name="duplicate_mode"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="skip">Skip row</option>
                        <option value="update">Update existing</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-between items-center">
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="send_credentials" value="1">
                    Generate login credentials for imported students
                </label>
                <button type="submit" id="submitBtn"
                        class="px-6 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 font-medium disabled:opacity-50">
                    Import Students
                </button>
            </div>
        </form>
    </div>

    <!-- Column Reference -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700">CSV Column Reference</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-700">Column Name</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-700">Required</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-700">Example</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-700">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $cols = [
                        ['first_name',     true,  'Abebe',      'Student first name'],
                        ['last_name',      true,  'Kebede',     'Student last name / father name'],
                        ['gender',         true,  'male',       '"male" or "female"'],
                        ['date_of_birth',  true,  '3/15/2010',  'M/D/YYYY or YYYY-MM-DD'],
                        ['class_name',     true,  'Grade 5',    'Must match an existing class'],
                        ['section_name',   true,  'A',          'Must match an existing section'],
                        ['admission_no',   false, 'STU-101',    'Auto-generated if blank'],
                        ['phone',          false, '0911223344', 'Student contact'],
                        ['email',          false, 'a@b.com',    ''],
                        ['religion',       false, 'Orthodox',   ''],
                        ['blood_group',    false, 'O+',         ''],
                        ['guardian_name',  false, 'Kebede Ali', 'Primary guardian'],
                        ['guardian_phone', false, '0922334455', ''],
                        ['address',        false, 'Addis Ababa',''],
                    ]; foreach ($cols as [$col, $req, $ex, $note]): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-gray-800"><?= $col ?></td>
                        <td class="px-4 py-2">
                            <?php if ($req): ?>
                                <span class="text-red-600 font-semibold">Required</span>
                            <?php else: ?>
                                <span class="text-gray-400">Optional</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2 font-mono text-gray-500"><?= e($ex) ?></td>
                        <td class="px-4 py-2 text-gray-500"><?= e($note) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showFile(input) {
    var name = input.files[0] ? input.files[0].name : '';
    document.getElementById('fileName').textContent = name ? '📄 ' + name : '';
}
function handleDrop(e) {
    e.preventDefault();
    var dropZone = document.getElementById('dropZone');
    dropZone.classList.remove('border-primary-500', 'bg-primary-50');
    var file = e.dataTransfer.files[0];
    if (!file) return;
    var input = document.getElementById('csv_file');
    // Transfer file to input via DataTransfer
    var dt = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    document.getElementById('fileName').textContent = '📄 ' + file.name;
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/templates/layout.php';
