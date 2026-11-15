<?php
$base = ''; require 'includes/db.php';
require_login();
$st = $pdo->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
$st->execute([user()['id']]);
$orders = $st->fetchAll();
$title = 'My Orders';
include 'includes/header.php';
?>
<h2>📦 My Orders</h2>
<?php foreach ($orders as $o): ?>
  <div class="card wide">
    <div class="row">
      <strong>Order #<?= $o['id'] ?></strong>
      <span class="badge <?= slug($o['status']) ?>"><?= e($o['status']) ?></span>
    </div>
    <small class="muted"><?= e($o['created_at']) ?></small>
    <ul>
    <?php $i = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $i->execute([$o['id']]);
      foreach ($i->fetchAll() as $it): ?>
      <li><?= e($it['name']) ?> × <?= $it['qty'] ?> — <?= taka($it['price'] * $it['qty']) ?></li>
    <?php endforeach; ?>
    </ul>
    <div class="row">
      <strong>Total: <?= taka($o['total']) ?></strong>
      <span>
        <?= e($o['payment'] ?: 'No method chosen') ?>
        <span class="badge <?= slug($o['payment_status']) ?>"><?= e($o['payment_status']) ?></span>
        <?php if ($o['trx_id']): ?><small class="muted">Ref: <?= e($o['trx_id']) ?></small><?php endif; ?>
      </span>
    </div>
    <?php if ($o['payment_status'] === 'Unpaid' && $o['status'] !== 'Cancelled'): ?>
      <p><a class="btn small" href="pay.php?id=<?= $o['id'] ?>">Pay Now</a></p>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
<?php if (!$orders): ?><p>No orders yet. <a href="index.php">Order now</a></p><?php endif; ?>
<?php include 'includes/footer.php'; ?>
