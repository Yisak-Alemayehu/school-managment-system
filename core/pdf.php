<?php
/**
 * PDF Generation Service — Base Class
 * Urji Beri School Management System — HR Module
 *
 * Extends FPDF to provide shared header, footer, taxpayer-info,
 * and table-drawing utilities used by all government form generators.
 *
 * School Information:
 *   Name:        Urji Beri School
 *   TIN:         0008845599
 *   Region:      Oromia
 *   City:        Sheger
 *   Subcity:     Furi
 *   Woreda:      Muda Furi
 *   House No:    New
 *   Telephone:   0912097003
 *   Tax Center:  Muda Furi
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

require_once APP_ROOT . '/vendor/setasign/fpdf/fpdf.php';

// School constants used across all PDF generators
define('SCHOOL_NAME',       'Urji Beri School');
define('SCHOOL_TIN',        '0008845599');
define('SCHOOL_REGION',     'Oromia');
define('SCHOOL_CITY',       'Sheger');
define('SCHOOL_SUBCITY',    'Furi');
define('SCHOOL_WOREDA',     'Muda Furi');
define('SCHOOL_HOUSE_NO',   'New');
define('SCHOOL_TELEPHONE',  '0912097003');
define('SCHOOL_TAX_CENTER', 'Muda Furi');

/**
 * Base PDF class with shared utilities for government forms.
 */
class SchoolPDF extends FPDF
{
    protected string $formTitle = '';
    protected string $formSubtitle = '';
    protected int $currentPage = 0;
    protected int $totalPages = 0;

    public function __construct(string $orientation = 'L', string $unit = 'mm', string $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->SetAutoPageBreak(false);
        $this->SetMargins(10, 10, 10);
    }

    // ── Header / Footer ───────────────────────────────────

    /**
     * Draw the official Ethiopian government form header.
     * Two logos (placeholders) flanking centred title text.
     */
    public function drawFormHeader(string $titleLine1, string $titleLine2, string $titleLine3 = ''): void
    {
        $this->formTitle    = $titleLine1;
        $this->formSubtitle = $titleLine2;

        $y = $this->GetY();

        // Left logo placeholder (15×15 box)
        $this->Rect(12, $y, 15, 15);
        $this->SetFont('Helvetica', '', 5);
        $this->SetXY(12, $y + 6);
        $this->Cell(15, 3, 'LOGO', 0, 0, 'C');

        // Right logo placeholder
        $pw = $this->GetPageWidth();
        $this->Rect($pw - 27, $y, 15, 15);
        $this->SetXY($pw - 27, $y + 6);
        $this->Cell(15, 3, 'LOGO', 0, 0, 'C');

        // Centred header text
        $cx = 30;
        $cw = $pw - 60;
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetXY($cx, $y);
        $this->Cell($cw, 4, 'Federal Democratic Republic of Ethiopia', 0, 2, 'C');
        $this->SetFont('Helvetica', '', 8);
        $this->Cell($cw, 4, 'Ethiopian Revenues & Customs Authority', 0, 2, 'C');
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell($cw, 5, $titleLine1, 0, 2, 'C');
        if ($titleLine2) {
            $this->SetFont('Helvetica', '', 8);
            $this->Cell($cw, 4, $titleLine2, 0, 2, 'C');
        }
        if ($titleLine3) {
            $this->SetFont('Helvetica', '', 7);
            $this->Cell($cw, 3, $titleLine3, 0, 2, 'C');
        }

        $this->SetY($y + 18);
    }

    /**
     * Draw page number footer.
     */
    public function drawPageFooter(int $pageNum, int $totalPages): void
    {
        $pw = $this->GetPageWidth();
        $ph = $this->GetPageHeight();
        $this->SetFont('Helvetica', '', 7);
        $this->SetXY(0, $ph - 8);
        $this->Cell($pw, 5, "Page {$pageNum} of {$totalPages}", 0, 0, 'C');
    }

    // ── Taxpayer Info Sections ────────────────────────────

