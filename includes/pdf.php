<?php
/********************************************************************
* FPDF                                                                *
* Version: 1.86                                                      *
* Author:  Olivier Plathey                                           *
* License: Freeware                                                  *
*********************************************************************/

if (class_exists('FPDF')) {
    return;
}

define('FPDF_VERSION','1.86');

class FPDF
{
    protected $page;               // current page number
    protected $n;                  // current object number
    protected $offsets;            // array of object offsets
    protected $buffer;             // buffer holding in-memory PDF
    protected $pages;              // array containing pages
    protected $state;              // current document state
    protected $compress;           // compression flag
    protected $k;                  // scale factor (number of points in user unit)
    protected $DefOrientation;     // default orientation
    protected $CurOrientation;     // current orientation
    protected $PageFormats;        // available page formats
    protected $DefPageFormat;      // default page format
    protected $CurPageFormat;      // current page format
    protected $PageOrientation;    // orientation of each page
    protected $wPt, $hPt;          // dimensions of current page in points
    protected $w, $h;              // dimensions of current page in user unit
    protected $lMargin;            // left margin
    protected $tMargin;            // top margin
    protected $rMargin;            // right margin
    protected $bMargin;            // page break margin
    protected $cMargin;            // cell margin
    protected $x, $y;              // current position in user unit for cell positioning
    protected $lasth;              // height of last printed cell
    protected $LineWidth;          // line width in user unit
    protected $fontpath;           // path containing fonts
    protected $CoreFonts;          // array of core font names
    protected $fonts;              // array of used fonts
    protected $FontFiles;          // array of font files
    protected $diffs;              // array of encoding differences
    protected $FontFamily;         // current font family
    protected $FontStyle;          // current font style
    protected $underline;          // underlining flag
    protected $CurrentFont;        // current font info
    protected $FontSizePt;         // current font size in points
    protected $FontSize;           // current font size in user unit
    protected $DrawColor;          // commands for drawing color
    protected $FillColor;          // commands for filling color
    protected $TextColor;          // commands for text color
    protected $ColorFlag;          // indicates whether fill and text colors are different
    protected $ws;                 // word spacing
    protected $images;             // array of used images
    protected $PageLinks;          // array of links in pages
    protected $links;              // array of internal links
    protected $AutoPageBreak;      // automatic page breaking
    protected $PageBreakTrigger;   // threshold used to trigger page breaks
    protected $InHeader;           // flag set when processing header
    protected $InFooter;           // flag set when processing footer
    protected $AliasNbPages;       // alias for total number of pages
    protected $ZoomMode;           // zoom display mode
    protected $LayoutMode;         // layout display mode
    protected $metadata;           // document metadata
    protected $PDFVersion;         // PDF version number

    function __construct($orientation='P', $unit='mm', $size='A4')
    {
        $this->page = 0;
        $this->n = 2;
        $this->buffer = '';
        $this->pages = [];
        $this->PageBreakTrigger = 0;
        $this->state = 0;
        $this->fonts = [];
        $this->FontFiles = [];
        $this->diffs = [];
        $this->images = [];
        $this->links = [];
        $this->InHeader = false;
        $this->InFooter = false;
        $this->lasth = 0;
        $this->FontFamily = '';
        $this->FontStyle = '';
        $this->FontSizePt = 12;
        $this->underline = false;
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->ws = 0;
        $this->metadata = [];
        $this->PDFVersion = '1.3';
        $this->fontpath = __DIR__;
        // Initialize scale factor for unit conversion
        $this->k = match(strtolower($unit)) {
            'pt' => 1,
            'mm' => 72 / 25.4,
            'cm' => 72 / 2.54,
            'in' => 72,
            default => 72 / 25.4,
        };
        $this->CoreFonts = ['courier','helvetica','times','symbol','zapfdingbats'];
        $this->PageFormats = [
            'a3' => [841.89, 1190.55],
            'a4' => [595.28, 841.89],
            'a5' => [420.94, 595.28],
            'letter' => [612, 792],
            'legal' => [612, 1008]
        ];
        $this->SetMargins(10, 10);
        $this->SetAutoPageBreak(true, 20);
        $this->setPageFormat($size);
        $this->setOrientation($orientation);
        $this->SetLineWidth(0.2);
        // Initialize page dimensions
        $this->wPt = $this->CurPageFormat[0];
        $this->hPt = $this->CurPageFormat[1];
        $this->w = $this->wPt / $this->k;
        $this->h = $this->hPt / $this->k;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->cMargin = 2;
        $this->LineWidth = 0.2 / $this->k;
        $this->SetFont('Arial', '', 12);
    }

