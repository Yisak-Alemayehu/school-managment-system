<?php
/**
 * Students — Update Action (POST)
 * Handles: first/last name, mandatory phone, Ethiopian address,
 * optional photo replacement, multiple guardians.
 */

csrf_protect();
$id = route_id();

$student = db_fetch_one("SELECT * FROM students WHERE id = ? AND deleted_at IS NULL", [$id]);
if (!$student) {
    set_flash('error', 'Student not found.');
    redirect(url('students'));
}

// ── Validation ───────────────────────────────────────────────
$errors = [];

if (!trim($_POST['first_name'] ?? ''))  $errors['first_name']  = 'First name is required.';
if (!trim($_POST['last_name'] ?? ''))   $errors['last_name']   = 'Last name is required.';
if (!in_array($_POST['gender'] ?? '', ['male', 'female'])) $errors['gender'] = 'Please select a gender.';
if (!trim($_POST['date_of_birth'] ?? '')) $errors['date_of_birth'] = 'Date of birth is required.';
if (!trim($_POST['phone'] ?? ''))       $errors['phone']       = 'Phone number is required.';

// Address
if (!trim($_POST['country'] ?? ''))     $errors['country']     = 'Country is required.';
if (!trim($_POST['region'] ?? ''))      $errors['region']      = 'Region is required.';
if (!trim($_POST['city'] ?? ''))        $errors['city']        = 'City is required.';
if (!trim($_POST['sub_city'] ?? ''))    $errors['sub_city']    = 'Sub-city is required.';
if (!trim($_POST['woreda'] ?? ''))      $errors['woreda']      = 'Woreda is required.';
$houseNumber = trim($_POST['house_number'] ?? '');
if ($houseNumber === '') $errors['house_number'] = 'House number is required.';

// Guardians
$guardians = $_POST['guardians'] ?? [];
if (empty($guardians) || !is_array($guardians)) {
    $errors['guardians'] = 'At least one guardian is required.';
} else {
    foreach ($guardians as $g) {
        if (!trim($g['first_name'] ?? '') || !trim($g['last_name'] ?? '') ||
            !trim($g['relation'] ?? '') || !trim($g['phone'] ?? '')) {
            $errors['guardians'] = 'All guardian required fields must be filled.';
            break;
        }
    }
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input($_POST);
    redirect(url('students', 'edit', $id));
}

// ── Build address ────────────────────────────────────────────
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

// ── Photo upload (optional for edit) ─────────────────────────
$photoPath = null;
if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photoPath = handle_upload('photo', 'students', [
        'allowed_types' => ['image/jpeg', 'image/png'],
        'max_size'      => 2 * 1024 * 1024,
    ]);
    if ($photoPath === null) {
        set_flash('error', 'Photo upload failed. Only JPEG/PNG under 2 MB.');
        set_old_input($_POST);
        redirect(url('students', 'edit', $id));
    }
}

// ── Update student ───────────────────────────────────────────
$updateData = [
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
    'medical_notes'  => trim($_POST['medical_conditions'] ?? '') ?: null,
    'status'         => $_POST['status'] ?? $student['status'],
];

if ($photoPath) {
    $updateData['photo'] = $photoPath;
    // Delete old photo
    if ($student['photo']) delete_upload($student['photo']);
}

db_begin();

try {
    db_update('students', $updateData, 'id = ?', [$id]);

    // ── Update guardians ─────────────────────────────────────
    // Get existing guardian IDs for this student
    $existingGuardianIds = array_column(
        db_fetch_all("SELECT guardian_id FROM student_guardians WHERE student_id = ?", [$id]),
        'guardian_id'
    );
    $submittedGuardianIds = [];

    foreach ($guardians as $i => $g) {
        $gId = intval($g['id'] ?? 0);

        if ($gId && in_array($gId, $existingGuardianIds)) {
            // Update existing guardian
            db_update('guardians', [
                'first_name' => trim($g['first_name']),
                'last_name'  => trim($g['last_name']),
                'relation'   => $g['relation'],
                'phone'      => trim($g['phone']),
                'email'      => trim($g['email'] ?? '') ?: null,
                'occupation' => trim($g['occupation'] ?? '') ?: null,
            ], 'id = ?', [$gId]);

            // Update the link table relationship
            db_query(
                "UPDATE student_guardians SET relationship = ?, is_primary = ? WHERE student_id = ? AND guardian_id = ?",
                [$g['relation'], ($i === 0) ? 1 : 0, $id, $gId]
            );
            $submittedGuardianIds[] = $gId;
        } else {
            // New guardian
            $newGId = db_insert('guardians', [
                'first_name' => trim($g['first_name']),
                'last_name'  => trim($g['last_name']),
                'relation'   => $g['relation'],
                'phone'      => trim($g['phone']),
                'email'      => trim($g['email'] ?? '') ?: null,
                'occupation' => trim($g['occupation'] ?? '') ?: null,
            ]);
            db_insert('student_guardians', [
                'student_id'   => $id,
                'guardian_id'   => $newGId,
                'relationship'  => $g['relation'],
                'is_primary'    => ($i === 0) ? 1 : 0,
            ]);
            $submittedGuardianIds[] = $newGId;
        }
    }

    // Remove guardians that were deleted from the form
    $removedIds = array_diff($existingGuardianIds, $submittedGuardianIds);
    foreach ($removedIds as $rId) {
        db_query("DELETE FROM student_guardians WHERE student_id = ? AND guardian_id = ?", [$id, $rId]);
        // Optionally delete the guardian record if not linked to other students
        $otherLinks = db_fetch_value("SELECT COUNT(*) FROM student_guardians WHERE guardian_id = ?", [$rId]);
        if ($otherLinks == 0) {
            db_query("DELETE FROM guardians WHERE id = ?", [$rId]);
        }
    }

    db_commit();

    audit_log('student_updated', 'students', $id);

    set_flash('success', 'Student updated successfully.');
    redirect(url('students', 'view', $id));

} catch (Exception $e) {
    db_rollback();
    set_flash('error', 'Failed to update student: ' . $e->getMessage());
    set_old_input($_POST);
    redirect(url('students', 'edit', $id));
}
