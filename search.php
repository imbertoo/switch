<?php
session_start();
require_once 'db_connect.php'; // Archivo para conectar a la base de datos

// Verificar si se recibió la consulta de búsqueda
if (isset($_GET['query'])) {
    $query = $_GET['query'];

    // Escapar la consulta para evitar inyecciones SQL
    $query = $conn->real_escape_string($query);

    // Buscar usuarios que coincidan con la consulta
    $searchQuery = $conn->prepare("
        SELECT id, username, profile_picture FROM users 
        WHERE username LIKE CONCAT('%', ?, '%') 
        LIMIT 10
    ");
    $searchQuery->bind_param("s", $query);
    $searchQuery->execute();
    $result = $searchQuery->get_result();

    if ($result->num_rows > 0) {
        while ($user = $result->fetch_assoc()) {
            echo '<div class="search-item">';
            echo '<img src="' . $user['profile_picture'] . '" alt="Perfil" width="30" height="30">';
            echo '<a href="profile.php?user_id=' . $user['id'] . '">' . htmlspecialchars($user['username']) . '</a>';
            echo '</div>';
        }
    } else {
        echo '<p>No se encontraron usuarios.</p>';
    }
}
?>