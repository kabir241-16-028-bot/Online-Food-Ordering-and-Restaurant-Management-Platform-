<?php
$base = '../'; require '../includes/db.php';
require_admin();
$statuses = ['Pending', 'Preparing', 'On the way', 'Delivered', 'Cancelled'];
$pstatuses = ['Unpaid', 'Pay on Delivery', 'Verification Pending', 'Paid', 'Failed', 'Refunded'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    if (isset($_POST['status']) && in_array($_POST['status'], $statuses)) {
        $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$_POST['status'], $id]);
        flash('Order status updated.');
    }
    if (isset($_POST['pstatus']) && in_array($_POST['pstatus'], $pstatuses)) {
        $pdo->prepare("UPDATE orders SET payment_status=? WHERE id=?")->execute([$_POST['pstatus'], $id]);
        flash('Payment status updated.');
    }
    go('index.php');
}
$stats = [
    'Total Orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'Pending Orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status='Pending'")->fetchColumn(),
    'Payments to Verify' => $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status='Verification Pending'")->fetchColumn(),
    'Revenue (Paid)' => taka($pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status='Paid'")->fetchColumn()),
    'Customers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
];
$orders = $pdo->query("SELECT o.*, u.name uname FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.id DESC")->fetchAll();
$title = 'Admin Dashboard';
include '../includes/header.php';
?>
<h2>📊 Restaurant Dashboard</h2>
<div class="stats">
  <?php foreach ($stats as $k => $v): ?><div class="stat"><span><?= e($k) ?></span><strong><?= e($v) ?></strong></div><?php endforeach; ?>
</div>
<h3>All Orders</h3>
<table>
  <tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Contact</th><th>Order Status</th></tr>
  <?php foreach ($orders as $o): ?>
  <tr>
    <td><?= $o['id'] ?></td>
    <td><?= e($o['uname']) ?><br><small class="muted"><?= e($o['created_at']) ?></small></td>
    <td><?php $i = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $i->execute([$o['id']]);
        foreach ($i->fetchAll() as $it) echo e($it['name']) . ' ×' . $it['qty'] . '<br>'; ?></td>
    <td><?= taka($o['total']) ?></td>
    <td>
      <?= e($o['payment'] ?: '—') ?><?php if ($o['trx_id']): ?><br><small class="muted">Ref/TrxID: <?= e($o['trx_id']) ?></small><?php endif; ?>
      <form method="post" class="inline">
        <input type="hidden" name="id" value="<?= $o['id'] ?>">
        <select name="pstatus" onchange="this.form.submit()">
          <?php foreach ($pstatuses as $s): ?><option <?= $s === $o['payment_status'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
      </form>
    </td>
    <td><?= e($o['phone']) ?><br><small><?= e($o['address']) ?></small></td>
    <td>
      <form method="post" class="inline">
        <input type="hidden" name="id" value="<?= $o['id'] ?>">
        <select name="status" onchange="this.form.submit()">
          <?php foreach ($statuses as $s): ?><option <?= $s === $o['status'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
        </select>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>
