<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getDB();

// День для отображения
$dateStr = $_GET['date'] ?? date('Y-m-d');
$date    = date('Y-m-d', strtotime($dateStr));
$prev    = date('Y-m-d', strtotime($date . ' -1 day'));
$next    = date('Y-m-d', strtotime($date . ' +1 day'));
$today   = date('Y-m-d');

// Часы работы: 08:00 – 20:00 по 30 мин
$slots = [];
for ($h = 8; $h < 20; $h++) {
    $slots[] = sprintf('%02d:00', $h);
    $slots[] = sprintf('%02d:30', $h);
}

// Все комнаты
$rooms = $db->query("SELECT * FROM rooms ORDER BY floor, name")->fetchAll();

// Бронирования на выбранный день
$stmt = $db->prepare("
    SELECT b.*, e.name AS emp_name, r.name AS room_name
    FROM bookings b
    JOIN employees e ON b.employee_id = e.id
    JOIN rooms r ON b.room_id = r.id
    WHERE DATE(b.start_at) = ? AND b.status = 'active'
    ORDER BY b.start_at
");
$stmt->execute([$date]);
$bookings = $stmt->fetchAll();

// Индексируем: [room_id][slot] = booking
$grid = [];
foreach ($rooms as $room) {
    $grid[$room['id']] = [];
}
foreach ($bookings as $b) {
    $startTs = strtotime($b['start_at']);
    $endTs   = strtotime($b['end_at']);
    foreach ($slots as $slot) {
        $slotTs = strtotime($date . ' ' . $slot);
        if ($slotTs >= $startTs && $slotTs < $endTs) {
            $grid[$b['room_id']][$slot] = $b;
        }
    }
}

$pageTitle = 'Сетка бронирования — ' . date('d.m.Y', strtotime($date));
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">

  <div class="grid-toolbar">
    <a href="?date=<?= $prev ?>" class="btn-nav-day">&#8592; Пред. день</a>
    <div class="grid-date">
      <?php
        $dayNames = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
        $dow = (int)date('w', strtotime($date));
        echo $dayNames[$dow] . ', ' . date('d.m.Y', strtotime($date));
        if ($date === $today) echo ' <span class="badge-today">Сегодня</span>';
      ?>
    </div>
    <a href="?date=<?= $next ?>" class="btn-nav-day">След. день &#8594;</a>
    <?php if ($date !== $today): ?>
      <a href="?" class="btn-today">Сегодня</a>
    <?php endif; ?>
  </div>

  <div class="grid-legend">
    <span class="legend-free">Свободно</span>
    <span class="legend-busy">Занято</span>
    <span class="legend-mine">Моё бронирование</span>
  </div>

  <div class="booking-grid-wrap">
    <table class="booking-grid">
      <thead>
        <tr>
          <th class="time-col">Время</th>
          <?php foreach ($rooms as $room): ?>
            <th>
              <a href="/rooms.php#room-<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?></a>
              <div class="room-cap">&#128100; до <?= $room['capacity'] ?> чел.</div>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($slots as $slot): ?>
        <tr>
          <td class="time-cell"><?= $slot ?></td>
          <?php foreach ($rooms as $room): ?>
            <?php
              $b   = $grid[$room['id']][$slot] ?? null;
              $mine = $b && isLoggedIn() && $b['employee_id'] == currentUserId();
              $cls  = $b ? ($mine ? 'cell-mine' : 'cell-busy') : 'cell-free';
              // Показываем название только в первом слоте бронирования
              $isFirst = $b && date('H:i', strtotime($b['start_at'])) === $slot;
              $slotTs  = strtotime($date . ' ' . $slot);
              $isPast  = $slotTs < time() && $date <= $today;
            ?>
            <td class="grid-cell <?= $cls ?> <?= $isPast ? 'cell-past' : '' ?>">
              <?php if ($b): ?>
                <?php if ($isFirst): ?>
                  <div class="booking-pill" title="<?= htmlspecialchars($b['title']) ?> — <?= htmlspecialchars($b['emp_name']) ?>">
                    <span class="pill-title"><?= htmlspecialchars($b['title']) ?></span>
                    <span class="pill-time"><?= date('H:i', strtotime($b['start_at'])) ?>–<?= date('H:i', strtotime($b['end_at'])) ?></span>
                    <?php if ($mine): ?>
                      <a href="/cancel.php?id=<?= $b['id'] ?>" class="pill-cancel" onclick="return confirm('Отменить бронирование?')">&#10005;</a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              <?php elseif (!$isPast && isLoggedIn()): ?>
                <a href="/book.php?room_id=<?= $room['id'] ?>&date=<?= $date ?>&time=<?= $slot ?>" class="cell-book-link">+</a>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (!isLoggedIn()): ?>
    <div class="alert alert-info" style="margin-top:24px;">
      Чтобы создать бронирование, <a href="/login.php"><strong>войдите</strong></a> или <a href="/register.php"><strong>зарегистрируйтесь</strong></a>.
    </div>
  <?php endif; ?>

</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
