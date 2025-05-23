
<?php
$host = 'localhost';
$dbname = 'canteenledger';
$username = 'root';
$password = ''; // leave blank for default XAMPP MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Optional: set error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}