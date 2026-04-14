<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$db = getDB();

$filter = $_GET['filter'] ?? 'upcoming';

if ($filter === 'past') {
    $where = "b.end_at < NOW()";
} elseif ($filter === 'all') {
    $where = "1=1";
} else {
    $filter = 'upcoming';
    $where = "b.end_at >= NOW() AND b.status = 'active'";
}

$stmt = $db->prepare("
    SELECT b.*, r.name AS room_name, r.floor
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.employee_id = ? AND $where
    ORDER BY b.start_at " . ($filter === 'past' ? 'DESC' : 'ASC')
);
$stmt->execute([currentUserId()]);
$bookings = $stmt->fetchAll();

$pageTitle = 'Мои бронирования';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <h1 class="page-title">&#128197; Мои бронирования</h1>

  <div class="filter-tabs">
    <a href="?filter=upcoming" class="<?= $filter === 'upcoming' ? 'active' : '' ?>">Предстоящие</a>
    <a href="?filter=past"     class="<?= $filter === 'past'     ? 'active' : '' ?>">Прошедшие</a>
    <a href="?filter=all"      class="<?= $filter === 'all'      ? 'active' : '' ?>">Все</a>
  </div>

  <?php if (empty($bookings)): ?>
    <div class="empty-state">
      <div class="icon">&#128197;</div>
      <p>Бронирований нет.</p>
      <a href="/book.php" class="btn btn-blue">Создать бронирование</a>
    </div>
  <?php else: ?>
    <div class="bookings-list">
      <?php foreach ($bookings as $b):
        $isPast      = strtotime($b['end_at']) < time();
        $isCancelled = $b['status'] === 'cancelled';
        $isNow       = strtotime($b['start_at']) <= time() && strtotime($b['end_at']) >= time();
      ?>
        <div class="booking-card <?= $isCancelled ? 'cancelled' : ($isPast ? 'past' : ($isNow ? 'now' : '')) ?>">
          <div class="booking-card-header">
            <span class="booking-title"><?= htmlspecialchars($b['title']) ?></span>
            <?php if ($isCancelled): ?>
              <span class="badge badge-cancelled">Отменено</span>
            <?php elseif ($isNow): ?>
              <span class="badge badge-now">Сейчас</span>
            <?php elseif ($isPast): ?>
              <span class="badge badge-past">Прошло</span>
            <?php else: ?>
              <span class="badge badge-active">Активно</span>
            <?php endif; ?>
          </div>
          <div class="booking-card-meta">
            <span>&#127970; <?= htmlspecialchars($b['room_name']) ?> (эт. <?= $b['floor'] ?>)</span>
            <span>&#128197; <?= date('d.m.Y', strtotime($b['start_at'])) ?></span>
            <span>&#128336; <?= date('H:i', strtotime($b['start_at'])) ?> – <?= date('H:i', strtotime($b['end_at'])) ?></span>
          </div>
          <?php if (!$isCancelled && !$isPast): ?>
            <div class="booking-card-actions">
              <a href="/cancel.php?id=<?= $b['id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Отменить бронирование «<?= htmlspecialchars($b['title']) ?>»?')">
                Отменить
              </a>
              <a href="/?date=<?= date('Y-m-d', strtotime($b['start_at'])) ?>" class="btn btn-ghost btn-sm">
                В сетке
              </a>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
