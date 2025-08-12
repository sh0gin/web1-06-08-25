-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0
-- Время создания: Авг 09 2025 г., 16:36
-- Версия сервера: 8.0.41
-- Версия PHP: 8.2.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `web1-06-08-25`
--

-- --------------------------------------------------------

--
-- Структура таблицы `File`
--

CREATE TABLE `File` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `extension` varchar(255) NOT NULL,
  `file_id` varchar(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `File`
--

INSERT INTO `File` (`id`, `name`, `extension`, `file_id`) VALUES
(219, 'IMG_4433', 'png', 'Ci5ZrfjpSM'),
(220, 'IMG_4433 (2)', 'png', '0lEJz-_Nw2'),
(221, 'IMG_4433 (3)', 'png', 'aVum0VbJL3'),
(222, 'IMG_4433 (4)', 'png', 'x-CPhcLuqv'),
(223, 'IMG_4433 (5)', 'png', 'Bc90xcA_wQ'),
(224, 'IMG_4433', 'jpg', 'buXbwkO0WO'),
(225, 'IMG_4433 (6)', 'png', '-E5WmdYZQ5'),
(226, 'IMG_4433 (2)', 'jpg', 'Dbv4b1rzfV');

-- --------------------------------------------------------

--
-- Структура таблицы `role`
--

CREATE TABLE `role` (
  `id` int NOT NULL,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `role`
--

INSERT INTO `role` (`id`, `role`) VALUES
(1, 'co-author'),
(2, 'author');

-- --------------------------------------------------------

--
-- Структура таблицы `User`
--

CREATE TABLE `User` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `User`
--

INSERT INTO `User` (`id`, `email`, `password`, `first_name`, `last_name`, `token`) VALUES
(2, 'admin@admin.ru', '$2y$13$wNv2YpNlRmlPKvNJ5rAWeeziz4XkFAJrfnuOTGY0c3fkW9CQIKLSC', 'fsd', 'sd', 'ARgU7K1vn7xHWk8gLelyV0ieZWpwQP4P'),
(3, 'admin2@admin.ru', '$2y$13$yJk4WUouNzabk5F6/Y/nQeOQT9.NTg5.g416cUaJwvDcSCAYZDkGS', 'fsd', 'sd', 'g7xS_cu1H6vtCapcq-HNhWaRTDWGQhRV'),
(4, 'admin3@admin.ru', '$2y$13$EGgwF2.9b9GwROrxSrJ2XOywhvw1.J5ax66NsIOELg9UbpwpGstZC', 'fsd', 'sd', 'W3saapIS3zQZp7pZ_S2etgbPwbFzacNs'),
(5, 'admin4@admin.ru', '$2y$13$8KTurYivUcgp1eZgoztTse7gXj.rnwc09Ee0NuA8jOwJfSlgH.3zS', 'fsd', 'sd', 'sf5-73bGcPxKEEoWY9atHpWlprOwcUov');

-- --------------------------------------------------------

--
-- Структура таблицы `user_access`
--

CREATE TABLE `user_access` (
  `id` int NOT NULL,
  `file_id` int NOT NULL,
  `user_id` int NOT NULL,
  `user_role` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `user_access`
--

INSERT INTO `user_access` (`id`, `file_id`, `user_id`, `user_role`) VALUES
(104, 219, 2, 2),
(105, 220, 2, 2),
(106, 221, 2, 2),
(107, 222, 2, 2),
(108, 223, 2, 2),
(109, 224, 2, 2),
(110, 225, 2, 2),
(111, 226, 2, 2),
(113, 226, 4, 1);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `File`
--
ALTER TABLE `File`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `User`
--
ALTER TABLE `User`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user_access`
--
ALTER TABLE `user_access`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_role` (`user_role`),
  ADD KEY `file_id` (`file_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `File`
--
ALTER TABLE `File`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=227;

--
-- AUTO_INCREMENT для таблицы `role`
--
ALTER TABLE `role`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `User`
--
ALTER TABLE `User`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `user_access`
--
ALTER TABLE `user_access`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `user_access`
--
ALTER TABLE `user_access`
  ADD CONSTRAINT `user_access_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `User` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_access_ibfk_2` FOREIGN KEY (`user_role`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_access_ibfk_3` FOREIGN KEY (`file_id`) REFERENCES `File` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
