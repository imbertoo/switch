<?php
// db_connect.php - Compatible con PHP 7.4 y Vercel
$host = getenv('DB_HOST') ? getenv('DB_HOST') : 'sql7.freesqldatabase.com';
$username = getenv('DB_USER') ? getenv('DB_USER') : 'sql7781528';
$password = getenv('DB_PASS') ? getenv('DB_PASS') : 'mtsRqPrmHH';
$database = getenv('DB_NAME') ? getenv('DB_NAME') : 'sql7781528';
$port = getenv('DB_PORT') ? getenv('DB_PORT') : 3306;

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    error_log("Error de conexión: " . $conn->connect_error);
    die("Conexión fallida: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>