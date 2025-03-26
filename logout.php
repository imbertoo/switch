<?php
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea, destruir la sesión en sí
session_destroy();

// Redirigir al usuario a la página de inicio de sesión
header("Location: index.php");
exit;
?>