    /**
     * Draw the taxpayer information block for Income Tax form.
     *
     * Returns the Y position after the info block.
     */
    public function drawTaxpayerInfoTax(string $monthEc, string $yearEc): float
    {
        $y  = $this->GetY() + 1;
        $lm = 10; // left margin
        $pw = $this->GetPageWidth() - 20; // usable width

        // Row heights
        $rh = 5;

        // ── Row 1: Taxpayer Name | TIN | Tax Account | Tax Period ──
        $c1w = $pw * 0.35;  // col 1
        $c2w = $pw * 0.15;  // col 2
        $c3w = $pw * 0.15;  // col 3
        $c4w = $pw * 0.35;  // col 4
        $rowH = 14;

        // Taxpayer Name box
        $this->SetFont('Helvetica', '', 6);
        $this->Rect($lm, $y, $c1w, $rowH);
        $this->SetXY($lm + 1, $y + 0.5);
        $this->Cell($c1w - 2, 3, "1. Taxpayer's Name", 0);
        $this->SetFont('Helvetica', '', 5);
        $this->SetXY($lm + 1, $y + 3.5);
        $this->Cell($c1w - 2, 3, "(Company Name or Your Name, Father's Name, Grandfather's Name*)", 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($lm + 1, $y + 8);
        $this->Cell($c1w - 2, 4, SCHOOL_NAME, 0);

        // TIN box
        $x2 = $lm + $c1w;
        $this->Rect($x2, $y, $c2w, $rowH);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($x2 + 1, $y + 0.5);
        $this->Cell($c2w - 2, 3, '3. Taxpayer', 0);
        $this->SetXY($x2 + 1, $y + 3.5);
        $this->Cell($c2w - 2, 3, 'Identification Number', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($x2 + 1, $y + 8);
        $this->Cell($c2w - 2, 4, SCHOOL_TIN, 0);

        // Tax Account Number box
        $x3 = $x2 + $c2w;
        $this->Rect($x3, $y, $c3w, $rowH);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($x3 + 1, $y + 0.5);
        $this->Cell($c3w - 2, 3, '4. Tax Account', 0);
        $this->SetXY($x3 + 1, $y + 3.5);
        $this->Cell($c3w - 2, 3, 'Number', 0);

        // Tax Period box (split into Month / Year)
        $x4 = $x3 + $c3w;
        $this->Rect($x4, $y, $c4w, $rowH);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($x4 + 1, $y + 0.5);
        $this->Cell($c4w - 2, 3, '8. Tax Period:', 0);
        // Divider line inside
        $halfW = $c4w / 2;
        $splitY = $y + 6;
        $this->Line($x4, $splitY, $x4 + $c4w, $splitY);
        $this->Line($x4 + $halfW, $splitY, $x4 + $halfW, $y + $rowH);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($x4 + 1, $splitY + 0.5);
        $this->Cell($halfW - 2, 3, 'Month', 0);
        $this->SetXY($x4 + $halfW + 1, $splitY + 0.5);
        $this->Cell($halfW - 2, 3, 'Year', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($x4 + 1, $splitY + 4);
        $this->Cell($halfW - 2, 4, $monthEc, 0);
        $this->SetXY($x4 + $halfW + 1, $splitY + 4);
        $this->Cell($halfW - 2, 4, $yearEc, 0);

        $y += $rowH;

        // ── Row 2: Region | Zone/K-Ketema | Tax Center ──
        $r2c1 = $pw * 0.15;
        $r2c2 = $pw * 0.35;
        $r2c3 = $pw * 0.50;
        $r2h  = 10;

        // Region
        $this->Rect($lm, $y, $r2c1, $r2h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($lm + 1, $y + 0.5);
        $this->Cell($r2c1 - 2, 3, '2a. Region', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($lm + 1, $y + 5);
        $this->Cell($r2c1 - 2, 4, SCHOOL_REGION, 0);

        // Zone
        $xz = $lm + $r2c1;
        $this->Rect($xz, $y, $r2c2, $r2h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xz + 1, $y + 0.5);
        $this->Cell($r2c2 - 2, 3, '2b. Zone/K-Ketema', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($xz + 1, $y + 5);
        $this->Cell($r2c2 - 2, 4, SCHOOL_CITY . ' - ' . SCHOOL_SUBCITY, 0);

        // Tax Center
        $xtc = $xz + $r2c2;
        $this->Rect($xtc, $y, $r2c3, $r2h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xtc + 1, $y + 0.5);
        $this->Cell($r2c3 - 2, 3, '5. Tax Center', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($xtc + 1, $y + 5);
        $this->Cell($r2c3 - 2, 4, SCHOOL_TAX_CENTER, 0);

        $y += $r2h;

        // ── Row 3: Woreda | Kebele | House No | Telephone | Fax ──
        $r3c1 = $pw * 0.15;
        $r3c2 = $pw * 0.20;
        $r3c3 = $pw * 0.15;
        $r3c4 = $pw * 0.25;
        $r3c5 = $pw * 0.25;
        $r3h  = 10;

        // Woreda
        $this->Rect($lm, $y, $r3c1, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($lm + 1, $y + 0.5);
        $this->Cell($r3c1 - 2, 3, '2c. Woreda', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($lm + 1, $y + 5);
        $this->Cell($r3c1 - 2, 4, SCHOOL_WOREDA, 0);

        // Kebele
        $xk = $lm + $r3c1;
        $this->Rect($xk, $y, $r3c2, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xk + 1, $y + 0.5);
        $this->Cell($r3c2 - 2, 3, '2d. Kebele/Farmers Assoc.', 0);

        // House No
        $xh = $xk + $r3c2;
        $this->Rect($xh, $y, $r3c3, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xh + 1, $y + 0.5);
        $this->Cell($r3c3 - 2, 3, '2e. House Number', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($xh + 1, $y + 5);
        $this->Cell($r3c3 - 2, 4, SCHOOL_HOUSE_NO, 0);

        // Telephone
        $xt = $xh + $r3c3;
        $this->Rect($xt, $y, $r3c4, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xt + 1, $y + 0.5);
        $this->Cell($r3c4 - 2, 3, '6. Telephone Number', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($xt + 1, $y + 5);
        $this->Cell($r3c4 - 2, 4, SCHOOL_TELEPHONE, 0);

        // Fax
        $xf = $xt + $r3c4;
        $this->Rect($xf, $y, $r3c5, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xf + 1, $y + 0.5);
        $this->Cell($r3c5 - 2, 3, '7. Fax Number', 0);

        $y += $r3h;
        $this->SetY($y);

        return $y;
    }

    /**
     * Draw the taxpayer information block for Pension form.
     * Slightly different layout from the tax form.
     */
    public function drawTaxpayerInfoPension(string $monthEc, string $yearEc): float
    {
        $y  = $this->GetY() + 1;
        $lm = 10;
        $pw = $this->GetPageWidth() - 20;

        // ── Row 1: Taxpayer Name | TIN | Tax Period ──
        $c1w = $pw * 0.40;
        $c2w = $pw * 0.20;
        $c3w = $pw * 0.40;
        $rowH = 14;

        // Taxpayer Name
        $this->Rect($lm, $y, $c1w, $rowH);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($lm + 1, $y + 0.5);
        $this->Cell($c1w - 2, 3, '1. Taxpayer Name', 0);
        $this->SetFont('Helvetica', '', 5);
        $this->SetXY($lm + 1, $y + 3.5);
        $this->Cell($c1w - 2, 3, "(Company Name Your Name, Father's Name, Grandfather's Name)", 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($lm + 1, $y + 8);
        $this->Cell($c1w - 2, 4, SCHOOL_NAME, 0);

        // TIN
        $x2 = $lm + $c1w;
        $this->Rect($x2, $y, $c2w, $rowH);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($x2 + 1, $y + 0.5);
        $this->Cell($c2w - 2, 3, '3. Taxpayer', 0);
        $this->SetXY($x2 + 1, $y + 3.5);
        $this->Cell($c2w - 2, 3, 'Identification Number', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($x2 + 1, $y + 8);
        $this->Cell($c2w - 2, 4, SCHOOL_TIN, 0);

        // Tax Period
        $x3 = $x2 + $c2w;
        $this->Rect($x3, $y, $c3w, $rowH);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($x3 + 1, $y + 0.5);
        $this->Cell($c3w - 2, 3, '8. Tax Period:', 0);
        $halfW = $c3w / 2;
        $splitY = $y + 6;
        $this->Line($x3, $splitY, $x3 + $c3w, $splitY);
        $this->Line($x3 + $halfW, $splitY, $x3 + $halfW, $y + $rowH);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($x3 + 1, $splitY + 0.5);
        $this->Cell($halfW - 2, 3, 'Month', 0);
        $this->SetXY($x3 + $halfW + 1, $splitY + 0.5);
        $this->Cell($halfW - 2, 3, 'Year', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($x3 + 1, $splitY + 4);
        $this->Cell($halfW - 2, 4, $monthEc, 0);
        $this->SetXY($x3 + $halfW + 1, $splitY + 4);
        $this->Cell($halfW - 2, 4, $yearEc, 0);

        $y += $rowH;

        // ── Row 2: Region | Zone/K-Ketema | Tax Account Number ──
        $r2c1 = $pw * 0.15;
        $r2c2 = $pw * 0.35;
        $r2c3 = $pw * 0.50;
        $r2h  = 10;

        $this->Rect($lm, $y, $r2c1, $r2h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($lm + 1, $y + 0.5);
        $this->Cell($r2c1 - 2, 3, '2a. Region', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($lm + 1, $y + 5);
        $this->Cell($r2c1 - 2, 4, SCHOOL_REGION, 0);

        $xz = $lm + $r2c1;
        $this->Rect($xz, $y, $r2c2, $r2h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xz + 1, $y + 0.5);
        $this->Cell($r2c2 - 2, 3, '2b. Zone/K-Ketema', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($xz + 1, $y + 5);
        $this->Cell($r2c2 - 2, 4, SCHOOL_CITY . ' - ' . SCHOOL_SUBCITY, 0);

        $xtc = $xz + $r2c2;
        $this->Rect($xtc, $y, $r2c3, $r2h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xtc + 1, $y + 0.5);
        $this->Cell($r2c3 - 2, 3, '4. Tax Account Number', 0);

        $y += $r2h;

        // ── Row 3: Woreda | Kebele | House No | Tax Center | Document Number ──
        $r3c1 = $pw * 0.15;
        $r3c2 = $pw * 0.20;
        $r3c3 = $pw * 0.15;
        $r3c4 = $pw * 0.25;
        $r3c5 = $pw * 0.25;
        $r3h  = 10;

        $this->Rect($lm, $y, $r3c1, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($lm + 1, $y + 0.5);
        $this->Cell($r3c1 - 2, 3, '2c. Woreda', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($lm + 1, $y + 5);
        $this->Cell($r3c1 - 2, 4, SCHOOL_WOREDA, 0);

        $xk = $lm + $r3c1;
        $this->Rect($xk, $y, $r3c2, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xk + 1, $y + 0.5);
        $this->Cell($r3c2 - 2, 3, '2d. Kebele/Farmers Assoc.', 0);

        $xh = $xk + $r3c2;
        $this->Rect($xh, $y, $r3c3, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xh + 1, $y + 0.5);
        $this->Cell($r3c3 - 2, 3, '2e. House Number', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($xh + 1, $y + 5);
        $this->Cell($r3c3 - 2, 4, SCHOOL_HOUSE_NO, 0);

        $xt = $xh + $r3c3;
        $this->Rect($xt, $y, $r3c4, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xt + 1, $y + 0.5);
        $this->Cell($r3c4 - 2, 3, '5. Tax Center', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($xt + 1, $y + 5);
        $this->Cell($r3c4 - 2, 4, SCHOOL_TAX_CENTER, 0);

        $xd = $xt + $r3c4;
        $this->Rect($xd, $y, $r3c5, $r3h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xd + 1, $y + 0.5);
        $this->Cell($r3c5 - 2, 3, 'Document Number', 0);
        $this->SetFont('Helvetica', '', 5);
        $this->SetXY($xd + 1, $y + 3.5);
        $this->Cell($r3c5 - 2, 3, '(Official Use)', 0);

        $y += $r3h;

        // ── Row 4: (empty) | Telephone | Fax ──
        $r4c1 = $pw * 0.50;
        $r4c2 = $pw * 0.25;
        $r4c3 = $pw * 0.25;
        $r4h  = 10;

        $this->Rect($lm, $y, $r4c1, $r4h);
        // empty left cells

        $xt2 = $lm + $r4c1;
        $this->Rect($xt2, $y, $r4c2, $r4h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xt2 + 1, $y + 0.5);
        $this->Cell($r4c2 - 2, 3, '6. Telephone Number', 0);
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY($xt2 + 1, $y + 5);
        $this->Cell($r4c2 - 2, 4, SCHOOL_TELEPHONE, 0);

        $xf2 = $xt2 + $r4c2;
        $this->Rect($xf2, $y, $r4c3, $r4h);
        $this->SetFont('Helvetica', '', 6);
        $this->SetXY($xf2 + 1, $y + 0.5);
        $this->Cell($r4c3 - 2, 3, '7. Fax Number', 0);

        $y += $r4h;
        $this->SetY($y);

        return $y;
    }

    // ── Table Drawing Helpers ─────────────────────────────

    /**
     * Draw a bordered cell with text.
     */
    public function borderedCell(float $x, float $y, float $w, float $h, string $text,
                                  string $align = 'L', bool $bold = false, float $fontSize = 7): void
    {
        $this->Rect($x, $y, $w, $h);
        $this->SetFont('Helvetica', $bold ? 'B' : '', $fontSize);
        $this->SetXY($x + 0.5, $y + 0.5);
        $this->MultiCell($w - 1, 3, $text, 0, $align);
    }

    /**
     * Draw a simple row of cells from an array.
     * Returns the Y position after the row.
     */
    public function drawRow(float $y, array $widths, array $texts, float $h = 5,
                             string $align = 'C', bool $bold = false, float $fontSize = 7): float
    {
        $x = 10;
        foreach ($widths as $i => $w) {
            $this->Rect($x, $y, $w, $h);
            $this->SetFont('Helvetica', $bold ? 'B' : '', $fontSize);
            $this->SetXY($x + 0.5, $y + ($h - 3) / 2);
            $this->Cell($w - 1, 3, $texts[$i] ?? '', 0, 0, $align);
            $x += $w;
        }
        return $y + $h;
    }

    /**
     * Draw a multi-line header row where each cell may have line breaks.
     */
    public function drawHeaderRow(float $y, array $widths, array $texts, float $h,
                                   float $fontSize = 5.5): float
    {
        $x = 10;
        foreach ($widths as $i => $w) {
            $this->Rect($x, $y, $w, $h);
            $this->SetFont('Helvetica', 'B', $fontSize);
            $lines = explode("\n", $texts[$i] ?? '');
            $lineH  = min(3, $h / max(count($lines), 1));
            $totalH = $lineH * count($lines);
            $startY = $y + ($h - $totalH) / 2;
            foreach ($lines as $li => $line) {
                $this->SetXY($x + 0.5, $startY + $li * $lineH);
                $this->Cell($w - 1, $lineH, trim($line), 0, 0, 'C');
            }
            $x += $w;
        }
        return $y + $h;
    }

    /**
     * Draw the employees-removed section (empty table).
     */
    public function drawEmployeesRemovedSection(float $y): float
    {
        $pw = $this->GetPageWidth() - 20;
        $this->SetFont('Helvetica', 'B', 7);
        $this->SetXY(10, $y + 2);
        $this->Cell($pw, 4, '9. Any Employees Removed Since Last Declaration:', 0);
        $y += 6;

        $widths = [$pw * 0.08, $pw * 0.15, $pw * 0.35, $pw * 0.22, $pw * 0.20];
        $headers = ['Seq.', 'TIN', 'Name', 'Date Removed', 'Reason'];
        $y = $this->drawRow($y, $widths, $headers, 5, 'C', true, 6);

        // 3 empty rows
        for ($i = 0; $i < 3; $i++) {
            $y = $this->drawRow($y, $widths, ['', '', '', '', ''], 5);
        }

        return $y;
    }

    /**
     * Draw certification + signature section.
     */
    public function drawCertificationSection(float $y): float
    {
        $pw  = $this->GetPageWidth() - 20;
        $this->SetFont('Helvetica', '', 6.5);
        $y += 3;
        $this->SetXY(10, $y);
        $this->MultiCell($pw, 3,
            "I declare that the above declaration and all information provided here-with "
            . "(including continuation sheets) is correct and complete. I understand that any "
            . "misrepresentation is punishable as per the tax Laws and the Penal code.", 0, 'L');
        $y = $this->GetY() + 3;

        // Two-column signatures
        $halfW = $pw / 2;
        $this->SetFont('Helvetica', 'B', 7);
        $this->SetXY(10, $y);
        $this->Cell($halfW, 4, 'Taxpayer/Authorized Agent:', 0);
        $this->Cell($halfW, 4, 'Authorized Tax Officer:', 0);
        $y += 5;

        $this->SetFont('Helvetica', '', 7);
        $labels = ['Name', 'Signature', 'Date'];
        foreach ($labels as $label) {
            $this->SetXY(10, $y);
            $this->Cell($halfW, 4, $label . ': ______________________________', 0);
            $this->Cell($halfW, 4, $label . ': ______________________________', 0);
            $y += 5;
        }

        // Seal in centre
        $this->SetXY(10 + $halfW - 15, $y - 12);
        $this->SetFont('Helvetica', '', 7);
        $this->Cell(30, 4, 'Seal', 0, 0, 'C');

        return $y;
    }

    // ── Utility ──────────────────────────────────────────

    /**
     * Format number with 2 decimal places.
     */
    public function fmt(float $n): string
    {
        return number_format($n, 2, '.', ',');
    }

    /**
     * Calculate total pages needed for a given employee count.
     */
    public function calcTotalPages(int $employeeCount, int $perPage = 15): int
    {
        return max(1, (int) ceil($employeeCount / $perPage));
    }

    /**
     * Output PDF to browser for download.
     */
    public function download(string $filename): void
    {
        $this->Output('D', $filename);
    }

    /**
     * Output PDF inline (for browser preview).
     */
    public function preview(string $filename): void
    {
        $this->Output('I', $filename);
    }

    /**
     * Save PDF to file.
     */
    public function saveTo(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->Output('F', $path);
    }
}
