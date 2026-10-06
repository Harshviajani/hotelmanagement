<?php
require __DIR__ . '/../config.php';
$title = 'Guests';
$guests = $pdo->query("SELECT * FROM guests ORDER BY first_name, last_name")->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="top"><h1>Guests</h1><a class="btn" href="form.php">+ Register guest</a></div>
<table>
  <tr><th>Name</th><th>Email</th><th>Phone</th><th>Address</th><th>Actions</th></tr>
  <?php foreach ($guests as $g): ?>
  <tr>
    <td><?= h($g['first_name'] . ' ' . $g['last_name']) ?></td>
    <td><?= h($g['email']) ?></td>
    <td><?= h($g['phone']) ?></td>
    <td><?= h($g['address']) ?></td>
    <td><a class="btn sm" href="form.php?id=<?= $g['guest_id'] ?>">Edit</a></td>
  </tr>
  <?php endforeach; if (!$guests): ?><tr><td colspan="5" class="muted">No guests yet.</td></tr><?php endif; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
