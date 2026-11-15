<?php
$base = ''; require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $id = (int)$_POST['add'];
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    flash('Added to cart!');
    go($_SERVER['REQUEST_URI']);
}

$cat = (int)($_GET['cat'] ?? 0);
$q = trim($_GET['q'] ?? '');
$sql = "SELECT f.*, c.name AS cname FROM foods f JOIN categories c ON c.id=f.category_id WHERE f.available=1";
$p = [];
if ($cat) { $sql .= " AND f.category_id=?"; $p[] = $cat; }
if ($q !== '') { $sql .= " AND (f.name LIKE ? OR f.description LIKE ?)"; $p[] = "%$q%"; $p[] = "%$q%"; }
$sql .= " ORDER BY c.id, f.name";
$st = $pdo->prepare($sql); $st->execute($p);
$foods = $st->fetchAll();
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();

$title = 'Menu';
include 'includes/header.php';
?>
<section class="hero">
  <h1>Hungry? Order your favourite food 😋</h1>
  <form method="get" class="search">
    <input type="text" name="q" placeholder="Search food..." value="<?= e($q) ?>">
    <button class="btn">Search</button>
  </form>
</section>

<div class="chips">
  <a class="chip <?= !$cat ? 'active' : '' ?>" href="index.php">All</a>
  <?php foreach ($cats as $c): ?>
    <a class="chip <?= $cat == $c['id'] ? 'active' : '' ?>" href="index.php?cat=<?= $c['id'] ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>

<div class="grid">
<?php foreach ($foods as $f): ?>
  <div class="card">
    <div class="icon"><?= e($f['icon']) ?></div>
    <h3><?= e($f['name']) ?></h3>
    <small class="muted"><?= e($f['cname']) ?></small>
    <p><?= e($f['description']) ?></p>
    <div class="row">
      <strong><?= taka($f['price']) ?></strong>
      <form method="post"><button class="btn" name="add" value="<?= $f['id'] ?>">+ Add</button></form>
    </div>
  </div>
<?php endforeach; ?>
<?php if (!$foods): ?><p>No food found.</p><?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
