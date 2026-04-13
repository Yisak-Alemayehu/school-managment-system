<?php
/**
 * Portal — Results View (student & parent)
 */

$role = portal_role();
if ($role === 'student') {
    $studentId = portal_linked_id();
} else {
    $child = portal_active_child();
    if (!$child) {
        portal_head('Results', portal_url('dashboard'));
        echo '<div class="card text-center py-12 text-gray-400"><p class="text-4xl mb-3">📊</p>';
        echo '<p class="text-sm">No child selected. Go back to the dashboard.</p></div>';
        portal_foot('results');
        return;
    }
    $studentId = (int) $child['id'];
}
$selectedId = isset($_GET['exam_id']) ? (int) $_GET['exam_id'] : null;

// Enrollment
$enrollment = db_fetch_one(
    "SELECT e.class_id, e.session_id, acs.name AS session_name
     FROM enrollments e
     JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
     WHERE e.student_id = ? AND e.status = 'active'
     ORDER BY e.id DESC LIMIT 1",
    [$studentId]
);

// All exams with results for this student
$exams = [];
if ($enrollment) {
    $exams = db_fetch_all(
        "SELECT DISTINCT e.id, e.name, e.type, e.start_date, e.status
         FROM exams e
         JOIN marks m ON m.exam_id = e.id AND m.student_id = ?
         WHERE e.session_id = ?
         ORDER BY e.start_date DESC",
        [$studentId, $enrollment['session_id']]
    );
}

// Determine which exam to show
if (!$selectedId && !empty($exams)) {
    $selectedId = (int) $exams[0]['id'];
}

// Selected exam
$selectedExam = null;
foreach ($exams as $ex) {
    if ((int) $ex['id'] === $selectedId) {
        $selectedExam = $ex;
        break;
    }
}

// Marks for selected exam
$marks = [];
if ($selectedId) {
    $marks = db_fetch_all(
        "SELECT m.marks_obtained, m.max_marks, m.is_absent, m.remarks,
                s.name AS subject_name, s.code AS subject_code,
                CASE WHEN m.max_marks > 0 AND m.is_absent = 0
                     THEN ROUND(m.marks_obtained / m.max_marks * 100, 1)
                     ELSE NULL END AS percentage
         FROM marks m
         JOIN subjects s ON s.id = m.subject_id
         WHERE m.exam_id = ? AND m.student_id = ?
         ORDER BY s.name ASC",
        [$selectedId, $studentId]
    );
}

// Report card
$reportCard = null;
if ($selectedId && $enrollment) {
    $reportCard = db_fetch_one(
        "SELECT percentage, grade, rank, total_marks, total_max_marks,
                teacher_remarks, status
         FROM report_cards
         WHERE student_id = ? AND exam_id = ? AND status = 'published'",
        [$studentId, $selectedId]
    );
}

portal_head('Results', portal_url('dashboard'));
?>

<!-- Exam selector -->
<?php if (!empty($exams)): ?>
<div class="mb-4">
  <p class="section-title">Select Exam</p>
  <form method="GET" action="<?= portal_url('results') ?>">
    <select name="exam_id" onchange="this.form.submit()"
            class="form-input">
      <?php foreach ($exams as $ex): ?>
      <option value="<?= (int)$ex['id'] ?>"
              <?= $selectedId === (int)$ex['id'] ? 'selected' : '' ?>>
        <?= e($ex['name']) ?>
        <?php if ($ex['status'] !== 'completed'): ?>(<?= e($ex['status']) ?>)<?php endif; ?>
      </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>
<?php else: ?>
<div class="card text-center py-10 text-gray-400">
  <p class="text-4xl mb-3">📊</p>
  <p class="text-sm">No exam results available yet.</p>
</div>
<?php portal_foot('results'); return; endif; ?>

<?php if ($selectedExam): ?>
<div class="flex items-center gap-2 mb-4">
  <h3 class="font-bold text-gray-900"><?= e($selectedExam['name']) ?></h3>
  <?php if ($selectedExam['start_date']): ?>
  <span class="badge badge-blue"><?= e(date('d M Y', strtotime($selectedExam['start_date']))) ?></span>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Report card summary -->
