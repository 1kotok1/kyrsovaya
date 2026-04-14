<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$roomId = (int)($_GET['room_id'] ?? 0);
$date   = $_GET['date'] ?? date('Y-m-d');

if (!$roomId) {
    echo json_encode(['bookings' => []]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("
    SELECT b.id, b.title, b.start_at, b.end_at, e.name AS emp_name
    FROM bookings b
    JOIN employees e ON b.employee_id = e.id
    WHERE b.room_id = ?
      AND DATE(b.start_at) = ?
      AND b.status = 'active'
    ORDER BY b.start_at
");
$stmt->execute([$roomId, $date]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['bookings' => $bookings]);
