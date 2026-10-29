<?php

$host = 'db'; // Uses the MySQL container service name
$user = 'dev_user';
$pass = 'dev_password';
$db   = 'my_database';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h1>Successfully connected to MySQL!</h1>";
} catch (PDOException $e) {
    echo "<h1>Database Connection Failed:</h1> " . $e->getMessage();
}
