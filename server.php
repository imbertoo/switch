<?php
require 'vendor/autoload.php';
require 'ChatHandler.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use ShareMyGym\Chat\ChatHandler;

// Configurar el puerto para el servidor WebSocket
$port = 8080;

// Crear el servidor WebSocket
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatHandler()
        )
    ),
    $port
);

echo "Servidor WebSocket iniciado en el puerto {$port}\n";
echo "Presiona Ctrl+C para detener el servidor\n";

// Ejecutar el servidor (bucle infinito)
$server->run();