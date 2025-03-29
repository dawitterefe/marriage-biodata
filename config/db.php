<?php
$host = 'localhost';
$dbname = 'biodata_db';
$username = 'root';
$password = ''; // Adjust if you have a password in Laragon

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
