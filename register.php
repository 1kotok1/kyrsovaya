<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (isLoggedIn()) { header('Location: /'); exit; }

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm'] ?? '';

    if (!$name)                                        $errors[] = 'Введите имя';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Введите корректный email';
    if (strlen($password) < 6)                        $errors[] = 'Пароль не менее 6 символов';
    if ($password !== $confirm)                       $errors[] = 'Пароли не совпадают';

    if (!$errors) {
        $db    = getDB();
        $check = $db->prepare("SELECT id FROM employees WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'Пользователь с таким email уже зарегистрирован';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $db->prepare("INSERT INTO employees (name, email, password, department) VALUES (?, ?, ?, ?)");
            $ins->execute([$name, $email, $hash, $department]);
            $success = 'Регистрация прошла успешно! <a href="/login.php">Войдите</a>.';
        }
    }
}

$pageTitle = 'Регистрация';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="auth-container">
    <div class="auth-card">
      <h2>&#128221; Регистрация</h2>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST">
        <div class="form-group">
          <label>Имя и фамилия</label>
          <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Отдел</label>
          <input type="text" name="department" value="<?= htmlspecialchars($_POST['department'] ?? '') ?>" placeholder="напр.: Маркетинг">
        </div>
        <div class="form-group">
          <label>Пароль</label>
          <input type="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
          <label>Подтверждение пароля</label>
          <input type="password" name="confirm" required>
        </div>
        <button type="submit" class="btn btn-blue" style="width:100%;padding:12px;">Зарегистрироваться</button>
      </form>
      <div class="auth-footer">
        Уже есть аккаунт? <a href="/login.php">Войти</a>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
