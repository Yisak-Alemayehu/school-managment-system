<?php
/**
 * Student ID Card — Professional A4 Print View
 * Layout: 5 cards per A4 page, front on right, back on left
 * Card size: 85.6mm × 54mm (standard credit card)
 */

// Parse IDs
$cardIds = [];
if (!empty($_GET['ids'])) {
    $cardIds = array_values(array_unique(array_filter(
        array_map('intval', explode(',', $_GET['ids'])),
        fn($v) => $v > 0
    )));
} else {
    $singleId = input_int('id');
    if ($singleId) $cardIds = [$singleId];
}

if (empty($cardIds)) {
    set_flash('error', 'No students specified.');
    redirect(url('students', 'id-cards'));
}

// Fetch students with guardian + enrollment info
$placeholders = implode(',', array_fill(0, count($cardIds), '?'));
$students = db_fetch_all("
    SELECT s.id, s.full_name, s.admission_no, s.gender, s.date_of_birth, s.photo, s.phone,
           c.name AS class_name, sec.name AS section_name,
           g.full_name AS guardian_name, g.phone AS guardian_phone,
           sess.name AS session_name
      FROM students s
      JOIN enrollments e ON e.student_id = s.id AND e.status = 'active'
      JOIN sections sec ON e.section_id = sec.id
      JOIN classes c ON sec.class_id = c.id
      LEFT JOIN academic_sessions sess ON sess.id = e.session_id
      LEFT JOIN student_guardians sg ON sg.student_id = s.id AND sg.is_primary = 1
      LEFT JOIN guardians g ON g.id = sg.guardian_id
     WHERE s.id IN ({$placeholders}) AND s.deleted_at IS NULL
     ORDER BY FIELD(s.id, {$placeholders})
", array_merge($cardIds, $cardIds));

if (empty($students)) {
    set_flash('error', 'No students found.');
    redirect(url('students', 'id-cards'));
}

// School info
$schoolName = 'Urji Beri School';
$schoolLogo = '/img/Logo.ico';
$schoolPhone = '0912097003';
$schoolAddress = 'Furi, Sheger, Oromia';
$schoolEmail = get_setting('school', 'email', '');
$schoolWebsite = get_setting('school', 'website', '');
$sessionName = $students[0]['session_name'] ?? (date('Y') . '/' . (date('Y') + 1));
$printDate = date('M j, Y');

// Calculate age
function calcAge(string $dob): int {
    return (int) date_diff(date_create($dob), date_create())->y;
}

// Chunk students into groups of 5 per page
$pages = array_chunk($students, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student ID Cards — <?= e($schoolName) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #e5e7eb;
            color: #111827;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Action Bar ── */
        .no-print {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: #0a3596; padding: 12px 24px;
            display: flex; align-items: center; gap: 12px;
        }
        .no-print .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
            cursor: pointer; border: none; transition: all 0.2s; text-decoration: none;
        }
        .btn-primary { background: #074DD9; color: #fff; }
        .btn-primary:hover { background: #0640b8; }
        .btn-outline { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .btn-outline:hover { background: #f3f4f6; }

        /* ── A4 Page ── */
        .a4-page {
            width: 210mm;
            height: 297mm;
            margin: 60px auto 20px;
            background: #fff;
            padding: 6mm 6mm;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 3.5mm;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
        }

        /* ── Card Row: Back (left) | gap | Front (right) ── */
        .card-row {
            display: flex;
            gap: 12mm;
            justify-content: center;
        }

        /* ── Card Shell ── */
        .id-card {
            width: 85.6mm;
            height: 54mm;
            border: 2px solid #074DD9;
            border-radius: 3.5mm;
            overflow: hidden;
            position: relative;
            background: #fff;
        }

        /* ══════════════════════════════
           FRONT SIDE
           ══════════════════════════════ */
        .card-front {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* Front Header */
        .front-header {
            background: #074DD9;
            color: #fff;
            padding: 2mm 3mm;
            display: flex;
            align-items: center;
            gap: 2.5mm;
        }
        .front-logo {
            width: 9mm;
            height: 9mm;
            object-fit: contain;
            border-radius: 1.5mm;
            background: #fff;
            padding: 0.3mm;
        }
        .front-logo-fallback {
            width: 9mm; height: 9mm;
            background: rgba(255,255,255,0.2);
            border-radius: 1.5mm;
            display: flex; align-items: center; justify-content: center;
            font-size: 12pt; font-weight: 800; color: #fff;
        }
        .front-school-name {
            font-size: 8.5pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .front-card-type {
            font-size: 5.5pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.85;
            margin-top: 0.3mm;
        }

        /* Front Body */
        .front-body {
            flex: 1;
            display: flex;
            padding: 2.5mm 3mm 1.5mm;
            gap: 3mm;
        }

        /* Photo Column */
        .front-photo-col {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .front-photo {
            width: 22mm;
            height: 26mm;
            object-fit: cover;
            border: 2px solid #074DD9;
            border-radius: 2mm;
            background: #f9fafb;
        }
        .front-photo-placeholder {
            width: 22mm; height: 26mm;
            background: #eef2ff;
            border: 2px solid #074DD9;
            border-radius: 2mm;
            display: flex; align-items: center; justify-content: center;
            font-size: 18pt; font-weight: 700; color: #074DD9;
        }

        /* Info Column */
        .front-info-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.6mm;
        }
        .front-name {
            font-size: 8pt;
            font-weight: 800;
            color: #074DD9;
            margin-bottom: 1mm;
            line-height: 1.2;
            border-bottom: 1px solid #dbe4ff;
            padding-bottom: 0.8mm;
        }
        .info-row {
            display: flex;
            align-items: baseline;
            line-height: 1.4;
        }
        .info-label {
            font-weight: 700;
            color: #374151;
            min-width: 15mm;
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .info-value {
            color: #111827;
            font-size: 7pt;
            font-weight: 600;
        }

        /* Front Footer */
        .front-footer {
            background: #074DD9;
            padding: 1mm 3mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 5.5pt;
            font-weight: 600;
            color: #fff;
            letter-spacing: 0.3px;
        }

        /* ══════════════════════════════
           BACK SIDE
           ══════════════════════════════ */
        .card-back {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* Back Header */
        .back-header {
            background: #074DD9;
            color: #fff;
            padding: 2mm 3mm;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2mm;
        }
        .back-logo {
            width: 7mm;
            height: 7mm;
            object-fit: contain;
            border-radius: 1mm;
            background: #fff;
            padding: 0.3mm;
        }
        .back-header-title {
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Back Body */
        .back-body {
            flex: 1;
            display: flex;
            padding: 2.5mm 3mm;
            gap: 3.5mm;
            align-items: center;
        }
        .back-qr-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1mm;
        }
        .back-qr {
            width: 28mm;
            height: 28mm;
            border: 2px solid #074DD9;
            border-radius: 2mm;
            padding: 1mm;
            background: #fff;
        }
        .back-qr-label {
            font-size: 5.5pt;
            color: #074DD9;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        /* Back Info */
        .back-info-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2mm;
        }
        .back-info-item {
            display: flex;
            align-items: flex-start;
            gap: 1.5mm;
            font-size: 6.5pt;
            color: #374151;
            line-height: 1.4;
            font-weight: 500;
        }
        .back-info-icon {
            width: 3.5mm;
            height: 3.5mm;
            flex-shrink: 0;
            margin-top: 0.2mm;
            color: #074DD9;
        }
        .back-divider {
            border: none;
            border-top: 1px solid #dbe4ff;
            margin: 0;
        }

        /* Back Footer */
        .back-footer {
            background: #074DD9;
            padding: 1.2mm 3mm;
            text-align: center;
            font-size: 5pt;
            color: #fff;
            font-weight: 600;
            letter-spacing: 0.3px;
            line-height: 1.4;
        }
        .back-notice {
            font-size: 5pt;
            color: #fecaca;
            font-weight: 700;
        }

        /* ── Print ── */
        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .a4-page {
                margin: 0;
                padding: 4mm 5mm;
                box-shadow: none;
                page-break-after: always;
                height: 297mm;
            }
            .a4-page:last-child {
                page-break-after: auto;
            }
            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar -->
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print ID Cards (<?= count($students) ?>)
        </button>
        <a href="<?= url('students', 'id-cards') ?>" class="btn btn-outline">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to List
        </a>
    </div>

    <?php foreach ($pages as $pageIndex => $pageStudents): ?>
    <div class="a4-page">
        <?php foreach ($pageStudents as $st):
            $age = $st['date_of_birth'] ? calcAge($st['date_of_birth']) : '—';
            $qrData = urlencode($st['admission_no']);
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . $qrData;
        ?>
        <div class="card-row">

            <!-- ═══ BACK SIDE (Left) ═══ -->
            <div class="id-card">
                <div class="card-back">
                    <div class="back-header">
                        <?php if (file_exists(APP_ROOT . '/public' . $schoolLogo)): ?>
                            <img src="<?= e($schoolLogo) ?>" alt="Logo" class="back-logo">
                        <?php endif; ?>
                        <div class="back-header-title"><?= e($schoolName) ?></div>
                    </div>
                    <div class="back-body">
                        <div class="back-qr-col">
                            <img src="<?= e($qrUrl) ?>" alt="QR" class="back-qr"
                                 onerror="this.style.display='none'">
                            <div class="back-qr-label"><?= e($st['admission_no']) ?></div>
                        </div>
                        <div class="back-info-col">
                            <div class="back-info-item">
                                <svg class="back-info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span><?= e($schoolAddress) ?></span>
                            </div>
                            <div class="back-info-item">
                                <svg class="back-info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span><?= e($schoolPhone) ?></span>
                            </div>
                            <?php if ($schoolEmail): ?>
                            <div class="back-info-item">
                                <svg class="back-info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span><?= e($schoolEmail) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($schoolWebsite): ?>
                            <div class="back-info-item">
                                <svg class="back-info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                                <span><?= e($schoolWebsite) ?></span>
                            </div>
                            <?php endif; ?>
                            <hr class="back-divider">
                            <div style="text-align:center; font-size:5pt; color:#64748b; line-height:1.3;">
                                If found, please return to the school.
                            </div>
                        </div>
                    </div>
                    <div class="back-footer">
                        This card is non-transferable
                        <span class="back-notice"> &bull; <?= e($sessionName) ?></span>
                    </div>
                </div>
            </div>

            <!-- ═══ FRONT SIDE (Right) ═══ -->
            <div class="id-card">
                <div class="card-front">
                    <div class="front-header">
                        <?php if (file_exists(APP_ROOT . '/public' . $schoolLogo)): ?>
                            <img src="<?= e($schoolLogo) ?>" alt="Logo" class="front-logo">
                        <?php else: ?>
                            <div class="front-logo-fallback"><?= strtoupper(substr($schoolName, 0, 1)) ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="front-school-name"><?= e($schoolName) ?></div>
                            <div class="front-card-type">Student Identity Card</div>
                        </div>
                    </div>
                    <div class="front-body">
                        <div class="front-photo-col">
                            <?php if ($st['photo']): ?>
                                <img src="<?= upload_url($st['photo']) ?>" alt="Photo" class="front-photo">
                            <?php else: ?>
                                <div class="front-photo-placeholder"><?= strtoupper(mb_substr($st['full_name'], 0, 1)) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="front-info-col">
                            <div class="front-name"><?= e($st['full_name']) ?></div>
                            <div class="info-row">
                                <span class="info-label">Sex:</span>
                                <span class="info-value"><?= e(ucfirst($st['gender'])) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Age:</span>
                                <span class="info-value"><?= e($age) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Grade:</span>
                                <span class="info-value"><?= e($st['class_name']) ?><?= $st['section_name'] ? ' – ' . e($st['section_name']) : '' ?></span>
                            </div>
                            <?php if ($st['guardian_name']): ?>
                            <div class="info-row">
                                <span class="info-label">Guardian:</span>
                                <span class="info-value"><?= e($st['guardian_name']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?= e($st['guardian_phone'] ?: $st['phone'] ?: '—') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">ID No:</span>
                                <span class="info-value"><?= e($st['admission_no']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="front-footer">
                        <span><?= e($sessionName) ?></span>
                        <span><?= e($printDate) ?></span>
                    </div>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

</body>
</html>
