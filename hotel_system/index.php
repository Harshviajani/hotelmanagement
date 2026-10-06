<?php
require __DIR__ . '/config.php';
$title = 'Dashboard';

$one = fn($sql) => $pdo->query($sql)->fetchColumn();
$stats = [
    'Total rooms'      => $one("SELECT COUNT(*) FROM rooms"),
    'Occupied today'   => $one("SELECT COUNT(DISTINCT room_id) FROM bookings
                                WHERE status IN ('Booked','Checked-In')
                                  AND CURDATE() >= check_in_date AND CURDATE() < check_out_date"),
    'Total guests'     => $one("SELECT COUNT(*) FROM guests"),
    "Today's check-ins"  => $one("SELECT COUNT(*) FROM bookings WHERE check_in_date = CURDATE() AND status <> 'Cancelled'"),
    "Today's check-outs" => $one("SELECT COUNT(*) FROM bookings WHERE check_out_date = CURDATE() AND status <> 'Cancelled'"),
];
$ins  = $pdo->query("SELECT * FROM vw_booking_list WHERE check_in_date = CURDATE() AND status <> 'Cancelled'")->fetchAll();
$outs = $pdo->query("SELECT * FROM vw_booking_list WHERE check_out_date = CURDATE() AND status <> 'Cancelled'")->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<h1>Dashboard</h1>
<div class="stats">
<?php foreach ($stats as $label => $val): ?>
  <div class="stat"><b><?= (int)$val ?></b><?= h($label) ?></div>
<?php endforeach; ?>
</div>

<div class="row">
  <div class="card">
    <h3>Today's check-ins</h3>
    <?php if (!$ins): ?><p class="muted">None.</p><?php else: ?>
    <table><tr><th>Guest</th><th>Room</th><th>Status</th></tr>
    <?php foreach ($ins as $b): ?>
      <tr><td><a href="bookings/view.php?id=<?= $b['booking_id'] ?>"><?= h($b['guest_name']) ?></a></td>
          <td><?= h($b['room_number']) ?></td>
          <td><span class="badge b-<?= strtolower($b['status']) ?>"><?= h($b['status']) ?></span></td></tr>
    <?php endforeach; ?></table><?php endif; ?>
  </div>
  <div class="card">
    <h3>Today's check-outs</h3>
    <?php if (!$outs): ?><p class="muted">None.</p><?php else: ?>
    <table><tr><th>Guest</th><th>Room</th><th>Status</th></tr>
    <?php foreach ($outs as $b): ?>
      <tr><td><a href="bookings/view.php?id=<?= $b['booking_id'] ?>"><?= h($b['guest_name']) ?></a></td>
          <td><?= h($b['room_number']) ?></td>
          <td><span class="badge b-<?= strtolower($b['status']) ?>"><?= h($b['status']) ?></span></td></tr>
    <?php endforeach; ?></table><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
