<?php
declare(strict_types=1);

function databaseConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('VOLT_DB_HOST') ?: '127.0.0.1';
    $database = getenv('VOLT_DB_NAME') ?: 'volt_gym';
    $username = getenv('VOLT_DB_USER') ?: 'root';
    $password = getenv('VOLT_DB_PASSWORD');
    if ($password === false) {
        $password = '';
    }

    $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}
