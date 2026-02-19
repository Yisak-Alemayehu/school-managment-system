<?php
/**
 * Academics — Save Class-Subject Assignments (Fixed)
 * Includes session_id (NOT NULL FK) and is_elective flag.
 */
csrf_protect();
auth_require_permission('academics_manage');

$class_id   = input_int('class_id');
$session_id = input_int('session_id');
$subjects   = input_array('subjects');   // array of subject IDs
$electives  = input_array('electives');  // array of subject IDs marked elective

if (!$class_id || !$session_id) {
    set_flash('error', 'Class and session are required.');
    redirect('academics', 'class-subjects');
}

try {
    db_begin();

    // Remove all existing assignments for this class + session
    db_delete('class_subjects', 'class_id = ? AND session_id = ?', [$class_id, $session_id]);

    // Insert new assignments
    if (!empty($subjects)) {
        foreach ($subjects as $subject_id) {
            $subject_id = (int) $subject_id;
            if ($subject_id <= 0) continue;

            $isElective = in_array($subject_id, $electives) ? 1 : 0;
            db_insert('class_subjects', [
                'class_id'    => $class_id,
                'subject_id'  => $subject_id,
                'session_id'  => $session_id,
                'is_elective' => $isElective,
            ]);
        }
    }

    db_commit();

    $count = count($subjects ?? []);
    set_flash('success', "{$count} subject(s) assigned successfully.");
    audit_log('class_subjects_update', "Updated class {$class_id} subjects for session {$session_id}: {$count} subjects");
} catch (Throwable $ex) {
    db_rollback();
    set_flash('error', 'Failed to save: ' . $ex->getMessage());
}

redirect(url('academics', 'class-subjects') . '&class_id=' . $class_id . '&session_id=' . $session_id);