    protected function setOrientation($orientation)
    {
        $orientation = strtolower($orientation);
        if ($orientation === 'p' || $orientation === 'portrait') {
            $this->DefOrientation = 'P';
        } elseif ($orientation === 'l' || $orientation === 'landscape') {
            $this->DefOrientation = 'L';
        } else {
            $this->Error('Unknown orientation: ' . $orientation);
        }
        $this->CurOrientation = $this->DefOrientation;
    }

    protected function setPageFormat($size)
    {
        if (is_string($size)) {
            $size = strtolower($size);
            if (!isset($this->PageFormats[$size])) {
                $this->Error('Unknown page size: ' . $size);
            }
            $size = $this->PageFormats[$size];
        }
        $this->DefPageFormat = $size;
        $this->CurPageFormat = $size;
    }

    function SetMargins($left, $top, $right=null)
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = $right === null ? $left : $right;
    }

    function SetAutoPageBreak($auto, $margin=0)
    {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }

    function SetLineWidth($width)
    {
        $this->LineWidth = $width;
        if ($this->page > 0) {
            $this->_out(sprintf('%.2F w', $width * $this->k));
        }
    }

    function SetFont($family, $style='', $size=0)
    {
        $family = strtolower($family);
        if ($family === 'arial') {
            $family = 'helvetica';
        }
        $style = strtoupper($style);
        $style = str_replace('U', '', $style);
        $this->underline = str_contains($style, 'U');
        $style = str_replace('U', '', $style);
        if ($family === '') {
            $family = $this->FontFamily;
        }
        if ($size === 0) {
            $size = $this->FontSizePt;
        }
        if ($this->FontFamily === $family && $this->FontStyle === $style && $this->FontSizePt === $size) {
            return;
        }
        $fontkey = $family . $style;
        if (!isset($this->fonts[$fontkey])) {
            $this->AddFont($family, $style);
        }
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        $this->CurrentFont = $this->fonts[$fontkey];
        if ($this->page > 0) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    }

    function AddPage($orientation='', $size=null)
    {
        if ($this->state === 0) {
            $this->Open();
        }
        $family = $this->FontFamily;
        $style = $this->FontStyle . ($this->underline ? 'U' : '');
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;
        if ($orientation === '') {
            $orientation = $this->DefOrientation;
        } else {
            $orientation = strtoupper($orientation[0]);
        }
        if ($size === null) {
            $size = $this->DefPageFormat;
        }
        $this->CurOrientation = $orientation;
        $this->CurPageFormat = $size;
        if ($orientation === 'P') {
            $this->wPt = $size[0];
            $this->hPt = $size[1];
        } else {
            $this->wPt = $size[1];
            $this->hPt = $size[0];
        }
        $this->k = $this->k ?? 72 / 25.4;
        $this->w = $this->wPt / $this->k;
        $this->h = $this->hPt / $this->k;
        $this->PageBreakTrigger = $this->h - $this->bMargin;
        $this->page++;
        $this->pages[$this->page] = '';
        $this->PageLinks[$this->page] = [];
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        if ($family) {
            $this->SetFont($family, $style, $fontsize);
        }
        $this->SetLineWidth($lw);
        $this->DrawColor = $dc;
        if ($dc !== '0 G') {
            $this->_out($dc);
        }
        $this->FillColor = $fc;
        if ($fc !== '0 g') {
            $this->_out($fc);
        }
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
        $this->InHeader = false;
        $this->InFooter = false;
    }

    function Open()
    {
        $this->state = 1;
    }

    function Close()
    {
        if ($this->state === 3) {
            return;
        }
        if ($this->page === 0) {
            $this->AddPage();
        }
        $this->InFooter = true;
        $this->_endpage();
        $this->_enddoc();
    }

    function Output($dest='', $name='', $isUTF8=false)
    {
        $this->Close();
        $dest = strtoupper($dest);
        if ($dest === '') {
            $dest = 'I';
        }
        if ($dest === 'I') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $this->_escape($name ?: 'document.pdf') . '"');
            echo $this->buffer;
        } elseif ($dest === 'D') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $this->_escape($name ?: 'document.pdf') . '"');
            echo $this->buffer;
        } else {
            $this->Error('Unsupported destination: ' . $dest);
        }
    }

    // Basic drawing helpers -------------------------------------------------
    function SetXY($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    function Ln($h=null)
    {
        $this->x = $this->lMargin;
        if ($h === null) {
            $this->y += $this->lasth;
        } else {
            $this->y += $h;
        }
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
    {
        $k = $this->k;
        if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AutoPageBreak) {
            $this->AddPage($this->CurOrientation, $this->CurPageFormat);
        }
        $s = '';
        if ($fill || $border === 1) {
            $op = $fill ? 'B' : 'S';
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
        }
        if (is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if (str_contains($border, 'L')) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
            }
            if (str_contains($border, 'T')) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
            }
            if (str_contains($border, 'R')) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
            if (str_contains($border, 'B')) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
        }
        if ($txt !== '') {
            $txt = $this->_escape($txt);
            $dx = 0;
            if ($align === 'R') {
                $dx = $w - $this->cMargin - $this->GetStringWidth($txt);
            } elseif ($align === 'C') {
                $dx = ($w - $this->GetStringWidth($txt)) / 2;
            } else {
                $dx = $this->cMargin;
            }
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET ', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k, $txt);
        }
        if ($s) {
            $this->_out($s);
        }
        $this->lasth = $h;
        if ($ln > 0) {
            $this->x = $this->lMargin;
            $this->y += $h;
        } else {
            $this->x += $w;
        }
    }

    function MultiCell($w, $h, $txt, $border=0, $align='L')
    {
        $cw = $this->CurrentFont['cw'];
        if ($w === 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string) $txt);
        $nb = strlen($s);
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") {
                if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AutoPageBreak) {
                    $this->AddPage($this->CurOrientation, $this->CurPageFormat);
                }
                $this->Cell($w, $h, substr($s, $j, $i - $j), $border, 2, $align);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                continue;
            }
            if ($c === ' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep === -1) {
                    if ($i === $j) {
                        $i++;
                    }
                    if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AutoPageBreak) {
                        $this->AddPage($this->CurOrientation, $this->CurPageFormat);
                    }
                    $this->Cell($w, $h, substr($s, $j, $i - $j), $border, 2, $align);
                } else {
                    if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AutoPageBreak) {
                        $this->AddPage($this->CurOrientation, $this->CurPageFormat);
                    }
                    $this->Cell($w, $h, substr($s, $j, $sep - $j), $border, 2, $align);
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AutoPageBreak) {
            $this->AddPage($this->CurOrientation, $this->CurPageFormat);
        }
        $this->Cell($w, $h, substr($s, $j, $i - $j), $border, 2, $align);
        $this->y -= $h;
    }

    function GetStringWidth($s)
    {
        $cw = $this->CurrentFont['cw'];
        $l = 0;
        $nb = strlen((string) $s);
        for ($i = 0; $i < $nb; $i++) {
            $l += $cw[$s[$i]] ?? 0;
        }
        return $l * $this->FontSize / 1000;
    }

    // Low-level --------------------------------------------------------------
    protected function _escape($s)
    {
        $s = str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], (string) $s);
        return $s;
    }

    protected function _textstring($s)
    {
        return '(' . $this->_escape($s) . ')';
    }

    protected function _out($s)
    {
        if ($this->state === 2) {
            $this->pages[$this->page] .= $s . "\n";
        } else {
            $this->buffer .= $s . "\n";
        }
    }

    protected function _newobj()
    {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_out($this->n . ' 1 obj');
    }

    protected function _putpages()
    {
        $nb = $this->page;
        for ($n = 1; $n <= $nb; $n++) {
            $this->_newobj();
            $this->_out('<</Type /Page');
            $this->_out('/Parent 1 0 R');
            $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->wPt, $this->hPt));
            $this->_out('/Resources 2 0 R');
            $this->_out('/Contents ' . ($this->n + 1) . ' 0 R>>');
            $this->_out('endobj');
            $this->_newobj();
            $this->_out('<< /Length ' . strlen($this->pages[$n]) . ' >>');
            $this->_out('stream');
            $this->_out($this->pages[$n]);
            $this->_out('endstream');
            $this->_out('endobj');
        }
        $this->offsets[1] = strlen($this->buffer);
        $this->_out('1 1 obj');
        $this->_out('<</Type /Pages');
        $kids = '/Kids [';
        for ($i = 0; $i < $nb; $i++) {
            $kids .= (3 + 2 * $i) . ' 0 R ';
        }
        $kids .= ']';
        $this->_out($kids);
        $this->_out('/Count ' . $nb);
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _putfonts()
    {
        $nf = $this->n;
        foreach ($this->fonts as $font) {
            $this->_newobj();
            $this->fonts[$font['i']]['n'] = $this->n;
            $this->_out('<</Type /Font');
            $this->_out('/Subtype /Type1');
            $this->_out('/BaseFont /' . $font['name']);
            $this->_out('/Encoding /WinAnsiEncoding');
            $this->_out('>>');
            $this->_out('endobj');
        }
        $this->n = $nf;
    }

    protected function _putresources()
    {
        $this->_putfonts();
        $this->_newobj();
        $this->_out('2 1 obj');
        $this->_out('<</Font <<');
        foreach ($this->fonts as $font) {
            $this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
        }
        $this->_out('>> >>');
        $this->_out('endobj');
    }

    protected function _putinfo()
    {
        $this->_out('3 1 obj');
        $this->_out('<<');
        $this->_out('/Producer ' . $this->_textstring('FPDF ' . FPDF_VERSION));
        if (!empty($this->metadata['title'])) {
            $this->_out('/Title ' . $this->_textstring($this->metadata['title']));
        }
        if (!empty($this->metadata['author'])) {
            $this->_out('/Author ' . $this->_textstring($this->metadata['author']));
        }
        if (!empty($this->metadata['subject'])) {
            $this->_out('/Subject ' . $this->_textstring($this->metadata['subject']));
        }
        if (!empty($this->metadata['keywords'])) {
            $this->_out('/Keywords ' . $this->_textstring($this->metadata['keywords']));
        }
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _putcatalog()
    {
        $this->_out('4 1 obj');
        $this->_out('<</Type /Catalog');
        $this->_out('/Pages 1 0 R');
        if ($this->ZoomMode) {
            if ($this->ZoomMode === 'fullpage') {
                $this->_out('/OpenAction [3 0 R /Fit]');
            } elseif ($this->ZoomMode === 'fullwidth') {
                $this->_out('/OpenAction [3 0 R /FitH null]');
            } elseif ($this->ZoomMode === 'real') {
                $this->_out('/OpenAction [3 0 R /XYZ null null 1]');
            } elseif (!is_string($this->ZoomMode)) {
                $this->_out(sprintf('/OpenAction [3 0 R /XYZ null null %.2F]', $this->ZoomMode / 100));
            }
        }
        if ($this->LayoutMode) {
            $this->_out('/PageLayout /' . $this->LayoutMode);
        }
        $this->_out('>>');
        $this->_out('endobj');
    }

    protected function _endpage()
    {
        $this->state = 1;
    }

    protected function _enddoc()
    {
        $this->_putpages();
        $this->_putresources();
        $this->_newobj();
        $this->_putinfo();
        $this->_newobj();
        $this->_putcatalog();
        $o = strlen($this->buffer);
        $this->_out('xref');
        $this->_out('0 ' . ($this->n + 1));
        $this->_out('0000000000 65535 f ');
        for ($i = 1; $i <= $this->n; $i++) {
            $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
        }
        $this->_out('trailer');
        $this->_out('<< /Size ' . ($this->n + 1) . ' /Root 4 0 R /Info 3 0 R >>');
        $this->_out('startxref');
        $this->_out($o);
        $this->_out('%%EOF');
        $this->state = 3;
    }

    protected function Error($msg)
    {
        throw new Exception('FPDF error: ' . $msg);
    }

    function AddFont($family, $style='', $file='')
    {
        $family = strtolower($family);
        if ($family === 'arial') {
            $family = 'helvetica';
        }
        $style = strtoupper($style);
        $fontkey = $family . $style;
        if (isset($this->fonts[$fontkey])) {
            return;
        }
        if (!in_array($family, $this->CoreFonts, true)) {
            $this->Error('Only core fonts are supported in this minimal build.');
        }
        $i = count($this->fonts) + 1;
        $name = $family === 'symbol' || $family === 'zapfdingbats' ? $family : ucfirst($family) . $style;
        $this->fonts[$fontkey] = ['i' => $i, 'type' => 'core', 'name' => $name, 'cw' => $this->getCoreMetrics($family)];
    }

    protected function getCoreMetrics($family)
    {
        $family = strtolower($family);
        $fontPath = __DIR__ . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . $family . '.php';
        
        if (!file_exists($fontPath)) {
            $this->Error('Metric file not found: ' . $fontPath);
        }
        
        // Include the font file and extract $cw variable
        $cw = [];
        include $fontPath;
        
        if (empty($cw)) {
            $this->Error('Font metrics not found in: ' . $fontPath);
        }
        
        return $cw;
    }

    function SetTitle($title, $isUTF8=false) { $this->metadata['title']=$title; }
    function SetAuthor($author, $isUTF8=false) { $this->metadata['author']=$author; }
    function SetSubject($subject, $isUTF8=false) { $this->metadata['subject']=$subject; }
    function SetKeywords($keywords, $isUTF8=false) { $this->metadata['keywords']=$keywords; }
}

// Font metrics files are loaded from includes/fonts/ directory
// Font metric files: helvetica.php, times.php, courier.php
