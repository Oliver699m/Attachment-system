<?php
$host = 'localhost';
$dbname = 'device_requisition';
$username = 'root';
$password = '';

try {
    $adminPdo = new PDO("mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");

    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            department VARCHAR(100),
            role VARCHAR(50) NOT NULL DEFAULT 'intern',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS devices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(100) NOT NULL,
            quantity INT DEFAULT 0,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            requisitioner VARCHAR(100) NOT NULL,
            department VARCHAR(100) NOT NULL,
            device_type VARCHAR(100) NOT NULL,
            description TEXT,
            reason TEXT NOT NULL,
            status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            requester_email VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )");

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'intern'");
    } catch (PDOException $e) {
        // Ignore if the column already exists.
    }

    try {
        $pdo->exec("ALTER TABLE requests ADD COLUMN requester_email VARCHAR(255) NULL");
    } catch (PDOException $e) {
        // Ignore if the column already exists.
    }

    $deviceCount = (int) $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();
    if ($deviceCount === 0) {
        $pdo->exec("INSERT INTO devices (type, quantity, description) VALUES
            ('GPS Tracker', 10, 'Standard vehicle GPS device'),
            ('SIM Card', 5, 'Data SIM for trackers')");
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

session_start();

define('SUPERVISOR_EMAILS', ['jmarire@itrackzimbabwe.com', 'inawu@itrackzimbabwe.com']);
?>