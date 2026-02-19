<?php
/**
 * Students — Store Action (POST)
 * Handles: first/last name, mandatory phone, Ethiopian address,
 * mandatory photo upload, multiple guardians.
 */

csrf_protect();

// ── Basic validation ─────────────────────────────────────────
$errors = [];

// Personal
if (!trim($_POST['first_name'] ?? ''))  $errors['first_name']  = 'First name is required.';
if (!trim($_POST['last_name'] ?? ''))   $errors['last_name']   = 'Last name is required.';
if (!in_array($_POST['gender'] ?? '', ['male', 'female'])) $errors['gender'] = 'Please select a gender.';
if (!trim($_POST['date_of_birth'] ?? '')) $errors['date_of_birth'] = 'Date of birth is required.';
if (!trim($_POST['phone'] ?? ''))       $errors['phone']       = 'Phone number is required.';

// Address (all mandatory)
if (!trim($_POST['country'] ?? ''))      $errors['country']      = 'Country is required.';
if (!trim($_POST['region'] ?? ''))       $errors['region']       = 'Region is required.';
if (!trim($_POST['city'] ?? ''))         $errors['city']         = 'City is required.';
if (!trim($_POST['sub_city'] ?? ''))     $errors['sub_city']     = 'Sub-city is required.';
if (!trim($_POST['woreda'] ?? ''))       $errors['woreda']       = 'Woreda is required.';

$houseNumber = trim($_POST['house_number'] ?? '');
if ($houseNumber === '') {
    $errors['house_number'] = 'House number is required. Enter "NEW" if not yet assigned.';
}

// Enrollment
if (!intval($_POST['class_id'] ?? 0))   $errors['class_id']   = 'Please select a class.';
if (!intval($_POST['section_id'] ?? 0)) $errors['section_id'] = 'Please select a section.';
if (!trim($_POST['admission_date'] ?? '')) $errors['admission_date'] = 'Admission date is required.';

// Photo (mandatory)
if (empty($_FILES['photo']['name']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $errors['photo'] = 'Student photo is required. Please upload a JPEG or PNG image.';
}

// Guardians
$guardians = $_POST['guardians'] ?? [];
if (empty($guardians) || !is_array($guardians)) {
    $errors['guardians'] = 'At least one guardian is required.';
} else {
    foreach ($guardians as $i => $g) {
        if (!trim($g['first_name'] ?? '')) $errors["guardians"] = 'All guardian first names are required.';
        if (!trim($g['last_name'] ?? ''))  $errors["guardians"] = 'All guardian last names are required.';
        if (!trim($g['relation'] ?? ''))   $errors["guardians"] = 'All guardian relationships are required.';
        if (!trim($g['phone'] ?? ''))      $errors["guardians"] = 'All guardian phone numbers are required.';
    }
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input($_POST);
    redirect(url('students', 'create'));
}

// ── Session check ────────────────────────────────────────────
$session = get_active_session();
if (!$session) {
    set_flash('error', 'No active academic session. Please configure one first.');
    set_old_input($_POST);
    redirect(url('students', 'create'));
}

// ── Photo upload ─────────────────────────────────────────────
$photoPath = null;
if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photoPath = handle_upload('photo', 'students', [
        'allowed_types' => ['image/jpeg', 'image/png'],
        'max_size'      => 2 * 1024 * 1024,
    ]);
    if ($photoPath === null) {
        set_flash('error', 'Photo upload failed. Only JPEG/PNG under 2 MB are allowed.');
        set_old_input($_POST);
        redirect(url('students', 'create'));
    }
}

// ── Generate admission number ────────────────────────────────
$admissionNo = generate_code('ADM');

// ── Build full address string ────────────────────────────────
$addressParts = array_filter([
    trim($_POST['address'] ?? ''),
    'House: ' . $houseNumber,
    'Woreda: ' . trim($_POST['woreda']),
    trim($_POST['sub_city']),
    trim($_POST['city']),
    trim($_POST['region']),
    trim($_POST['country']),
]);
$fullAddress = implode(', ', $addressParts);

// ── Insert everything in a transaction ───────────────────────
db_begin();

try {
    // 1. Create student
    $studentId = db_insert('students', [
        'admission_no'   => $admissionNo,
        'first_name'     => trim($_POST['first_name']),
        'last_name'      => trim($_POST['last_name']),
        'gender'         => $_POST['gender'],
        'date_of_birth'  => $_POST['date_of_birth'],
        'blood_group'    => $_POST['blood_group'] ?: null,
        'religion'       => trim($_POST['religion'] ?? '') ?: null,
        'phone'          => trim($_POST['phone']),
        'email'          => trim($_POST['email'] ?? '') ?: null,
        'address'        => $fullAddress,
        'country'        => trim($_POST['country']),
        'region'         => trim($_POST['region']),
        'city'           => trim($_POST['city']),
        'sub_city'       => trim($_POST['sub_city']),
        'woreda'         => trim($_POST['woreda']),
        'house_number'   => $houseNumber,
        'photo'          => $photoPath,
        'medical_notes'  => trim($_POST['medical_conditions'] ?? '') ?: null,
        'previous_school' => trim($_POST['previous_school'] ?? '') ?: null,
        'admission_date' => $_POST['admission_date'],
        'status'         => 'active',
    ]);

    // 2. Create guardians (multiple) and link them
    foreach ($guardians as $i => $g) {
        $guardianId = db_insert('guardians', [
            'first_name' => trim($g['first_name']),
            'last_name'  => trim($g['last_name']),
            'relation'   => $g['relation'],
            'phone'      => trim($g['phone']),
            'email'      => trim($g['email'] ?? '') ?: null,
            'occupation' => trim($g['occupation'] ?? '') ?: null,
        ]);

        db_insert('student_guardians', [
            'student_id'   => $studentId,
            'guardian_id'   => $guardianId,
            'relationship'  => $g['relation'],
            'is_primary'    => ($i === 0) ? 1 : 0,
        ]);
    }

    // 3. Create enrollment — use correct column names per schema
    $sectionRow = db_fetch_one("SELECT id, class_id FROM sections WHERE id = ?", [intval($_POST['section_id'])]);
    $classId    = $sectionRow ? $sectionRow['class_id'] : intval($_POST['class_id']);

    db_insert('enrollments', [
        'student_id'  => $studentId,
        'session_id'  => $session['id'],
        'class_id'    => $classId,
        'section_id'  => intval($_POST['section_id']),
        'enrolled_at' => $_POST['admission_date'],
        'status'      => 'active',
    ]);

    db_commit();

    $fullName = trim($_POST['first_name']) . ' ' . trim($_POST['last_name']);

    audit_log('student_admitted', 'students', $studentId, null, [
        'admission_no' => $admissionNo,
        'full_name'    => $fullName,
    ]);

    set_flash('success', "Student \"{$fullName}\" admitted successfully. Admission No: {$admissionNo}");
    redirect(url('students', 'view', $studentId));

} catch (Exception $e) {
    db_rollback();
    if ($photoPath) delete_upload($photoPath);
    set_flash('error', 'Failed to register student: ' . $e->getMessage());
    set_old_input($_POST);
    redirect(url('students', 'create'));
}
