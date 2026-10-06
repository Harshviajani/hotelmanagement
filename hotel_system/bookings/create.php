<?php
require __DIR__ . '/../config.php';
$title = 'New booking';

$guests = $pdo->query("SELECT guest_id, CONCAT(first_name,' ',last_name) AS name, phone FROM guests ORDER BY first_name")->fetchAll();
$rooms  = $pdo->query("SELECT room_id, room_number, room_type, price_per_night FROM rooms ORDER BY room_number")->fetchAll();

$f = ['guest_id' => (int)($_GET['guest_id'] ?? 0), 'room_id' => (int)($_GET['room_id'] ?? 0),
      'check_in' => '', 'check_out' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['guest_id']  = (int)($_POST['guest_id'] ?? 0);
    $f['room_id']   = (int)($_POST['room_id'] ?? 0);
    $f['check_in']  = $_POST['check_in'] ?? '';
    $f['check_out'] = $_POST['check_out'] ?? '';

    if (!$f['guest_id']) $errors[] = 'Choose a guest.';
    if (!$f['room_id'])  $errors[] = 'Choose a room.';
    if (!valid_date($f['check_in']) || !valid_date($f['check_out'])) {
        $errors[] = 'Enter valid check-in and check-out dates.';
    } else {
        if ($f['check_in'] < date('Y-m-d')) $errors[] = 'Check-in date cannot be in the past.';
        if ($f['check_out'] <= $f['check_in']) $errors[] = 'Check-out must be after check-in.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            // Lock the room row so two people can't book the same dates at once
            $s = $pdo->prepare("SELECT price_per_night FROM rooms WHERE room_id = ? FOR UPDATE");
            $s->execute([$f['room_id']]);
            $price = $s->fetchColumn();

            if ($price === false) {
                $errors[] = 'Room not found.';
                $pdo->rollBack();
            } elseif (!room_is_free($pdo, $f['room_id'], $f['check_in'], $f['check_out'])) {
                $errors[] = 'This room is already booked for overlapping dates. Choose other dates or another room.';
                $pdo->rollBack();
            } else {
                $pdo->prepare(
                    "INSERT INTO bookings (guest_id, room_id, check_in_date, check_out_date, status, room_price_per_night)
                     VALUES (?,?,?,?,'Booked',?)"
                )->execute([$f['guest_id'], $f['room_id'], $f['check_in'], $f['check_out'], $price]);
                $newId = $pdo->lastInsertId();
                $pdo->commit();
                flash('Booking created.');
                redirect('bookings/view.php?id=' . $newId);
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
require __DIR__ . '/../includes/header.php';
?>
<h1>New booking</h1>
<?php foreach ($errors as $e): ?><div class="alert error"><?= h($e) ?></div><?php endforeach; ?>
<?php if (!$guests || !$rooms): ?>
  <div class="alert error">You need at least one guest and one room before creating a booking.</div>
<?php endif; ?>
<form method="post" class="card">
  <label>Guest</label>
  <select name="guest_id" required><option value="">-- choose --</option>
    <?php foreach ($guests as $g): ?><option value="<?= $g['guest_id'] ?>" <?= $f['guest_id'] == $g['guest_id'] ? 'selected' : '' ?>><?= h($g['name'] . ' - ' . $g['phone']) ?></option><?php endforeach; ?>
  </select>
  <label>Room</label>
  <select name="room_id" required><option value="">-- choose --</option>
    <?php foreach ($rooms as $r): ?><option value="<?= $r['room_id'] ?>" <?= $f['room_id'] == $r['room_id'] ? 'selected' : '' ?>><?= h($r['room_number'] . ' (' . $r['room_type'] . ') - ' . money($r['price_per_night']) . '/night') ?></option><?php endforeach; ?>
  </select>
  <label>Check-in date</label>
  <input type="date" name="check_in" min="<?= date('Y-m-d') ?>" value="<?= h($f['check_in']) ?>" required>
  <label>Check-out date</label>
  <input type="date" name="check_out" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= h($f['check_out']) ?>" required>
  <p><button class="btn">Book room</button> <a class="btn grey" href="index.php">Cancel</a></p>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
