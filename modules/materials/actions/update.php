<?php
/**
 * Academic Materials — Update Action (POST)
 */

csrf_protect();
$id = route_id();

// ── Fetch existing material ──────────────────────────────────────────────────
$material = db_fetch_one(
    "SELECT * FROM academic_materials WHERE id = ? AND deleted_at IS NULL",
    [$id]
);
if (!$material) {
    set_flash('error', 'Material not found.');
    redirect(url('materials'));
}

// ── Validation ───────────────────────────────────────────────────────────────
$errors = [];

$title     = trim($_POST['title'] ?? '');
$classId   = (int) ($_POST['class_id'] ?? 0);
$subjectId = (int) ($_POST['subject_id'] ?? 0);
$bookType  = $_POST['book_type'] ?? '';

if ($title === '')   $errors['title']      = 'Title is required.';
if (!$classId)       $errors['class_id']   = 'Grade/Class is required.';
if (!$subjectId)     $errors['subject_id'] = 'Subject is required.';
if (!in_array($bookType, ['teachers_guide', 'student_book', 'supplementary'], true))
    $errors['book_type'] = 'Book type is required.';

// Cover image validation (optional on update, PNG only if provided)
if (!empty($_FILES['cover_image']['name']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $covMime = finfo_file($finfo, $_FILES['cover_image']['tmp_name']);
    finfo_close($finfo);
    if ($covMime !== 'image/png') {
        $errors['cover_image'] = 'Cover image must be PNG format.';
    }
    if ($_FILES['cover_image']['size'] > 5 * 1024 * 1024) {
        $errors['cover_image'] = 'Cover image must be under 5 MB.';
    }
}

// Material file validation (optional on update, PDF only if provided)
if (!empty($_FILES['material_file']['name']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $pdfMime = finfo_file($finfo, $_FILES['material_file']['tmp_name']);
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
    redirect(url('materials', 'edit', $id));
}

// ── Handle optional cover image replacement ──────────────────────────────────
$coverPath = null;
if (!empty($_FILES['cover_image']['name']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    $coverPath = handle_upload('cover_image', 'materials/covers', [
        'allowed_types' => ['image/png'],
        'max_size'      => 5 * 1024 * 1024,
    ]);
    if ($coverPath === null) {
        set_flash('error', 'Cover image upload failed.');
        set_old_input($_POST);
        redirect(url('materials', 'edit', $id));
    }
}

// ── Handle optional material file replacement ────────────────────────────────
$filePath = null;
$fileSize = null;
if (!empty($_FILES['material_file']['name']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
    $filePath = handle_upload('material_file', 'materials/files', [
        'allowed_types' => ['application/pdf'],
        'max_size'      => 50 * 1024 * 1024,
    ]);
    if ($filePath === null) {
        set_flash('error', 'Material file upload failed.');
        set_old_input($_POST);
        redirect(url('materials', 'edit', $id));
    }
    $fileSize = $_FILES['material_file']['size'];
}

// ── Update database ──────────────────────────────────────────────────────────
db_begin();
try {
    $updateData = [
        'title'      => $title,
        'class_id'   => $classId,
        'subject_id' => $subjectId,
        'book_type'  => $bookType,
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($coverPath) {
        $updateData['cover_image'] = $coverPath;
    }
    if ($filePath) {
        $updateData['file_path'] = $filePath;
        $updateData['file_size'] = $fileSize;
    }

    db_update('academic_materials', $updateData, 'id = ?', [$id]);
    db_commit();

    set_flash('success', 'Material updated successfully!');
    audit_log('materials.edit', "Material ID $id updated: {$title}");
    redirect(url('materials', 'view', $id));

} catch (Throwable $e) {
    db_rollback();
    error_log('Material update error: ' . $e->getMessage());
    set_flash('error', 'An error occurred. Please try again.');
    set_old_input($_POST);
    redirect(url('materials', 'edit', $id));
}
