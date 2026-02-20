<?php
/**
 * Students — Bulk Import Action
 * Processes uploaded CSV file and inserts student records.
 */
csrf_protect();

if (empty($_FILES['csv_file']['tmp_name'])) {
    set_flash('error', 'Please upload a CSV file.');
    redirect(url('students', 'bulk-import'));
}

$tmpFile        = $_FILES['csv_file']['tmp_name'];
$defaultClassId = (int)($_POST['default_class_id'] ?? 0);
$duplicateMode  = $_POST['duplicate_mode'] ?? 'skip';
$sendCreds      = !empty($_POST['send_credentials']);

$handle = fopen($tmpFile, 'r');
if (!$handle) {
    set_flash('error', 'Could not read the uploaded file.');
    redirect(url('students', 'bulk-import'));
}

// Strip BOM
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

$headers = fgetcsv($handle);
if (!$headers) {
    set_flash('error', 'The CSV file appears to be empty or invalid.');
    fclose($handle);
    redirect(url('students', 'bulk-import'));
}

$headers = array_map('trim', $headers);
$map     = array_flip($headers);

$col = fn(string $name, array $row) => isset($map[$name]) ? trim($row[$map[$name]] ?? '') : '';

$required = ['first_name', 'last_name', 'gender', 'date_of_birth'];
$missing  = array_diff($required, $headers);
if ($missing) {
    set_flash('error', 'Missing required columns: ' . implode(', ', $missing));
    fclose($handle);
    redirect(url('students', 'bulk-import'));
}

// Cache class/section lookups
$classCache   = [];
$sectionCache = [];
$lookupClass  = function(string $name) use (&$classCache) {
    if (isset($classCache[$name])) return $classCache[$name];
    $id = db_fetch_value("SELECT id FROM classes WHERE LOWER(name) = LOWER(?) AND is_active = 1 LIMIT 1", [$name]);
    return $classCache[$name] = $id;
};
$lookupSection = function(string $name, int $classId) use (&$sectionCache) {
    $key = $classId . ':' . $name;
    if (isset($sectionCache[$key])) return $sectionCache[$key];
    $id = db_fetch_value("SELECT id FROM sections WHERE LOWER(name) = LOWER(?) AND class_id = ? AND is_active = 1 LIMIT 1", [$name, $classId]);
    return $sectionCache[$key] = $id;
};

// Auto admission number generator
$counter  = (int)(db_fetch_value("SELECT COUNT(*) FROM students WHERE deleted_at IS NULL") ?? 0) + 1;
$makeAdmNo = function() use (&$counter) {
    return 'STU-' . str_pad($counter++, 4, '0', STR_PAD_LEFT);
};

$inserted = 0; $updated = 0; $skipped = 0; $rowErrors = [];
$rowNum = 1;

