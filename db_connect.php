<?php
// db_connect.php - Configuración para FreeSQLDatabase y Vercel

// Variables de entorno (para producción en Vercel)
$servername = getenv('DB_HOST') ?: 'sql7.freesqldatabase.com';
$username = getenv('DB_USER') ?: 'sql7781528';
$password = getenv('DB_PASS') ?: 'mtsRqPrmHH';
$dbname = getenv('DB_NAME') ?: 'sql7781528';
$port = getenv('DB_PORT') ?: 3306;

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Verificar conexión
if ($conn->connect_error) {
    error_log("Error de conexión: " . $conn->connect_error);
    die("Conexión fallida: " . $conn->connect_error);
}

// Configurar charset
$conn->set_charset("utf8mb4");

// Log para debugging (opcional)
error_log("Conexión exitosa a la base de datos: " . $dbname);
?>