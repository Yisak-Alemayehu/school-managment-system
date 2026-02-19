<?php
/**
 * Exams — Save Grade Scale Action
 */
csrf_protect();

$scaleId = input_int('grade_scale_id');
$entries = $_POST['entries'] ?? [];

if (!$scaleId) {
    set_flash('error', 'No grade scale specified.');
    redirect_back();
}

db_begin();
try {
    foreach ($entries as $entry) {
        $grade = trim($entry['grade'] ?? '');
        if (!$grade) continue; // skip empty rows

        $data = [
            'grade_scale_id' => $scaleId,
            'grade'          => $grade,
            'min_mark'       => (float)($entry['min_mark'] ?? 0),
            'max_mark'       => (float)($entry['max_mark'] ?? 100),
            'grade_point'    => (float)($entry['grade_point'] ?? 0),
            'description'    => trim($entry['description'] ?? ''),
        ];

        if (!empty($entry['id'])) {
            db_update('grade_scale_entries', $data, 'id = ?', [(int)$entry['id']]);
        } else {
            db_insert('grade_scale_entries', $data);
        }
    }

    db_commit();
    audit_log('grade_scale.update', "Updated grade scale ID: {$scaleId}");
    set_flash('success', 'Grade scale saved.');
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to save: ' . $ex->getMessage());
}

redirect(url('exams', 'grade-scale') . '&scale_id=' . $scaleId);
