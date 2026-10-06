<?php
require __DIR__ . '/../config.php';
$id = (int)($_GET['id'] ?? 0);

$load = function () use ($pdo, $id) {
    $s = $pdo->prepare("SELECT * FROM vw_booking_list WHERE booking_id = ?");
    $s->execute([$id]);
    return $s->fetch();
};
$b = $load();
if (!$b) { flash('Booking not found.', 'error'); redirect('bookings/index.php'); }

// Allowed status changes
$next = [
    'Booked'      => ['Checked-In', 'Cancelled'],
    'Checked-In'  => ['Checked-Out'],
    'Checked-Out' => [],
    'Cancelled'   => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_service') {
        $sid = (int)($_POST['service_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        if (!in_array($b['status'], ['Booked', 'Checked-In'], true)) {
            flash('Services can only be added to Booked or Checked-In bookings.', 'error');
        } elseif ($sid <= 0 || $qty <= 0) {
            flash('Choose a service and a quantity of at least 1.', 'error');
        } else {
            // Copies the current service price so later price edits don't change this bill
            $ins = $pdo->prepare(
                "INSERT INTO booking_services (booking_id, service_id, quantity, price_at_booking)
                 SELECT ?, service_id, ?, price FROM services WHERE service_id = ?"
            );
            $ins->execute([$id, $qty, $sid]);
            flash($ins->rowCount() ? 'Service added to booking.' : 'Service not found.', $ins->rowCount() ? 'success' : 'error');
        }
    }

    if ($action === 'update_status') {
        $new = $_POST['status'] ?? '';
        if (in_array($new, $next[$b['status']], true)) {
            $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?")->execute([$new, $id]);
            flash("Booking status changed to $new.");
        } else {
            flash('That status change is not allowed.', 'error');
        }
    }
    redirect('bookings/view.php?id=' . $id);
}

$title = 'Booking #' . $id;
$sv = $pdo->prepare(
    "SELECT bs.*, s.name FROM booking_services bs
     JOIN services s ON s.service_id = bs.service_id
     WHERE bs.booking_id = ? ORDER BY bs.added_at"
);
$sv->execute([$id]);
$items = $sv->fetchAll();
$allServices = $pdo->query("SELECT service_id, name, price FROM services ORDER BY name")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="top"><h1>Booking #<?= $id ?></h1><a class="btn grey" href="index.php">← All bookings</a></div>

<div class="row">
  <div class="card">
    <h3>Details</h3>
    <p><b>Guest:</b> <?= h($b['guest_name']) ?></p>
    <p><b>Room:</b> <?= h($b['room_number']) ?> (<?= h($b['room_type']) ?>)</p>
    <p><b>Stay:</b> <?= h($b['check_in_date']) ?> → <?= h($b['check_out_date']) ?> (<?= (int)$b['nights'] ?> nights)</p>
    <p><b>Status:</b> <span class="badge b-<?= strtolower($b['status']) ?>"><?= h($b['status']) ?></span>
       <?= $b['is_active'] ? '<strong>● Active (in house)</strong>' : '' ?></p>
    <p><b>Room cost:</b> <?= money($b['room_cost']) ?><br>
       <b>Services:</b> <?= money($b['services_cost']) ?><br>
       <b>Total:</b> <?= money($b['total_amount']) ?></p>

    <?php if ($next[$b['status']]): ?>
    <form method="post">
      <input type="hidden" name="action" value="update_status">
      <label>Update status</label>
      <select name="status">
        <?php foreach ($next[$b['status']] as $st): ?><option><?= $st ?></option><?php endforeach; ?>
      </select>
      <p><button class="btn">Update</button></p>
    </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <h3>Services</h3>
    <?php if ($items): ?>
    <table><tr><th>Service</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
      <?php foreach ($items as $it): ?>
      <tr><td><?= h($it['name']) ?></td><td><?= (int)$it['quantity'] ?></td>
          <td><?= money($it['price_at_booking']) ?></td>
          <td><?= money($it['price_at_booking'] * $it['quantity']) ?></td></tr>
      <?php endforeach; ?></table>
    <?php else: ?><p class="muted">No services added.</p><?php endif; ?>

    <?php if (in_array($b['status'], ['Booked', 'Checked-In'], true)): ?>
    <form method="post">
      <input type="hidden" name="action" value="add_service">
      <label>Add service</label>
      <select name="service_id" required><option value="">-- choose --</option>
        <?php foreach ($allServices as $s): ?><option value="<?= $s['service_id'] ?>"><?= h($s['name'] . ' - ' . money($s['price'])) ?></option><?php endforeach; ?>
      </select>
      <label>Quantity</label>
      <input type="number" name="quantity" min="1" value="1">
      <p><button class="btn">Add to booking</button></p>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
