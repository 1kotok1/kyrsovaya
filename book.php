<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$db     = getDB();
$errors = [];
$success = '';

// Предзаполнение из GET (клик по ячейке сетки)
$preRoom = (int)($_GET['room_id'] ?? 0);
$preDate = $_GET['date'] ?? date('Y-m-d');
$preTime = $_GET['time'] ?? '09:00';

$rooms = $db->query("SELECT * FROM rooms ORDER BY floor, name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId  = (int)($_POST['room_id'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $dateVal = trim($_POST['date'] ?? '');
    $startT  = trim($_POST['start_time'] ?? '');
    $endT    = trim($_POST['end_time'] ?? '');

    // --- Базовая валидация ---
    if (!$roomId)  $errors[] = 'Выберите комнату';
    if (!$title)   $errors[] = 'Введите название встречи';
    if (!$dateVal) $errors[] = 'Выберите дату';
    if (!$startT || !$endT) $errors[] = 'Укажите время';

    if (!$errors) {
        $startDt = $dateVal . ' ' . $startT . ':00';
        $endDt   = $dateVal . ' ' . $endT   . ':00';

        if ($startDt >= $endDt) {
            $errors[] = 'Время окончания должно быть позже времени начала';
        }
        if (strtotime($startDt) < time() - 60) {
            $errors[] = 'Нельзя бронировать прошедшее время';
        }
        $startH = (int)substr($startT, 0, 2);
        $endH   = (int)substr($endT, 0, 2);
        $endM   = (int)substr($endT, 3, 2);
        if ($startH < 8 || $endH > 20 || ($endH === 20 && $endM > 0)) {
            $errors[] = 'Бронирование доступно с 08:00 до 20:00';
        }
    }

    // --- Валидация пересечений (ключевая фишка) ---
    if (!$errors) {
        $check = $db->prepare("
            SELECT b.id, b.title, b.start_at, b.end_at, e.name AS emp_name
            FROM bookings b
            JOIN employees e ON b.employee_id = e.id
            WHERE b.room_id = ?
              AND b.status  = 'active'
              AND b.start_at < ?
              AND b.end_at   > ?
        ");
        $check->execute([$roomId, $endDt, $startDt]);
        $conflict = $check->fetch();

        if ($conflict) {
            $errors[] = sprintf(
                'Комната уже занята с %s до %s ("%s", %s). Выберите другое время.',
                date('H:i', strtotime($conflict['start_at'])),
                date('H:i', strtotime($conflict['end_at'])),
                htmlspecialchars($conflict['title']),
                htmlspecialchars($conflict['emp_name'])
            );
        }
    }

    // --- Сохраняем ---
    if (!$errors) {
        $ins = $db->prepare("
            INSERT INTO bookings (room_id, employee_id, title, start_at, end_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ins->execute([$roomId, currentUserId(), $title, $startDt, $endDt]);
        header('Location: /?date=' . $dateVal);
        exit;
    }
}

$pageTitle = 'Новое бронирование';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <div class="auth-card" style="max-width:540px;">
    <h2>&#128197; Забронировать комнату</h2>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-error"><?= $e ?></div>
    <?php endforeach; ?>

    <form method="POST" id="bookForm">
      <div class="form-group">
        <label>Комната</label>
        <select name="room_id" required onchange="loadBusy()">
          <option value="">— выберите —</option>
          <?php foreach ($rooms as $r): ?>
            <option value="<?= $r['id'] ?>"
              <?= ($preRoom === (int)$r['id'] || (int)($_POST['room_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($r['name']) ?> (этаж <?= $r['floor'] ?>, до <?= $r['capacity'] ?> чел.)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Название встречи</label>
        <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
               placeholder="Напр.: Планёрка отдела" required maxlength="200">
      </div>

      <div class="form-group">
        <label>Дата</label>
        <input type="date" name="date" id="bookDate"
               value="<?= htmlspecialchars($_POST['date'] ?? $preDate) ?>"
               min="<?= date('Y-m-d') ?>" required onchange="loadBusy()">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Начало</label>
          <input type="time" name="start_time" id="startTime"
                 value="<?= htmlspecialchars($_POST['start_time'] ?? $preTime) ?>"
                 min="08:00" max="19:30" step="1800" required onchange="loadBusy()">
        </div>
        <div class="form-group">
          <label>Конец</label>
          <input type="time" name="end_time" id="endTime"
                 value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>"
                 min="08:30" max="20:00" step="1800" required>
        </div>
      </div>

      <!-- Занятые слоты для выбранной комнаты/даты -->
      <div id="busy-info" class="busy-info" style="display:none;"></div>

      <button type="submit" class="btn btn-blue" style="width:100%;padding:12px;margin-top:8px;">
        Забронировать
      </button>
    </form>
    <div style="margin-top:12px;"><a href="/">&#8592; К сетке</a></div>
  </div>
</div>
<script>
const BASE = '/api/check.php';
function loadBusy() {
    const roomId = document.querySelector('[name=room_id]').value;
    const date   = document.getElementById('bookDate').value;
    if (!roomId || !date) return;
    fetch(`${BASE}?room_id=${roomId}&date=${date}`)
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById('busy-info');
            if (!data.bookings || data.bookings.length === 0) {
                box.style.display = 'none';
                return;
            }
            box.style.display = 'block';
            box.innerHTML = '<strong>Уже занято:</strong><ul>' +
                data.bookings.map(b =>
                    `<li>${b.start_at.slice(11,16)}–${b.end_at.slice(11,16)} — ${b.title} (${b.emp_name})</li>`
                ).join('') + '</ul>';
        });
}
document.addEventListener('DOMContentLoaded', loadBusy);
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
