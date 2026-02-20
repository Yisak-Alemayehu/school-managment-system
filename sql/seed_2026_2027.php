<?php
/**
 * Full Seed Data — Academic Session 2026/2027
 *
 * Creates:
 *  - Academic session 2026/2027 (set as active)
 *  - 4 terms
 *  - 8 classes (Grade 1–8), 1 section each
 *  - Subjects per class (5–10)
 *  - 10 teachers with user accounts
 *  - 10 students per class (80 total) with Ethiopian names
 *  - Assessments per class/subject/term (sum = 100 per subject/term)
 *  - Term 1 results for ALL students, ALL classes, ALL subjects
 *  - Attendance for Term 1 (most present; ~4 lazy students per class absent often)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=urjiberi_school;charset=utf8mb4',
    'root', '0000',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$PASSWORD_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// ── helpers ────────────────────────────────────────────────────────────────
function ins(PDO $pdo, string $table, array $data): int {
    $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
    $phs  = implode(',', array_fill(0, count($data), '?'));
    $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($phs)")->execute(array_values($data));
    return (int)$pdo->lastInsertId();
}
function insIgnore(PDO $pdo, string $table, array $data): int {
    $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
    $phs  = implode(',', array_fill(0, count($data), '?'));
    $pdo->prepare("INSERT IGNORE INTO `$table` ($cols) VALUES ($phs)")->execute(array_values($data));
    return (int)$pdo->lastInsertId();
}
function one(PDO $pdo, string $sql, array $p = []): ?array {
    $s = $pdo->prepare($sql); $s->execute($p); return $s->fetch() ?: null;
}
function val(PDO $pdo, string $sql, array $p = []): mixed {
    $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn();
}
function say(string $msg): void { echo $msg . PHP_EOL; }

// ── 0. Deactivate all existing sessions and terms ────────────────────────────
$pdo->exec("UPDATE academic_sessions SET is_active = 0");
$pdo->exec("UPDATE terms SET is_active = 0");
say("✓ Deactivated all existing sessions/terms");

// ── 1. Academic session ──────────────────────────────────────────────────────
$sessId = val($pdo, "SELECT id FROM academic_sessions WHERE slug='2026-2027' LIMIT 1");
if (!$sessId) {
    $sessId = ins($pdo, 'academic_sessions', [
        'name'       => '2026/2027',
        'slug'       => '2026-2027',
        'start_date' => '2026-09-01',
        'end_date'   => '2027-07-31',
        'is_active'  => 1,
    ]);
} else {
    $pdo->prepare("UPDATE academic_sessions SET is_active=1 WHERE id=?")->execute([$sessId]);
}
say("✓ Session 2026/2027 id=$sessId");

// ── 2. Terms ─────────────────────────────────────────────────────────────────
$termDefs = [
    ['Term 1', 'term-1-2026', '2026-09-01', '2026-11-30', 1, 1],
    ['Term 2', 'term-2-2026', '2026-12-01', '2027-02-28', 0, 2],
    ['Term 3', 'term-3-2027', '2027-03-01', '2027-05-15', 0, 3],
    ['Term 4', 'term-4-2027', '2027-05-16', '2027-07-31', 0, 4],
];
$termIds = [];
foreach ($termDefs as [$tName, $tSlug, $tStart, $tEnd, $tActive, $tSort]) {
    $tid = val($pdo, "SELECT id FROM terms WHERE session_id=? AND slug=?", [$sessId, $tSlug]);
    if (!$tid) {
        $tid = ins($pdo, 'terms', [
            'session_id' => $sessId, 'name' => $tName, 'slug' => $tSlug,
            'start_date' => $tStart,  'end_date' => $tEnd,
            'is_active'  => $tActive, 'sort_order' => $tSort,
        ]);
    } else {
        $pdo->prepare("UPDATE terms SET is_active=?, sort_order=? WHERE id=?")->execute([$tActive, $tSort, $tid]);
    }
    $termIds[$tName] = (int)$tid;
    say("  ✓ Term: $tName id=$tid");
}
$term1Id = $termIds['Term 1'];

// ── 3. Classes (Grade 1–8) ────────────────────────────────────────────────────
$classDefs = [
    [1, 'Grade 1', 'grade-1'],
    [2, 'Grade 2', 'grade-2'],
    [3, 'Grade 3', 'grade-3'],
    [4, 'Grade 4', 'grade-4'],
    [5, 'Grade 5', 'grade-5'],
    [6, 'Grade 6', 'grade-6'],
    [7, 'Grade 7', 'grade-7'],
    [8, 'Grade 8', 'grade-8'],
];
$classIds = [];
foreach ($classDefs as [$num, $cName, $cSlug]) {
    $cid = val($pdo, "SELECT id FROM classes WHERE slug=?", [$cSlug]);
    if (!$cid) {
        $cid = ins($pdo, 'classes', [
            'name' => $cName, 'slug' => $cSlug, 'numeric_name' => $num,
            'sort_order' => $num, 'is_active' => 1, 'status' => 'active',
        ]);
    }
    $classIds[$cName] = (int)$cid;
    say("  ✓ Class: $cName id=$cid");
}

// ── 4. Sections (1 per class) ─────────────────────────────────────────────────
$sectionIds = []; // class_id => section_id
foreach ($classIds as $cName => $cid) {
    $sid = val($pdo, "SELECT id FROM sections WHERE class_id=? AND name='A'", [$cid]);
    if (!$sid) {
        $sid = ins($pdo, 'sections', ['class_id' => $cid, 'name' => 'A', 'capacity' => 40, 'is_active' => 1, 'status' => 'active']);
    }
    $sectionIds[$cid] = (int)$sid;
}
say("✓ Sections created (1 per class)");

// ── 5. Subjects ───────────────────────────────────────────────────────────────
// Core subjects for lower grades, more for upper grades
$allSubjectDefs = [
    // code  => name
    'AMHR'  => 'Amharic',
    'ENGL'  => 'English',
    'MATH'  => 'Mathematics',
    'SCIN'  => 'General Science',
    'SSST'  => 'Social Studies',
    'ARTS'  => 'Arts & Crafts',
    'HPE'   => 'Health & Physical Education',
    'MORLS' => 'Moral Education',
    'PHYS'  => 'Physics',
    'CHEM'  => 'Chemistry',
    'BIOL'  => 'Biology',
    'HIST'  => 'History',
    'GEOG'  => 'Geography',
    'CIVCS' => 'Civic & Ethics',
    'ICT'   => 'ICT / Computer',
];
$subjectIds = []; // code => id
foreach ($allSubjectDefs as $code => $name) {
    $sid = val($pdo, "SELECT id FROM subjects WHERE code=?", [$code]);
    if (!$sid) {
        $sid = ins($pdo, 'subjects', ['name' => $name, 'code' => $code, 'type' => 'theory', 'is_active' => 1, 'status' => 'active']);
    }
    $subjectIds[$code] = (int)$sid;
}
say("✓ " . count($subjectIds) . " subjects created");

// Per-class subject assignments
$classSubjectMap = [
    'Grade 1' => ['AMHR','ENGL','MATH','SCIN','SSST','ARTS','HPE'],
    'Grade 2' => ['AMHR','ENGL','MATH','SCIN','SSST','ARTS','HPE'],
    'Grade 3' => ['AMHR','ENGL','MATH','SCIN','SSST','ARTS','HPE','MORLS'],
    'Grade 4' => ['AMHR','ENGL','MATH','SCIN','SSST','ARTS','HPE','MORLS'],
    'Grade 5' => ['AMHR','ENGL','MATH','SCIN','SSST','HPE','MORLS','CIVCS','ICT'],
    'Grade 6' => ['AMHR','ENGL','MATH','SCIN','SSST','HPE','MORLS','CIVCS','ICT'],
    'Grade 7' => ['AMHR','ENGL','MATH','PHYS','CHEM','BIOL','HIST','GEOG','CIVCS','ICT'],
    'Grade 8' => ['AMHR','ENGL','MATH','PHYS','CHEM','BIOL','HIST','GEOG','CIVCS','ICT'],
];
$classSubjectIds = []; // class_id => [subject_id, ...]
foreach ($classSubjectMap as $cName => $codes) {
    $cid = $classIds[$cName];
    $classSubjectIds[$cid] = [];
    foreach ($codes as $code) {
        $subId = $subjectIds[$code];
        insIgnore($pdo, 'class_subjects', ['class_id'=>$cid,'subject_id'=>$subId,'session_id'=>$sessId,'is_elective'=>0]);
        $classSubjectIds[$cid][] = $subId;
    }
}
say("✓ Class-subject mappings done");

// ── 6. Teachers (10 teachers) ────────────────────────────────────────────────
$teacherDefs = [
    ['Tesfaye',   'Bekele',    'male',   'tesfaye.bekele',  'teacher1@urjiberi.edu.et'],
    ['Almaz',     'Tadesse',   'female', 'almaz.tadesse',   'teacher2@urjiberi.edu.et'],
    ['Girma',     'Haile',     'male',   'girma.haile',     'teacher3@urjiberi.edu.et'],
    ['Selamawit', 'Mengistu',  'female', 'selam.mengistu',  'teacher4@urjiberi.edu.et'],
    ['Yohannes',  'Gebre',     'male',   'yohannes.gebre',  'teacher5@urjiberi.edu.et'],
    ['Tigist',    'Alemu',     'female', 'tigist.alemu',    'teacher6@urjiberi.edu.et'],
    ['Dawit',     'Worku',     'male',   'dawit.worku',     'teacher7@urjiberi.edu.et'],
    ['Hiwot',     'Seyoum',    'female', 'hiwot.seyoum',    'teacher8@urjiberi.edu.et'],
    ['Mulugeta',  'Assefa',    'male',   'mulugeta.assefa', 'teacher9@urjiberi.edu.et'],
    ['Bethlehem', 'Kebede',    'female', 'bethlehem.kebede','teacher10@urjiberi.edu.et'],
];
// Get teacher role id
$teacherRoleId = val($pdo, "SELECT id FROM roles WHERE slug='teacher'");
$teacherUserIds = [];
foreach ($teacherDefs as [$fn, $ln, $gender, $uname, $email]) {
    $uid = val($pdo, "SELECT id FROM users WHERE username=?", [$uname]);
    if (!$uid) {
        $uid = ins($pdo, 'users', [
            'username'      => $uname,
            'email'         => $email,
            'password_hash' => $PASSWORD_HASH,
            'full_name'     => "$fn $ln",
            'first_name'    => $fn,
            'last_name'     => $ln,
            'gender'        => $gender,
            'is_active'     => 1,
            'status'        => 'active',
        ]);
        insIgnore($pdo, 'user_roles', ['user_id' => $uid, 'role_id' => $teacherRoleId]);
    } else {
        $uid = (int)$uid;
    }
    $teacherUserIds[] = $uid;
}
say("✓ " . count($teacherUserIds) . " teachers created");

// ── 7. Assign teachers to subjects per class ──────────────────────────────────
// Round-robin teacher assignment: class->subjects->teacher
$tIdx = 0;
foreach ($classSubjectIds as $cid => $subIds) {
    $secId    = $sectionIds[$cid];
    $isFirst  = true;
    foreach ($subIds as $subId) {
        $teacherId = $teacherUserIds[$tIdx % count($teacherUserIds)];
        insIgnore($pdo, 'class_teachers', [
            'class_id'        => $cid,
            'section_id'      => $secId,
            'subject_id'      => $subId,
            'teacher_id'      => $teacherId,
            'session_id'      => $sessId,
            'is_class_teacher'=> $isFirst ? 1 : 0,
        ]);
        $tIdx++;
        $isFirst = false;
    }
}
say("✓ Teacher-subject-class assignments done");

// ── 8. Students (10 per class, Ethiopian names) ───────────────────────────────
$maleFirstNames = [
    'Abebe','Bereket','Chala','Dawit','Ermias','Fikru','Girma','Henok',
    'Ibsa','Jemal','Kaleb','Lema','Mesfin','Nahom','Obsa','Petros',
    'Robel','Samuel','Tariku','Urgesa','Yared','Zeleke','Abel','Biniyam','Desta',
];
$femaleFirstNames = [
    'Abeba','Birke','Chaltu','Desta','Eyerusalem','Fikerte','Genet','Hiwot',
    'Iman','Jemila','Kumari','Liya','Meron','Nilufar','Obsitu','Peniel',
    'Roza','Sara','Tigist','Urge','Yeshi','Zewditu','Almaz','Blen','Dinke',
];
$lastNames = [
    'Tadesse','Bekele','Haile','Mengistu','Gebre','Alemu','Worku','Seyoum',
    'Assefa','Kebede','Tesfaye','Abebe','Girma','Mamo','Wolde','Nega',
    'Desta','Mulatu','Teshome','Berhane','Demeke','Kassaye','Lemma','Tilahun',
];
// Lazy students: indices 7,8,9 in each class (0-indexed) — absent ~40% of days
$lazyIndices = [7, 8, 9];

function randFrom(array $arr, int &$seed): string {
    $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
    return $arr[$seed % count($arr)];
}
$seed = 42;

$studentsByClass = []; // class_id => [student_id => isLazy]
$admNoCounter = (int)(val($pdo, "SELECT MAX(CAST(admission_no AS UNSIGNED)) FROM students") ?? 1000);
if ($admNoCounter < 1000) $admNoCounter = 1000;

foreach ($classIds as $cName => $cid) {
    $studentsByClass[$cid] = [];
    $grade = (int)filter_var($cName, FILTER_SANITIZE_NUMBER_INT);
    // Typical age: grade + 5 (Grade 1 → age 6, Grade 8 → age 13)
    $baseYear = 2026 - ($grade + 5);

    for ($i = 0; $i < 10; $i++) {
        $isMale   = ($i % 2 === 0);
        $fn       = $isMale ? randFrom($maleFirstNames, $seed) : randFrom($femaleFirstNames, $seed);
        $ln       = randFrom($lastNames, $seed);
        $gender   = $isMale ? 'male' : 'female';
        $dob      = sprintf('%d-%02d-%02d', $baseYear, ($seed % 12) + 1, ($seed % 28) + 1);
        $admNo    = 'URJ' . str_pad(++$admNoCounter, 5, '0', STR_PAD_LEFT);
        $rollNo   = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
        $isLazy   = in_array($i, $lazyIndices);

        // Check if student exists
        $stId = val($pdo, "SELECT id FROM students WHERE admission_no=?", [$admNo]);
        if (!$stId) {
            $stId = ins($pdo, 'students', [
                'admission_no'  => $admNo,
                'roll_no'       => $rollNo,
                'first_name'    => $fn,
                'last_name'     => $ln,
                'gender'        => $gender,
                'date_of_birth' => $dob,
                'nationality'   => 'Ethiopian',
                'admission_date'=> '2026-09-01',
                'status'        => 'active',
            ]);
        }
        $stId = (int)$stId;

        // Enrollment
        insIgnore($pdo, 'enrollments', [
            'student_id'  => $stId,
            'session_id'  => $sessId,
            'class_id'    => $cid,
            'section_id'  => $sectionIds[$cid],
            'roll_no'     => $rollNo,
            'status'      => 'active',
            'enrolled_at' => '2026-09-01',
        ]);

        $studentsByClass[$cid][$stId] = $isLazy;
    }
    say("  ✓ 10 students for $cName");
}
say("✓ All students enrolled");

// ── 9. Assessments (per class / subject / term, sum = 100) ───────────────────
// Assessment structure: Test1(15) + Test2(15) + Assignment(20) + Exam(50) = 100
$assessmentStructure = [
    ['Test 1',          15],
    ['Test 2',          15],
    ['Assignment',      20],
    ['Final Exam',      50],
];
// Get admin user id for created_by (fall back to first teacher if no admin)
$adminUid = val($pdo, "SELECT id FROM users WHERE username='admin' LIMIT 1");
if (!$adminUid) $adminUid = val($pdo, "SELECT MIN(id) FROM users");
$adminUid = (int)$adminUid;

$assessmentIds = []; // [class_id][subject_id][term_id][name] => assessment_id
foreach ($classIds as $cName => $cid) {
    $subIds = $classSubjectIds[$cid];
    foreach ($termIds as $tName => $tid) {
        foreach ($subIds as $subId) {
            foreach ($assessmentStructure as [$aName, $marks]) {
                // Check if exists
                $aid = val($pdo,
                    "SELECT id FROM assessments WHERE class_id=? AND subject_id=? AND session_id=? AND term_id=? AND name=?",
                    [$cid, $subId, $sessId, $tid, $aName]
                );
                if (!$aid) {
                    $aid = ins($pdo, 'assessments', [
                        'name'        => $aName,
                        'class_id'    => $cid,
                        'subject_id'  => $subId,
                        'session_id'  => $sessId,
                        'term_id'     => $tid,
                        'total_marks' => $marks,
                        'created_by'  => $adminUid,
                    ]);
                }
                $assessmentIds[$cid][$subId][$tid][$aName] = (int)$aid;
            }
        }
    }
}
say("✓ Assessments created for all class/subject/term combos");

// ── 10. Term 1 Results — all students, all classes, all subjects ──────────────
// Mark distribution per assessment type for a "good" student:
//   Test1 (15): 9-15, Test2 (15): 9-15, Assignment (20): 13-20, Exam (50): 30-50
// Lazy student: 30-70% of marks each
function randomMark(int $max, bool $isLazy, int &$seed): float {
    $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
    $pct  = $isLazy
        ? (30 + ($seed % 41)) / 100          // 30-70%
        : (60 + ($seed % 41)) / 100;         // 60-100%
    return round($max * $pct, 0);
}

foreach ($classIds as $cName => $cid) {
    $subIds     = $classSubjectIds[$cid];
    $studentsMap = $studentsByClass[$cid];
    $secId       = $sectionIds[$cid];

    foreach ($subIds as $subId) {
        foreach ($assessmentStructure as [$aName, $maxMarks]) {
            $aid = $assessmentIds[$cid][$subId][$term1Id][$aName] ?? null;
            if (!$aid) continue;

            foreach ($studentsMap as $stId => $isLazy) {
                $marks = randomMark($maxMarks, $isLazy, $seed);
                // Upsert
                $existing = val($pdo, "SELECT id FROM student_results WHERE assessment_id=? AND student_id=?", [$aid, $stId]);
                if (!$existing) {
                    ins($pdo, 'student_results', [
                        'assessment_id'  => $aid,
                        'student_id'     => $stId,
                        'class_id'       => $cid,
                        'section_id'     => $secId,
                        'marks_obtained' => $marks,
                        'is_absent'      => 0,
                        'entered_by'     => $adminUid,
                    ]);
                }
            }
        }
    }
    say("  ✓ Term 1 results for $cName");
}
say("✓ All Term 1 results inserted");

// ── 11. Attendance — Term 1 school days ──────────────────────────────────────
// Clear existing Term 1 attendance for this session before re-seeding (avoids dupe NULLs in unique key)
$pdo->prepare("DELETE FROM attendance WHERE session_id=? AND term_id=?")->execute([$sessId, $term1Id]);
// Term 1: 2026-09-01 → 2026-11-30 (Mon–Fri only)
$attStart = new DateTime('2026-09-01');
$attEnd   = new DateTime('2026-11-30');
$interval = new DateInterval('P1D');
$period   = new DatePeriod($attStart, $interval, (clone $attEnd)->modify('+1 day'));

$schoolDays = [];
foreach ($period as $day) {
    $dow = (int)$day->format('N'); // 1=Mon 7=Sun
    if ($dow <= 5) $schoolDays[] = $day->format('Y-m-d');
}

// Batch insert for performance
$pdo->exec("SET foreign_key_checks=0");
$attStmt = $pdo->prepare(
    "INSERT IGNORE INTO attendance
     (student_id, class_id, section_id, session_id, term_id, date, status, marked_by)
     VALUES (?,?,?,?,?,?,?,?)"
);
$attCount = 0;
$aSeed = 99;
foreach ($classIds as $cName => $cid) {
    $secId       = $sectionIds[$cid];
    $studentsMap = $studentsByClass[$cid];

    foreach ($schoolDays as $dateStr) {
        foreach ($studentsMap as $stId => $isLazy) {
            // Absent probability: lazy = 40%, normal = 5%
            $aSeed = ($aSeed * 1103515245 + 12345) & 0x7fffffff;
            $roll  = $aSeed % 100;
            $thresh = $isLazy ? 40 : 5;
            $status = $roll < $thresh ? 'absent' : 'present';
            $attStmt->execute([$stId, $cid, $secId, $sessId, $term1Id, $dateStr, $status, $adminUid]);
            $attCount++;
        }
    }
}
$pdo->exec("SET foreign_key_checks=1");
say("✓ Attendance inserted: $attCount records (" . count($schoolDays) . " school days)");

// ── Done ─────────────────────────────────────────────────────────────────────
say("");
say("═══════════════════════════════════════════");
say("  SEED COMPLETE — Summary:");
say("  Session  : 2026/2027 (id=$sessId)");
say("  Terms    : 4 (Term 1 active)");
say("  Classes  : " . count($classIds));
say("  Subjects : " . count($subjectIds));
say("  Teachers : " . count($teacherUserIds));
say("  Students : " . (count($classIds) * 10) . " (10 per class)");
say("  Results  : Term 1 fully entered");
say("  Attendance: Term 1 school days");
say("═══════════════════════════════════════════");
