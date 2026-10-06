<?php
require __DIR__ . '/../config.php';
$id = (int)($_GET['id'] ?? 0);
$sv = ['name' => '', 'description' => '', 'price' => ''];

if ($id) {
    $s = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $s->execute([$id]);
    $sv = $s->fetch();
    if (!$sv) { flash('Service not found.', 'error'); redirect('services/index.php'); }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['name', 'description', 'price'] as $k) $sv[$k] = trim($_POST[$k] ?? '');

    if ($sv['name'] === '' || strlen($sv['name']) > 100) $errors[] = 'Service name is required (max 100 characters).';
    if (!is_numeric($sv['price']) || $sv['price'] < 0) $errors[] = 'Price must be 0 or more.';

    if (!$errors) {
        $desc = $sv['description'] === '' ? null : $sv['description'];
        try {
            if ($id) {
                $pdo->prepare("UPDATE services SET name=?, description=?, price=? WHERE service_id=?")
                    ->execute([$sv['name'], $desc, $sv['price'], $id]);
                flash('Service updated.');
            } else {
                $pdo->prepare("INSERT INTO services (name, description, price) VALUES (?,?,?)")
                    ->execute([$sv['name'], $desc, $sv['price']]);
                flash('Service added.');
            }
            redirect('services/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) $errors[] = 'A service with that name already exists.';
            else throw $e;
        }
    }
}
$title = $id ? 'Edit service' : 'Add service';
require __DIR__ . '/../includes/header.php';
?>
<h1><?= h($title) ?></h1>
<?php foreach ($errors as $e): ?><div class="alert error"><?= h($e) ?></div><?php endforeach; ?>
<form method="post" class="card">
  <label>Name</label><input name="name" value="<?= h($sv['name']) ?>" required maxlength="100">
  <label>Description</label><input name="description" value="<?= h($sv['description']) ?>" maxlength="255">
  <label>Price (₹)</label><input type="number" step="0.01" min="0" name="price" value="<?= h($sv['price']) ?>" required>
  <p><button class="btn">Save</button> <a class="btn grey" href="index.php">Cancel</a></p>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
