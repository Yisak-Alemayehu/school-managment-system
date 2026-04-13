<?php
/**
 * Portal — Profile View (student & parent)
 */

$role   = portal_role();
$userId = portal_user_id();

// Fetch fresh user info
$user = db_fetch_one(
    "SELECT id, username, full_name, email, phone, avatar FROM users WHERE id = ?",
    [$userId]
);

if ($role === 'student') {
    $linkedId = portal_linked_id();
    $profile  = db_fetch_one(
        "SELECT s.id, s.full_name, s.gender, s.date_of_birth AS dob,
                s.blood_group, s.address, s.admission_no, s.status,
                c.name  AS class_name,
                sec.name AS section_name,
                e.roll_no,
                acs.name AS session_name,
                acs.start_date AS session_start,
                acs.end_date   AS session_end
         FROM students s
         LEFT JOIN enrollments e     ON e.student_id = s.id AND e.status = 'active'
         LEFT JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
         LEFT JOIN classes c          ON c.id = e.class_id
         LEFT JOIN sections sec       ON sec.id = e.section_id
         WHERE s.id = ?
         ORDER BY e.id DESC LIMIT 1",
        [$linkedId]
    );
} else {
    $linkedId = portal_linked_id();
    $profile  = db_fetch_one(
        "SELECT g.id, g.full_name, g.relation, g.phone, g.email, g.address
         FROM guardians g
         WHERE g.id = ?",
        [$linkedId]
    );
    $children = portal_children();
}

portal_head('My Profile', portal_url('dashboard'));

// Generate initials avatar
$initials = '';
$words = explode(' ', trim($user['full_name'] ?? ''));
foreach (array_slice($words, 0, 2) as $w) {
    $initials .= strtoupper($w[0] ?? '');
}
?>

<!-- Avatar + name -->
<div class="card flex items-center gap-4 mb-4">
  <div class="w-16 h-16 rounded-full bg-primary-600 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
    <?= e($initials) ?>
  </div>
  <div class="flex-1 min-w-0">
    <p class="font-bold text-gray-900 text-lg leading-tight truncate"><?= e($user['full_name']) ?></p>
    <p class="text-sm text-gray-500">@<?= e($user['username']) ?></p>
    <span class="badge <?= $role === 'student' ? 'badge-blue' : 'badge-green' ?> mt-1">
      <?= $role === 'student' ? 'Student' : 'Parent / Guardian' ?>
    </span>
  </div>
</div>

<!-- Account info -->
<h2 class="section-title mb-2">Account</h2>
<div class="card mb-4 divide-y divide-gray-100">
  <?php if ($user['email']): ?>
  <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
    <span class="text-xs text-gray-500">Email</span>
    <span class="text-sm font-medium text-gray-900"><?= e($user['email']) ?></span>
  </div>
  <?php endif; ?>
  <?php if ($user['phone']): ?>
  <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
    <span class="text-xs text-gray-500">Phone</span>
    <span class="text-sm font-medium text-gray-900"><?= e($user['phone']) ?></span>
  </div>
  <?php endif; ?>
  <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
    <span class="text-xs text-gray-500">Username</span>
    <span class="text-sm font-medium text-gray-900"><?= e($user['username']) ?></span>
  </div>
</div>

<!-- Role-specific info -->
<?php if ($role === 'student' && $profile): ?>
<h2 class="section-title mb-2">Student Info</h2>
<div class="card mb-4 divide-y divide-gray-100">
  <?php $rows = [
    'Admission No'    => $profile['admission_no'],
    'Class'           => trim(($profile['class_name'] ?? '') . ' ' . ($profile['section_name'] ?? '')),
    'Roll No'         => $profile['roll_no'],
    'Academic Session'=> $profile['session_name'],
    'Gender'          => $profile['gender'],
    'Date of Birth'   => $profile['dob'] ? date('d M Y', strtotime($profile['dob'])) : null,
    'Blood Group'     => $profile['blood_group'],
    'Address'         => $profile['address'],
    'Status'          => $profile['status'],
  ]; ?>
  <?php foreach ($rows as $label => $val): ?>
  <?php if ($val): ?>
  <div class="flex items-start justify-between py-3 gap-2 first:pt-0 last:pb-0">
    <span class="text-xs text-gray-500 flex-shrink-0"><?= e($label) ?></span>
    <span class="text-sm font-medium text-gray-900 text-right"><?= e($val) ?></span>
  </div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>

<?php elseif ($role === 'parent'): ?>
<h2 class="section-title mb-2">Guardian Info</h2>
<div class="card mb-4 divide-y divide-gray-100">
  <?php $rows = [
    'Relation' => $profile['relation'] ?? null,
    'Phone'    => $profile['phone']    ?? null,
    'Email'    => $profile['email']    ?? null,
    'Address'  => $profile['address']  ?? null,
  ]; ?>
  <?php foreach ($rows as $label => $val): ?>
  <?php if ($val): ?>
  <div class="flex items-start justify-between py-3 gap-2 first:pt-0 last:pb-0">
    <span class="text-xs text-gray-500 flex-shrink-0"><?= e($label) ?></span>
    <span class="text-sm font-medium text-gray-900 text-right"><?= e($val) ?></span>
  </div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>

<!-- Children list -->
<?php if (!empty($children)): ?>
<h2 class="section-title mb-2">My Children</h2>
<div class="card mb-4 divide-y divide-gray-100">
  <?php foreach ($children as $child): ?>
  <div class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
    <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm flex-shrink-0">
      <?= strtoupper(($child['full_name'][0] ?? '?')) ?>
    </div>
    <div class="flex-1 min-w-0">
      <p class="text-sm font-semibold text-gray-900 truncate"><?= e($child['full_name']) ?></p>
      <p class="text-xs text-gray-500"><?= e(trim(($child['class_name'] ?? '') . ' ' . ($child['section_name'] ?? ''))) ?></p>
    </div>
    <span class="badge <?= $child['status'] === 'active' ? 'badge-green' : 'badge-gray' ?>">
      <?= e($child['status']) ?>
    </span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Actions -->
<div class="space-y-3 mb-6">
  <a href="<?= portal_url('change-password') ?>" class="btn-secondary w-full flex items-center justify-center gap-2 py-3">
    🔑 Change Password
  </a>
  <form method="POST" action="<?= portal_url('logout') ?>" onsubmit="return confirm('Log out?')">
    <?= csrf_field() ?>
    <button type="submit" class="w-full btn-danger py-3 flex items-center justify-center gap-2">
      🚪 Log Out
    </button>
  </form>
</div>

<?php portal_foot('profile'); ?>
