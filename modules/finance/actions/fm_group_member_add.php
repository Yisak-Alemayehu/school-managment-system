<?php
/**
 * Fee Management — Add Members to Group (single or bulk)
 */

if (!is_post()) { redirect('finance', 'fm-groups'); }
verify_csrf();

$groupId    = input_int('group_id');
$studentIds = $_POST['student_ids'] ?? [];
$bulkCodes  = trim($_POST['bulk_codes'] ?? '');

if (!$groupId) {
    set_flash('error', 'Invalid group.');
    redirect('finance', 'fm-groups');
}

$group = db_fetch_one("SELECT * FROM student_groups WHERE id = ?", [$groupId]);
if (!$group) {
    set_flash('error', 'Group not found.');
    redirect('finance', 'fm-groups');
}

// Parse bulk codes (admission numbers, one per line or comma-separated)
if ($bulkCodes) {
    $codes = preg_split('/[\r\n,]+/', $bulkCodes);
    $codes = array_filter(array_map('trim', $codes));
    
    if (!empty($codes)) {
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $rows = db_fetch_all(
            "SELECT id FROM students WHERE admission_no IN ($placeholders) AND status = 'active' AND deleted_at IS NULL",
            array_values($codes)
        );
        $bulkIds = array_column($rows, 'id');
        $studentIds = array_merge((array)$studentIds, $bulkIds);
        
        $notFound = count($codes) - count($bulkIds);
    }
}

if (!is_array($studentIds)) $studentIds = [$studentIds];
$studentIds = array_unique(array_filter(array_map('intval', $studentIds)));

if (empty($studentIds)) {
    set_flash('error', 'No valid students to add.');
    redirect('finance', 'fm-group-members', $groupId);
}

$added   = 0;
$skipped = 0;

foreach ($studentIds as $sid) {
    // Check student exists
    $exists = db_exists('students', 'id = ? AND deleted_at IS NULL', [$sid]);
    if (!$exists) { $skipped++; continue; }

    // Check not already member
    $isMember = db_exists('student_group_members', 'group_id = ? AND student_id = ?', [$groupId, $sid]);
    if ($isMember) { $skipped++; continue; }

    db_insert('student_group_members', [
        'group_id'    => $groupId,
        'student_id'  => $sid,
        'assigned_by' => auth_user_id(),
    ]);
    $added++;
}

$msg = "{$added} member(s) added.";
if ($skipped > 0) $msg .= " {$skipped} skipped (already member or not found).";
if (!empty($notFound) && $notFound > 0) $msg .= " {$notFound} admission code(s) not found.";

set_flash('success', $msg);
redirect('finance', 'fm-group-members', $groupId);
