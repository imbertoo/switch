<?php
// db_connect.php - Optimizado para Vercel
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción

// Configuración de base de datos
$host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'sql7.freesqldatabase.com';
$username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'sql7781528';
$password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? 'enelgmail';
$database = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'sql7781528';
$port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 3306;

try {
    $conn = new mysqli($host, $username, $password, $database, $port);
    
    if ($conn->connect_error) {
        error_log("Error de conexión: " . $conn->connect_error);
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Error en db_connect.php: " . $e->getMessage());
    // En producción, mostrar mensaje genérico
    if (getenv('VERCEL_ENV') === 'production') {
        die("Servicio temporalmente no disponible");
    } else {
        die("Error de conexión: " . $e->getMessage());
    }
}
?>