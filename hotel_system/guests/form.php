<?php
require __DIR__ . '/../config.php';
$id = (int)($_GET['id'] ?? 0);
$g = ['first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'address' => ''];

if ($id) {
    $s = $pdo->prepare("SELECT * FROM guests WHERE guest_id = ?");
    $s->execute([$id]);
    $g = $s->fetch();
    if (!$g) { flash('Guest not found.', 'error'); redirect('guests/index.php'); }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['first_name', 'last_name', 'email', 'phone', 'address'] as $k) $g[$k] = trim($_POST[$k] ?? '');

    if ($g['first_name'] === '' || $g['last_name'] === '') $errors[] = 'First and last name are required.';
    if ($g['phone'] === '' || !preg_match('/^[0-9+\-\s()]{6,20}$/', $g['phone'])) $errors[] = 'Enter a valid phone number.';
    if ($g['email'] !== '' && !filter_var($g['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';

    if (!$errors) {
        $email = $g['email'] === '' ? null : $g['email'];
        $addr  = $g['address'] === '' ? null : $g['address'];
        try {
            if ($id) {
                $pdo->prepare("UPDATE guests SET first_name=?, last_name=?, email=?, phone=?, address=? WHERE guest_id=?")
                    ->execute([$g['first_name'], $g['last_name'], $email, $g['phone'], $addr, $id]);
                flash('Guest updated.');
            } else {
                $pdo->prepare("INSERT INTO guests (first_name, last_name, email, phone, address) VALUES (?,?,?,?,?)")
                    ->execute([$g['first_name'], $g['last_name'], $email, $g['phone'], $addr]);
                flash('Guest registered.');
            }
            redirect('guests/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) $errors[] = 'A guest with that email already exists.';
            else throw $e;
        }
    }
}
$title = $id ? 'Edit guest' : 'Register guest';
require __DIR__ . '/../includes/header.php';
?>
<h1><?= h($title) ?></h1>
<?php foreach ($errors as $e): ?><div class="alert error"><?= h($e) ?></div><?php endforeach; ?>
<form method="post" class="card">
  <label>First name</label><input name="first_name" value="<?= h($g['first_name']) ?>" required>
  <label>Last name</label><input name="last_name" value="<?= h($g['last_name']) ?>" required>
  <label>Email (optional)</label><input type="email" name="email" value="<?= h($g['email']) ?>">
  <label>Phone</label><input name="phone" value="<?= h($g['phone']) ?>" required>
  <label>Address (optional)</label><input name="address" value="<?= h($g['address']) ?>">
  <p><button class="btn">Save</button> <a class="btn grey" href="index.php">Cancel</a></p>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
