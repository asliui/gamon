<?php

declare(strict_types=1);

/**
 * CLI: Create or reset the default admin account.
 * Usage: php scripts/create_admin.php [password]
 * Default password: Demo123!
 */

require_once __DIR__ . '/../core/bootstrap.php';

use WebGamon\Core\DB;

$email = 'asliuzar4@gmail.com';
$name = 'System Admin';
$password = $argv[1] ?? 'Demo123!';

if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo = DB::pdo();

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$existing = $stmt->fetch();

if ($existing) {
    $upd = $pdo->prepare('
        UPDATE users
        SET name = :name, password_hash = :hash, role = :role, is_deleted = 0, deleted_at = NULL
        WHERE id = :id
    ');
    $upd->execute([
        ':name' => $name,
        ':hash' => $hash,
        ':role' => 'admin',
        ':id' => (int)$existing['id'],
    ]);
    echo "Admin updated: {$email} (id {$existing['id']})\n";
} else {
    $ins = $pdo->prepare('
        INSERT INTO users (name, email, password_hash, role)
        VALUES (:name, :email, :hash, :role)
    ');
    $ins->execute([
        ':name' => $name,
        ':email' => $email,
        ':hash' => $hash,
        ':role' => 'admin',
    ]);
    echo "Admin created: {$email} (id " . $pdo->lastInsertId() . ")\n";
}

echo "Login with the password you provided.\n";
