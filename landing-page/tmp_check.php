<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=3306;dbname=eduelevate;charset=utf8mb4', 'root', '0000', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Check users table
    $cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    echo "users columns: " . implode(", ", $cols) . "\n\n";
    
    // Check if any users exist
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "users count: $count\n";
    
    if ($count > 0) {
        $users = $pdo->query("SELECT id, email, name, role, is_active FROM users LIMIT 5")->fetchAll();
        foreach ($users as $u) {
            echo "  User #{$u['id']}: {$u['email']} | {$u['name']} | role={$u['role']} | active={$u['is_active']}\n";
        }
    }
    
    echo "\n";
    // Check schools table
    $count2 = $pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn();
    echo "schools count: $count2\n";
    
    // Test what happens on Auth::attempt-like query
    echo "\nTest login query:\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute(['admin@eduelevate.com']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo $row ? "Found user: {$row['email']}\n" : "No user found for admin@eduelevate.com\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
