<?php
require __DIR__ . '/../config.php';
$title = 'Services';
$services = $pdo->query("SELECT * FROM services ORDER BY name")->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="top"><h1>Services</h1><a class="btn" href="form.php">+ Add service</a></div>
<table>
  <tr><th>Name</th><th>Description</th><th>Price</th><th>Actions</th></tr>
  <?php foreach ($services as $s): ?>
  <tr>
    <td><?= h($s['name']) ?></td>
    <td><?= h($s['description']) ?></td>
    <td><?= money($s['price']) ?></td>
    <td><a class="btn sm" href="form.php?id=<?= $s['service_id'] ?>">Edit</a></td>
  </tr>
  <?php endforeach; if (!$services): ?><tr><td colspan="4" class="muted">No services yet.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
