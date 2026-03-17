<?php
/**
 * Students — Admission Form (Create)
 * Supports: first/last name, mandatory phone, Ethiopian address fields,
 * mandatory photo, multiple guardians with dynamic "Add Guardian" button,
 * guardian search (sibling detection), returning student search for re-enrollment.
 */

$classes  = db_fetch_all("SELECT id, name, numeric_name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$sections = db_fetch_all("SELECT id, name, class_id FROM sections WHERE is_active = 1 ORDER BY name");
$session  = get_active_session();
$sessions = db_fetch_all("SELECT id, name FROM academic_sessions ORDER BY start_date DESC LIMIT 5");

require_once APP_ROOT . '/core/ethiopian_calendar.php';

ob_start();
partial('ethiopian_datepicker');
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('students') ?>" class="p-1 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:text-dark-muted rounded">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-dark-text">Student Admission</h1>
    </div>

    <?php if ($msg = get_flash('error')): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('success')): ?>
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"><?= $msg ?></div>
    <?php endif; ?>

    <!-- ─── Returning Student Search ─────────────────────── -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6 mb-6">
        <h2 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Search Returning Student (Re-enrollment)
        </h2>
        <p class="text-xs text-blue-700 dark:text-blue-400 mb-3">Search for a student from a previous academic session to re-enroll them. Their information will be auto-filled.</p>
        <div class="flex gap-3">
            <input type="text" id="returningStudentSearch" placeholder="Search by name, admission number, or phone..."
                   class="flex-1 px-3 py-2 border border-blue-300 dark:border-blue-700 rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-blue-500">
            <button type="button" onclick="searchReturningStudent()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition">Search</button>
        </div>
        <div id="returningStudentResults" class="mt-3 hidden">
            <div class="bg-white dark:bg-dark-card rounded-lg border border-blue-200 dark:border-blue-700 max-h-48 overflow-y-auto"></div>
        </div>
    </div>

    <form method="POST" action="<?= url('students', 'create') ?>" enctype="multipart/form-data" class="space-y-6" id="admissionForm">
        <?= csrf_field() ?>
        <input type="hidden" name="returning_student_id" id="returning_student_id" value="">
        <input type="hidden" name="use_existing_guardians" id="use_existing_guardians" value="0">

        <!-- ─── Personal Information ─────────────────────────── -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b">Personal Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="<?= e(old('first_name')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <?php if ($err = get_validation_error('first_name')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="<?= e(old('last_name')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <?php if ($err = get_validation_error('last_name')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender <span class="text-red-500">*</span></label>
                    <select id="gender" name="gender" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">Select...</option>
                        <option value="male" <?= old('gender') === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= old('gender') === 'female' ? 'selected' : '' ?>>Female</option>
                    </select>
                    <?php if ($err = get_validation_error('gender')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Birth (EC) <span class="text-red-500">*</span></label>
                    <div x-data="ecDatePicker({ value: '<?= e(old('date_of_birth_ec')) ?>', name: 'date_of_birth_ec', required: true })">
                        <div class="flex gap-2">
                            <select x-model="day" @change="updateValue()" class="w-16 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                                <option value="">Day</option>
                                <?php for ($d = 1; $d <= 30; $d++): ?><option value="<?= $d ?>"><?= $d ?></option><?php endfor; ?>
                            </select>
                            <select x-model="month" @change="updateValue()" class="flex-1 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text">
                                <option value="">Month</option>
                                <?php foreach (ec_month_names() as $mnum => $mdata): ?>
                                    <option value="<?= $mnum ?>"><?= e($mdata['en']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" x-model="year" @change="updateValue()" placeholder="Year" class="w-24 px-2 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text" min="1900" max="2100">
                        </div>
                        <input type="hidden" :name="fieldName" :value="formatted" :required="isRequired">
                    </div>
                    <?php if ($err = get_validation_error('date_of_birth_ec')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="blood_group" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Blood Group</label>
                    <select id="blood_group" name="blood_group" class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">Select...</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= old('blood_group') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="religion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Religion</label>
                    <input type="text" id="religion" name="religion" value="<?= e(old('religion')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone <span class="text-red-500">*</span></label>
                    <input type="text" id="phone" name="phone" value="<?= e(old('phone')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500" placeholder="+251...">
                    <?php if ($err = get_validation_error('phone')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="medical_conditions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Medical Conditions</label>
                    <input type="text" id="medical_conditions" name="medical_conditions" value="<?= e(old('medical_conditions')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- ─── Photo Upload ─────────────────────────────────── -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b">Student Photo <span class="text-red-500">*</span></h2>
            <div class="flex items-start gap-6">
                <div class="flex-shrink-0">
                    <div id="photoPreview" class="w-32 h-32 rounded-lg border-2 border-dashed border-gray-300 dark:border-dark-border flex items-center justify-center bg-gray-50 dark:bg-dark-bg overflow-hidden">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                </div>
                <div class="flex-1">
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-primary-50 file:text-primary-700 file:text-sm"
                           onchange="previewPhoto(this)">
                    <p class="mt-1 text-xs text-gray-500 dark:text-dark-muted">JPEG or PNG, max 2 MB. This photo will appear on the student ID card.</p>
                    <?php if ($err = get_validation_error('photo')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ─── Address Section ──────────────────────────────── -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b">Address Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country <span class="text-red-500">*</span></label>
                    <input type="text" id="country" name="country" value="<?= e(old('country') ?: 'Ethiopia') ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('country')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="region" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Region <span class="text-red-500">*</span></label>
                    <input type="text" id="region" name="region" value="<?= e(old('region')) ?>" required placeholder="e.g. Addis Ababa"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('region')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City <span class="text-red-500">*</span></label>
                    <input type="text" id="city" name="city" value="<?= e(old('city')) ?>" required placeholder="e.g. Addis Ababa"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('city')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="sub_city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sub-city <span class="text-red-500">*</span></label>
                    <input type="text" id="sub_city" name="sub_city" value="<?= e(old('sub_city')) ?>" required placeholder="e.g. Bole"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('sub_city')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="woreda" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Woreda <span class="text-red-500">*</span></label>
                    <input type="text" id="woreda" name="woreda" value="<?= e(old('woreda')) ?>" required placeholder="e.g. 03"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('woreda')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="house_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">House Number <span class="text-red-500">*</span></label>
                    <input type="text" id="house_number" name="house_number" value="<?= e(old('house_number')) ?>" required placeholder="e.g. 123 or NEW"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-dark-muted">Enter "NEW" if the house has no assigned number.</p>
                    <?php if ($err = get_validation_error('house_number')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Additional Address Details</label>
                    <textarea id="address" name="address" rows="2" placeholder="Street name, landmarks, etc."
                              class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500"><?= e(old('address')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- ─── Enrollment ───────────────────────────────────── -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text mb-4 pb-2 border-b">Enrollment Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class <span class="text-red-500">*</span></label>
                    <select id="class_id" name="class_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500" onchange="loadSections(this.value)">
                        <option value="">Select class...</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= $cls['id'] ?>" <?= old('class_id') == $cls['id'] ? 'selected' : '' ?>><?= e($cls['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err = get_validation_error('class_id')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="section_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Section <span class="text-red-500">*</span></label>
                    <select id="section_id" name="section_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        <option value="">Select section...</option>
                    </select>
                    <?php if ($err = get_validation_error('section_id')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="admission_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Admission Date <span class="text-red-500">*</span></label>
                    <input type="date" id="admission_date" name="admission_date" value="<?= e(old('admission_date') ?: date('Y-m-d')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="previous_school" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Previous School</label>
                    <input type="text" id="previous_school" name="previous_school" value="<?= e(old('previous_school')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- ─── Guardian Information (Multiple) ──────────────── -->
        <div class="bg-white dark:bg-dark-card rounded-xl border border-gray-200 dark:border-dark-border p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-dark-text">Guardian Information</h2>
                <div class="flex gap-2">
                    <button type="button" onclick="addGuardian()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary-50 text-primary-700 hover:bg-primary-100 text-xs font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add New
                    </button>
                </div>
            </div>

            <!-- Guardian Search (Sibling Detection) -->
            <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <p class="text-xs text-yellow-800 dark:text-yellow-300 mb-2 font-medium">Search existing guardians (for siblings already registered)</p>
                <div class="flex gap-2">
                    <input type="text" id="guardianSearchInput" placeholder="Search by phone number or name..."
                           class="flex-1 px-3 py-1.5 border border-yellow-300 dark:border-yellow-700 rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-yellow-500">
                    <button type="button" onclick="searchGuardian()" class="px-3 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium rounded-lg transition">Search</button>
                </div>
                <div id="guardianSearchResults" class="mt-2 hidden">
                    <div class="bg-white dark:bg-dark-card rounded-lg border border-yellow-200 dark:border-yellow-700 max-h-40 overflow-y-auto"></div>
                </div>
            </div>

            <div id="guardiansContainer">
                <!-- Guardian #1 (default, cannot be removed) -->
                <div class="guardian-block border border-gray-100 dark:border-dark-border rounded-lg p-4 mb-4 bg-gray-50 dark:bg-dark-bg" data-guardian-index="0">
                    <input type="hidden" name="guardians[0][existing_guardian_id]" value="" class="existing-guardian-id">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Guardian #1 <span class="text-xs text-green-600">(Primary)</span></h3>
                        <span class="existing-guardian-badge hidden text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Existing Guardian</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name <span class="text-red-500">*</span></label>
                            <input type="text" name="guardians[0][first_name]" value="<?= e($_POST['guardians'][0]['first_name'] ?? '') ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name <span class="text-red-500">*</span></label>
                            <input type="text" name="guardians[0][last_name]" value="<?= e($_POST['guardians'][0]['last_name'] ?? '') ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Relationship <span class="text-red-500">*</span></label>
                            <select name="guardians[0][relation]" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                                <option value="">Select...</option>
                                <?php foreach (['father','mother','guardian','uncle','aunt','sibling','grandparent','other'] as $rel): ?>
                                    <option value="<?= $rel ?>" <?= ($_POST['guardians'][0]['relation'] ?? '') === $rel ? 'selected' : '' ?>><?= ucfirst($rel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone <span class="text-red-500">*</span></label>
                            <input type="text" name="guardians[0][phone]" value="<?= e($_POST['guardians'][0]['phone'] ?? '') ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500" placeholder="+251...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                            <input type="email" name="guardians[0][email]" value="<?= e($_POST['guardians'][0]['email'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Occupation</label>
                            <input type="text" name="guardians[0][occupation]" value="<?= e($_POST['guardians'][0]['occupation'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($err = get_validation_error('guardians')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
        </div>

        <div class="flex justify-end gap-3">
            <a href="<?= url('students') ?>" class="px-4 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:bg-dark-bg">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                Register Student
            </button>
        </div>
    </form>
</div>

<!-- ─── Guardian Template (hidden) ────────────────────────── -->
<template id="guardianTemplate">
    <div class="guardian-block border border-gray-100 dark:border-dark-border rounded-lg p-4 mb-4 bg-gray-50 dark:bg-dark-bg" data-guardian-index="__INDEX__">
        <input type="hidden" name="guardians[__INDEX__][existing_guardian_id]" value="" class="existing-guardian-id">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Guardian #__DISPLAY__</h3>
            <div class="flex items-center gap-2">
                <span class="existing-guardian-badge hidden text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Existing Guardian</span>
                <button type="button" onclick="removeGuardian(this)" class="text-red-500 hover:text-red-700 text-xs font-medium flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Remove
            </button>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">First Name <span class="text-red-500">*</span></label>
                <input type="text" name="guardians[__INDEX__][first_name]" required
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Last Name <span class="text-red-500">*</span></label>
                <input type="text" name="guardians[__INDEX__][last_name]" required
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Relationship <span class="text-red-500">*</span></label>
                <select name="guardians[__INDEX__][relation]" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
                    <option value="">Select...</option>
                    <option value="father">Father</option>
                    <option value="mother">Mother</option>
                    <option value="guardian">Guardian</option>
                    <option value="uncle">Uncle</option>
                    <option value="aunt">Aunt</option>
                    <option value="sibling">Sibling</option>
                    <option value="grandparent">Grandparent</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone <span class="text-red-500">*</span></label>
                <input type="text" name="guardians[__INDEX__][phone]" required
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500" placeholder="+251...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="guardians[__INDEX__][email]"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Occupation</label>
                <input type="text" name="guardians[__INDEX__][occupation]"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">
            </div>
        </div>
    </div>
</template>

<script>
// ─── Section loader ──────────────────────────────────────────
var allSections = <?= json_encode($sections) ?>;

function loadSections(classId) {
    var select = document.getElementById('section_id');
    select.innerHTML = '<option value="">Select section...</option>';
    allSections.forEach(function(s) {
        if (s.class_id == classId) {
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            select.appendChild(opt);
        }
    });
}

var selectedClass = document.getElementById('class_id').value;
if (selectedClass) loadSections(selectedClass);
var oldSection = '<?= e(old('section_id')) ?>';
if (oldSection) document.getElementById('section_id').value = oldSection;

// ─── Photo preview ───────────────────────────────────────────
function previewPhoto(input) {
    var preview = document.getElementById('photoPreview');
    if (input.files && input.files[0]) {
        var file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            alert('Photo must be under 2 MB.');
            input.value = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
        };
        reader.readAsDataURL(file);
    }
}

// ─── Dynamic guardians ──────────────────────────────────────
var guardianIndex = 1;

function addGuardian() {
    var template = document.getElementById('guardianTemplate').content.cloneNode(true);
    var html = template.querySelector('.guardian-block').outerHTML;
    html = html.replace(/__INDEX__/g, guardianIndex);
    html = html.replace(/__DISPLAY__/g, guardianIndex + 1);
    var container = document.getElementById('guardiansContainer');
    container.insertAdjacentHTML('beforeend', html);
    guardianIndex++;
}

function removeGuardian(btn) {
    var block = btn.closest('.guardian-block');
    block.remove();
    renumberGuardians();
}

function renumberGuardians() {
    var blocks = document.querySelectorAll('#guardiansContainer .guardian-block');
    blocks.forEach(function(b, i) {
        var h3 = b.querySelector('h3');
        if (i === 0) {
            h3.innerHTML = 'Guardian #' + (i + 1) + ' <span class="text-xs text-green-600">(Primary)</span>';
        } else {
            h3.textContent = 'Guardian #' + (i + 1);
        }
    });
}

// ─── Returning Student Search ────────────────────────────────
function searchReturningStudent() {
    var q = document.getElementById('returningStudentSearch').value.trim();
    if (q.length < 2) { alert('Enter at least 2 characters.'); return; }

    var container = document.getElementById('returningStudentResults');
    var inner = container.querySelector('div');
    inner.innerHTML = '<p class="p-3 text-sm text-gray-500">Searching...</p>';
    container.classList.remove('hidden');

    fetch('<?= url('api') ?>&action=student-search&q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.data || data.data.length === 0) {
                inner.innerHTML = '<p class="p-3 text-sm text-gray-500">No students found.</p>';
                return;
            }
            var html = '';
            data.data.forEach(function(s) {
                html += '<div class="p-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer border-b border-blue-100 dark:border-blue-800 flex justify-between items-center" onclick="selectReturningStudent(' + s.id + ')">'
                     + '<div><span class="font-medium text-sm text-gray-900 dark:text-dark-text">' + escHtml(s.first_name + ' ' + s.last_name) + '</span>'
                     + '<span class="text-xs text-gray-500 ml-2">' + escHtml(s.admission_number || '') + '</span></div>'
                     + '<span class="text-xs text-blue-600">' + escHtml(s.class_name || '') + '</span></div>';
            });
            inner.innerHTML = html;
        })
        .catch(function() { inner.innerHTML = '<p class="p-3 text-sm text-red-500">Search failed.</p>'; });
}

function selectReturningStudent(id) {
    fetch('<?= url('api') ?>&action=student-for-enroll&id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.data) { alert('Could not load student data.'); return; }
            var s = data.data;
            document.getElementById('returning_student_id').value = s.id;

            // Fill personal info
            setVal('first_name', s.first_name);
            setVal('last_name', s.last_name);
            setVal('gender', s.gender);
            setVal('date_of_birth', s.date_of_birth);
            setVal('nationality', s.nationality);
            setVal('religion', s.religion);
            setVal('country', s.country);
            setVal('sub_city', s.sub_city);
            setVal('woreda', s.woreda);
            setVal('house_number', s.house_number);
            setVal('phone', s.phone);
            setVal('medical_info', s.medical_info);
            setVal('previous_school', s.previous_school);

            // Fill guardians
            if (s.guardians && s.guardians.length > 0) {
                document.getElementById('use_existing_guardians').value = '1';
                fillGuardiansFromData(s.guardians);
            }

            // Close search results
            document.getElementById('returningStudentResults').classList.add('hidden');
            document.getElementById('returningStudentSearch').value = s.first_name + ' ' + s.last_name + ' (selected)';

            // Show notice
            var notice = document.createElement('div');
            notice.className = 'mt-2 p-2 bg-green-100 text-green-800 text-xs rounded-lg';
            notice.textContent = 'Student data loaded. Update enrollment details (class, section) and submit.';
            document.getElementById('returningStudentResults').parentNode.appendChild(notice);
        })
        .catch(function() { alert('Failed to load student data.'); });
}

// ─── Guardian Search (Sibling Detection) ─────────────────────
function searchGuardian() {
    var q = document.getElementById('guardianSearchInput').value.trim();
    if (q.length < 2) { alert('Enter at least 2 characters.'); return; }

    var container = document.getElementById('guardianSearchResults');
    var inner = container.querySelector('div');
    inner.innerHTML = '<p class="p-2 text-sm text-gray-500">Searching...</p>';
    container.classList.remove('hidden');

    fetch('<?= url('api') ?>&action=guardian-search&q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.data || data.data.length === 0) {
                inner.innerHTML = '<p class="p-2 text-sm text-gray-500">No guardians found.</p>';
                return;
            }
            var html = '';
            data.data.forEach(function(g) {
                var children = g.children ? ' — Children: ' + escHtml(g.children) : '';
                html += '<div class="p-2 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 cursor-pointer border-b border-yellow-100 dark:border-yellow-800 text-sm" onclick=\'selectExistingGuardian(' + JSON.stringify(g).replace(/'/g, "\\'") + ')\'>'
                     + '<span class="font-medium">' + escHtml(g.first_name + ' ' + g.last_name) + '</span>'
                     + '<span class="text-xs text-gray-500 ml-2">' + escHtml(g.phone || '') + '</span>'
                     + '<span class="text-xs text-gray-400 block">' + escHtml(children) + '</span></div>';
            });
            inner.innerHTML = html;
        })
        .catch(function() { inner.innerHTML = '<p class="p-2 text-sm text-red-500">Search failed.</p>'; });
}

function selectExistingGuardian(guardian) {
    // Find first empty guardian block or add a new one
    var blocks = document.querySelectorAll('#guardiansContainer .guardian-block');
    var targetBlock = null;
    blocks.forEach(function(b) {
        var fname = b.querySelector('input[name$="[first_name]"]');
        if (fname && !fname.value && !targetBlock) targetBlock = b;
    });
    if (!targetBlock) {
        addGuardian();
        blocks = document.querySelectorAll('#guardiansContainer .guardian-block');
        targetBlock = blocks[blocks.length - 1];
    }

    // Populate fields
    var idx = targetBlock.getAttribute('data-guardian-index');
    setGuardianField(targetBlock, 'first_name', guardian.first_name);
    setGuardianField(targetBlock, 'last_name', guardian.last_name);
    setGuardianField(targetBlock, 'phone', guardian.phone);
    setGuardianField(targetBlock, 'email', guardian.email);
    setGuardianField(targetBlock, 'occupation', guardian.occupation);

    // Set existing_guardian_id
    var hiddenInput = targetBlock.querySelector('.existing-guardian-id');
    if (hiddenInput) hiddenInput.value = guardian.id;

    // Show badge
    var badge = targetBlock.querySelector('.existing-guardian-badge');
    if (badge) badge.classList.remove('hidden');

    // Make fields read-only (visual indicator)
    targetBlock.querySelectorAll('input[type="text"], input[type="email"]').forEach(function(inp) {
        inp.classList.add('bg-green-50', 'dark:bg-green-900/20');
    });

    document.getElementById('guardianSearchResults').classList.add('hidden');
    document.getElementById('guardianSearchInput').value = '';
}

function fillGuardiansFromData(guardians) {
    var container = document.getElementById('guardiansContainer');
    // Clear existing blocks
    container.innerHTML = '';
    guardianIndex = 0;

    guardians.forEach(function(g, i) {
        var isFirst = (i === 0);
        var html = '<div class="guardian-block border border-gray-100 dark:border-dark-border rounded-lg p-4 mb-4 bg-gray-50 dark:bg-dark-bg" data-guardian-index="' + i + '">'
            + '<input type="hidden" name="guardians[' + i + '][existing_guardian_id]" value="' + (g.id || '') + '" class="existing-guardian-id">'
            + '<div class="flex items-center justify-between mb-3">'
            + '<h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Guardian #' + (i + 1) + (isFirst ? ' <span class="text-xs text-green-600">(Primary)</span>' : '') + '</h3>'
            + '<span class="existing-guardian-badge text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Existing Guardian</span>'
            + '</div>'
            + '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">'
            + guardianField(i, 'first_name', 'First Name', g.first_name, true)
            + guardianField(i, 'last_name', 'Last Name', g.last_name, true)
            + guardianSelect(i, 'relation', 'Relationship', g.relationship || g.relation || '')
            + guardianField(i, 'phone', 'Phone', g.phone, true)
            + guardianField(i, 'email', 'Email', g.email, false)
            + guardianField(i, 'occupation', 'Occupation', g.occupation, false)
            + '</div></div>';
        container.insertAdjacentHTML('beforeend', html);
        guardianIndex = i + 1;
    });
}

// ─── Helper functions ────────────────────────────────────────
function setVal(name, value) {
    var el = document.querySelector('[name="' + name + '"]');
    if (el && value !== null && value !== undefined) el.value = value;
}

function setGuardianField(block, field, value) {
    var inp = block.querySelector('input[name$="[' + field + ']"]');
    if (inp && value) inp.value = value;
}

function guardianField(idx, name, label, value, required) {
    return '<div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + label + (required ? ' <span class="text-red-500">*</span>' : '') + '</label>'
        + '<input type="' + (name === 'email' ? 'email' : 'text') + '" name="guardians[' + idx + '][' + name + ']" value="' + escAttr(value || '') + '"' + (required ? ' required' : '')
        + ' class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-green-50 dark:bg-green-900/20 dark:text-dark-text focus:ring-2 focus:ring-primary-500"></div>';
}

function guardianSelect(idx, name, label, value) {
    var opts = ['father','mother','guardian','uncle','aunt','sibling','grandparent','other'];
    var html = '<div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">' + label + ' <span class="text-red-500">*</span></label>'
        + '<select name="guardians[' + idx + '][relation]" required class="w-full px-3 py-2 border border-gray-300 dark:border-dark-border rounded-lg text-sm bg-white dark:bg-dark-card dark:text-dark-text focus:ring-2 focus:ring-primary-500">'
        + '<option value="">Select...</option>';
    opts.forEach(function(o) {
        html += '<option value="' + o + '"' + (value === o ? ' selected' : '') + '>' + o.charAt(0).toUpperCase() + o.slice(1) + '</option>';
    });
    return html + '</select></div>';
}

function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
}

function escAttr(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ─── Enter key triggers search ───────────────────────────────
document.getElementById('returningStudentSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); searchReturningStudent(); }
});
document.getElementById('guardianSearchInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); searchGuardian(); }
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Student Admission';
require APP_ROOT . '/templates/layout.php';
