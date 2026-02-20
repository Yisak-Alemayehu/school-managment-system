<?php
/**
 * Attendance — Save Action
 */
csrf_protect();

$classId   = input_int('class_id');
$sectionId = input_int('section_id') ?: null;
$sessionId = input_int('session_id');
$date      = input('date');
$studentsData = $_POST['students'] ?? [];

if (!$classId || !$date || empty($studentsData)) {
    set_flash('error', 'Invalid attendance data.');
    redirect_back();
}

// Validate date not in future
if ($date > date('Y-m-d')) {
    set_flash('error', 'Cannot mark attendance for a future date.');
    redirect_back();
}

// Get active term
$activeTerm = get_active_term();
$termId = $activeTerm['id'] ?? null;

$validStatuses = ['present', 'absent', 'late', 'excused'];
$takenBy = auth_user()['id'];

db_begin();
try {
    foreach ($studentsData as $studentId => $entry) {
        $studentId = (int)$studentId;
        $status    = in_array($entry['status'] ?? '', $validStatuses) ? $entry['status'] : 'present';
        $remarks   = trim($entry['remarks'] ?? '');

        // Upsert: check if record exists
        $existing = db_fetch_one(
            "SELECT id FROM attendance WHERE student_id = ? AND date = ? AND class_id = ?",
            [$studentId, $date, $classId]
        );

        if ($existing) {
            db_update('attendance', [
                'status'    => $status,
                'remarks'   => $remarks,
                'marked_by' => $takenBy,
            ], 'id = ?', [$existing['id']]);
        } else {
            db_insert('attendance', [
                'student_id' => $studentId,
                'class_id'   => $classId,
                'section_id' => $sectionId,
                'session_id' => $sessionId,
                'term_id'    => $termId,
                'date'       => $date,
                'status'     => $status,
                'remarks'    => $remarks,
                'marked_by'  => $takenBy,
            ]);
        }
    }

    db_commit();
    audit_log('attendance.save', "Saved attendance for class {$classId} on {$date} (" . count($studentsData) . " students)");
    set_flash('success', 'Attendance saved for ' . count($studentsData) . ' students.');
} catch (Exception $ex) {
    db_rollback();
    set_flash('error', 'Failed to save attendance: ' . $ex->getMessage());
}

$redir = url('attendance', 'index') . '&class_id=' . $classId . '&date=' . $date;
if ($sectionId) $redir .= '&section_id=' . $sectionId;
redirect($redir);