while (($row = fgetcsv($handle)) !== false) {
    $rowNum++;
    $row = array_map('trim', $row);

    $firstName  = $col('first_name', $row);
    $lastName   = $col('last_name', $row);
    $gender     = strtolower($col('gender', $row));
    $dob        = $col('date_of_birth', $row);

    // Basic validation
    if (!$firstName || !$lastName || !in_array($gender, ['male','female']) || !$dob) {
        $rowErrors[] = "Row $rowNum: Missing or invalid required field (first_name, last_name, gender, date_of_birth).";
        $skipped++;
        continue;
    }

    // Validate & normalise date — accepts M/D/YYYY, MM/DD/YYYY, YYYY-MM-DD, D-M-YYYY, etc.
    $dobTs = false;
    $dobRaw = $dob;
    // Try M/D/YYYY or MM/DD/YYYY first
    if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $dobRaw, $m)) {
        $dobTs = mktime(0, 0, 0, (int)$m[1], (int)$m[2], (int)$m[3]);
    } else {
        $dobTs = strtotime($dobRaw);
    }
    if (!$dobTs) {
        $rowErrors[] = "Row $rowNum: Invalid date_of_birth '$dob'. Use YYYY-MM-DD.";
        $skipped++;
        continue;
    }
    $dob = date('Y-m-d', $dobTs);

    // Resolve class
    $className   = $col('class_name', $row);
    $sectionName = $col('section_name', $row);
    $classId     = $className ? $lookupClass($className) : $defaultClassId;
    $sectionId   = ($classId && $sectionName) ? $lookupSection($sectionName, $classId) : null;

    // Admission number
    $admNo = $col('admission_no', $row) ?: $makeAdmNo();

    // Check duplicate
    $existingId = db_fetch_value("SELECT id FROM students WHERE admission_no = ? LIMIT 1", [$admNo]);
    if ($existingId) {
        if ($duplicateMode === 'skip') {
            $rowErrors[] = "Row $rowNum: Admission no '$admNo' already exists — skipped.";
            $skipped++;
            continue;
        }
        // Update mode
        db_update('students',
            [
                'gender'        => $gender,
                'date_of_birth' => $dob,
                'phone'         => $col('phone', $row) ?: null,
                'email'         => $col('email', $row) ?: null,
                'religion'      => $col('religion', $row) ?: null,
                'blood_group'   => $col('blood_group', $row) ?: null,
            ],
            'id = ?',
            [$existingId]
        );
        $updated++;
        continue;
    }

    // Insert new student
    $studentId = db_insert('students', [
        'admission_no'  => $admNo,
        'first_name'    => $firstName,
        'last_name'     => $lastName,
        'gender'        => $gender,
        'date_of_birth' => $dob,
        'phone'         => $col('phone', $row) ?: null,
        'email'         => $col('email', $row) ?: null,
        'religion'      => $col('religion', $row) ?: null,
        'blood_group'   => $col('blood_group', $row) ?: null,
        'address'       => $col('address', $row) ?: null,
        'admission_date'=> date('Y-m-d'),
        'status'        => 'active',
    ]);

    // Guardian
    $guardianName  = $col('guardian_name',  $row);
    $guardianPhone = $col('guardian_phone', $row);
    if ($guardianName && $studentId) {
        try {
            $guardianId = db_insert('guardians', [
                'first_name' => $guardianName,
                'last_name'  => '',
                'relation'   => 'parent',
                'phone'      => $guardianPhone ?: null,
            ]);
            db_insert('student_guardians', [
                'student_id'  => $studentId,
                'guardian_id' => $guardianId,
                'relationship'=> 'parent',
                'is_primary'  => 1,
            ]);
        } catch (\Exception $ge) {
            // Guardian insert failed gracefully — not critical
        }
    }

    // Enroll in section
    $activeSession = get_active_session();
    if ($sectionId && $classId && $activeSession) {
        try {
            db_insert('enrollments', [
                'student_id'  => $studentId,
                'session_id'  => $activeSession['id'],
                'class_id'    => $classId,
                'section_id'  => $sectionId,
                'enrolled_at' => date('Y-m-d'),
                'status'      => 'active',
            ]);
        } catch (\Exception $ee) {
            // enrollment error — non-critical
        }
    }

    // Credentials
    if ($sendCreds && $studentId) {
        try {
            $username = strtolower(str_replace([' ','/'], '_', $admNo));
            $plain    = $admNo;
            $uid = db_insert('users', [
                'username'      => $username,
                'email'         => $username . '@student.local',
                'password_hash' => password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]),
                'full_name'     => "$firstName $lastName",
                'is_active'     => 1,
            ]);
            db_update('students', ['user_id' => $uid], 'id = ?', [$studentId]);
            $roleId = db_fetch_value("SELECT id FROM roles WHERE slug = 'student' LIMIT 1");
            if ($roleId) db_query("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)", [$uid, $roleId]);
        } catch (\Exception $ce) {
            // credential creation failed — non-critical
        }
    }

    $inserted++;
}
fclose($handle);

$summary  = "<strong>Import complete.</strong><br>";
$summary .= "Inserted: <strong>$inserted</strong> | Updated: <strong>$updated</strong> | Skipped: <strong>$skipped</strong>";
if (!empty($rowErrors)) {
    $summary .= '<br><br><strong>Issues:</strong><ul class="mt-1 list-disc list-inside">';
    foreach (array_slice($rowErrors, 0, 20) as $e) {
        $summary .= '<li>' . htmlspecialchars($e) . '</li>';
    }
    if (count($rowErrors) > 20) $summary .= '<li>… and ' . (count($rowErrors) - 20) . ' more.</li>';
    $summary .= '</ul>';
}

set_flash('import_results', $summary);
redirect(url('students', 'bulk-import'));
