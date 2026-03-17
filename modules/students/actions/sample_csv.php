<?php
/**
 * Sample Excel download for student bulk import
 * Generates a minimal .xlsx file with the required columns.
 */

function xlsx_column_index(string $col): int {
    $col = strtoupper($col);
    $index = 0;
    for ($i = 0; $i < strlen($col); $i++) {
        $index = $index * 26 + (ord($col[$i]) - 64);
    }
    return $index;
}

function build_xlsx_sheet(array $rows): string {
    // Build shared strings
    $shared = [];
    foreach ($rows as $row) {
        foreach ($row as $cell) {
            $shared[$cell] = $cell;
        }
    }
    $shared = array_values($shared);
    $sharedIndex = array_flip($shared);

    // Build sheet data
    $sheet = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $sheet .= '<sheetData>';

    foreach ($rows as $rIndex => $row) {
        $sheet .= '<row r="' . ($rIndex + 1) . '">';
        foreach ($row as $cIndex => $cell) {
            $col = ''; $n = $cIndex + 1;
            while ($n > 0) {
                $mod = ($n - 1) % 26;
                $col = chr(65 + $mod) . $col;
                $n = intdiv($n - 1, 26);
            }
            $ref = $col . ($rIndex + 1);
            $sidx = $sharedIndex[$cell];
            $sheet .= '<c r="' . $ref . '" t="s"><v>' . $sidx . '</v></c>';
        }
        $sheet .= '</row>';
    }

    $sheet .= '</sheetData></worksheet>';
    return $sheet;
}

function build_shared_strings(array $shared): string {
    $xml = '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared) . '" uniqueCount="' . count($shared) . '">';
    foreach ($shared as $str) {
        $xml .= '<si><t>' . htmlspecialchars($str, ENT_XML1|ENT_COMPAT, 'UTF-8') . '</t></si>';
    }
    $xml .= '</sst>';
    return $xml;
}

$rows = [
    ['first_name', 'last_name', 'gender', 'date_of_birth', 'class_name', 'section_name', 'admission_no', 'phone', 'email', 'religion', 'blood_group', 'guardian_name', 'guardian_phone', 'address'],
    ['Abebe',  'Kebede',  'male',   '3/15/2010',  'Grade 5', 'A', '',        '911223344', '',             'Orthodox',   'O+', 'Kebede Ali',   '922334455', 'Addis Ababa'],
    ['Tigist', 'Haile',   'female', '7/22/2011',  'Grade 4', 'B', '',        '933445566', 'tigi@mail.com','Protestant', 'A+', 'Haile Tekle',  '944556677', 'Dire Dawa'],
    ['Dawit',  'Tadesse', 'male',   '11/5/2009',  'Grade 6', 'A', 'STU-101', '',          '',             '',           'B+', 'Tadesse Mamo', '',          'Bahir Dar'],
    ['Meron',  'Solomon', 'female', '1/18/2012',  'Grade 3', 'C', '',        '955667788', '',             'Catholic',   '',   'Solomon Tafa', '966778899', 'Hawassa'],
    ['Yonas',  'Girma',   'male',   '6/30/2010',  'Grade 5', 'B', '',        '977889900', '',             'Orthodox',   'AB+','Girma Debebe', '988990011', 'Adama'],
];

$sharedStrings = array_values(array_unique(array_merge(...$rows)));

$workbook = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Students" sheetId="1" r:id="rId1"/></sheets></workbook>';

$rels = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
    . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>';

$contentTypes = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
    . '</Types>';

$styles = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>'
    . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="1"><xf xfId="0"/></cellXfs>'
    . '</styleSheet>';

$sheetData = build_xlsx_sheet($rows);
$sharedXml = build_shared_strings($sharedStrings);

$tmp = tempnam(sys_get_temp_dir(), 'xlsx');
$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::OVERWRITE);
$zip->addFromString('[Content_Types].xml', $contentTypes);
$zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>' . "<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">" . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . '</Relationships>');
$zip->addFromString('xl/workbook.xml', $workbook);
$zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
$zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?>' . $sheetData);
$zip->addFromString('xl/styles.xml', $styles);
$zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8"?>' . $sharedXml);
$zip->close();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="students_import_sample.xlsx"');
header('Cache-Control: no-cache');
readfile($tmp);
unlink($tmp);
exit;
