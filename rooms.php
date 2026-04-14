<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$db = getDB();
$rooms = $db->query("SELECT * FROM rooms ORDER BY floor, name")->fetchAll();

$equipment = [];
$eStmt = $db->query("SELECT * FROM equipment ORDER BY room_id, name");
foreach ($eStmt->fetchAll() as $eq) {
    $equipment[$eq['room_id']][] = $eq;
}

// Ближайшее бронирование каждой комнаты
$nextBook = [];
$nbStmt = $db->query("
    SELECT b.room_id, b.title, b.start_at, b.end_at, e.name AS emp_name
    FROM bookings b
    JOIN employees e ON b.employee_id = e.id
    WHERE b.status = 'active' AND b.end_at > NOW()
    ORDER BY b.start_at
");
foreach ($nbStmt->fetchAll() as $nb) {
    if (!isset($nextBook[$nb['room_id']])) {
        $nextBook[$nb['room_id']] = $nb;
    }
}

$pageTitle = 'Переговорные комнаты';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <h1 class="page-title">&#127970; Переговорные комнаты</h1>

  <?php
  $floorGroups = [];
  foreach ($rooms as $r) $floorGroups[$r['floor']][] = $r;
  ksort($floorGroups);
  ?>

  <?php foreach ($floorGroups as $floor => $floorRooms): ?>
    <h2 class="floor-heading">Этаж <?= $floor ?></h2>
    <div class="rooms-grid">
      <?php foreach ($floorRooms as $room): ?>
        <?php
          $eqs  = $equipment[$room['id']] ?? [];
          $nb   = $nextBook[$room['id']] ?? null;
          $busy = $nb && date('Y-m-d H:i:s') >= $nb['start_at'] && date('Y-m-d H:i:s') < $nb['end_at'];
        ?>
        <div class="room-card <?= $busy ? 'room-busy' : 'room-free' ?>" id="room-<?= $room['id'] ?>">
          <div class="room-card-header">
            <span class="room-name"><?= htmlspecialchars($room['name']) ?></span>
            <span class="room-status <?= $busy ? 'status-busy' : 'status-free' ?>">
              <?= $busy ? '&#128308; Занято' : '&#128994; Свободно' ?>
            </span>
          </div>
          <div class="room-cap-line">&#128100; Вместимость: <strong><?= $room['capacity'] ?> человек</strong></div>
          <?php if ($room['description']): ?>
            <p class="room-desc"><?= htmlspecialchars($room['description']) ?></p>
          <?php endif; ?>

          <?php if ($eqs): ?>
            <div class="room-equipment">
              <strong>Оборудование:</strong>
              <ul>
                <?php foreach ($eqs as $eq): ?>
                  <li><?= htmlspecialchars($eq['name']) ?><?= $eq['quantity'] > 1 ? ' &times; ' . $eq['quantity'] : '' ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if ($nb): ?>
            <div class="room-next-booking">
              <?php if ($busy): ?>
                &#128308; Занято до <?= date('H:i', strtotime($nb['end_at'])) ?>: «<?= htmlspecialchars($nb['title']) ?>»
              <?php else: ?>
                &#128336; Следующее: <?= date('d.m H:i', strtotime($nb['start_at'])) ?> – «<?= htmlspecialchars($nb['title']) ?>»
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if (isLoggedIn()): ?>
            <a href="/book.php?room_id=<?= $room['id'] ?>" class="btn btn-blue" style="margin-top:12px;">
              Забронировать
            </a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
