<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isLoggedIn()) { header('Location: /'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $errors[] = 'Заполните все поля';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM employees WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];
            header('Location: /');
            exit;
        } else {
            $errors[] = 'Неверный email или пароль';
        }
    }
}

$pageTitle = 'Вход';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="auth-container">
    <div class="auth-card">
      <h2>&#128274; Вход в систему</h2>

      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
          <label>Пароль</label>
          <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-blue" style="width:100%;padding:12px;">Войти</button>
      </form>

      <div class="alert alert-info" style="margin-top:16px;font-size:0.85rem;">
        Тестовые аккаунты (пароль для всех: <strong>password123</strong>):<br>
        Администратор: <strong>admin@company.ru</strong><br>
        Сотрудник: <strong>ivanov@company.ru</strong>
      </div>

      <div class="auth-footer">
        Нет аккаунта? <a href="/register.php">Зарегистрироваться</a>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
