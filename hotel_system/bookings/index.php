<?php
require __DIR__ . '/../config.php';
$title = 'Bookings';

$guests = $pdo->query("SELECT guest_id, CONCAT(first_name,' ',last_name) AS name FROM guests ORDER BY first_name")->fetchAll();
$rooms  = $pdo->query("SELECT room_id, room_number FROM rooms ORDER BY room_number")->fetchAll();

$fg = (int)($_GET['guest_id'] ?? 0);
$fr = (int)($_GET['room_id'] ?? 0);
$fd = $_GET['check_in'] ?? '';
$fa = !empty($_GET['active']);

$where = []; $params = [];
if ($fg) { $where[] = 'guest_id = ?';      $params[] = $fg; }
if ($fr) { $where[] = 'room_id = ?';       $params[] = $fr; }
if ($fd !== '' && valid_date($fd)) { $where[] = 'check_in_date = ?'; $params[] = $fd; }
if ($fa) { $where[] = "status = 'Checked-In'"; }

$sql = "SELECT * FROM vw_booking_list" . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY check_in_date DESC, booking_id DESC";
$s = $pdo->prepare($sql);
$s->execute($params);
$bookings = $s->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="top"><h1>Bookings</h1><a class="btn" href="create.php">+ New booking</a></div>

<form method="get" class="card filters">
  <div><label>Guest</label>
    <select name="guest_id"><option value="0">All</option>
      <?php foreach ($guests as $g): ?><option value="<?= $g['guest_id'] ?>" <?= $fg == $g['guest_id'] ? 'selected' : '' ?>><?= h($g['name']) ?></option><?php endforeach; ?>
    </select></div>
  <div><label>Room</label>
    <select name="room_id"><option value="0">All</option>
      <?php foreach ($rooms as $r): ?><option value="<?= $r['room_id'] ?>" <?= $fr == $r['room_id'] ? 'selected' : '' ?>><?= h($r['room_number']) ?></option><?php endforeach; ?>
    </select></div>
  <div><label>Check-in date</label><input type="date" name="check_in" value="<?= h($fd) ?>"></div>
  <div><label><input type="checkbox" name="active" value="1" <?= $fa ? 'checked' : '' ?> style="width:auto"> Active only</label></div>
  <div><button class="btn">Filter</button> <a class="btn grey" href="index.php">Reset</a></div>
</form>

<table>
  <tr><th>#</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Nights</th><th>Total</th><th>Status</th><th></th></tr>
  <?php foreach ($bookings as $b): ?>
  <tr>
    <td><?= $b['booking_id'] ?></td>
    <td><?= h($b['guest_name']) ?></td>
    <td><?= h($b['room_number']) ?> <span class="muted">(<?= h($b['room_type']) ?>)</span></td>
    <td><?= h($b['check_in_date']) ?></td>
    <td><?= h($b['check_out_date']) ?></td>
    <td><?= (int)$b['nights'] ?></td>
    <td><?= money($b['total_amount']) ?></td>
    <td><span class="badge b-<?= strtolower($b['status']) ?>"><?= h($b['status']) ?></span>
        <?= $b['is_active'] ? '<strong title="Currently in house">● Active</strong>' : '' ?></td>
    <td><a class="btn sm" href="view.php?id=<?= $b['booking_id'] ?>">Open</a></td>
  </tr>
  <?php endforeach; if (!$bookings): ?><tr><td colspan="9" class="muted">No bookings found.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
