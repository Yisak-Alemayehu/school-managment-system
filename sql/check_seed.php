<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=urjiberi_school;charset=utf8mb4','root','0000',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
echo 'Students: ' . $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn() . PHP_EOL;
echo 'Results:  ' . $pdo->query('SELECT COUNT(*) FROM student_results')->fetchColumn() . PHP_EOL;
echo 'Attend:   ' . $pdo->query('SELECT COUNT(*) FROM attendance')->fetchColumn() . PHP_EOL;
echo 'Assess:   ' . $pdo->query('SELECT COUNT(*) FROM assessments')->fetchColumn() . PHP_EOL;
echo 'Teachers: ' . $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id=(SELECT id FROM roles WHERE slug='teacher')")->fetchColumn() . PHP_EOL;
echo 'Enrollments: ' . $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn() . PHP_EOL;
