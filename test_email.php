<?php
// Archivo: test_email.php
$to = "albertotriv03@gmail.com"; // Cambia por tu email personal
$subject = "Prueba de Email desde XAMPP";
$message = "¡Hola! Este es un email de prueba desde XAMPP.";
$headers = "From: tfgswitch@gmail.com\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo "✅ Email enviado correctamente";
} else {
    echo "❌ Error al enviar email";
}
?>