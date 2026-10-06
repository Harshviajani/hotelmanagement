<?php
require __DIR__ . '/../config.php';
$id = (int)($_GET['id'] ?? 0);
$types = ['Single', 'Double', 'Suite'];
$room = ['room_number' => '', 'room_type' => 'Single', 'price_per_night' => ''];

if ($id) {
    $s = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ?");
    $s->execute([$id]);
    $room = $s->fetch();
    if (!$room) { flash('Room not found.', 'error'); redirect('rooms/index.php'); }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room['room_number']     = trim($_POST['room_number'] ?? '');
    $room['room_type']       = $_POST['room_type'] ?? '';
    $room['price_per_night'] = trim($_POST['price_per_night'] ?? '');

    if ($room['room_number'] === '' || strlen($room['room_number']) > 10) $errors[] = 'Room number is required (max 10 characters).';
    if (!in_array($room['room_type'], $types, true)) $errors[] = 'Choose a valid room type.';
    if (!is_numeric($room['price_per_night']) || $room['price_per_night'] <= 0) $errors[] = 'Price must be greater than 0.';

    if (!$errors) {
        try {
            if ($id) {
                $pdo->prepare("UPDATE rooms SET room_number=?, room_type=?, price_per_night=? WHERE room_id=?")
                    ->execute([$room['room_number'], $room['room_type'], $room['price_per_night'], $id]);
                flash('Room updated.');
            } else {
                $pdo->prepare("INSERT INTO rooms (room_number, room_type, price_per_night) VALUES (?,?,?)")
                    ->execute([$room['room_number'], $room['room_type'], $room['price_per_night']]);
                flash('Room added.');
            }
            redirect('rooms/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) $errors[] = 'That room number already exists.';
            else throw $e;
        }
    }
}
$title = $id ? 'Edit room' : 'Add room';
require __DIR__ . '/../includes/header.php';
?>
<h1><?= h($title) ?></h1>
<?php foreach ($errors as $e): ?><div class="alert error"><?= h($e) ?></div><?php endforeach; ?>
<form method="post" class="card">
  <label>Room number</label>
  <input name="room_number" value="<?= h($room['room_number']) ?>" required maxlength="10">
  <label>Type</label>
  <select name="room_type">
    <?php foreach ($types as $t): ?>
      <option <?= $room['room_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <label>Price per night (₹)</label>
  <input type="number" step="0.01" min="0.01" name="price_per_night" value="<?= h($room['price_per_night']) ?>" required>
  <p><button class="btn">Save</button> <a class="btn grey" href="index.php">Cancel</a></p>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
