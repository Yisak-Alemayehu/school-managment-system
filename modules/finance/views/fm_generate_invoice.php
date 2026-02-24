<?php
/**
 * Finance — Generate Professional Invoice (A5 Portrait Print)
 * Features: School branding, diagonal "Attachment" watermark, full invoice details
 */

$id = input_int('id');
if (!$id) {
    // If no ID, show invoice selection / search
    $sessionId = get_active_session_id();
    $search    = trim($_GET['q'] ?? '');
    $status    = $_GET['status'] ?? '';
    $page      = max(1, input_int('page') ?: 1);

    $where  = ['i.session_id = ?'];
    $params = [$sessionId];

    if ($status) {
        $where[]  = 'i.status = ?';
        $params[] = $status;
    }
    if ($search) {
        $where[]  = '(s.first_name LIKE ? OR s.last_name LIKE ? OR i.invoice_no LIKE ? OR s.admission_no LIKE ?)';
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    $whereStr = implode(' AND ', $where);

    $invoices = db_paginate("
        SELECT i.*, s.first_name, s.last_name, s.admission_no, c.name AS class_name
        FROM invoices i
        JOIN students s ON s.id = i.student_id
        JOIN classes c ON c.id = i.class_id
        WHERE {$whereStr}
        ORDER BY i.created_at DESC
    ", $params, $page, 15);

    $pageTitle = 'Generate Invoice';

    ob_start();
    ?>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Generate Invoice</h1>
                <p class="text-sm text-gray-500 mt-1">Select an invoice to generate a professional printable copy</p>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
                <input type="hidden" name="module" value="finance">
                <input type="hidden" name="action" value="fm-generate-invoice">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="q" value="<?= e($search) ?>"
                           placeholder="Student name, admission # or invoice #..."
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All</option>
                        <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Issued</option>
                        <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Partial</option>
                        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="overdue" <?= $status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-primary-800 text-white rounded-lg text-sm font-medium hover:bg-primary-900">Search</button>
                    <a href="<?= url('finance', 'fm-generate-invoice') ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Reset</a>
                </div>
            </form>
        </div>

        <!-- Invoice List -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($invoices['data'])): ?>
                            <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">
                                <svg class="mx-auto h-10 w-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                No invoices found.
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($invoices['data'] as $inv):
                                $badge = match($inv['status']) {
                                    'paid'      => 'bg-green-100 text-green-800',
                                    'partial'   => 'bg-yellow-100 text-yellow-800',
                                    'cancelled' => 'bg-gray-100 text-gray-800',
                                    'overdue'   => 'bg-red-100 text-red-800',
                                    default     => 'bg-blue-100 text-blue-800',
                                };
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-mono font-medium text-primary-700"><?= e($inv['invoice_no']) ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900"><?= e($inv['first_name'] . ' ' . $inv['last_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= e($inv['admission_no']) ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600"><?= e($inv['class_name']) ?></td>
                                <td class="px-4 py-3 text-sm text-right font-semibold"><?= format_currency($inv['total_amount']) ?></td>
                                <td class="px-4 py-3 text-sm text-right text-green-700"><?= format_currency($inv['paid_amount']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs rounded-full font-medium <?= $badge ?>"><?= ucfirst($inv['status']) ?></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="<?= url('finance', 'fm-generate-invoice') ?>&id=<?= $inv['id'] ?>"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-800 text-white rounded-lg text-xs font-medium hover:bg-primary-900 transition-colors"
                                       target="_blank">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Print
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (($invoices['last_page'] ?? 1) > 1): ?>
                <div class="px-6 py-3 border-t bg-gray-50">
                    <?= render_pagination($invoices, url('finance', 'fm-generate-invoice') . "&status={$status}&q=" . urlencode($search)) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    require ROOT_PATH . '/templates/layout.php';
    exit;
}

// ── Invoice found — render professional A5 print layout ──────
$invoice = db_fetch_one("
    SELECT i.*, s.first_name, s.last_name, s.admission_no, s.date_of_birth, s.gender,
           c.name AS class_name, sec.name AS section_name,
           sess.name AS session_name, t.name AS term_name
    FROM invoices i
    JOIN students s ON s.id = i.student_id
    JOIN classes c ON c.id = i.class_id
    LEFT JOIN enrollments e ON e.student_id = s.id AND e.session_id = i.session_id AND e.status = 'active'
    LEFT JOIN sections sec ON sec.id = e.section_id
    LEFT JOIN academic_sessions sess ON sess.id = i.session_id
    LEFT JOIN terms t ON t.id = i.term_id
    WHERE i.id = ?
", [$id]);

if (!$invoice) {
    set_flash('error', 'Invoice not found.');
    redirect('finance', 'fm-generate-invoice');
}

// Get line items
$items = db_fetch_all("
    SELECT ii.*, fc.name AS category_name
    FROM invoice_items ii
    LEFT JOIN fee_categories fc ON fc.id = ii.fee_category_id
    WHERE ii.invoice_id = ?
    ORDER BY ii.id
", [$id]);

// Get payments
$payments = db_fetch_all("
    SELECT p.*, u.full_name AS received_by_name
    FROM payments p
    LEFT JOIN users u ON u.id = p.received_by
    WHERE p.invoice_id = ? AND p.status = 'completed'
    ORDER BY p.payment_date ASC
", [$id]);

// Get guardian info
$guardian = db_fetch_one("
    SELECT g.full_name, g.phone, g.relation
    FROM student_guardians sg
    JOIN guardians g ON g.id = sg.guardian_id
    WHERE sg.student_id = ? AND sg.is_primary = 1
    LIMIT 1
", [$invoice['student_id']]);

$due = (float)$invoice['total_amount'] - (float)$invoice['paid_amount'];

// School info
$schoolName    = get_setting('school', 'name', APP_NAME);
$schoolAddress = get_setting('school', 'address', '');
$schoolCity    = get_setting('school', 'city', '');
$schoolPhone   = get_setting('school', 'phone', '');
$schoolEmail   = get_setting('school', 'email', '');
$schoolTagline = get_setting('school', 'tagline', '');

// Invoice status label
$statusLabel = strtoupper($invoice['status']);
$isPaid = $invoice['status'] === 'paid';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= e($invoice['invoice_no']) ?> — <?= e($schoolName) ?></title>
    <style>
        /* ── Reset & Base ───────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 11px; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a1a;
            background: #f0f0f0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── A5 Portrait Container ──────────────────────────── */
        .invoice-page {
            width: 148mm;
            min-height: 210mm;
            margin: 20px auto;
            background: #fff;
            padding: 10mm 10mm 8mm 10mm;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
        }

        /* ── Diagonal Watermark ─────────────────────────────── */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 52px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.04);
            text-transform: uppercase;
            letter-spacing: 12px;
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            user-select: none;
        }

        /* ── Content over watermark ─────────────────────────── */
        .invoice-content {
            position: relative;
            z-index: 1;
        }

        /* ── Header / School Branding ───────────────────────── */
        .header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 8px;
            border-bottom: 3px solid #1a365d;
            margin-bottom: 6px;
        }
        .header-logo {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .header-text { flex: 1; }
        .school-name {
            font-size: 16px;
            font-weight: 800;
            color: #1a365d;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.2;
        }
        .school-tagline {
            font-size: 8.5px;
            color: #64748b;
            font-style: italic;
            margin-top: 1px;
        }
        .school-contacts {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 2px;
        }
        .invoice-badge {
            text-align: right;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: 800;
            color: #1a365d;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .invoice-status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 3px;
        }
        .status-paid { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .status-partial { background: #fef9c3; color: #a16207; border: 1px solid #fde047; }
        .status-overdue { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .status-default { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }

        /* ── Thin accent line below header ──────────────────── */
        .accent-line {
            height: 2px;
            background: linear-gradient(90deg, #1a365d 0%, #3b82f6 50%, #1a365d 100%);
            margin-bottom: 8px;
        }

        /* ── Info Grid ──────────────────────────────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 8px;
        }
        .info-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 8px;
            background: #f8fafc;
        }
        .info-box-title {
            font-size: 7px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            line-height: 1.6;
        }
        .info-label { color: #64748b; }
        .info-value { font-weight: 600; color: #1e293b; text-align: right; }

        /* ── Items Table ────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 9px;
        }
        .items-table thead th {
            background: #1a365d;
            color: #fff;
            padding: 5px 6px;
            text-align: left;
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table thead th:first-child { border-radius: 4px 0 0 0; }
        .items-table thead th:last-child { border-radius: 0 4px 0 0; text-align: right; }
        .items-table tbody td {
            padding: 4px 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .items-table tbody tr:nth-child(even) { background: #f8fafc; }
        .items-table tbody tr:hover { background: #f1f5f9; }
        .items-table .col-sn { width: 28px; text-align: center; color: #94a3b8; }
        .items-table .col-qty { width: 36px; text-align: center; }
        .items-table .col-amount { text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; }

        /* ── Totals Section ─────────────────────────────────── */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 8px;
        }
        .totals-box {
            width: 55%;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 8px;
            font-size: 9px;
            border-bottom: 1px solid #f1f5f9;
        }
        .totals-row:last-child { border-bottom: none; }
        .totals-label { color: #64748b; }
        .totals-value { font-weight: 600; font-variant-numeric: tabular-nums; }
        .totals-discount { color: #16a34a; }
        .totals-fine { color: #dc2626; }
        .totals-grand {
            background: #1a365d;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 5px 8px;
        }
        .totals-paid {
            background: #f0fdf4;
            color: #15803d;
            font-weight: 700;
        }
        .totals-balance {
            background: #fef2f2;
            color: #b91c1c;
            font-weight: 700;
            font-size: 10px;
        }
        .totals-zero-balance {
            background: #f0fdf4;
            color: #15803d;
            font-weight: 700;
            font-size: 10px;
        }

        /* ── Payment History ────────────────────────────────── */
        .payments-section {
            margin-bottom: 8px;
        }
        .section-title {
            font-size: 8px;
            font-weight: 700;
            color: #1a365d;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #cbd5e1;
            margin-bottom: 4px;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .payment-table th {
            background: #f1f5f9;
            padding: 3px 5px;
            text-align: left;
            font-size: 7px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }
        .payment-table td {
            padding: 3px 5px;
            border-bottom: 1px solid #f1f5f9;
        }
        .payment-table .text-right { text-align: right; }

        /* ── Amount in Words ────────────────────────────────── */
        .amount-words {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 8px;
            color: #475569;
            margin-bottom: 8px;
            font-style: italic;
        }
        .amount-words strong {
            color: #1e293b;
            font-style: normal;
        }

        /* ── Notes ──────────────────────────────────────────── */
        .notes-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 7.5px;
            color: #92400e;
            margin-bottom: 8px;
        }

        /* ── Signature Block ────────────────────────────────── */
        .signature-block {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
            padding-top: 4px;
        }
        .sig-area {
            text-align: center;
        }
        .sig-line {
            border-top: 1px solid #94a3b8;
            margin-top: 28px;
            padding-top: 3px;
            font-size: 7.5px;
            color: #64748b;
            font-weight: 600;
        }

        /* ── Footer ─────────────────────────────────────────── */
        .footer {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 6.5px;
            color: #94a3b8;
            line-height: 1.5;
        }
        .footer-note {
            font-size: 7px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ── Print Controls ─────────────────────────────────── */
        .no-print {
            text-align: center;
            padding: 16px;
        }
        .no-print button, .no-print a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-print {
            background: #1a365d;
            color: #fff;
            border: none;
        }
        .btn-print:hover { background: #1e40af; }
        .btn-back {
            background: #fff;
            color: #475569;
            border: 1px solid #cbd5e1;
            margin-left: 8px;
        }
        .btn-back:hover { background: #f8fafc; }

        /* ── Print Media ────────────────────────────────────── */
        @media print {
            html { font-size: 11px; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .invoice-page {
                margin: 0;
                padding: 8mm;
                box-shadow: none;
                width: 148mm;
                min-height: 210mm;
            }
            @page {
                size: A5 portrait;
                margin: 0;
            }
        }

        /* ── Screen responsiveness ──────────────────────────── */
        @media screen and (max-width: 600px) {
            .invoice-page {
                width: 100%;
                margin: 0;
                padding: 6mm;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<!-- Print Controls -->
<div class="no-print">
    <button class="btn-print" onclick="window.print()">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        Print Invoice
    </button>
    <a class="btn-back" href="<?= url('finance', 'fm-generate-invoice') ?>">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to List
    </a>
</div>

<!-- A5 Invoice Page -->
<div class="invoice-page">
    <!-- Diagonal Watermark -->
    <div class="watermark">Attachment</div>

    <div class="invoice-content">

        <!-- ── Header: School Branding + Invoice Title ──────── -->
        <div class="header">
            <img src="<?= APP_URL ?>/img/Logo.png" alt="Logo" class="header-logo" onerror="this.style.display='none'">
            <div class="header-text">
                <div class="school-name"><?= e($schoolName) ?></div>
                <?php if ($schoolTagline): ?>
                    <div class="school-tagline"><?= e($schoolTagline) ?></div>
                <?php endif; ?>
                <div class="school-contacts">
                    <?= e($schoolAddress) ?>
                    <?php if ($schoolCity): ?> &bull; <?= e($schoolCity) ?><?php endif; ?>
                    <?php if ($schoolPhone): ?> &bull; Tel: <?= e($schoolPhone) ?><?php endif; ?>
                    <?php if ($schoolEmail): ?> &bull; <?= e($schoolEmail) ?><?php endif; ?>
                </div>
            </div>
            <div class="invoice-badge">
                <div class="invoice-title">INVOICE</div>
                <?php
                $statusCls = match($invoice['status']) {
                    'paid'    => 'status-paid',
                    'partial' => 'status-partial',
                    'overdue' => 'status-overdue',
                    default   => 'status-default',
                };
                ?>
                <div class="invoice-status <?= $statusCls ?>"><?= $statusLabel ?></div>
            </div>
        </div>
        <div class="accent-line"></div>

        <!-- ── Invoice & Student Info ───────────────────────── -->
        <div class="info-grid">
            <!-- Bill To -->
            <div class="info-box">
                <div class="info-box-title">Bill To</div>
                <div class="info-row">
                    <span class="info-label">Student Name</span>
                    <span class="info-value"><?= e($invoice['first_name'] . ' ' . $invoice['last_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Admission No</span>
                    <span class="info-value"><?= e($invoice['admission_no']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Class / Section</span>
                    <span class="info-value"><?= e($invoice['class_name']) ?><?= $invoice['section_name'] ? ' — ' . e($invoice['section_name']) : '' ?></span>
                </div>
                <?php if ($guardian): ?>
                <div class="info-row">
                    <span class="info-label">Parent/Guardian</span>
                    <span class="info-value"><?= e($guardian['full_name']) ?></span>
                </div>
                <?php if ($guardian['phone']): ?>
                <div class="info-row">
                    <span class="info-label">Guardian Tel</span>
                    <span class="info-value"><?= e($guardian['phone']) ?></span>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Invoice Details -->
            <div class="info-box">
                <div class="info-box-title">Invoice Details</div>
                <div class="info-row">
                    <span class="info-label">Invoice No</span>
                    <span class="info-value" style="font-family: 'Courier New', monospace;"><?= e($invoice['invoice_no']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Issue Date</span>
                    <span class="info-value"><?= $invoice['issued_date'] ? format_date($invoice['issued_date']) : format_date($invoice['created_at']) ?></span>
                </div>
                <?php if ($invoice['due_date']): ?>
                <div class="info-row">
                    <span class="info-label">Due Date</span>
                    <span class="info-value" style="color: <?= (strtotime($invoice['due_date']) < time() && !$isPaid) ? '#b91c1c' : '#1e293b' ?>;">
                        <?= format_date($invoice['due_date']) ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Academic Session</span>
                    <span class="info-value"><?= e($invoice['session_name'] ?? '—') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Term</span>
                    <span class="info-value"><?= e($invoice['term_name'] ?? '—') ?></span>
                </div>
            </div>
        </div>

        <!-- ── Line Items Table ─────────────────────────────── -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:28px; text-align:center;">S/N</th>
                    <th>Description</th>
                    <th style="width:36px; text-align:center;">Qty</th>
                    <th style="width:70px; text-align:right;">Unit Price</th>
                    <th style="width:70px; text-align:right;">Amount (<?= CURRENCY_SYMBOL ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:8px;">No items</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $i => $item):
                        $qty       = (int)($item['quantity'] ?? 1);
                        $unitPrice = (float)($item['amount'] ?? 0);
                        $lineTotal = (float)($item['total'] ?? $unitPrice * $qty);
                    ?>
                    <tr>
                        <td class="col-sn"><?= $i + 1 ?></td>
                        <td><?= e($item['description'] ?? $item['category_name'] ?? 'Fee Item') ?></td>
                        <td class="col-qty"><?= $qty ?></td>
                        <td class="col-amount"><?= number_format($unitPrice, 2) ?></td>
                        <td class="col-amount"><?= number_format($lineTotal, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ── Totals ───────────────────────────────────────── -->
        <div class="totals-section">
            <div class="totals-box">
                <?php
                $subtotal = (float)($invoice['subtotal'] ?? $invoice['total_amount']);
                $discount = (float)($invoice['discount_amount'] ?? 0);
                $fine     = (float)($invoice['fine_amount'] ?? 0);
                $total    = (float)$invoice['total_amount'];
                $paid     = (float)$invoice['paid_amount'];
                ?>

                <div class="totals-row">
                    <span class="totals-label">Subtotal</span>
                    <span class="totals-value"><?= CURRENCY_SYMBOL ?> <?= number_format($subtotal, 2) ?></span>
                </div>

                <?php if ($discount > 0): ?>
                <div class="totals-row">
                    <span class="totals-label">Discount<?= $invoice['discount_reason'] ? ' (' . e($invoice['discount_reason']) . ')' : '' ?></span>
                    <span class="totals-value totals-discount">- <?= CURRENCY_SYMBOL ?> <?= number_format($discount, 2) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($fine > 0): ?>
                <div class="totals-row">
                    <span class="totals-label">Fine<?= $invoice['fine_reason'] ? ' (' . e($invoice['fine_reason']) . ')' : '' ?></span>
                    <span class="totals-value totals-fine">+ <?= CURRENCY_SYMBOL ?> <?= number_format($fine, 2) ?></span>
                </div>
                <?php endif; ?>

                <div class="totals-row totals-grand">
                    <span>Grand Total</span>
                    <span><?= CURRENCY_SYMBOL ?> <?= number_format($total, 2) ?></span>
                </div>

                <div class="totals-row totals-paid">
                    <span>Amount Paid</span>
                    <span><?= CURRENCY_SYMBOL ?> <?= number_format($paid, 2) ?></span>
                </div>

                <div class="totals-row <?= $due <= 0 ? 'totals-zero-balance' : 'totals-balance' ?>">
                    <span>Balance Due</span>
                    <span><?= CURRENCY_SYMBOL ?> <?= number_format(max(0, $due), 2) ?></span>
                </div>
            </div>
        </div>

        <!-- ── Amount in Words ──────────────────────────────── -->
        <div class="amount-words">
            <strong>Amount in Words:</strong> <?= e(numberToWords($total)) ?> Birr Only
        </div>

        <!-- ── Payment History ──────────────────────────────── -->
        <?php if (!empty($payments)): ?>
        <div class="payments-section">
            <div class="section-title">Payment History</div>
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td style="font-family:'Courier New',monospace;"><?= e($pay['receipt_no'] ?? '—') ?></td>
                        <td><?= format_date($pay['payment_date']) ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $pay['method'])) ?></td>
                        <td><?= e($pay['reference'] ?? '—') ?></td>
                        <td class="text-right" style="font-weight:600; color:#15803d;"><?= CURRENCY_SYMBOL ?> <?= number_format($pay['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- ── Notes ────────────────────────────────────────── -->
        <?php if ($invoice['notes']): ?>
        <div class="notes-box">
            <strong>Note:</strong> <?= e($invoice['notes']) ?>
        </div>
        <?php endif; ?>

        <!-- ── Signature Block ──────────────────────────────── -->
        <div class="signature-block">
            <div class="sig-area">
                <div class="sig-line">Prepared By</div>
            </div>
            <div class="sig-area">
                <div class="sig-line">Accountant / Cashier</div>
            </div>
            <div class="sig-area">
                <div class="sig-line">Parent / Guardian</div>
            </div>
        </div>

        <!-- ── Footer ───────────────────────────────────────── -->
        <div class="footer">
            <div><?= e($schoolName) ?> &bull; <?= e($schoolAddress) ?> &bull; <?= e($schoolPhone) ?> &bull; <?= e($schoolEmail) ?></div>
            <div class="footer-note">
                This is a computer-generated invoice. &bull; Printed on <?= date('F j, Y \a\t g:i A') ?>
                &bull; Invoice ID: <?= e($invoice['invoice_no']) ?>
            </div>
            <div class="footer-note">Please retain this document for your records.</div>
        </div>

    </div><!-- /.invoice-content -->
</div><!-- /.invoice-page -->

<script>
// Auto-trigger print dialog when coming from the list (optional)
<?php if (isset($_GET['autoprint'])): ?>
window.addEventListener('load', function() { setTimeout(function() { window.print(); }, 400); });
<?php endif; ?>
</script>

</body>
</html>
<?php

// ── Helper: Number to Words (for Ethiopian Birr) ─────────────
function numberToWords(float $num): string {
    $num = abs(round($num, 2));
    $whole = (int)floor($num);
    $fraction = (int)round(($num - $whole) * 100);

    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
             'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
             'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $convert = function(int $n) use (&$convert, $ones, $tens): string {
        if ($n < 20) return $ones[$n];
        if ($n < 100) return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
        if ($n < 1000) return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 ? ' and ' . $convert($n % 100) : '');
        if ($n < 1000000) return $convert((int)($n / 1000)) . ' Thousand' . ($n % 1000 ? ' ' . $convert($n % 1000) : '');
        if ($n < 1000000000) return $convert((int)($n / 1000000)) . ' Million' . ($n % 1000000 ? ' ' . $convert($n % 1000000) : '');
        return $convert((int)($n / 1000000000)) . ' Billion' . ($n % 1000000000 ? ' ' . $convert($n % 1000000000) : '');
    };

    $result = $whole === 0 ? 'Zero' : $convert($whole);
    if ($fraction > 0) {
        $result .= ' and ' . $convert($fraction) . ' Cents';
    }
    return $result;
}

exit;
?>
