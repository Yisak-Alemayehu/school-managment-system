<?php
/**
 * Academics — Save Elective Subject Assignments
 */
csrf_protect();

$classId   = input_int('class_id');
$sectionId = input_int('section_id');
$sessionId = input_int('session_id');
$studentIds = input_array('student_ids');
$electives  = $_POST['electives'] ?? [];

if (!$classId || !$sessionId || empty($studentIds)) {
    set_flash('error', 'Missing required data.');
    redirect_back();
}

// Get all elective class_subject IDs for this class
$electiveCSIds = db_fetch_all("
    SELECT id FROM class_subjects WHERE class_id = ? AND is_elective = 1
", [$classId]);
$validCSIds = array_column($electiveCSIds, 'id');

db_begin();
try {
    foreach ($studentIds as $studentId) {
        $studentId = (int)$studentId;
        if ($studentId <= 0) continue;

        // Delete old elective assignments for this student in this session for these class_subjects
        if (!empty($validCSIds)) {
            $inClause = implode(',', $validCSIds);
            db_query("
                DELETE FROM student_elective_subjects
                WHERE student_id = ? AND session_id = ? AND class_subject_id IN ({$inClause})
            ", [$studentId, $sessionId]);
        }

        // Insert new selections
        $selected = $electives[$studentId] ?? [];
        foreach ($selected as $csId) {
            $csId = (int)$csId;
            if ($csId > 0 && in_array($csId, $validCSIds)) {
                db_insert('student_elective_subjects', [
                    'student_id'       => $studentId,
                    'class_subject_id' => $csId,
                    'session_id'       => $sessionId,
                ]);
            }
        }
    }

    db_commit();
    audit_log('elective_subjects.update', "Updated elective assignments for class ID: {$classId}");
    set_flash('success', 'Elective subject assignments saved successfully.');
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to save elective assignments: ' . $ex->getMessage());
}

$redir = url('academics', 'elective-subjects') . '&class_id=' . $classId;
if ($sectionId) $redir .= '&section_id=' . $sectionId;
redirect($redir);
