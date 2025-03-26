<?php
namespace ShareMyGym\Chat;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class ChatHandler implements MessageComponentInterface {
    protected $clients;
    protected $users;
    protected $conn;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        
        // Conectar a la base de datos directamente en lugar de incluir bd.php
        try {
            // Reemplaza estos valores con los de tu configuración
            $host = 'localhost';
            $dbname = 'sharemygym';
            $username = 'root';
            $password = '';
            
            $this->conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "Conexión a la base de datos establecida correctamente.\n";
        } catch (\PDOException $e) {
            echo "Error de conexión a la base de datos: " . $e->getMessage() . "\n";
            exit;
        }
        
        echo "Servidor de chat iniciado!\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->connectionId = $conn->resourceId;
        echo "Nueva conexión! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!isset($data['type'])) {
            return;
        }
        
        switch ($data['type']) {
            case 'auth':
                $this->handleAuthentication($from, $data);
                break;
                
            case 'message':
                $this->handleMessage($from, $data);
                break;
                
            case 'get_history':
                $this->handleGetHistory($from, $data);
                break;
                
            case 'get_users':
                $this->handleGetUsers($from);
                break;
                
            case 'test':
                // Para pruebas
                $from->send(json_encode([
                    'type' => 'test_response',
                    'message' => 'Conexión de prueba exitosa'
                ]));
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Actualizar estado del usuario a desconectado
        $userId = $this->findUserByConnection($conn);
        if ($userId) {
            unset($this->users[$userId]);
            echo "Usuario {$userId} desconectado (conexión {$conn->resourceId}).\n";
            
            // Notificar a otros usuarios
            $this->notifyUserStatus($userId, false);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function handleAuthentication($conn, $data) {
        if (!isset($data['user_id']) || !isset($data['session_id'])) {
            return;
        }
        
        $userId = (int)$data['user_id'];
        $sessionId = $data['session_id'];
        
        // Verificar la sesión (esto es simplificado, deberías implementar una verificación más robusta)
        // En un entorno de producción, deberías verificar que el session_id es válido para este usuario
        
        // Asociar la conexión con el usuario
        $this->users[$userId] = $conn;
        $conn->userId = $userId;
        
        echo "Usuario {$userId} autenticado (conexión {$conn->resourceId}).\n";
        
        // Enviar confirmación de autenticación
        $conn->send(json_encode([
            'type' => 'auth_success'
        ]));
        
        // Notificar a otros usuarios
        $this->notifyUserStatus($userId, true);
        
        // Enviar mensajes no leídos
        $this->sendUnreadMessages($conn, $userId);
    }

    protected function handleMessage($from, $data) {
        if (!isset($from->userId) || !isset($data['receiver_id']) || !isset($data['message'])) {
            return;
        }
        
        $senderId = $from->userId;
        $receiverId = (int)$data['receiver_id'];
        $message = $data['message'];
        
        // Guardar el mensaje en la base de datos
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO private_messages (sender_id, receiver_id, message, timestamp) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([$senderId, $receiverId, $message]);
            $messageId = $this->conn->lastInsertId();
            
            // Obtener el timestamp del mensaje insertado
            $stmt = $this->conn->prepare("
                SELECT timestamp FROM private_messages WHERE id = ?
            ");
            $stmt->execute([$messageId]);
            $timestamp = $stmt->fetchColumn();
            
            // Crear el objeto de mensaje
            $messageData = [
                'type' => 'new_message',
                'id' => $messageId,
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $message,
                'timestamp' => $timestamp,
                'is_read' => 0
            ];
            
            // Enviar el mensaje al remitente
            $from->send(json_encode($messageData));
            
            // Enviar el mensaje al destinatario si está conectado
            if (isset($this->users[$receiverId])) {
                $this->users[$receiverId]->send(json_encode($messageData));
            }
            
            echo "Mensaje enviado de {$senderId} a {$receiverId}: {$message}\n";
        } catch (\PDOException $e) {
            echo "Error al guardar mensaje: " . $e->getMessage() . "\n";
            
            // Enviar error al cliente
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Error al enviar el mensaje'
            ]));
        }
    }

    protected function handleGetHistory($from, $data) {
        if (!isset($from->userId) || !isset($data['other_user_id'])) {
            return;
        }
        
        $userId = $from->userId;
        $otherUserId = (int)$data['other_user_id'];
        
        try {
            // Obtener historial de mensajes
            $stmt = $this->conn->prepare("
                SELECT id, sender_id, receiver_id, message, timestamp, is_read
                FROM private_messages
                WHERE (sender_id = ? AND receiver_id = ?)
                   OR (sender_id = ? AND receiver_id = ?)
                ORDER BY timestamp ASC
            ");
            
            $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Marcar mensajes como leídos
            $stmt = $this->conn->prepare("
                UPDATE private_messages
                SET is_read = 1
                WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
            ");
            
            $stmt->execute([$otherUserId, $userId]);
            
            // Enviar historial al cliente
            $from->send(json_encode([
                'type' => 'chat_history',
                'messages' => $messages
            ]));
            
            echo "Historial enviado a {$userId} para conversación con {$otherUserId}\n";
        } catch (\PDOException $e) {
            echo "Error al obtener historial: " . $e->getMessage() . "\n";
            
            // Enviar error al cliente
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Error al obtener el historial de mensajes'
            ]));
        }
    }

    protected function handleGetUsers($from) {
        if (!isset($from->userId)) {
            return;
        }
        
        $userId = $from->userId;
        
        try {
            // Obtener todos los usuarios excepto el actual
            $stmt = $this->conn->prepare("
                SELECT id, username
                FROM users
                WHERE id != ?
            ");
            
            $stmt->execute([$userId]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Añadir estado en línea
            foreach ($users as &$user) {
                $user['online'] = isset($this->users[$user['id']]);
            }
            
            // Enviar lista de usuarios al cliente
            $from->send(json_encode([
                'type' => 'user_list',
                'users' => $users
            ]));
            
            echo "Lista de usuarios enviada a {$userId}\n";
        } catch (\PDOException $e) {
            echo "Error al obtener usuarios: " . $e->getMessage() . "\n";
            
            // Enviar error al cliente
            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Error al obtener la lista de usuarios'
            ]));
        }
    }

    protected function sendUnreadMessages($conn, $userId) {
        try {
            // Verificar si la conexión a la base de datos está establecida
            if (!$this->conn) {
                echo "Error: La conexión a la base de datos no está disponible.\n";
                return;
            }
            
            // Obtener mensajes no leídos
            $stmt = $this->conn->prepare("
                SELECT id, sender_id, receiver_id, message, timestamp
                FROM private_messages
                WHERE receiver_id = ? AND is_read = 0
            ");
            
            $stmt->execute([$userId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($messages) > 0) {
                // Enviar mensajes no leídos al cliente
                $conn->send(json_encode([
                    'type' => 'unread_messages',
                    'messages' => $messages
                ]));
                
                echo "Mensajes no leídos enviados a {$userId}\n";
            }
        } catch (\PDOException $e) {
            echo "Error al obtener mensajes no leídos: " . $e->getMessage() . "\n";
        }
    }

    protected function notifyUserStatus($userId, $online) {
        $statusData = [
            'type' => 'user_status',
            'user_id' => $userId,
            'online' => $online
        ];
        
        foreach ($this->clients as $client) {
            if (isset($client->userId) && $client->userId != $userId) {
                $client->send(json_encode($statusData));
            }
        }
    }

    protected function findUserByConnection($conn) {
        foreach ($this->users as $userId => $userConn) {
            if ($userConn === $conn) {
                return $userId;
            }
        }
        return null;
    }
}