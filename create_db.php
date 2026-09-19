<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS rinasa_catering_ops');
    echo "Database created successfully\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
