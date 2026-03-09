<?php
/**
 * Finance — Payment Attachment PDF Generator
 * Generates a professional A5 portrait PDF with:
 *   - Decorative border and header styling
 *   - "Attachment" watermark
 *   - "COPY" label on subsequent prints (print_count > 0)
 *   - All payment details in structured sections
 *   - QR code for receipt verification (goQR.me API)
 *   - Signature & seal area
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/vendor/setasign/fpdf/fpdf.php';

class PaymentAttachmentPDF extends FPDF
{
    private array $txData;
    private bool $isCopy;
    private ?string $qrTempFile = null;

    // Brand colours
    private array $primary   = [30, 64, 120];   // dark navy
    private array $accent    = [0, 122, 204];    // bright blue
    private array $lightBg   = [245, 247, 250];  // section header bg
    private array $borderClr = [200, 210, 220];  // table borders

    public function __construct(array $txData, bool $isCopy = false)
    {
        // A5 portrait: 148.5mm × 210mm
        parent::__construct('P', 'mm', [148.5, 210]);
        $this->txData = $txData;
        $this->isCopy = $isCopy;
        $this->SetAutoPageBreak(false);
        $this->SetMargins(8, 8, 8);
    }

    public function __destruct()
    {
        // Clean up temp QR file
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
        $this->Output('I', 'Payment_Attachment_' . ($this->txData['receipt_no'] ?? 'N-A') . '.pdf');
    }

    // ── QR Code ──────────────────────────────────────────

    /**
     * Download QR code image from goQR.me API to a temp file.
     * Encodes receipt verification data.
     */
    private function prepareQrCode(): void
    {
        $tx = $this->txData;
        $qrData = implode("\n", [
            'PAYMENT RECEIPT',
            'School: ' . (defined('SCHOOL_NAME') ? SCHOOL_NAME : 'Urji Beri School'),
            'Receipt: ' . ($tx['receipt_no'] ?? '—'),
            'Student: ' . ($tx['student_name'] ?? '—'),
            'Code: '    . ($tx['admission_no'] ?? '—'),
            'Amount: '  . number_format(abs((float)($tx['amount'] ?? 0)), 2) . ' ' . ($tx['currency'] ?? 'ETB'),
            'Method: '  . ucfirst(str_replace('_', ' ', $tx['channel'] ?? '—')),
            'Date: '    . (!empty($tx['created_at']) ? date('d/m/Y H:i', strtotime($tx['created_at'])) : '—'),
            'TxID: '    . ($tx['id'] ?? '—'),
        ]);

        $url = 'https://api.qrserver.com/v1/create-qr-code/?'
             . http_build_query(['size' => '200x200', 'data' => $qrData, 'format' => 'png', 'margin' => '4']);

        $tmpFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';

        // Attempt download with a short timeout
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $img = @file_get_contents($url, false, $ctx);

        if ($img !== false && strlen($img) > 100) {
            file_put_contents($tmpFile, $img);
            $this->qrTempFile = $tmpFile;
        }
    }

    // ── Decorative Elements ──────────────────────────────

    /**
     * Draw a double-line decorative border around the page.
     */
    private function drawPageBorder(): void
    {
        $pw = $this->GetPageWidth();
        $ph = $this->GetPageHeight();

        // Outer border
        $this->SetDrawColor(...$this->primary);
        $this->SetLineWidth(0.6);
        $this->Rect(4, 4, $pw - 8, $ph - 8);

        // Inner border
        $this->SetLineWidth(0.2);
        $this->Rect(5.5, 5.5, $pw - 11, $ph - 11);

        // Reset
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.2);
    }

    /**
     * Draw diagonal "Attachment" watermark.
     */
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

    // ── Section Heading Helper ───────────────────────────

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

    // ── Row Drawing Helper ───────────────────────────────

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
        $tx = $this->txData;
        $pw = $this->GetPageWidth();
        $usable = $pw - 18; // 9mm inner margins
        $lm = 9;
        $rh = 5;
        $labelW = 40;
        $valueW = $usable - $labelW;

        $y = 8;

        // ── "COPY" banner ──
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
        // Top accent line
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

        // Bottom accent line
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
        $this->Cell($usable, 7, 'PAYMENT ATTACHMENT', 0, 0, 'C', true);
        $this->SetTextColor(0, 0, 0);
        $y += 9;

        // ── Receipt & Date Row ──
        $this->SetFont('Helvetica', 'B', 7);
        $this->SetTextColor(...$this->accent);
        $this->SetXY($lm, $y);
        $this->Cell($usable * 0.5, $rh, 'Receipt: ' . ($tx['receipt_no'] ?? '—'), 0);
        $this->SetTextColor(80, 80, 80);
        $this->SetFont('Helvetica', '', 7);
        $this->Cell($usable * 0.5, $rh, 'Date: ' . date('d/m/Y  H:i', strtotime($tx['created_at'])), 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
        $y += $rh + 1;

        // ── STUDENT INFORMATION ──
        $y = $this->sectionHeading($lm, $y, $usable, 'STUDENT INFORMATION');

        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Student Name', $tx['student_name'] ?? '—');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Student Code', $tx['admission_no'] ?? '—');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Class', $tx['class_name'] ?? '—');
        $y += 2;

        // ── PAYMENT DETAILS ──
        $y = $this->sectionHeading($lm, $y, $usable, 'PAYMENT DETAILS');

        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Fee Description', $tx['fee_description'] ?? $tx['description'] ?? '—');

        $amountStr = number_format(abs((float)($tx['amount'] ?? 0)), 2) . ' ' . ($tx['currency'] ?? 'ETB');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Payment Amount', $amountStr, true);

        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Payment Method', ucfirst(str_replace('_', ' ', $tx['channel'] ?? '—')));
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Balance Before', number_format((float)($tx['balance_before'] ?? 0), 2) . ' ' . ($tx['currency'] ?? 'ETB'));
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Balance After', number_format((float)($tx['balance_after'] ?? 0), 2) . ' ' . ($tx['currency'] ?? 'ETB'));

        // Channel-specific rows
        if (!empty($tx['channel_transaction_id'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Transaction ID', $tx['channel_transaction_id']);
        }
        if (!empty($tx['payer_phone'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Payer Phone', $tx['payer_phone']);
        }
        if (!empty($tx['channel_payment_type'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Payment Type', ucfirst($tx['channel_payment_type']));
        }
        if (!empty($tx['channel_depositor_name'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Depositor Name', $tx['channel_depositor_name']);
        }
        if (!empty($tx['channel_depositor_branch'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Bank / Branch', $tx['channel_depositor_branch']);
        }
        if (!empty($tx['reference'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Reference', $tx['reference']);
        }
        if (!empty($tx['notes'])) {
            $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Notes', $tx['notes']);
        }
        $y += 2;

        // ── PROCESSED BY ──
        $y = $this->sectionHeading($lm, $y, $usable, 'PROCESSED BY');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Cashier', $tx['processed_by_name'] ?? '—');
        $y = $this->infoRow($lm, $y, $labelW, $valueW, $rh, 'Date & Time', !empty($tx['created_at']) ? date('d/m/Y H:i:s', strtotime($tx['created_at'])) : '—');
        $y += 3;

        // ── QR Code & Signature Area ──
        $qrSize = 24;
        $sigAreaTop = $y;

        // QR code on the left
        if ($this->qrTempFile && file_exists($this->qrTempFile)) {
            $this->Image($this->qrTempFile, $lm, $sigAreaTop, $qrSize, $qrSize, 'PNG');
            $this->SetFont('Helvetica', '', 5);
            $this->SetTextColor(100, 100, 100);
            $this->SetXY($lm, $sigAreaTop + $qrSize + 0.5);
            $this->Cell($qrSize, 3, 'Scan to verify', 0, 0, 'C');
            $this->SetTextColor(0, 0, 0);
        }

        // Signatures on the right of QR
        $sigLeft = $lm + $qrSize + 6;
        $sigW    = ($lm + $usable - $sigLeft - 4) / 2;

        $this->SetFont('Helvetica', '', 6.5);
        $this->SetTextColor(80, 80, 80);

        // Cashier signature
        $sigY = $sigAreaTop + $qrSize - 2;
        $this->SetDrawColor(...$this->borderClr);
        $this->Line($sigLeft, $sigY, $sigLeft + $sigW, $sigY);
        $this->SetXY($sigLeft, $sigY + 1);
        $this->Cell($sigW, 3.5, 'Cashier Signature', 0, 0, 'C');

        // Stamp / Seal
        $sealLeft = $sigLeft + $sigW + 4;
        $this->Line($sealLeft, $sigY, $sealLeft + $sigW, $sigY);
        $this->SetXY($sealLeft, $sigY + 1);
        $this->Cell($sigW, 3.5, 'Stamp / Seal', 0, 0, 'C');

        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);

        // ── Footer ──
        $footerY = 195;

        // Thin line
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
