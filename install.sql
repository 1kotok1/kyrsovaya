-- ============================================================
--  Сервис бронирования переговорных комнат
--  Загрузите этот файл через phpMyAdmin -> вкладка "Импорт"
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- Таблица: сотрудники
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employees` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(120) NOT NULL,
  `email`      VARCHAR(180) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `role`       ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Таблица: переговорные комнаты
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rooms` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(120) NOT NULL,
  `capacity`    TINYINT UNSIGNED NOT NULL DEFAULT 6,
  `floor`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `description` TEXT DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Таблица: оборудование (привязано к комнате)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `equipment` (
  `id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_id`  INT UNSIGNED NOT NULL,
  `name`     VARCHAR(120) NOT NULL,
  `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Таблица: бронирования
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_id`     INT UNSIGNED NOT NULL,
  `employee_id` INT UNSIGNED NOT NULL,
  `title`       VARCHAR(200) NOT NULL,
  `start_at`    DATETIME NOT NULL,
  `end_at`      DATETIME NOT NULL,
  `status`      ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`room_id`)     REFERENCES `rooms`(`id`)     ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  -- Индекс для быстрой проверки доступности
  INDEX `idx_room_time` (`room_id`, `start_at`, `end_at`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Демо-данные: сотрудники
-- Пароль для всех: password123
-- -----------------------------------------------------------
INSERT INTO `employees` (`name`, `email`, `password`, `department`, `role`) VALUES
('Администратор', 'admin@company.ru', '$2y$10$BLkOjn1RNVclqVluxf2wieh0Oxhpn1SAAejKcrEJZognglGHd2Z.2', 'IT', 'admin'),
('Иванов Алексей',  'ivanov@company.ru',  '$2y$10$BLkOjn1RNVclqVluxf2wieh0Oxhpn1SAAejKcrEJZognglGHd2Z.2', 'Маркетинг', 'user'),
('Петрова Мария',   'petrova@company.ru', '$2y$10$BLkOjn1RNVclqVluxf2wieh0Oxhpn1SAAejKcrEJZognglGHd2Z.2', 'Продажи',   'user'),
('Сидоров Денис',   'sidorov@company.ru', '$2y$10$BLkOjn1RNVclqVluxf2wieh0Oxhpn1SAAejKcrEJZognglGHd2Z.2', 'Разработка','user');

-- -----------------------------------------------------------
-- Демо-данные: комнаты
-- -----------------------------------------------------------
INSERT INTO `rooms` (`name`, `capacity`, `floor`, `description`) VALUES
('Альфа',  8,  1, 'Большой экран, видеоконференция, маркерная доска'),
('Бета',   4,  1, 'Компактная комната для встреч 1:1'),
('Гамма',  12, 2, 'Просторный зал для совещаний и презентаций'),
('Дельта', 6,  2, 'Тихая комната для переговоров'),
('Эпсилон',20, 3, 'Конференц-зал с проектором и звуком');

-- -----------------------------------------------------------
-- Демо-данные: оборудование
-- -----------------------------------------------------------
INSERT INTO `equipment` (`room_id`, `name`, `quantity`) VALUES
(1, 'Проектор',          1),
(1, 'Веб-камера',        1),
(1, 'Маркерная доска',   1),
(1, 'ТВ 65"',            1),
(2, 'Маркерная доска',   1),
(3, 'Проектор',          1),
(3, 'Маркерная доска',   2),
(3, 'Микрофонная стойка',1),
(4, 'ТВ 55"',            1),
(4, 'Веб-камера',        1),
(5, 'Проектор',          2),
(5, 'Звуковая система',  1),
(5, 'Маркерная доска',   2),
(5, 'Веб-камера',        2);

SET FOREIGN_KEY_CHECKS = 1;
