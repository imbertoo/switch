<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de WebSocket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .log {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Prueba de Conexión WebSocket</h1>
    
    <div class="log" id="log"></div>
    
    <button id="connectBtn">Conectar</button>
    <button id="disconnectBtn" disabled>Desconectar</button>
    
    <script>
        const logElement = document.getElementById('log');
        const connectBtn = document.getElementById('connectBtn');
        const disconnectBtn = document.getElementById('disconnectBtn');
        let socket;
        
        function log(message, type = 'info') {
            const entry = document.createElement('div');
            entry.className = type;
            entry.textContent = `${new Date().toLocaleTimeString()} - ${message}`;
            logElement.appendChild(entry);
            logElement.scrollTop = logElement.scrollHeight;
        }
        
        function connect() {
            // Determinar la URL del WebSocket basada en la ubicación actual
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const host = window.location.hostname;
            // Usar el puerto 8080 específicamente para WebSocket
            const wsUrl = `${protocol}//${host}:8080`;
            
            log(`Intentando conectar a: ${wsUrl}`);
            
            try {
                socket = new WebSocket(wsUrl);
                
                socket.onopen = function(event) {
                    log('Conexión establecida correctamente', 'success');
                    connectBtn.disabled = true;
                    disconnectBtn.disabled = false;
                    
                    // Enviar un mensaje de prueba
                    const testMessage = {
                        type: 'test',
                        message: 'Prueba de conexión'
                    };
                    socket.send(JSON.stringify(testMessage));
                    log('Mensaje de prueba enviado', 'info');
                };
                
                socket.onmessage = function(event) {
                    log(`Mensaje recibido: ${event.data}`, 'success');
                };
                
                socket.onerror = function(error) {
                    log(`Error de WebSocket: ${error}`, 'error');
                };
                
                socket.onclose = function(event) {
                    const reason = event.reason ? ` Razón: ${event.reason}` : '';
                    log(`Conexión cerrada. Código: ${event.code}.${reason}`, 'info');
                    connectBtn.disabled = false;
                    disconnectBtn.disabled = true;
                };
            } catch (e) {
                log(`Error al crear WebSocket: ${e.message}`, 'error');
            }
        }
        
        function disconnect() {
            if (socket) {
                socket.close();
                log('Desconexión iniciada', 'info');
            }
        }
        
        connectBtn.addEventListener('click', connect);
        disconnectBtn.addEventListener('click', disconnect);
        
        // Información del navegador
        log(`Navegador: ${navigator.userAgent}`, 'info');
        log(`Soporte WebSocket: ${window.WebSocket ? 'Sí' : 'No'}`, window.WebSocket ? 'success' : 'error');
    </script>
</body>
</html>