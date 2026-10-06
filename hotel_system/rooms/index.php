<?php
require __DIR__ . '/../config.php';
$title = 'Rooms';
$rooms = $pdo->query(
    "SELECT r.*,
       CASE WHEN EXISTS (
         SELECT 1 FROM bookings b
         WHERE b.room_id = r.room_id AND b.status IN ('Booked','Checked-In')
           AND CURDATE() >= b.check_in_date AND CURDATE() < b.check_out_date
       ) THEN 'Occupied' ELSE 'Available' END AS availability
     FROM rooms r ORDER BY r.room_number"
)->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="top"><h1>Rooms</h1><a class="btn" href="form.php">+ Add room</a></div>
<table>
  <tr><th>Room no.</th><th>Type</th><th>Price / night</th><th>Today</th><th>Actions</th></tr>
  <?php foreach ($rooms as $r): ?>
  <tr>
    <td><?= h($r['room_number']) ?></td>
    <td><?= h($r['room_type']) ?></td>
    <td><?= money($r['price_per_night']) ?></td>
    <td><span class="badge b-<?= strtolower($r['availability']) ?>"><?= h($r['availability']) ?></span></td>
    <td>
      <a class="btn sm" href="form.php?id=<?= $r['room_id'] ?>">Edit</a>
      <form class="inline" method="post" action="delete.php" onsubmit="return confirm('Delete this room?')">
        <input type="hidden" name="id" value="<?= $r['room_id'] ?>">
        <button class="btn sm red">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; if (!$rooms): ?><tr><td colspan="5" class="muted">No rooms yet.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
