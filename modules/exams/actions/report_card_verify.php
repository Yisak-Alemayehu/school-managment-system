<?php
/**
 * Report Card — QR Code Verification Endpoint
 * When a QR code on a report card is scanned, this page displays
 * a verification badge confirming the document's authenticity.
 */

$id  = input_int('id');
$sig = trim($_GET['sig'] ?? '');

$schoolName = get_school_name();
$schoolLogo = '/img/Logo.png';

// Validate inputs
$valid   = false;
$rc      = null;
$message = 'Invalid or tampered verification link.';

if ($id > 0 && $sig !== '') {
    // Recompute expected signature
    $rc = db_fetch_one("
        SELECT rc.*, s.first_name, s.last_name, s.admission_no, s.gender, s.date_of_birth, s.photo,
               c.name AS class_name, sec.name AS section_name,
               sess.name AS session_name, t.name AS term_name
        FROM report_cards rc
        JOIN students s ON s.id = rc.student_id
        JOIN classes c ON c.id = rc.class_id
        LEFT JOIN sections sec ON sec.id = rc.section_id
        LEFT JOIN academic_sessions sess ON sess.id = rc.session_id
        LEFT JOIN terms t ON t.id = rc.term_id
        WHERE rc.id = ?
    ", [$id]);

    if ($rc) {
        $expectedSig = hash_hmac('sha256', $id . '|' . $rc['student_id'] . '|' . $rc['session_id'], 'urjiberi_report_card_secret_2026');
        if (hash_equals($expectedSig, $sig)) {
            $valid   = true;
            $message = 'This report card is authentic and was issued by ' . $schoolName . '.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card Verification — <?= e($schoolName) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated background pattern */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(59,130,246,0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(16,185,129,0.08) 0%, transparent 50%),
                radial-gradient(circle at 50% 80%, rgba(139,92,246,0.06) 0%, transparent 50%);
            animation: bgPulse 8s ease-in-out infinite alternate;
            z-index: 0;
        }

        @keyframes bgPulse {
            from { opacity: 0.6; }
            to { opacity: 1; }
        }

        /* Card entrance animation */
        @keyframes cardSlideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.5); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes checkDraw {
            from { stroke-dashoffset: 48; }
            to { stroke-dashoffset: 0; }
        }
        @keyframes shieldPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0.3); }
            50% { box-shadow: 0 0 0 12px rgba(16,185,129,0); }
        }
        @keyframes shieldPulseRed {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.3); }
            50% { box-shadow: 0 0 0 12px rgba(239,68,68,0); }
        }

        .verify-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.05);
            max-width: 420px;
            width: 100%;
            overflow: hidden;
            position: relative;
            z-index: 1;
            animation: cardSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Header */
        .verify-header {
            padding: 24px 24px 16px;
            text-align: center;
            background: linear-gradient(180deg, #f8fafc, #ffffff);
            animation: fadeInUp 0.5s 0.2s both;
        }
        .verify-logo {
            width: 52px; height: 52px;
            border-radius: 14px;
            object-fit: contain;
            margin: 0 auto 8px;
            display: block;
            border: 2px solid #e2e8f0;
        }
        .verify-logo-fallback {
            width: 52px; height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, #1e293b, #334155);
            color: #fff; font-size: 22px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 8px;
        }
        .verify-school-name {
            font-size: 14px; font-weight: 700; color: #1e293b;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .verify-subtitle {
            font-size: 11px; color: #94a3b8; margin-top: 2px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        /* Status Badge */
        .verify-status {
            padding: 20px 24px 16px;
            text-align: center;
            animation: fadeInUp 0.5s 0.35s both;
        }
        .badge-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            animation: scaleIn 0.4s 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }
        .badge-valid .badge-icon {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            animation: scaleIn 0.4s 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both,
                       shieldPulse 2s 1s infinite;
        }
        .badge-invalid .badge-icon {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            animation: scaleIn 0.4s 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both,
                       shieldPulseRed 2s 1s infinite;
        }
        .badge-icon svg { filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1)); }
        .badge-icon .check-path {
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: checkDraw 0.6s 0.8s ease forwards;
        }

        .badge-label {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 16px; border-radius: 20px;
            font-size: 13px; font-weight: 700;
            letter-spacing: 0.3px;
            margin-bottom: 8px;
        }
        .badge-valid .badge-label {
            background: #ecfdf5; color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .badge-invalid .badge-label {
            background: #fef2f2; color: #991b1b;
            border: 1px solid #fecaca;
        }
        .badge-message {
            font-size: 12.5px; color: #64748b; line-height: 1.6;
            max-width: 300px; margin: 0 auto;
        }

        /* Student Details */
        .verify-details {
            padding: 0 20px 20px;
            animation: fadeInUp 0.5s 0.5s both;
        }
        .detail-grid {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
        }
        .detail-grid-title {
            padding: 10px 16px 6px;
            font-size: 10px; font-weight: 600;
            color: #94a3b8; text-transform: uppercase;
            letter-spacing: 1px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 16px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-row:hover { background: #f1f5f9; }
        .detail-label {
            color: #64748b; font-weight: 500;
            display: flex; align-items: center; gap: 6px;
        }
        .detail-label svg { width: 14px; height: 14px; color: #94a3b8; flex-shrink: 0; }
        .detail-value {
            color: #1e293b; font-weight: 600;
            text-align: right; max-width: 55%;
            word-break: break-word;
        }

        /* Grade badge inline */
        .grade-pill {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 2px 10px; border-radius: 6px;
            font-size: 12px; font-weight: 700;
        }
        .grade-a { background: #d1fae5; color: #065f46; }
        .grade-b { background: #dbeafe; color: #1e40af; }
        .grade-c { background: #fef3c7; color: #92400e; }
        .grade-d { background: #fed7aa; color: #9a3412; }
        .grade-f { background: #fee2e2; color: #991b1b; }

        /* Print button */
        .verify-actions {
            padding: 0 20px 16px;
            animation: fadeInUp 0.5s 0.6s both;
        }
        .btn-print {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, #1e293b, #334155);
            color: #fff; border: none; border-radius: 12px;
            font-size: 14px; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            text-decoration: none;
        }
        .btn-print:hover { background: linear-gradient(135deg, #334155, #475569); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .btn-print:active { transform: translateY(0); }
        .btn-print svg { width: 18px; height: 18px; }

        /* Footer */
        .verify-footer {
            padding: 14px 20px;
            text-align: center;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            animation: fadeInUp 0.5s 0.65s both;
        }
        .verify-footer-text {
            font-size: 10px; color: #94a3b8; line-height: 1.5;
        }
        .verify-time {
            display: inline-flex; align-items: center; gap: 4px;
            margin-top: 4px; font-size: 11px; color: #64748b; font-weight: 500;
        }
        .verify-time svg { width: 12px; height: 12px; }

        /* Responsive */
        @media (max-width: 480px) {
            body { padding: 12px; align-items: flex-start; padding-top: 24px; }
            .verify-card { border-radius: 20px; }
            .verify-header { padding: 20px 16px 12px; }
            .verify-status { padding: 16px 16px 12px; }
            .verify-details { padding: 0 14px 16px; }
            .verify-actions { padding: 0 14px 14px; }
            .verify-footer { padding: 12px 14px; }
            .badge-icon { width: 64px; height: 64px; }
            .detail-row { padding: 7px 12px; font-size: 12.5px; }
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <!-- Header -->
        <div class="verify-header">
            <?php if (file_exists(APP_ROOT . '/public' . $schoolLogo)): ?>
                <img src="<?= e(rtrim(APP_URL, '/') . $schoolLogo) ?>" alt="" class="verify-logo">
            <?php else: ?>
                <div class="verify-logo-fallback"><?= strtoupper(substr($schoolName, 0, 1)) ?></div>
            <?php endif; ?>
            <div class="verify-school-name"><?= e($schoolName) ?></div>
            <div class="verify-subtitle">Document Verification</div>
        </div>

        <!-- Status Badge -->
        <div class="verify-status <?= $valid ? 'badge-valid' : 'badge-invalid' ?>">
            <div class="badge-icon">
                <?php if ($valid): ?>
                <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="#065f46" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    <path class="check-path" stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/>
                </svg>
                <?php else: ?>
                <svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="#991b1b" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <?php endif; ?>
            </div>
            <div class="badge-label">
                <?php if ($valid): ?>
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Verified &mdash; Authentic Document
                <?php else: ?>
                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    Verification Failed
                <?php endif; ?>
            </div>
            <div class="badge-message"><?= e($message) ?></div>
        </div>

        <?php if ($valid && $rc): ?>
        <!-- Student Details -->
        <div class="verify-details">
            <div class="detail-grid">
                <div class="detail-grid-title">Student Information</div>
                <div class="detail-row">
                    <span class="detail-label">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Student
                    </span>
                    <span class="detail-value"><?= e($rc['first_name'] . ' ' . $rc['last_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        Admission No
                    </span>
                    <span class="detail-value"><?= e($rc['admission_no']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Class
                    </span>
                    <span class="detail-value"><?= e($rc['class_name']) ?><?= $rc['section_name'] ? ' — ' . e($rc['section_name']) : '' ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Session
                    </span>
                    <span class="detail-value"><?= e($rc['session_name'] ?? '—') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Term
                    </span>
                    <span class="detail-value"><?= e($rc['term_name'] ?? '—') ?></span>
                </div>

                <div class="detail-grid-title" style="margin-top:4px;">Academic Results</div>
                <div class="detail-row">
                    <span class="detail-label">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Average
                    </span>
                    <span class="detail-value"><?= number_format($rc['percentage'] ?? 0, 1) ?>%</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        Grade
                    </span>
                    <span class="detail-value">
                        <?php
                            $g = $rc['grade'] ?? '—';
                            $gc = match($g) { 'A' => 'grade-a', 'B' => 'grade-b', 'C' => 'grade-c', 'D' => 'grade-d', 'F' => 'grade-f', default => '' };
                        ?>
                        <span class="grade-pill <?= $gc ?>"><?= e($g) ?></span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Rank
                    </span>
                    <span class="detail-value"><?= $rc['rank'] ?? '—' ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Issued On
                    </span>
                    <span class="detail-value"><?= $rc['generated_at'] ? date('M j, Y', strtotime($rc['generated_at'])) : '—' ?></span>
                </div>
            </div>
        </div>

        <!-- Print Button -->
        <div class="verify-actions">
            <a href="/exams/report-card-print?id=<?= (int)$id ?>&copy=1&sig=<?= urlencode($sig) ?>" target="_blank" class="btn-print">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Copy
            </a>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="verify-footer">
            <div class="verify-footer-text">Document Verification System &mdash; <?= e($schoolName) ?></div>
            <div class="verify-time">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Verified at <?= date('M j, Y g:i A') ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php exit; ?>
