<?php
/**
 * Portal — Enhanced Results View (student & parent)
 * Features: Subject list → Term selector → Assessment details → Totals/Grade
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

// Get active enrollment
$enrollment = db_fetch_one(
    "SELECT e.class_id, e.section_id, e.session_id, acs.name AS session_name,
            c.name AS class_name, sec.name AS section_name
     FROM enrollments e
     JOIN academic_sessions acs ON acs.id = e.session_id AND acs.is_active = 1
     JOIN classes c ON c.id = e.class_id
     LEFT JOIN sections sec ON sec.id = e.section_id
     WHERE e.student_id = ? AND e.status = 'active'
     ORDER BY e.id DESC LIMIT 1",
    [$studentId]
);

if (!$enrollment) {
    portal_head('Results', portal_url('dashboard'));
    echo '<div class="card text-center py-12 text-gray-400"><p class="text-4xl mb-3">📊</p>';
    echo '<p class="text-sm">No active enrollment found.</p></div>';
    portal_foot('results');
    return;
}

// Get enrolled subjects
// Get enrolled subjects (will be re-fetched below if term is from a different session)
$subjects = db_fetch_all(
    "SELECT s.id, s.name, s.code, s.type, cs.is_elective
     FROM class_subjects cs
     JOIN subjects s ON s.id = cs.subject_id
     WHERE cs.class_id = ? AND cs.session_id = ?
     ORDER BY s.name ASC",
    [$enrollment['class_id'], $enrollment['session_id']]
);

// Get all terms across all sessions the student has been enrolled in
$terms = db_fetch_all(
    "SELECT t.id, t.name, t.start_date, t.end_date, t.is_active, t.session_id,
            acs.name AS session_name
     FROM terms t
     JOIN academic_sessions acs ON acs.id = t.session_id
     WHERE t.session_id IN (
         SELECT DISTINCT e2.session_id FROM enrollments e2 WHERE e2.student_id = ? AND e2.status = 'active'
     )
     ORDER BY t.session_id DESC, t.start_date ASC",
    [$studentId]
);

// Current selections
$selectedSubjectId = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : null;
$selectedTermId = isset($_GET['term_id']) ? (int) $_GET['term_id'] : null;

// Default to first subject
if (!$selectedSubjectId && !empty($subjects)) {
    $selectedSubjectId = (int) $subjects[0]['id'];
}

// Default to active term
if (!$selectedTermId && !empty($terms)) {
    foreach ($terms as $t) {
        if ($t['is_active']) { $selectedTermId = (int) $t['id']; break; }
    }
    if (!$selectedTermId) $selectedTermId = (int) $terms[0]['id'];
}

// Get selected subject info
$selectedSubject = null;
foreach ($subjects as $s) {
    if ((int) $s['id'] === $selectedSubjectId) { $selectedSubject = $s; break; }
}

// Get selected term info
$selectedTerm = null;
foreach ($terms as $t) {
    if ((int) $t['id'] === $selectedTermId) { $selectedTerm = $t; break; }
}

// Resolve the enrollment for the selected term's session (may differ from current)
$termSessionId = $selectedTerm ? (int) $selectedTerm['session_id'] : $enrollment['session_id'];
$termEnrollment = $enrollment; // default to current
if ($termSessionId !== (int) $enrollment['session_id']) {
    $alt = db_fetch_one(
        "SELECT e.class_id, e.section_id, e.session_id, acs.name AS session_name,
                c.name AS class_name, sec.name AS section_name
         FROM enrollments e
         JOIN academic_sessions acs ON acs.id = e.session_id
         JOIN classes c ON c.id = e.class_id
         LEFT JOIN sections sec ON sec.id = e.section_id
         WHERE e.student_id = ? AND e.session_id = ? AND e.status = 'active'
         ORDER BY e.id DESC LIMIT 1",
        [$studentId, $termSessionId]
    );
    if ($alt) $termEnrollment = $alt;
}

// Re-fetch subjects for the selected term's session/class
$subjects = db_fetch_all(
    "SELECT s.id, s.name, s.code, s.type, cs.is_elective
     FROM class_subjects cs
     JOIN subjects s ON s.id = cs.subject_id
     WHERE cs.class_id = ? AND cs.session_id = ?
     ORDER BY s.name ASC",
    [$termEnrollment['class_id'], $termSessionId]
);

// Re-default subject if not in the new subject list
if ($selectedSubjectId) {
    $found = false;
    foreach ($subjects as $s) {
        if ((int) $s['id'] === $selectedSubjectId) { $found = true; break; }
    }
    if (!$found) $selectedSubjectId = !empty($subjects) ? (int) $subjects[0]['id'] : null;
}

// Get selected subject info
$selectedSubject = null;
foreach ($subjects as $s) {
    if ((int) $s['id'] === $selectedSubjectId) { $selectedSubject = $s; break; }
}
$assessments = [];
$totalObtained = 0;
$totalMax = 0;
$assessmentCount = 0;

if ($selectedSubjectId && $selectedTermId) {
    $assessments = db_fetch_all(
        "SELECT a.id, a.name, a.total_marks AS max_marks, a.created_at,
                sr.marks_obtained, sr.is_absent
         FROM assessments a
         LEFT JOIN student_results sr ON sr.assessment_id = a.id AND sr.student_id = ?
         WHERE a.subject_id = ? AND a.term_id = ?
           AND a.class_id = ? AND a.session_id = ?
         ORDER BY a.created_at ASC, a.name ASC",
        [$studentId, $selectedSubjectId, $selectedTermId,
         $termEnrollment['class_id'], $termSessionId]
    );

    foreach ($assessments as $a) {
        if ($a['marks_obtained'] !== null && !$a['is_absent']) {
            $totalObtained += (float) $a['marks_obtained'];
            $totalMax += (float) $a['max_marks'];
            $assessmentCount++;
        }
    }
}

// Also fetch exam-based marks for this subject + term
$examMarks = [];
if ($selectedSubjectId && $selectedTermId) {
    $examMarks = db_fetch_all(
        "SELECT e.name AS exam_name, e.type AS exam_type,
                m.marks_obtained, m.max_marks, m.is_absent
         FROM marks m
         JOIN exams e ON e.id = m.exam_id
         WHERE m.student_id = ? AND m.subject_id = ?
           AND e.session_id = ? AND e.term_id = ?
         ORDER BY e.start_date ASC",
        [$studentId, $selectedSubjectId, $termSessionId, $selectedTermId]
    );

    foreach ($examMarks as $em) {
        if ($em['marks_obtained'] !== null && !$em['is_absent']) {
            $totalObtained += (float) $em['marks_obtained'];
            $totalMax += (float) $em['max_marks'];
            $assessmentCount++;
        }
    }
}

// Calculate final percentage and grade
$finalPct = $totalMax > 0 ? round($totalObtained / $totalMax * 100, 1) : null;
if ($finalPct === null) { $finalGrade = '—'; $gradeColor = 'text-gray-400'; }
elseif ($finalPct >= 90) { $finalGrade = 'A+'; $gradeColor = 'text-green-600'; }
elseif ($finalPct >= 80) { $finalGrade = 'A'; $gradeColor = 'text-green-600'; }
elseif ($finalPct >= 70) { $finalGrade = 'B'; $gradeColor = 'text-blue-600'; }
elseif ($finalPct >= 60) { $finalGrade = 'C'; $gradeColor = 'text-yellow-600'; }
elseif ($finalPct >= 50) { $finalGrade = 'D'; $gradeColor = 'text-orange-600'; }
else { $finalGrade = 'F'; $gradeColor = 'text-red-600'; }

// Report card for this term
$reportCard = null;
if ($selectedTermId) {
    $reportCard = db_fetch_one(
        "SELECT rc.percentage, rc.grade, rc.rank, rc.total_marks, rc.total_max_marks,
                rc.teacher_remarks, rc.status
         FROM report_cards rc
         JOIN exams e ON e.id = rc.exam_id
         WHERE rc.student_id = ? AND e.term_id = ? AND e.session_id = ?
           AND rc.status = 'published'
         ORDER BY rc.id DESC LIMIT 1",
        [$studentId, $selectedTermId, $termSessionId]
    );
}

// Subject-level overview: all subjects with their average for selected term
$subjectOverview = [];
if ($selectedTermId) {
    foreach ($subjects as $s) {
        $sid = (int) $s['id'];
        // Assessment marks
        $aMarks = db_fetch_one(
            "SELECT SUM(sr.marks_obtained) AS obtained, SUM(a.total_marks) AS max_marks
             FROM assessments a
             LEFT JOIN student_results sr ON sr.assessment_id = a.id AND sr.student_id = ? AND sr.is_absent = 0
             WHERE a.subject_id = ? AND a.term_id = ? AND a.class_id = ? AND a.session_id = ?
               AND sr.marks_obtained IS NOT NULL",
            [$studentId, $sid, $selectedTermId, $termEnrollment['class_id'], $termSessionId]
        );
        // Exam marks
        $eMarks = db_fetch_one(
            "SELECT SUM(m.marks_obtained) AS obtained, SUM(m.max_marks) AS max_marks
             FROM marks m
             JOIN exams e ON e.id = m.exam_id
             WHERE m.student_id = ? AND m.subject_id = ? AND e.session_id = ? AND e.term_id = ?
               AND m.is_absent = 0 AND m.marks_obtained IS NOT NULL",
            [$studentId, $sid, $termSessionId, $selectedTermId]
        );

        $obt = ((float)($aMarks['obtained'] ?? 0)) + ((float)($eMarks['obtained'] ?? 0));
        $max = ((float)($aMarks['max_marks'] ?? 0)) + ((float)($eMarks['max_marks'] ?? 0));
        $pct = $max > 0 ? round($obt / $max * 100, 1) : null;

        $subjectOverview[] = [
            'id' => $sid,
            'name' => $s['name'],
            'code' => $s['code'],
            'obtained' => $obt,
            'max' => $max,
            'percentage' => $pct,
            'has_data' => $max > 0,
        ];
    }
}

portal_head('Results', portal_url('dashboard'));
?>

<!-- Enrollment badge -->
<div class="flex items-center gap-2 mb-4 flex-wrap">
  <span class="badge badge-blue"><?= e($termEnrollment['class_name']) ?></span>
  <?php if ($termEnrollment['section_name']): ?>
  <span class="badge badge-gray"><?= e($termEnrollment['section_name']) ?></span>
  <?php endif; ?>
  <span class="badge badge-gray"><?= e($termEnrollment['session_name']) ?></span>
</div>

<!-- Term selector pills (grouped by session) -->
<?php if (!empty($terms)):
  $termsBySession = [];
  foreach ($terms as $t) {
      $termsBySession[$t['session_name']][] = $t;
  }
?>
<div class="mb-4">
  <p class="section-title">Select Term</p>
  <?php foreach ($termsBySession as $sessName => $sessTerms): ?>
  <?php if (count($termsBySession) > 1): ?>
  <p class="text-xs text-gray-400 mb-1 mt-2"><?= e($sessName) ?></p>
  <?php endif; ?>
  <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
    <?php foreach ($sessTerms as $t):
      $isActive = (int) $t['id'] === $selectedTermId;
    ?>
    <a href="<?= portal_url('results', ['term_id' => $t['id'], 'subject_id' => $selectedSubjectId]) ?>"
       class="flex-shrink-0 px-4 py-2 rounded-xl text-sm font-semibold border-2 transition-all
              <?= $isActive
                ? 'border-primary-600 bg-primary-600 text-white shadow-md shadow-primary-200'
                : 'border-gray-200 bg-white text-gray-600 hover:border-primary-300 hover:bg-primary-50' ?>">
      <?= e($t['name']) ?>
      <?php if ($t['is_active']): ?>
        <span class="ml-1 text-xs opacity-75">(current)</span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Report Card Summary (if available) -->
<?php if ($reportCard): ?>
<div class="card mb-5 bg-gradient-to-br from-primary-50 to-white border-primary-200 animate-fade-in">
  <p class="section-title text-primary-700">📋 Report Card Summary</p>
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
  <?php if ($reportCard['total_marks']): ?>
  <p class="text-center text-xs text-gray-500 mt-2">
    Total: <?= e($reportCard['total_marks']) ?> / <?= e($reportCard['total_max_marks']) ?>
  </p>
  <?php endif; ?>
  <?php if ($reportCard['teacher_remarks']): ?>
  <p class="text-xs text-gray-600 italic mt-2 border-t border-primary-100 pt-2">
    "<?= e($reportCard['teacher_remarks']) ?>"
  </p>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Subject Overview Grid -->
<?php if (!empty($subjectOverview)): ?>
<div class="mb-5">
  <p class="section-title">📚 Subjects Overview</p>
  <div class="grid grid-cols-2 gap-2">
    <?php foreach ($subjectOverview as $so):
      $isSelected = $so['id'] === $selectedSubjectId;
      if ($so['percentage'] === null) { $g = '—'; $gc = 'text-gray-400'; }
      elseif ($so['percentage'] >= 90) { $g = 'A+'; $gc = 'text-green-600'; }
      elseif ($so['percentage'] >= 80) { $g = 'A'; $gc = 'text-green-600'; }
      elseif ($so['percentage'] >= 70) { $g = 'B'; $gc = 'text-blue-600'; }
      elseif ($so['percentage'] >= 60) { $g = 'C'; $gc = 'text-yellow-600'; }
      elseif ($so['percentage'] >= 50) { $g = 'D'; $gc = 'text-orange-600'; }
      else { $g = 'F'; $gc = 'text-red-600'; }
    ?>
    <a href="<?= portal_url('results', ['term_id' => $selectedTermId, 'subject_id' => $so['id']]) ?>"
       class="card p-3 transition-all hover:shadow-md <?= $isSelected ? 'ring-2 ring-primary-500 bg-primary-50' : '' ?>">
      <div class="flex items-start justify-between mb-1">
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-gray-900 truncate"><?= e($so['name']) ?></p>
          <?php if ($so['code']): ?>
          <p class="text-xs text-gray-400"><?= e($so['code']) ?></p>
          <?php endif; ?>
        </div>
        <?php if ($so['has_data']): ?>
        <span class="text-lg font-bold <?= $gc ?>"><?= $g ?></span>
        <?php endif; ?>
      </div>
      <?php if ($so['has_data']): ?>
      <div class="progress-bar mt-2" style="height:4px">
        <div class="progress-fill <?= $so['percentage'] >= 70 ? 'bg-green-500' : ($so['percentage'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?>"
             style="width:<?= min(100, $so['percentage']) ?>%"></div>
      </div>
      <p class="text-xs text-gray-500 mt-1"><?= $so['percentage'] ?>% · <?= e($so['obtained']) ?>/<?= e($so['max']) ?></p>
      <?php else: ?>
      <p class="text-xs text-gray-400 mt-1">No results yet</p>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Selected Subject Detail -->
<?php if ($selectedSubject): ?>
<div class="mb-5 animate-fade-in">
  <div class="flex items-center justify-between mb-3">
    <div>
      <p class="section-title mb-0">📝 Assessment Details</p>
      <p class="text-sm font-bold text-gray-900"><?= e($selectedSubject['name']) ?>
        <?php if ($selectedTerm): ?>
          <span class="text-gray-400 font-normal">· <?= e($selectedTerm['name']) ?></span>
        <?php endif; ?>
      </p>
    </div>
  </div>

  <?php if (!empty($assessments) || !empty($examMarks)): ?>
  <div class="card p-0 overflow-hidden">
    <!-- Header -->
    <div class="grid grid-cols-12 gap-1 px-4 py-2.5 bg-gray-50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-100">
      <div class="col-span-6">Assessment</div>
      <div class="col-span-3 text-center">Score</div>
      <div class="col-span-3 text-center">%</div>
    </div>
    <div class="divide-y divide-gray-50">
      <?php
      // Show assessments
      foreach ($assessments as $a):
        $pct = ($a['max_marks'] > 0 && $a['marks_obtained'] !== null && !$a['is_absent'])
               ? round($a['marks_obtained'] / $a['max_marks'] * 100, 1) : null;
        $pctColor = $a['is_absent'] ? 'text-gray-400'
                  : ($pct === null ? 'text-gray-400'
                  : ($pct >= 75 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600')));
      ?>
      <div class="grid grid-cols-12 gap-1 px-4 py-3 items-center">
        <div class="col-span-6">
          <p class="text-sm font-medium text-gray-900"><?= e($a['name']) ?></p>
          <?php if (!empty($a['created_at'])): ?>
          <p class="text-xs text-gray-400"><?= e(date('d M', strtotime($a['created_at']))) ?></p>
          <?php endif; ?>
        </div>
        <div class="col-span-3 text-center text-sm">
          <?php if ($a['is_absent']): ?>
            <span class="badge badge-red text-[10px]">Absent</span>
          <?php elseif ($a['marks_obtained'] !== null): ?>
            <span class="font-semibold"><?= e($a['marks_obtained']) ?></span><span class="text-gray-400">/<?= e($a['max_marks']) ?></span>
          <?php else: ?>
            <span class="text-gray-400">—</span>
          <?php endif; ?>
        </div>
        <div class="col-span-3 text-center text-sm font-bold <?= $pctColor ?>">
          <?= $pct !== null ? $pct . '%' : '—' ?>
        </div>
      </div>
      <?php endforeach; ?>

      <?php
      // Show exam marks
      foreach ($examMarks as $em):
        $pct = ($em['max_marks'] > 0 && $em['marks_obtained'] !== null && !$em['is_absent'])
               ? round($em['marks_obtained'] / $em['max_marks'] * 100, 1) : null;
        $pctColor = $em['is_absent'] ? 'text-gray-400'
                  : ($pct === null ? 'text-gray-400'
                  : ($pct >= 75 ? 'text-green-600' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600')));
        $typeBadge = match(strtolower($em['exam_type'] ?? '')) {
            'midterm' => 'badge-yellow',
            'final' => 'badge-red',
            default => 'badge-blue',
        };
      ?>
      <div class="grid grid-cols-12 gap-1 px-4 py-3 items-center bg-blue-50/30">
        <div class="col-span-6">
          <p class="text-sm font-medium text-gray-900"><?= e($em['exam_name']) ?></p>
          <p class="text-xs text-gray-400">
            <span class="badge <?= $typeBadge ?> text-[9px]"><?= e(ucfirst($em['exam_type'] ?? 'Exam')) ?></span>
          </p>
        </div>
        <div class="col-span-3 text-center text-sm">
          <?php if ($em['is_absent']): ?>
            <span class="badge badge-red text-[10px]">Absent</span>
          <?php elseif ($em['marks_obtained'] !== null): ?>
            <span class="font-semibold"><?= e($em['marks_obtained']) ?></span><span class="text-gray-400">/<?= e($em['max_marks']) ?></span>
          <?php else: ?>
            <span class="text-gray-400">—</span>
          <?php endif; ?>
        </div>
        <div class="col-span-3 text-center text-sm font-bold <?= $pctColor ?>">
          <?= $pct !== null ? $pct . '%' : '—' ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Total / Summary row -->
    <?php if ($assessmentCount > 0): ?>
    <div class="grid grid-cols-12 gap-1 px-4 py-3 items-center bg-gray-50 border-t-2 border-gray-200">
      <div class="col-span-6">
        <p class="text-sm font-bold text-gray-900">Total / Average</p>
        <p class="text-xs text-gray-500">
          <?= $assessmentCount ?> assessment<?= $assessmentCount > 1 ? 's' : '' ?>
          · <span class="badge <?= $finalGrade !== 'F' ? 'badge-green' : 'badge-red' ?> text-xs font-bold"><?= $finalGrade ?></span>
        </p>
      </div>
      <div class="col-span-3 text-center text-sm">
        <span class="font-bold"><?= e($totalObtained) ?></span><span class="text-gray-400">/<?= e($totalMax) ?></span>
      </div>
      <div class="col-span-3 text-center text-sm font-bold <?= $gradeColor ?>">
        <?= $finalPct ?>%
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Visual summary card -->
  <?php if ($assessmentCount > 0): ?>
  <div class="card mt-3 bg-gradient-to-r <?= $finalPct >= 70 ? 'from-green-50 to-emerald-50 border-green-200' : ($finalPct >= 50 ? 'from-yellow-50 to-amber-50 border-yellow-200' : 'from-red-50 to-rose-50 border-red-200') ?>">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs text-gray-500 font-semibold uppercase">Final Score</p>
        <p class="text-2xl font-bold <?= $gradeColor ?>"><?= $finalPct ?>%</p>
        <p class="text-sm text-gray-600"><?= e($totalObtained) ?> out of <?= e($totalMax) ?></p>
      </div>
      <div class="text-center">
        <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold <?= $gradeColor ?> 
                    <?= $finalPct >= 70 ? 'bg-green-100' : ($finalPct >= 50 ? 'bg-yellow-100' : 'bg-red-100') ?>">
          <?= $finalGrade ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="card text-center py-8 text-gray-400">
    <p class="text-3xl mb-2">📋</p>
    <p class="text-sm">No assessments or results recorded yet for this term.</p>
    <p class="text-xs mt-1">Results will appear here once your teacher enters them.</p>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php portal_foot('results'); ?>
