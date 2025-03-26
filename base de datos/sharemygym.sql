-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-03-2025 a las 21:00:54
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
(5, 14, 2, 'maricon', '2025-01-23 17:58:08'),
(7, 1, 2, 'bloste', '2025-01-23 18:08:19'),
(9, 14, 10, 'sii', '2025-01-23 18:30:18'),
(10, 1, 11, 'sii', '2025-01-23 19:20:10'),
(11, 14, 1, 'me la pela', '2025-01-23 19:24:26'),
(12, 14, 8, 'nigga', '2025-01-23 19:25:05');

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
(6, 1, 5, '2025-01-23 18:00:30'),
(9, 1, 9, '2025-01-23 18:38:51'),
(10, 14, 9, '2025-01-23 18:41:30');

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
(4, 1, 5, 'accepted', '2024-10-03 14:26:35'),
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
(22, 14, 2, 'accepted', '2025-01-23 17:58:02'),
(24, 14, 1, 'accepted', '2025-01-23 19:25:18'),
(28, 1, 14, 'accepted', '2025-01-24 19:55:16'),
(29, 15, 1, 'accepted', '2025-03-26 14:15:35');

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
(20, 1, 8, '2025-01-23 18:38:55'),
(24, 1, 11, '2025-01-24 19:50:18'),
(25, 1, 1, '2025-02-26 16:58:11'),
(26, 14, 11, '2025-03-26 00:22:32');

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
(1, 1, '¡Hola, soy Imberto! Estoy emocionado por compartir mi progreso en el gimnasio.', NULL, NULL, '2024-10-03 14:28:12'),
(2, 2, '¡Primera semana de entrenamiento completada! ¡Listo para seguir mejorando!', NULL, NULL, '2024-10-03 14:28:12'),
(3, 3, '¡Hoy logré levantar 180 kg en peso muerto! ¡Me siento genial!', NULL, NULL, '2024-10-03 14:28:12'),
(4, 4, 'El ejercicio es clave para una vida saludable. ¡A seguir entrenando!', NULL, NULL, '2024-10-03 14:28:12'),
(5, 5, '¡Iniciando mi viaje fitness! Estoy ansioso por ver resultados.', NULL, NULL, '2024-10-03 14:28:12'),
(8, 1, 'siii bloste', NULL, NULL, '2024-10-08 13:28:49'),
(10, 1, 'esto es una prueba', NULL, NULL, '2025-01-23 13:17:18'),
(11, 14, 'bloste', 'uploads/217bd083-abe1-41fe-a277-d230753cf987.jpg', NULL, '2025-01-23 13:20:19'),
(13, 1, 'prueba', NULL, NULL, '2025-03-26 14:13:56');

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
(18, 15, 1, 'hola!', '2025-03-26 15:17:01', 0),
(19, 1, 15, 'hey!', '2025-03-26 15:17:06', 0),
(20, 15, 1, 'work', '2025-03-26 15:17:13', 0),
(21, 1, 15, 'sii', '2025-03-26 15:17:14', 0),
(22, 1, 15, ':)', '2025-03-26 15:17:16', 0);

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
(1, 'imberto', 'albertotriv03@gmail.com', '$2y$10$qGpAcTILtbrp4uVjX7CyfuSH1svYPz1mFwp9Jpx62Mrp6iMpK9zNO', NULL, NULL, '2024-10-03 13:42:30', 'uploads/144DD9A2-F42E-4C7C-B89B-660A2339F521 (1).JPG'),
(2, 'pelaaf', 'pelayo@gmail.com', 'hashed_password1', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default.png'),
(3, 'mandestroyer56', 'user1@example.com', 'hashed_password2', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default.png'),
(4, 'osbo777', 'user2@example.com', 'hashed_password3', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default.png'),
(5, 'bloste', 'user3@example.com', 'hashed_password4', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default.png'),
(6, 'canadre', 'user4@example.com', 'hashed_password5', NULL, NULL, '2024-10-03 14:23:47', 'uploads/default.png'),
(14, 'test', 'test@test.com', '$2y$10$MVoYSHS/g2qnsdAvhIs.2u.pBc2VvfcykCyjVIFz.RSP3/6aB./Me', NULL, NULL, '2025-01-23 13:18:35', 'uploads/historico.PNG'),
(15, 'rodrii12', 'rodrii12@gmail.com', '$2y$10$4JzZpK4iU3/f.OwvBLjIZepg1oksukDo8o1xhNEIEhMKRE1ZL18TS', NULL, NULL, '2025-03-26 14:15:05', 'uploads/FOTO.jpg');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `exercises`
--
ALTER TABLE `exercises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
