<?php
/**
 * Portal — Notices / Announcements View (shared)
 */

$role   = portal_role();
$page   = max(1, (int) ($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

// Role-based filter on target_roles
$roleFilter = $role === 'student' ? 'student' : 'parent';

$total = (int) db_fetch_value(
    "SELECT COUNT(*) FROM announcements
     WHERE status = 'published'
       AND (target_roles IS NULL OR target_roles = '' OR target_roles LIKE ?)",
    ["%{$roleFilter}%"]
);

$notices = db_fetch_all(
    "SELECT a.id, a.title, a.content, a.target_roles, a.type, a.created_at,
            u.full_name AS author
     FROM announcements a
     LEFT JOIN users u ON u.id = a.created_by
     WHERE a.status = 'published'
       AND (a.target_roles IS NULL OR a.target_roles = '' OR a.target_roles LIKE ?)
     ORDER BY a.is_pinned DESC, a.created_at DESC
     LIMIT ? OFFSET ?",
    ["%{$roleFilter}%", $limit, $offset]
);

$totalPages = (int) ceil($total / $limit);

portal_head('Notices', portal_url('dashboard'));
?>

<div class="flex items-center justify-between mb-4">
  <h2 class="font-bold text-gray-900">School Notices</h2>
  <span class="badge badge-blue"><?= $total ?> total</span>
</div>

<?php if (empty($notices)): ?>
<div class="card text-center py-12 text-gray-400">
  <p class="text-4xl mb-3">🔔</p>
  <p class="text-sm">No notices available.</p>
</div>
<?php else: ?>

<div class="space-y-3 mb-5">
  <?php foreach ($notices as $notice): ?>
  <div class="card space-y-2">
    <!-- Title row -->
    <div class="flex items-start justify-between gap-2">
      <h3 class="text-sm font-bold text-gray-900 flex-1"><?= e($notice['title']) ?></h3>
      <?php if ($notice['type']): ?>
      <span class="badge badge-blue flex-shrink-0">
        <?= e(ucfirst($notice['type'])) ?>
      </span>
      <?php endif; ?>
    </div>

    <!-- Meta -->
    <p class="text-xs text-gray-400">
      <?= e(date('d M Y', strtotime($notice['created_at']))) ?>
      <?php if ($notice['author']): ?>
       · By <?= e($notice['author']) ?>
      <?php endif; ?>
    </p>

    <!-- Content (expandable) -->
    <?php $contentLen = mb_strlen($notice['content'] ?? ''); ?>
    <?php if ($contentLen <= 200): ?>
      <p class="text-sm text-gray-600"><?= nl2br(e($notice['content'])) ?></p>
    <?php else: ?>
      <details>
        <summary class="text-sm text-gray-600 cursor-pointer list-none">
          <span class="line-clamp-3"><?= e(mb_substr($notice['content'], 0, 200)) ?>…</span>
          <span class="text-primary-600 text-xs font-semibold mt-1 block">Read more ↓</span>
        </summary>
        <p class="text-sm text-gray-600 mt-2"><?= nl2br(e($notice['content'])) ?></p>
        <span class="text-primary-600 text-xs font-semibold cursor-pointer">Show less ↑</span>
      </details>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="flex items-center justify-between mb-4">
  <?php if ($page > 1): ?>
  <a href="<?= portal_url('notices', ['page' => $page - 1]) ?>" class="btn-secondary px-4 py-2 text-sm">
    ← Previous
  </a>
  <?php else: ?>
  <span></span>
  <?php endif; ?>

  <span class="text-sm text-gray-500">Page <?= $page ?> of <?= $totalPages ?></span>

  <?php if ($page < $totalPages): ?>
  <a href="<?= portal_url('notices', ['page' => $page + 1]) ?>" class="btn-secondary px-4 py-2 text-sm">
    Next →
  </a>
  <?php else: ?>
  <span></span>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php portal_foot('notices'); ?>
