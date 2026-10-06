<?php
require __DIR__ . '/../config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('rooms/index.php');
$id = (int)($_POST['id'] ?? 0);

// Rule: only delete if the room has no active/upcoming bookings
$s = $pdo->prepare(
    "SELECT COUNT(*) FROM bookings
     WHERE room_id = ? AND status IN ('Booked','Checked-In') AND check_out_date >= CURDATE()"
);
$s->execute([$id]);

if ($s->fetchColumn() > 0) {
    flash('Cannot delete: this room has active or upcoming bookings.', 'error');
} else {
    try {
        $pdo->prepare("DELETE FROM rooms WHERE room_id = ?")->execute([$id]);
        flash('Room deleted.');
    } catch (PDOException $e) {
        // Foreign key blocks rooms that still appear in past booking records
        flash('Cannot delete: this room is part of past booking history.', 'error');
    }
}
redirect('rooms/index.php');
