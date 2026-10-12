<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$username = $argv[1] ?? '';
if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $username)) {
    fwrite(STDERR, "Usage: php php/create_admin.php <username>\n");
    fwrite(STDERR, "Username must be 3-60 characters: letters, numbers, dot, dash or underscore.\n");
    exit(1);
}

fwrite(STDOUT, 'Password (minimum 12 characters): ');
$password = rtrim((string) fgets(STDIN), "\r\n");
fwrite(STDOUT, 'Confirm password: ');
$confirmation = rtrim((string) fgets(STDIN), "\r\n");

if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters long.\n");
    exit(1);
}
if (!hash_equals($password, $confirmation)) {
    fwrite(STDERR, "Passwords do not match.\n");
    exit(1);
}

require_once __DIR__ . '/config.php';

try {
    $statement = databaseConnection()->prepare(
        'INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)'
    );
    $statement->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Admin account created. Sign in at /Volt_Gym/php/admin.php.\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Could not create the admin account: {$error->getMessage()}\n");
    exit(1);
}
