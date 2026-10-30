<?php

declare(strict_types=1);

function getDbConnection(): ?PDO
{
    // Host must match the service name in docker-compose.yml ('db')
    $host = 'db';
    $db   = 'retro-db';
    $user = 'dev_user';
    $pass = 'dev_password';
    $port = '3306';

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        return null;
    }
}