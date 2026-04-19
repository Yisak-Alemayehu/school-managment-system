<?php
/**
 * Academic Materials — Store Action (POST)
 * Validates input, uploads cover image + PDF file, saves to database.
 */

csrf_protect();

// ── Validation ───────────────────────────────────────────────────────────────
$errors = [];

$title     = trim($_POST['title'] ?? '');
$classId   = (int) ($_POST['class_id'] ?? 0);
$subjectId = (int) ($_POST['subject_id'] ?? 0);
$bookType  = $_POST['book_type'] ?? '';

if ($title === '')                                              $errors['title']      = 'Title is required.';
if (!$classId)                                                  $errors['class_id']   = 'Grade/Class is required.';
if (!$subjectId)                                                $errors['subject_id'] = 'Subject is required.';
if (!in_array($bookType, ['teachers_guide', 'student_book', 'supplementary'], true))
    $errors['book_type'] = 'Book type is required.';

// Cover image validation (PNG only)
if (empty($_FILES['cover_image']['name']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
    $errors['cover_image'] = 'Cover image is required (PNG only).';
} else {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $covMime  = finfo_file($finfo, $_FILES['cover_image']['tmp_name']);
    finfo_close($finfo);
    if ($covMime !== 'image/png') {
        $errors['cover_image'] = 'Cover image must be PNG format.';
    }
    if ($_FILES['cover_image']['size'] > 5 * 1024 * 1024) {
        $errors['cover_image'] = 'Cover image must be under 5 MB.';
    }
}

// Material file validation (PDF only)
if (empty($_FILES['material_file']['name']) || $_FILES['material_file']['error'] !== UPLOAD_ERR_OK) {
    $errors['material_file'] = 'Material file is required (PDF only).';
} else {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $pdfMime  = finfo_file($finfo, $_FILES['material_file']['tmp_name']);
    finfo_close($finfo);
    if ($pdfMime !== 'application/pdf') {
        $errors['material_file'] = 'Material file must be PDF format.';
    }
    if ($_FILES['material_file']['size'] > 50 * 1024 * 1024) {
        $errors['material_file'] = 'Material file must be under 50 MB.';
    }
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input($_POST);
    redirect(url('materials', 'create'));
}

// ── Verify class & subject exist ─────────────────────────────────────────────
$class = db_fetch_one("SELECT id FROM classes WHERE id = ? AND is_active = 1", [$classId]);
if (!$class) {
    set_flash('error', 'Selected class/grade does not exist.');
    set_old_input($_POST);
    redirect(url('materials', 'create'));
}

$subject = db_fetch_one("SELECT id FROM subjects WHERE id = ? AND is_active = 1", [$subjectId]);
if (!$subject) {
    set_flash('error', 'Selected subject does not exist.');
    set_old_input($_POST);
    redirect(url('materials', 'create'));
}

// ── Upload cover image ───────────────────────────────────────────────────────
$coverPath = handle_upload('cover_image', 'materials/covers', [
    'allowed_types' => ['image/png'],
    'max_size'      => 5 * 1024 * 1024,
]);
if ($coverPath === null) {
    set_flash('error', 'Cover image upload failed. Please try again.');
    set_old_input($_POST);
    redirect(url('materials', 'create'));
}

// ── Upload material PDF ──────────────────────────────────────────────────────
$filePath = handle_upload('material_file', 'materials/files', [
    'allowed_types' => ['application/pdf'],
    'max_size'      => 50 * 1024 * 1024,
]);
if ($filePath === null) {
    set_flash('error', 'Material file upload failed. Please try again.');
    set_old_input($_POST);
    redirect(url('materials', 'create'));
}

$fileSize = $_FILES['material_file']['size'];

// ── Insert into database ─────────────────────────────────────────────────────
db_begin();
try {
    $materialId = db_insert('academic_materials', [
        'title'       => $title,
        'class_id'    => $classId,
        'subject_id'  => $subjectId,
        'book_type'   => $bookType,
        'cover_image' => $coverPath,
        'file_path'   => $filePath,
        'file_size'   => $fileSize,
        'uploaded_by'  => auth_user_id(),
        'status'      => 'active',
        'created_at'  => date('Y-m-d H:i:s'),
    ]);

    db_commit();

    set_flash('success', 'Material uploaded successfully!');
    audit_log('materials.create', "New material: {$title} (ID: {$materialId})");
    redirect(url('materials', 'view', $materialId));

} catch (Throwable $e) {
    db_rollback();
    error_log('Material upload error: ' . $e->getMessage());
    set_flash('error', 'An error occurred while saving. Please try again.');
    set_old_input($_POST);
    redirect(url('materials', 'create'));
}
