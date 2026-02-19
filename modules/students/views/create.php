<?php
/**
 * Students — Admission Form (Create)
 * Supports: first/last name, mandatory phone, Ethiopian address fields,
 * mandatory photo, multiple guardians with dynamic "Add Guardian" button.
 */

$classes  = db_fetch_all("SELECT id, name FROM classes WHERE is_active = 1 ORDER BY sort_order");
$sections = db_fetch_all("SELECT id, name, class_id FROM sections WHERE is_active = 1 ORDER BY name");
$session  = get_active_session();

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= url('students') ?>" class="p-1 text-gray-400 hover:text-gray-600 rounded">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">Student Admission</h1>
    </div>

    <?php if ($msg = get_flash('error')): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= e($msg) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('students', 'create') ?>" enctype="multipart/form-data" class="space-y-6" id="admissionForm">
        <?= csrf_field() ?>

        <!-- ─── Personal Information ─────────────────────────── -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4 pb-2 border-b">Personal Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" id="first_name" name="first_name" value="<?= e(old('first_name')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <?php if ($err = get_validation_error('first_name')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" id="last_name" name="last_name" value="<?= e(old('last_name')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <?php if ($err = get_validation_error('last_name')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                    <select id="gender" name="gender" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Select...</option>
                        <option value="male" <?= old('gender') === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= old('gender') === 'female' ? 'selected' : '' ?>>Female</option>
                    </select>
                    <?php if ($err = get_validation_error('gender')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e(old('date_of_birth')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('date_of_birth')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="blood_group" class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                    <select id="blood_group" name="blood_group" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Select...</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= old('blood_group') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="religion" class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                    <input type="text" id="religion" name="religion" value="<?= e(old('religion')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                    <input type="text" id="phone" name="phone" value="<?= e(old('phone')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500" placeholder="+251...">
                    <?php if ($err = get_validation_error('phone')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="medical_conditions" class="block text-sm font-medium text-gray-700 mb-1">Medical Conditions</label>
                    <input type="text" id="medical_conditions" name="medical_conditions" value="<?= e(old('medical_conditions')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- ─── Photo Upload ─────────────────────────────────── -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4 pb-2 border-b">Student Photo <span class="text-red-500">*</span></h2>
            <div class="flex items-start gap-6">
                <div class="flex-shrink-0">
                    <div id="photoPreview" class="w-32 h-32 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center bg-gray-50 overflow-hidden">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                </div>
                <div class="flex-1">
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-primary-50 file:text-primary-700 file:text-sm"
                           onchange="previewPhoto(this)">
                    <p class="mt-1 text-xs text-gray-500">JPEG or PNG, max 2 MB. This photo will appear on the student ID card.</p>
                    <?php if ($err = get_validation_error('photo')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ─── Address Section ──────────────────────────────── -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4 pb-2 border-b">Address Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country <span class="text-red-500">*</span></label>
                    <input type="text" id="country" name="country" value="<?= e(old('country') ?: 'Ethiopia') ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('country')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Region <span class="text-red-500">*</span></label>
                    <input type="text" id="region" name="region" value="<?= e(old('region')) ?>" required placeholder="e.g. Addis Ababa"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('region')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City <span class="text-red-500">*</span></label>
                    <input type="text" id="city" name="city" value="<?= e(old('city')) ?>" required placeholder="e.g. Addis Ababa"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('city')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="sub_city" class="block text-sm font-medium text-gray-700 mb-1">Sub-city <span class="text-red-500">*</span></label>
                    <input type="text" id="sub_city" name="sub_city" value="<?= e(old('sub_city')) ?>" required placeholder="e.g. Bole"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('sub_city')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="woreda" class="block text-sm font-medium text-gray-700 mb-1">Woreda <span class="text-red-500">*</span></label>
                    <input type="text" id="woreda" name="woreda" value="<?= e(old('woreda')) ?>" required placeholder="e.g. 03"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <?php if ($err = get_validation_error('woreda')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="house_number" class="block text-sm font-medium text-gray-700 mb-1">House Number <span class="text-red-500">*</span></label>
                    <input type="text" id="house_number" name="house_number" value="<?= e(old('house_number')) ?>" required placeholder="e.g. 123 or NEW"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-gray-500">Enter "NEW" if the house has no assigned number.</p>
                    <?php if ($err = get_validation_error('house_number')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Additional Address Details</label>
                    <textarea id="address" name="address" rows="2" placeholder="Street name, landmarks, etc."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500"><?= e(old('address')) ?></textarea>
                </div>
            </div>
        </div>

        <!-- ─── Enrollment ───────────────────────────────────── -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4 pb-2 border-b">Enrollment Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-red-500">*</span></label>
                    <select id="class_id" name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500" onchange="loadSections(this.value)">
                        <option value="">Select class...</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= $cls['id'] ?>" <?= old('class_id') == $cls['id'] ? 'selected' : '' ?>><?= e($cls['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err = get_validation_error('class_id')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="section_id" class="block text-sm font-medium text-gray-700 mb-1">Section <span class="text-red-500">*</span></label>
                    <select id="section_id" name="section_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">Select section...</option>
                    </select>
                    <?php if ($err = get_validation_error('section_id')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
                </div>

                <div>
                    <label for="admission_date" class="block text-sm font-medium text-gray-700 mb-1">Admission Date <span class="text-red-500">*</span></label>
                    <input type="date" id="admission_date" name="admission_date" value="<?= e(old('admission_date') ?: date('Y-m-d')) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>

                <div>
                    <label for="previous_school" class="block text-sm font-medium text-gray-700 mb-1">Previous School</label>
                    <input type="text" id="previous_school" name="previous_school" value="<?= e(old('previous_school')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
        </div>

        <!-- ─── Guardian Information (Multiple) ──────────────── -->
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b">
                <h2 class="text-sm font-semibold text-gray-900">Guardian Information</h2>
                <button type="button" onclick="addGuardian()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary-50 text-primary-700 hover:bg-primary-100 text-xs font-medium rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Guardian
                </button>
            </div>

            <div id="guardiansContainer">
                <!-- Guardian #1 (default, cannot be removed) -->
                <div class="guardian-block border border-gray-100 rounded-lg p-4 mb-4 bg-gray-50" data-guardian-index="0">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-700">Guardian #1 <span class="text-xs text-green-600">(Primary)</span></h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                            <input type="text" name="guardians[0][first_name]" value="<?= e($_POST['guardians'][0]['first_name'] ?? '') ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                            <input type="text" name="guardians[0][last_name]" value="<?= e($_POST['guardians'][0]['last_name'] ?? '') ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Relationship <span class="text-red-500">*</span></label>
                            <select name="guardians[0][relation]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                                <option value="">Select...</option>
                                <?php foreach (['father','mother','guardian','uncle','aunt','sibling','grandparent','other'] as $rel): ?>
                                    <option value="<?= $rel ?>" <?= ($_POST['guardians'][0]['relation'] ?? '') === $rel ? 'selected' : '' ?>><?= ucfirst($rel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                            <input type="text" name="guardians[0][phone]" value="<?= e($_POST['guardians'][0]['phone'] ?? '') ?>" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500" placeholder="+251...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="guardians[0][email]" value="<?= e($_POST['guardians'][0]['email'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                            <input type="text" name="guardians[0][occupation]" value="<?= e($_POST['guardians'][0]['occupation'] ?? '') ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($err = get_validation_error('guardians')): ?><p class="mt-1 text-xs text-red-600"><?= e($err) ?></p><?php endif; ?>
        </div>

        <div class="flex justify-end gap-3">
            <a href="<?= url('students') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-primary-800 hover:bg-primary-900 text-white font-medium rounded-lg text-sm transition">
                Register Student
            </button>
        </div>
    </form>
</div>

<!-- ─── Guardian Template (hidden) ────────────────────────── -->
<template id="guardianTemplate">
    <div class="guardian-block border border-gray-100 rounded-lg p-4 mb-4 bg-gray-50" data-guardian-index="__INDEX__">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-700">Guardian #__DISPLAY__</h3>
            <button type="button" onclick="removeGuardian(this)" class="text-red-500 hover:text-red-700 text-xs font-medium flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Remove
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                <input type="text" name="guardians[__INDEX__][first_name]" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                <input type="text" name="guardians[__INDEX__][last_name]" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Relationship <span class="text-red-500">*</span></label>
                <select name="guardians[__INDEX__][relation]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                <input type="text" name="guardians[__INDEX__][phone]" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500" placeholder="+251...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="guardians[__INDEX__][email]"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                <input type="text" name="guardians[__INDEX__][occupation]"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
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
        // Validate size client-side (2 MB)
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
var guardianIndex = 1; // start at 1 since index 0 is already rendered

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
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Student Admission';
require APP_ROOT . '/templates/layout.php';
