<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=urjiberi_school;charset=utf8mb4', 'root', '0000', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$sqlFile = __DIR__ . '/012_results_module.sql';
$sql     = file_get_contents($sqlFile);

// Split on semicolons, run each statement
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $stmt) {
    if ($stmt) {
        try {
            $pdo->exec($stmt);
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

// Verify
$tables = ['assessments', 'student_results'];
foreach ($tables as $t) {
    $r = $pdo->query("SHOW TABLES LIKE '$t'")->fetchAll();
    echo count($r) ? "OK: $t exists\n" : "MISSING: $t\n";
}
echo "Migration complete.\n";
