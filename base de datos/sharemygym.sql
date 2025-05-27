-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-05-2025 a las 15:16:04
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sharemygym`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `comment_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comments`
--

INSERT INTO `comments` (`id`, `user_id`, `post_id`, `comment_text`, `created_at`) VALUES
(10, 1, 11, 'sii', '2025-01-23 19:20:10'),
(15, 1, 14, 'mola', '2025-03-28 00:20:18'),
(17, 1, 18, 'jajaj chema', '2025-03-28 01:24:54'),
(21, 14, 32, 'hola!', '2025-05-06 16:39:05'),
(22, 14, 41, 'mola', '2025-05-06 16:39:14'),
(23, 14, 59, 'hola!', '2025-05-06 16:39:19'),
(24, 14, 29, 'te comento', '2025-05-26 16:13:50'),
(25, 14, 20, 'me alegro!', '2025-05-26 16:14:03'),
(26, 14, 20, 'sii ', '2025-05-26 16:14:49'),
(27, 14, 26, 'mola👍', '2025-05-26 16:15:43'),
(28, 1, 74, 'mola', '2025-05-26 16:37:40'),
(29, 1, 74, 'te comento', '2025-05-26 16:37:47'),
(31, 1, 74, '👍😄', '2025-05-26 16:43:44'),
(32, 1, 20, '👍', '2025-05-26 16:44:01'),
(33, 1, 74, '🤘', '2025-05-26 16:47:02'),
(34, 14, 65, 'me la pela tio', '2025-05-26 22:12:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comment_likes`
--

CREATE TABLE `comment_likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comment_likes`
--

