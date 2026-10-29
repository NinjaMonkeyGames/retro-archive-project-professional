<?php

// Read variables injected by Docker (with fallbacks if needed)
$host = getenv('DB_HOST') ?: 'db';
$user = getenv('DB_USER') ?: 'dev_user';
$pass = getenv('DB_PASS') ?: 'dev_password';
$db   = getenv('DB_NAME') ?: 'my_database';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h1>Successfully connected to MySQL!</h1>";
} catch (PDOException $e) {
    echo "<h1>Database Connection Failed:</h1> " . $e->getMessage();
}
