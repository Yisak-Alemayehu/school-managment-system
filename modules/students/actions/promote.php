<?php
/**
 * Students — Promote Action (POST)
 */

csrf_protect();

$studentIds  = input_array('student_ids');
$toSession   = input_int('to_session');
$toSection   = input_int('to_section');
$fromSession = input_int('from_session');

if (empty($studentIds) || !$toSession || !$toSection) {
    set_flash('error', 'Please select students and target class/section.');
    redirect(url('students', 'promote'));
}

$promoted = 0;
$repeated = 0;
$graduated = 0;

db_begin();

try {
    foreach ($studentIds as $sid) {
        $sid = (int) $sid;
        $status = $_POST['promote_status'][$sid] ?? 'promoted';

        // Close current enrollment
        db_update('enrollments', ['status' => $status], 'student_id = ? AND status = ?', [$sid, 'active']);

        if ($status === 'graduated') {
            db_update('students', ['status' => 'graduated'], 'id = ?', [$sid]);
            $graduated++;
            continue;
        }

        // Create new enrollment
        db_insert('enrollments', [
            'student_id'  => $sid,
            'session_id'  => $toSession,
            'class_id'    => $toClass,
            'section_id'  => $toSection,
            'enrolled_at' => date('Y-m-d'),
            'status'      => 'active',
        ]);

        // Record promotion
        $oldEnrollment = db_fetch_one(
            "SELECT e.section_id FROM enrollments e WHERE e.student_id = ? AND e.session_id = ? LIMIT 1",
            [$sid, $fromSession]
        );

        db_insert('promotions', [
            'student_id'               => $sid,
            'from_session_id' => $fromSession,
            'to_session_id'   => $toSession,
            'from_section_id'          => $oldEnrollment['section_id'] ?? null,
            'to_section_id'            => $toSection,
            'status'                   => $status,
            'promoted_by'              => auth_user()['id'],
        ]);

        $status === 'promoted' ? $promoted++ : $repeated++;
    }

    db_commit();

    audit_log('students_promoted', 'promotions', null, null, [
        'promoted' => $promoted,
        'repeated' => $repeated,
        'graduated' => $graduated,
    ]);

    set_flash('success', "Promotion complete: $promoted promoted, $repeated repeated, $graduated graduated.");
    redirect(url('students', 'promote'));

} catch (Exception $e) {
    db_rollback();
    set_flash('error', 'Promotion failed. Please try again.');
    redirect(url('students', 'promote'));
}