INSERT INTO `comment_likes` (`id`, `user_id`, `comment_id`, `created_at`) VALUES
(13, 1, 17, '2025-03-31 13:16:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exercises`
--

CREATE TABLE `exercises` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `exercise_name` varchar(100) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `reps` int(11) DEFAULT NULL,
  `sets` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `followers`
--

CREATE TABLE `followers` (
  `follower_id` int(11) NOT NULL,
  `followed_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `friends`
--

CREATE TABLE `friends` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `friend_id` int(11) NOT NULL,
  `status` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `friends`
--

INSERT INTO `friends` (`id`, `user_id`, `friend_id`, `status`, `created_at`) VALUES
(1, 1, 2, 'accepted', '2024-10-03 14:26:35'),
(2, 1, 3, 'accepted', '2024-10-03 14:26:35'),
(3, 1, 4, 'accepted', '2024-10-03 14:26:35'),
(5, 2, 3, 'accepted', '2024-10-03 14:26:35'),
(6, 2, 4, 'accepted', '2024-10-03 14:26:35'),
(7, 2, 5, 'accepted', '2024-10-03 14:26:35'),
(8, 2, 1, 'accepted', '2024-10-03 14:26:35'),
(9, 3, 4, 'accepted', '2024-10-03 14:26:35'),
(10, 3, 5, 'accepted', '2024-10-03 14:26:35'),
(11, 3, 1, 'accepted', '2024-10-03 14:26:35'),
(12, 3, 2, 'accepted', '2024-10-03 14:26:35'),
(13, 4, 5, 'accepted', '2024-10-03 14:26:35'),
(14, 4, 1, 'accepted', '2024-10-03 14:26:35'),
(15, 4, 2, 'accepted', '2024-10-03 14:26:35'),
(16, 4, 3, 'accepted', '2024-10-03 14:26:35'),
(17, 5, 1, 'accepted', '2024-10-03 14:26:35'),
(18, 5, 2, 'accepted', '2024-10-03 14:26:35'),
(19, 5, 3, 'accepted', '2024-10-03 14:26:35'),
(20, 5, 4, 'accepted', '2024-10-03 14:26:35'),
(24, 14, 1, 'accepted', '2025-01-23 19:25:18'),
(29, 15, 1, 'accepted', '2025-03-26 14:15:35'),
(35, 15, 2, 'accepted', '2025-03-28 00:23:50'),
(39, 1, 14, 'accepted', '2025-03-28 00:36:40'),
(41, 1, 15, 'accepted', '2025-03-31 13:11:10'),
(42, 19, 1, 'accepted', '2025-03-31 13:42:58'),
(44, 1, 19, 'accepted', '2025-03-31 13:46:35'),
(45, 19, 15, 'accepted', '2025-03-31 13:49:00'),
(46, 20, 1, 'accepted', '2025-03-31 21:31:18'),
(47, 20, 14, 'accepted', '2025-03-31 21:31:21'),
(48, 1, 20, 'accepted', '2025-03-31 21:31:57'),
(52, 1, 5, 'accepted', '2025-05-06 16:34:20'),
(53, 14, 2, 'accepted', '2025-05-06 16:38:34'),
(55, 14, 15, 'accepted', '2025-05-06 16:39:25'),
(57, 14, 4, 'accepted', '2025-05-26 16:20:54'),
(59, 14, 3, 'accepted', '2025-05-26 22:14:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `likes`
--

INSERT INTO `likes` (`id`, `user_id`, `post_id`, `created_at`) VALUES
(24, 1, 11, '2025-01-24 19:50:18'),
(26, 14, 11, '2025-03-26 00:22:32'),
(49, 1, 18, '2025-03-31 13:11:15'),
(55, 20, 20, '2025-03-31 21:31:32'),
(58, 14, 65, '2025-05-26 22:11:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `content`, `image_url`, `video_url`, `created_at`) VALUES
(11, 14, 'bloste', 'uploads/217bd083-abe1-41fe-a277-d230753cf987.jpg', NULL, '2025-01-23 13:20:19'),
(14, 14, 'Asi voy programando esto.', NULL, 'uploads/videos/1743120261_2025-03-26 15-16-56.mp4', '2025-03-28 00:04:21'),
(18, 15, '🗣️ ENDERPEEEARL', NULL, 'uploads/videos/1743125054_v0f044gc0000cro13u7og65pd6pvsrkg.mp4', '2025-03-28 01:24:14'),
(20, 1, 'Hoy ha sido un día increíble, el clima perfecto para un paseo.', NULL, NULL, '2025-03-31 21:26:08'),
(21, 2, 'No hay nada mejor que empezar el día con un buen café ☕.', NULL, NULL, '2025-03-31 21:26:08'),
(22, 3, '¿Alguien más siente que esta semana ha sido eterna? 😂', NULL, NULL, '2025-03-31 21:26:08'),
(23, 4, 'Acabo de descubrir una nueva serie y estoy enganchado.', NULL, NULL, '2025-03-31 21:26:08'),
(24, 5, 'Las pequeñas cosas de la vida son las que más importan.', NULL, NULL, '2025-03-31 21:26:08'),
(25, 6, 'Hoy vi una película que me dejó pensando mucho.', NULL, NULL, '2025-03-31 21:26:08'),
(26, 14, 'Mañana es un gran día, espero que todo salga bien.', NULL, NULL, '2025-03-31 21:26:08'),
(27, 15, 'Un día más, un aprendizaje más.', NULL, NULL, '2025-03-31 21:26:08'),
(28, 19, 'Voy a intentar cocinar algo nuevo hoy.', NULL, NULL, '2025-03-31 21:26:08'),
(29, 1, 'Leer un buen libro antes de dormir es lo mejor.', NULL, NULL, '2025-03-31 21:26:08'),
(30, 2, '¿Por qué los lunes son tan difíciles? 😩', NULL, NULL, '2025-03-31 21:26:08'),
(31, 3, 'Hoy decidí salir a correr y me sentí genial.', NULL, NULL, '2025-03-31 21:26:08'),
(32, 4, 'Me recomendaron una nueva serie, a ver qué tal.', NULL, NULL, '2025-03-31 21:26:08'),
(33, 5, 'Escuchar música a todo volumen es terapéutico.', NULL, NULL, '2025-03-31 21:26:08'),
(34, 6, 'Mi gato se pasó todo el día durmiendo, qué envidia.', NULL, NULL, '2025-03-31 21:26:08'),
(35, 14, 'A veces un cambio de rutina viene bien.', NULL, NULL, '2025-03-31 21:26:08'),
(36, 15, 'Extraño viajar, ojalá pronto pueda hacerlo.', NULL, NULL, '2025-03-31 21:26:08'),
(37, 19, 'Los días de lluvia son perfectos para relajarse.', NULL, NULL, '2025-03-31 21:26:08'),
(38, 1, 'Nada como un buen desayuno para empezar el día.', NULL, NULL, '2025-03-31 21:26:08'),
(39, 2, 'El tráfico hoy estaba imposible, qué locura.', NULL, NULL, '2025-03-31 21:26:08'),
(40, 3, 'Terminé un proyecto importante, qué alivio.', NULL, NULL, '2025-03-31 21:26:08'),
(41, 4, 'Me encontré con un amigo de la infancia hoy.', NULL, NULL, '2025-03-31 21:26:08'),
(42, 5, 'Las redes sociales a veces pueden ser agotadoras.', NULL, NULL, '2025-03-31 21:26:08'),
(43, 6, 'Hoy me propuse aprender algo nuevo.', NULL, NULL, '2025-03-31 21:26:08'),
(44, 14, 'Tengo ganas de hacer algo diferente este fin de semana.', NULL, NULL, '2025-03-31 21:26:08'),
(45, 15, 'Me puse a organizar mi escritorio, ahora todo está en orden.', NULL, NULL, '2025-03-31 21:26:08'),
(46, 19, 'A veces el silencio es lo mejor para concentrarse.', NULL, NULL, '2025-03-31 21:26:08'),
(48, 2, 'Hoy me desperté con mucha energía, qué raro.', NULL, NULL, '2025-03-31 21:26:08'),
(49, 3, 'Caminé por el parque y fue muy relajante.', NULL, NULL, '2025-03-31 21:26:08'),
(50, 4, 'Escuché un podcast muy interesante hoy.', NULL, NULL, '2025-03-31 21:26:08'),
(51, 5, 'Las pequeñas victorias también cuentan.', NULL, NULL, '2025-03-31 21:26:08'),
(52, 6, 'Voy a empezar un reto de 30 días, a ver cómo me va.', NULL, NULL, '2025-03-31 21:26:08'),
(53, 14, 'Reencontrarse con viejos amigos siempre es bonito.', NULL, NULL, '2025-03-31 21:26:08'),
(54, 15, 'Un café bien cargado para seguir el día.', NULL, NULL, '2025-03-31 21:26:08'),
(55, 19, 'Cocinando mi plato favorito hoy.', NULL, NULL, '2025-03-31 21:26:08'),
(56, 1, 'A veces hace falta un respiro.', NULL, NULL, '2025-03-31 21:26:08'),
(57, 2, 'Pensando en nuevos proyectos.', NULL, NULL, '2025-03-31 21:26:08'),
(58, 3, 'Hoy compré un libro nuevo, emocionado por leerlo.', NULL, NULL, '2025-03-31 21:26:08'),
(59, 4, 'Nada como un paseo al aire libre para despejar la mente.', NULL, NULL, '2025-03-31 21:26:08'),
(60, 5, 'Hoy intenté hacer meditación, fue interesante.', NULL, NULL, '2025-03-31 21:26:08'),
(61, 6, 'Me gusta la sensación de un día productivo.', NULL, NULL, '2025-03-31 21:26:08'),
(62, 14, 'El tiempo pasa volando, ya casi se acaba el mes.', NULL, NULL, '2025-03-31 21:26:08'),
(63, 15, 'Hoy fue un día normal, pero a veces eso es lo mejor.', NULL, NULL, '2025-03-31 21:26:08'),
(64, 19, 'Probé una nueva cafetería y me encantó.', NULL, NULL, '2025-03-31 21:26:08'),
(65, 1, 'Voy a empezar a aprender un nuevo idioma.', NULL, NULL, '2025-03-31 21:26:08'),
(66, 2, 'Nada como una tarde de juegos con amigos.', NULL, NULL, '2025-03-31 21:26:08'),
(67, 3, 'Se acerca el fin de semana, ya era hora.', NULL, NULL, '2025-03-31 21:26:08'),
(68, 4, 'Hoy cociné algo improvisado y quedó genial.', NULL, NULL, '2025-03-31 21:26:08'),
(69, 5, 'A veces es bueno hacer una pausa y reflexionar.', NULL, NULL, '2025-03-31 21:26:08'),
(70, 6, 'Días largos, pero satisfactorios.', NULL, NULL, '2025-03-31 21:26:08'),
(71, 14, 'Escuchar música mientras trabajo me motiva.', NULL, NULL, '2025-03-31 21:26:08'),
(72, 15, 'Hoy aprendí algo nuevo, eso siempre es bueno.', NULL, NULL, '2025-03-31 21:26:08'),
(73, 19, 'Planificando un viaje, a ver si se da.', NULL, NULL, '2025-03-31 21:26:08'),
(74, 20, 'jeje', 'uploads/images/1743456666_IMG_4225.jpg', NULL, '2025-03-31 21:31:06'),
(75, 1, 'historia💢', 'uploads/images/1748279034_IMG_0954.jpg', NULL, '2025-05-26 17:03:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `private_messages`
--

CREATE TABLE `private_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `timestamp` datetime NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `private_messages`
--

INSERT INTO `private_messages` (`id`, `sender_id`, `receiver_id`, `message`, `timestamp`, `is_read`) VALUES
(1, 14, 1, 'holaa', '2025-03-26 01:34:58', 1),
(2, 1, 14, 'hola', '2025-03-26 01:35:59', 1),
(3, 14, 1, 'funciona ajajajaj', '2025-03-26 01:37:08', 1),
(4, 14, 1, 'locura esto eh', '2025-03-26 01:37:11', 1),
(5, 1, 14, 'sii', '2025-03-26 01:37:13', 1),
(6, 1, 14, 'uy', '2025-03-26 01:37:21', 1),
(7, 1, 14, 'como que', '2025-03-26 01:37:22', 1),
(8, 1, 14, 'me quedo sin espacio eh', '2025-03-26 01:37:34', 1),
(9, 1, 14, 'se va pa abajo', '2025-03-26 01:37:37', 1),
(10, 1, 14, 'nooo', '2025-03-26 01:44:52', 1),
(11, 1, 14, 'ya esta', '2025-03-26 01:44:54', 1),
(12, 1, 14, 'siiii', '2025-03-26 01:44:56', 1),
(13, 14, 1, 'holaa', '2025-03-26 01:45:42', 1),
(14, 1, 14, 'holaa', '2025-03-26 01:45:44', 1),
(15, 14, 1, 'que tal', '2025-03-26 01:45:54', 1),
(16, 1, 14, 'bieen', '2025-03-26 01:45:57', 1),
(17, 14, 1, 'hola!', '2025-03-26 01:46:44', 1),
(18, 15, 1, 'hola!', '2025-03-26 15:17:01', 1),
(19, 1, 15, 'hey!', '2025-03-26 15:17:06', 1),
(20, 15, 1, 'work', '2025-03-26 15:17:13', 1),
(21, 1, 15, 'sii', '2025-03-26 15:17:14', 1),
(22, 1, 15, ':)', '2025-03-26 15:17:16', 1),
(23, 1, 15, 'meca', '2025-03-26 21:11:47', 1),
(24, 1, 15, 'holaa', '2025-03-26 21:11:50', 1),
(25, 1, 15, 'jeje', '2025-03-26 21:11:51', 1),
(26, 1, 15, 'good', '2025-03-26 21:11:53', 1),
(27, 15, 1, 'que pasó?', '2025-03-26 21:15:39', 1),
(28, 1, 15, 'nana', '2025-03-28 00:30:45', 1),
(29, 1, 15, 'todo chill', '2025-03-28 00:30:48', 1),
(30, 14, 15, 'hola', '2025-03-28 01:13:20', 1),
(31, 15, 14, 'hey!', '2025-03-28 01:13:24', 1),
(32, 14, 15, 'jeje te seguí', '2025-03-28 01:17:58', 1),
(33, 15, 14, 'gracias!', '2025-03-28 01:18:05', 1),
(34, 19, 1, 'nigga', '2025-03-31 15:44:58', 1),
(35, 1, 19, 'que cojones tio', '2025-03-31 15:45:09', 1),
(36, 1, 19, 'pero', '2025-03-31 15:45:10', 1),
(37, 1, 19, 'porqie', '2025-03-31 15:45:11', 1),
(38, 1, 19, 'nooo', '2025-03-31 15:45:12', 1),
(39, 19, 1, 'siiii', '2025-03-31 15:45:14', 1),
(40, 19, 1, 'siii', '2025-03-31 15:45:15', 1),
(41, 19, 1, 'siii', '2025-03-31 15:45:16', 1),
(42, 1, 19, 'no...', '2025-03-31 15:45:21', 1),
(43, 1, 19, 'porfa', '2025-03-31 15:45:22', 1),
(44, 1, 19, 'joder macho', '2025-03-31 15:45:32', 1),
(45, 19, 1, 'siii siii', '2025-03-31 15:45:42', 1),
(46, 19, 15, 'holaaaaaa', '2025-03-31 15:49:04', 0),
(47, 14, 1, 'holaa', '2025-05-26 18:09:52', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `progress`
--

CREATE TABLE `progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `body_weight` decimal(5,2) DEFAULT NULL,
  `body_fat` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT 'default_profile_picture.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `bio`, `profile_image`, `created_at`, `profile_picture`) VALUES
(1, 'imberto', 'albertotriv03@gmail.com', '$2y$10$87FATs66zepCibthINuY9u8Cz.1hkxQrbnWX9l3zwhc7Zv3Bbeb9S', NULL, NULL, '2024-10-03 13:42:30', 'uploads/144DD9A2-F42E-4C7C-B89B-660A2339F521 (1).JPG'),
(2, 'pelaaf', 'pelayo@gmail.com', 'hashed_password1', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default-avatar.png'),
(3, 'mandestroyer56', 'user1@example.com', 'hashed_password2', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default-avatar.png'),
(4, 'osbo777', 'user2@example.com', 'hashed_password3', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default-avatar.png'),
(5, 'bloste', 'user3@example.com', 'hashed_password4', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default-avatar.png'),
(6, 'canadre', 'user4@example.com', 'hashed_password5', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default-avatar.png'),
(14, 'test', 'test@test.com', '$2y$10$MVoYSHS/g2qnsdAvhIs.2u.pBc2VvfcykCyjVIFz.RSP3/6aB./Me', NULL, NULL, '2025-01-23 13:18:35', 'uploads/0eda11ba-5822-4429-95e4-54530b8c1dd1.jpg'),
(15, 'rodrii12', 'rodrii12@gmail.com', '$2y$10$4JzZpK4iU3/f.OwvBLjIZepg1oksukDo8o1xhNEIEhMKRE1ZL18TS', NULL, NULL, '2025-03-26 14:15:05', 'uploads/FOTO.jpg'),
(19, 'PatitoGames', 'patitoyt@gmail.com', '$2y$10$Vtao6KmzXFaQ19dsMuzucOPvM6OBORznfOFFUHon5JOYqX0wapdf2', NULL, NULL, '2025-03-31 13:41:56', 'uploads/images.jpg'),
(20, 'soybertopro', 'soybertopro@gmail.com', '$2y$10$OZvRSS7er/H/GvhddZZgcusehxKUKxmiI5LC5Oh67X/9BSuAA1NRm', NULL, NULL, '2025-03-31 21:26:54', 'uploads/default-avatar.png');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indices de la tabla `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_comment` (`user_id`,`comment_id`),
  ADD KEY `comment_id` (`comment_id`);

--
-- Indices de la tabla `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `followers`
--
ALTER TABLE `followers`
  ADD PRIMARY KEY (`follower_id`,`followed_id`),
  ADD KEY `followed_id` (`followed_id`);

--
-- Indices de la tabla `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_friendship` (`user_id`,`friend_id`),
  ADD KEY `friend_id` (`friend_id`);

--
-- Indices de la tabla `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indices de la tabla `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indices de la tabla `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT de la tabla `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT de la tabla `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD CONSTRAINT `api_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comment_likes_ibfk_2` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `exercises`
--
ALTER TABLE `exercises`
  ADD CONSTRAINT `exercises_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `followers`
--
ALTER TABLE `followers`
  ADD CONSTRAINT `followers_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `followers_ibfk_2` FOREIGN KEY (`followed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`friend_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `private_messages`
--
ALTER TABLE `private_messages`
  ADD CONSTRAINT `private_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
