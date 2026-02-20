<?php
/**
 * Sample CSV download for student bulk import
 * Serves the canonical sample file from sampleCSV/students_import_sample.csv
 */
$sampleFile = APP_ROOT . '/sampleCSV/students_import_sample.csv';

if (file_exists($sampleFile)) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="students_import_sample.csv"');
    header('Cache-Control: no-cache');
    header('Content-Length: ' . filesize($sampleFile));
    readfile($sampleFile);
    exit;
}

// Fallback: generate inline if file missing
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="students_import_sample.csv"');
header('Cache-Control: no-cache');

$handle = fopen('php://output', 'w');
fwrite($handle, "\xEF\xBB\xBF"); // BOM for Excel

fputcsv($handle, [
    'first_name', 'last_name', 'gender', 'date_of_birth',
    'class_name', 'section_name', 'admission_no',
    'phone', 'email', 'religion', 'blood_group',
    'guardian_name', 'guardian_phone', 'address',
]);

$samples = [
    ['Abebe',  'Kebede',  'male',   '3/15/2010',  'Grade 5', 'A', '',        '911223344', '',             'Orthodox',   'O+', 'Kebede Ali',   '922334455', 'Addis Ababa'],
    ['Tigist', 'Haile',   'female', '7/22/2011',  'Grade 4', 'B', '',        '933445566', 'tigi@mail.com','Protestant', 'A+', 'Haile Tekle',  '944556677', 'Dire Dawa'],
    ['Dawit',  'Tadesse', 'male',   '11/5/2009',  'Grade 6', 'A', 'STU-101', '',          '',             '',           'B+', 'Tadesse Mamo', '',          'Bahir Dar'],
    ['Meron',  'Solomon', 'female', '1/18/2012',  'Grade 3', 'C', '',        '955667788', '',             'Catholic',   '',   'Solomon Tafa', '966778899', 'Hawassa'],
    ['Yonas',  'Girma',   'male',   '6/30/2010',  'Grade 5', 'B', '',        '977889900', '',             'Orthodox',   'AB+','Girma Debebe', '988990011', 'Adama'],
];

foreach ($samples as $row) {
    fputcsv($handle, $row);
}

fclose($handle);
exit;