<?php if ($reportCard): ?>
<div class="card mb-5 bg-gradient-to-br from-primary-50 to-white border-primary-200">
  <p class="section-title text-primary-700">Report Card Summary</p>
  <div class="grid grid-cols-3 gap-3 text-center">
    <div>
      <div class="text-2xl font-bold text-primary-700"><?= e($reportCard['grade'] ?? '—') ?></div>
      <div class="text-xs text-gray-500">Grade</div>
    </div>
    <div>
      <div class="text-2xl font-bold text-gray-900"><?= e($reportCard['percentage'] ?? '—') ?>%</div>
      <div class="text-xs text-gray-500">Score</div>
    </div>
    <div>
      <div class="text-2xl font-bold text-gray-700">#<?= e($reportCard['rank'] ?? '—') ?></div>
      <div class="text-xs text-gray-500">Rank</div>
    </div>
  </div>
  <?php if ($reportCard['total_marks'] ?? null): ?>
  <p class="text-center text-xs text-gray-500 mt-2">
    Total: <?= e($reportCard['total_marks']) ?> / <?= e($reportCard['total_max_marks']) ?>
  </p>
  <?php endif; ?>
  <?php if ($reportCard['teacher_remarks'] ?? null): ?>
  <p class="text-xs text-gray-600 italic mt-2 border-t border-primary-100 pt-2">
    "<?= e($reportCard['teacher_remarks']) ?>"
  </p>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Marks table -->
<?php if (!empty($marks)): ?>
<div class="mb-5">
  <p class="section-title">Subject Marks</p>
  <div class="card p-0 overflow-hidden">
    <!-- Header -->
    <div class="grid grid-cols-12 gap-1 px-4 py-2 bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
      <div class="col-span-5">Subject</div>
      <div class="col-span-3 text-center">Marks</div>
      <div class="col-span-2 text-center">%</div>
      <div class="col-span-2 text-center">Grade</div>
    </div>
    <div class="divide-y divide-gray-50">
      <?php foreach ($marks as $m):
        $pct = $m['percentage'];
        $pctColor = $m['is_absent'] ? 'text-gray-400'
                  : ($pct >= 75 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600'));
        // Simple grade calculation
        if ($m['is_absent']) {
            $grade = 'Ab';
        } elseif ($pct === null) {
            $grade = '—';
        } elseif ($pct >= 90) { $grade = 'A+';
        } elseif ($pct >= 80) { $grade = 'A';
        } elseif ($pct >= 70) { $grade = 'B';
        } elseif ($pct >= 60) { $grade = 'C';
        } elseif ($pct >= 50) { $grade = 'D';
        } else { $grade = 'F'; }
      ?>
      <div class="grid grid-cols-12 gap-1 px-4 py-3 items-center">
        <div class="col-span-5">
          <p class="text-sm font-medium text-gray-900"><?= e($m['subject_name']) ?></p>
          <?php if ($m['subject_code']): ?>
          <p class="text-xs text-gray-400"><?= e($m['subject_code']) ?></p>
          <?php endif; ?>
        </div>
        <div class="col-span-3 text-center text-sm">
          <?php if ($m['is_absent']): ?>
            <span class="badge badge-red text-[10px]">Absent</span>
          <?php else: ?>
            <span class="font-semibold"><?= e($m['marks_obtained']) ?></span
            ><span class="text-gray-400">/ <?= e($m['max_marks']) ?></span>
          <?php endif; ?>
        </div>
        <div class="col-span-2 text-center text-sm font-bold <?= $pctColor ?>">
          <?= $pct !== null ? $pct . '%' : '—' ?>
        </div>
        <div class="col-span-2 text-center">
          <span class="badge <?= $grade !== 'F' && $grade !== 'Ab' ? 'badge-green' : 'badge-red' ?>"><?= $grade ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php elseif ($selectedId): ?>
<div class="card text-center py-8 text-gray-400">
  <p class="text-3xl mb-2">📋</p>
  <p class="text-sm">No marks entered for this exam yet.</p>
</div>
<?php endif; ?>

<?php portal_foot('results'); ?>
