<?php
/**
 * Finance — Batch Payment Attachment PDF Generator
 * Generates an A5 portrait PDF showing all fees paid in one batch.
 * Follows the same style as PaymentAttachmentPDF (single payment).
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/vendor/setasign/fpdf/fpdf.php';

class BatchPaymentAttachmentPDF extends FPDF
{
    private array $batchData;
    private array $txRows;
    private bool $isCopy;
    private ?string $qrTempFile = null;

    // Brand colours (same as single receipt)
    private array $primary   = [30, 64, 120];
    private array $accent    = [0, 122, 204];
    private array $lightBg   = [245, 247, 250];
    private array $borderClr = [200, 210, 220];

    /**
     * @param array $batchData  Associative with keys: batch_receipt_no, student_name, admission_no,
     *                          class_name, channel, created_at, processed_by_name, reference, notes,
     *                          channel_transaction_id, payer_phone, channel_payment_type,
     *                          channel_depositor_name, channel_depositor_branch, currency
     * @param array $txRows     Array of rows, each: fee_description, amount, receipt_no, balance_before, balance_after
     * @param bool  $isCopy
     */
    public function __construct(array $batchData, array $txRows, bool $isCopy = false)
    {
        parent::__construct('P', 'mm', [148.5, 210]);
        $this->batchData = $batchData;
        $this->txRows    = $txRows;
        $this->isCopy    = $isCopy;
        $this->SetAutoPageBreak(false);
        $this->SetMargins(8, 8, 8);
    }

    public function __destruct()
    {
        if ($this->qrTempFile && file_exists($this->qrTempFile)) {
            @unlink($this->qrTempFile);
        }
    }

    public function generate(): void
    {
        $this->prepareQrCode();
        $this->AddPage();
        $this->drawPageBorder();
        $this->drawWatermark();
        $this->drawContent();
        $this->Output('I', 'Batch_Payment_' . ($this->batchData['batch_receipt_no'] ?? 'N-A') . '.pdf');
    }

    // ── QR Code ──────────────────────────────────────────

    private function prepareQrCode(): void
    {
        $b = $this->batchData;
        $totalPaid = 0;
        foreach ($this->txRows as $r) {
            $totalPaid += abs((float)($r['amount'] ?? 0));
        }
        $qrData = implode("\n", [
            'BATCH PAYMENT RECEIPT',
            'School: ' . (defined('SCHOOL_NAME') ? SCHOOL_NAME : 'Urji Beri School'),
            'Batch: ' . ($b['batch_receipt_no'] ?? '—'),
            'Student: ' . ($b['student_name'] ?? '—'),
            'Code: ' . ($b['admission_no'] ?? '—'),
            'Total: ' . number_format($totalPaid, 2) . ' ' . ($b['currency'] ?? 'ETB'),
            'Fees: ' . count($this->txRows),
            'Method: ' . ucfirst(str_replace('_', ' ', $b['channel'] ?? '—')),
            'Date: ' . (!empty($b['created_at']) ? date('d/m/Y H:i', strtotime($b['created_at'])) : '—'),
        ]);

        $url = 'https://api.qrserver.com/v1/create-qr-code/?'
             . http_build_query(['size' => '200x200', 'data' => $qrData, 'format' => 'png', 'margin' => '4']);

        $tmpFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $img = @file_get_contents($url, false, $ctx);
        if ($img !== false && strlen($img) > 100) {
            file_put_contents($tmpFile, $img);
            $this->qrTempFile = $tmpFile;
        }
    }

    // ── Decorative Elements ──────────────────────────────

    private function drawPageBorder(): void
    {
        $pw = $this->GetPageWidth();
        $ph = $this->GetPageHeight();
        $this->SetDrawColor(...$this->primary);
        $this->SetLineWidth(0.6);
        $this->Rect(4, 4, $pw - 8, $ph - 8);
        $this->SetLineWidth(0.2);
        $this->Rect(5.5, 5.5, $pw - 11, $ph - 11);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.2);
    }

    private function drawWatermark(): void
    {
        $this->SetFont('Helvetica', 'B', 44);
        $this->SetTextColor(230, 235, 240);
        $pw = $this->GetPageWidth();
        $text = 'Attachment';
        $textW = $this->GetStringWidth($text);
        $cx = $pw / 2;
        $cy = $this->GetPageHeight() / 2;
        $this->_rotatedText($cx - $textW / 2.5, $cy + 15, $text, 35);
        $this->SetTextColor(0, 0, 0);
    }

    private function _rotatedText(float $x, float $y, string $text, float $angle): void
    {
        $this->_rotate($angle, $x, $y);
        $this->SetXY($x, $y);
        $this->Write(0, $text);
        $this->_rotate(0, $x, $y);
    }

    private function _rotate(float $angle, float $x = -1, float $y = -1): void
    {
        if ($x == -1) $x = $this->x;
        if ($y == -1) $y = $this->y;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm',
                $c, $s, -$s, $c,
                $cx - $c * $cx - (-$s) * $cy,
                $cy - $s * $cx - $c * $cy
            ));
        } else {
            $this->_out('Q');
        }
    }

    // ── Helpers ──────────────────────────────────────────

    private function sectionHeading(float $x, float $y, float $w, string $title): float
    {
        $this->SetFillColor(...$this->primary);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetXY($x, $y);
        $this->Cell($w, 5.5, '  ' . $title, 0, 0, 'L', true);
        $this->SetTextColor(0, 0, 0);
        return $y + 5.5;
    }

    private function infoRow(float $x, float $y, float $labelW, float $valueW, float $rh, string $label, string $value, bool $highlight = false): float
    {
        if ($highlight) {
            $this->SetFillColor(255, 252, 235);
            $this->SetXY($x, $y);
            $this->Cell($labelW + $valueW, $rh, '', 0, 0, '', true);
        }
        $this->SetDrawColor(...$this->borderClr);
        $this->SetXY($x, $y);
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(90, 90, 90);
        $this->Cell($labelW, $rh, '  ' . $label, 'LB');
        $this->SetFont('Helvetica', 'B', 7);
        $this->SetTextColor(30, 30, 30);
        $this->Cell($valueW, $rh, ' ' . $value . ' ', 'RB');
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        return $y + $rh;
    }

    // ── Main Content ─────────────────────────────────────

    private function drawContent(): void
    {
        $b = $this->batchData;
        $pw = $this->GetPageWidth();
        $usable = $pw - 18;
        $lm = 9;
        $rh = 5;
        $labelW = 40;
        $valueW = $usable - $labelW;

        $y = 8;

        // ── COPY banner ──
        if ($this->isCopy) {
            $this->SetFillColor(200, 0, 0);
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('Helvetica', 'B', 10);
            $this->SetXY($lm, $y);
            $this->Cell($usable, 6, 'COPY', 0, 0, 'C', true);
            $this->SetTextColor(0, 0, 0);
            $y += 8;
        }

        // ── School Header ──
        $this->SetDrawColor(...$this->primary);
        $this->SetLineWidth(0.8);
        $this->Line($lm, $y, $lm + $usable, $y);
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);
        $y += 2;

        $schoolName = defined('SCHOOL_NAME') ? SCHOOL_NAME : 'Urji Beri School';
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetTextColor(...$this->primary);
        $this->SetXY($lm, $y);
        $this->Cell($usable, 6, strtoupper($schoolName), 0, 0, 'C');
        $y += 6;

        $this->SetFont('Helvetica', '', 6.5);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY($lm, $y);
        $tagline = 'School Management System';
        if (defined('SCHOOL_TELEPHONE')) {
            $tagline .= '  |  Tel: ' . SCHOOL_TELEPHONE;
        }
        $this->Cell($usable, 3.5, $tagline, 0, 0, 'C');
        $y += 4;

        $this->SetDrawColor(...$this->primary);
        $this->SetLineWidth(0.8);
        $this->Line($lm, $y, $lm + $usable, $y);
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);
        $y += 3;

        // ── Title ──
        $this->SetFillColor(...$this->primary);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetXY($lm, $y);
        $this->Cell($usable, 7, 'BATCH PAYMENT ATTACHMENT', 0, 0, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $y += 9;

        // ── Batch Receipt & Date Row ──
        $this->SetFont('Helvetica', 'B', 7);
        $this->SetTextColor(...$this->accent);
        $this->SetXY($lm, $y);
        $this->Cell($usable * 0.5, $rh, 'Batch: ' . ($b['batch_receipt_no'] ?? '—'), 0);
        $this->SetTextColor(80, 80, 80);
        $this->SetFont('Helvetica', '', 7);
        $dateStr = !empty($b['created_at']) ? date('d/m/Y  H:i', strtotime($b['created_at'])) : date('d/m/Y  H:i');
        $this->Cell($usable * 0.5, $rh, 'Date: ' . $dateStr, 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
        $y += $rh + 1;

        // ── STUDENT INFORMATION ──
        $y = $this->sectionHeading($lm, $y, $usable, 'STUDENT INFORMATION');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Student Name', $b['student_name'] ?? '—');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Student Code', $b['admission_no'] ?? '—');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Class', $b['class_name'] ?? '—');
        $y += 2;

        // ── FEES PAID (table) ──
        $y = $this->sectionHeading($lm, $y, $usable, 'FEES PAID');

        // Table header
        $colFee = $usable * 0.45;
        $colAmt = $usable * 0.25;
        $colRcp = $usable * 0.30;
        $thH = 4.5;

        $this->SetFillColor(...$this->lightBg);
        $this->SetDrawColor(...$this->borderClr);
        $this->SetFont('Helvetica', 'B', 6.5);
        $this->SetTextColor(60, 60, 60);
        $this->SetXY($lm, $y);
        $this->Cell($colFee, $thH, '  Fee Description', 'LBR', 0, 'L', true);
        $this->Cell($colAmt, $thH, 'Amount', 'BR', 0, 'R', true);
        $this->Cell($colRcp, $thH, 'Receipt #  ', 'BR', 0, 'R', true);
        $y += $thH;

        // Table rows
        $totalPaid = 0;
        $this->SetFont('Helvetica', '', 6.5);
        foreach ($this->txRows as $row) {
            $amt = abs((float)($row['amount'] ?? 0));
            $totalPaid += $amt;
            $currency = $b['currency'] ?? 'ETB';

            $this->SetTextColor(30, 30, 30);
            $this->SetXY($lm, $y);
            $this->Cell($colFee, $thH, '  ' . ($row['fee_description'] ?? '—'), 'LB');
            $this->Cell($colAmt, $thH, number_format($amt, 2) . ' ' . $currency . ' ', 'B', 0, 'R');
            $this->SetFont('Helvetica', '', 5.5);
            $this->SetTextColor(100, 100, 100);
            $this->Cell($colRcp, $thH, ($row['receipt_no'] ?? '—') . '  ', 'BR', 0, 'R');
            $this->SetFont('Helvetica', '', 6.5);
            $y += $thH;
        }

        // Total row
        $currency = $b['currency'] ?? 'ETB';
        $this->SetFillColor(255, 252, 235);
        $this->SetFont('Helvetica', 'B', 7);
        $this->SetTextColor(30, 30, 30);
        $this->SetXY($lm, $y);
        $this->Cell($colFee, $rh, '  TOTAL', 'LB', 0, 'L', true);
        $this->Cell($colAmt, $rh, number_format($totalPaid, 2) . ' ' . $currency . ' ', 'B', 0, 'R', true);
        $this->Cell($colRcp, $rh, count($this->txRows) . ' fee(s)  ', 'BR', 0, 'R', true);
        $y += $rh + 2;

        // ── PAYMENT DETAILS ──
        $y = $this->sectionHeading($lm, $y, $usable, 'PAYMENT DETAILS');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Payment Method', ucfirst(str_replace('_', ' ', $b['channel'] ?? '—')));

        if (!empty($b['channel_transaction_id'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Transaction ID', $b['channel_transaction_id']);
        }
        if (!empty($b['payer_phone'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Payer Phone', $b['payer_phone']);
        }
        if (!empty($b['channel_payment_type'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Payment Type', ucfirst($b['channel_payment_type']));
        }
        if (!empty($b['channel_depositor_name'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Depositor Name', $b['channel_depositor_name']);
        }
        if (!empty($b['channel_depositor_branch'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Bank / Branch', $b['channel_depositor_branch']);
        }
        if (!empty($b['reference'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Reference', $b['reference']);
        }
        if (!empty($b['notes'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Notes', $b['notes']);
        }
        $y += 2;

        // ── PROCESSED BY ──
        $y = $this->sectionHeading($lm, $y, $usable, 'PROCESSED BY');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Cashier', $b['processed_by_name'] ?? '—');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Date & Time', $dateStr);
        $y += 3;

        // ── QR Code & Signature Area ──
        $qrSize = 24;
        $sigAreaTop = $y;

        if ($this->qrTempFile && file_exists($this->qrTempFile)) {
            $this->Image($this->qrTempFile, $lm, $sigAreaTop, $qrSize, $qrSize, 'PNG');
            $this->SetFont('Helvetica', '', 5);
            $this->SetTextColor(100, 100, 100);
            $this->SetXY($lm, $sigAreaTop + $qrSize + 0.5);
            $this->Cell($qrSize, 3, 'Scan to verify', 0, 0, 'C');
            $this->SetTextColor(0, 0, 0);
        }

        $sigLeft = $lm + $qrSize + 6;
        $sigW    = ($lm + $usable - $sigLeft - 4) / 2;
        $this->SetFont('Helvetica', '', 6.5);
        $this->SetTextColor(80, 80, 80);

        $sigY = $sigAreaTop + $qrSize - 2;
        $this->SetDrawColor(...$this->borderClr);
        $this->Line($sigLeft, $sigY, $sigLeft + $sigW, $sigY);
        $this->SetXY($sigLeft, $sigY + 1);
        $this->Cell($sigW, 3.5, 'Cashier Signature', 0, 0, 'C');

        $sealLeft = $sigLeft + $sigW + 4;
        $this->Line($sealLeft, $sigY, $sealLeft + $sigW, $sigY);
        $this->SetXY($sealLeft, $sigY + 1);
        $this->Cell($sigW, 3.5, 'Stamp / Seal', 0, 0, 'C');

        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);

        // ── Footer ──
        $footerY = 195;
        $this->SetDrawColor(...$this->borderClr);
        $this->Line($lm, $footerY, $lm + $usable, $footerY);
        $this->SetDrawColor(0, 0, 0);

        $this->SetFont('Helvetica', '', 5.5);
        $this->SetTextColor(130, 130, 130);
        $this->SetXY($lm, $footerY + 1);
        $this->Cell($usable, 3, 'This is a computer-generated document.  Printed: ' . date('d/m/Y H:i:s'), 0, 0, 'C');

        if ($this->isCopy) {
            $this->SetFont('Helvetica', 'B', 5.5);
            $this->SetTextColor(200, 0, 0);
            $this->SetXY($lm, $footerY + 4.5);
            $this->Cell($usable, 3, 'This is a copy of the original attachment.', 0, 0, 'C');
        }

        $this->SetTextColor(0, 0, 0);
    }
}
