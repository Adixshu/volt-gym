<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$password = getenv('VOLT_INITIAL_ADMIN_PASSWORD');
if ($password === false || strlen($password) < 12) {
    fwrite(STDERR, "Set VOLT_INITIAL_ADMIN_PASSWORD to a password of at least 12 characters.\n");
    exit(1);
}

require_once __DIR__ . '/config.php';

try {
    $connection = databaseConnection();
    if ($connection->query('SELECT 1 FROM admins LIMIT 1')->fetchColumn()) {
        fwrite(STDERR, "Initial admin was not created because an admin account already exists.\n");
        exit(1);
    }

    $statement = $connection->prepare(
        'INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)'
    );
    $statement->execute([
        'username' => 'admin',
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Initial admin account created. Sign in at /Volt_Gym/php/admin.php.\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Could not create the initial admin account: {$error->getMessage()}\n");
    exit(1);
}
