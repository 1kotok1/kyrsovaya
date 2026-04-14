<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Переговорные комнаты') ?></title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <a href="/" class="logo">&#128196; МоиКомнаты</a>
    <nav class="nav">
      <a href="/">Сетка бронирования</a>
      <a href="/rooms.php">Комнаты</a>
      <?php if (isLoggedIn()): ?>
        <a href="/my-bookings.php">Мои брони</a>
        <a href="/book.php" class="btn-nav">+ Забронировать</a>
        <span class="nav-user">&#128100; <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
        <?php if (isAdmin()): ?>
          <a href="/admin/" class="nav-admin">&#9881; Админ</a>
        <?php endif; ?>
        <a href="/logout.php" class="nav-logout">Выйти</a>
      <?php else: ?>
        <a href="/login.php">Войти</a>
        <a href="/register.php" class="btn-nav">Регистрация</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="main-content">
