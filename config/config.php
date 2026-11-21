<?php

// Database configuration
$host = 'localhost:3307';
$dbname = 'iron_elite_fitness';
$user = 'root';
$pass = '';

try {

    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    // Stop execution if connection fails
    error_log($e->getMessage());

    die("Database connection failed. Please check your database settings.");

}

?>
