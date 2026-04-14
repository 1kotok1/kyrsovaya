<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    // Можно отменить только своё (или admin — любое)
    if (isAdmin()) {
        $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? AND employee_id = ?");
        $stmt->execute([$id, currentUserId()]);
    }
    $b = $stmt->fetch();

    if ($b && $b['status'] === 'active') {
        $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")
           ->execute([$id]);
    }
}

$ref = $_SERVER['HTTP_REFERER'] ?? '/my-bookings.php';
header('Location: ' . $ref);
exit;
