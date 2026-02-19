<?php
/**
 * Academics — Promote Students Action
 * Closes current enrollment, creates new enrollment, records promotion.
 */
csrf_protect();

$fromSession = input_int('from_session');
$fromClass   = input_int('from_class');
$fromSection = input_int('from_section');
$toSession   = input_int('to_session');
$toClass     = input_int('to_class');
$toSection   = input_int('to_section') ?: null;
$studentIds  = input_array('student_ids');
$statuses    = $_POST['status'] ?? [];

if (!$fromSession || !$fromClass || !$toSession || !$toClass || empty($studentIds)) {
    set_flash('error', 'Please fill all required fields and select at least one student.');
    redirect_back();
}

$userId = current_user_id();
$now    = date('Y-m-d H:i:s');
$promoted  = 0;
$repeated  = 0;
$graduated = 0;
$errors    = 0;

db_begin();
try {
    foreach ($studentIds as $studentId) {
        $studentId = (int)$studentId;
        $status    = $statuses[$studentId] ?? 'promoted';

        if (!in_array($status, ['promoted', 'repeated', 'graduated'])) {
            $status = 'promoted';
        }

        // Find current active enrollment
        $enrollment = db_fetch_one("
            SELECT id, section_id FROM enrollments
            WHERE student_id = ? AND session_id = ? AND class_id = ? AND status = 'active'
            LIMIT 1
        ", [$studentId, $fromSession, $fromClass]);

        if (!$enrollment) {
            $errors++;
            continue;
        }

        // Close the old enrollment
        db_update('enrollments', [
            'status' => $status === 'graduated' ? 'graduated' : 'promoted',
        ], 'id = ?', [$enrollment['id']]);

        // Determine destination class for repeated students
        $destClass   = ($status === 'repeated') ? $fromClass : $toClass;
        $destSection = ($status === 'repeated') ? ($fromSection ?: $toSection) : $toSection;
        $destSession = ($status === 'repeated') ? $toSession : $toSession;

        // Create new enrollment (unless graduated)
        if ($status !== 'graduated') {
            db_insert('enrollments', [
                'student_id' => $studentId,
                'session_id' => $destSession,
                'class_id'   => $destClass,
                'section_id' => $destSection,
                'status'     => 'active',
                'enrolled_at' => $now,
            ]);
        }

        // Record the promotion
        db_insert('promotions', [
            'student_id'      => $studentId,
            'from_session_id' => $fromSession,
            'from_class_id'   => $fromClass,
            'from_section_id' => $enrollment['section_id'],
            'to_session_id'   => $destSession,
            'to_class_id'     => $destClass,
            'to_section_id'   => $destSection,
            'status'          => $status,
            'promoted_by'     => $userId,
            'promoted_at'     => $now,
        ]);

        if ($status === 'promoted') $promoted++;
        elseif ($status === 'repeated') $repeated++;
        elseif ($status === 'graduated') $graduated++;
    }

    db_commit();

    $msg = "Promotion complete: {$promoted} promoted, {$repeated} repeated, {$graduated} graduated.";
    if ($errors > 0) $msg .= " {$errors} skipped (no enrollment found).";
    audit_log('promotion.bulk', $msg);
    set_flash('success', $msg);

} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Promotion failed: ' . $ex->getMessage());
}

redirect(url('academics', 'promote'));
