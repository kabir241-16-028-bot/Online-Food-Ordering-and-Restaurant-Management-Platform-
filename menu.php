<?php
$base = '../'; require '../includes/db.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $d = [(int)$_POST['category_id'], trim($_POST['name']), trim($_POST['description']), (float)$_POST['price'],
              trim($_POST['icon']) ?: '🍽️', isset($_POST['available']) ? 1 : 0];
        if ($d[1] === '' || $d[3] <= 0) { flash('Name and a valid price are required.', 'err'); go('menu.php'); }
        if ($id) { $d[] = $id; $pdo->prepare("UPDATE foods SET category_id=?,name=?,description=?,price=?,icon=?,available=? WHERE id=?")->execute($d); flash('Food updated.'); }
        else { $pdo->prepare("INSERT INTO foods(category_id,name,description,price,icon,available) VALUES(?,?,?,?,?,?)")->execute($d); flash('Food added.'); }
    } elseif ($a === 'delete') {
        $pdo->prepare("DELETE FROM foods WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Food deleted.');
    } elseif ($a === 'add_cat') {
        $n = trim($_POST['cname'] ?? '');
        if ($n !== '') { try { $pdo->prepare("INSERT INTO categories(name) VALUES(?)")->execute([$n]); flash('Category added.'); } catch (Exception $e) { flash('Category already exists.', 'err'); } }
    }
    go('menu.php');
}

$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
$foods = $pdo->query("SELECT f.*, c.name cname FROM foods f JOIN categories c ON c.id=f.category_id ORDER BY f.id DESC")->fetchAll();
$edit = ['id' => 0, 'category_id' => $cats[0]['id'] ?? 1, 'name' => '', 'description' => '', 'price' => '', 'icon' => '🍽️', 'available' => 1];
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM foods WHERE id=?"); $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch() ?: $edit;
}
$title = 'Manage Menu';
include '../includes/header.php';
?>
<h2>🍽️ Manage Menu</h2>
<div class="two">
  <form method="post" class="form">
    <h3><?= $edit['id'] ? 'Edit Food' : 'Add New Food' ?></h3>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit['id'] ?>">
    <label>Name<input name="name" value="<?= e($edit['name']) ?>" required></label>
    <label>Category
      <select name="category_id"><?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $edit['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
    </label>
    <label>Description<textarea name="description"><?= e($edit['description']) ?></textarea></label>
    <label>Price (৳)<input type="number" step="1" min="1" name="price" value="<?= e($edit['price']) ?>" required></label>
    <label>Emoji icon<input name="icon" value="<?= e($edit['icon']) ?>"></label>
    <label class="check"><input type="checkbox" name="available" <?= $edit['available'] ? 'checked' : '' ?>> Available</label>
    <button class="btn"><?= $edit['id'] ? 'Update' : 'Add Food' ?></button>
    <?php if ($edit['id']): ?><a href="menu.php" class="btn small">Cancel</a><?php endif; ?>
  </form>
  <form method="post" class="form">
    <h3>Add Category</h3>
    <input type="hidden" name="action" value="add_cat">
    <label>Category name<input name="cname" required></label>
    <button class="btn">Add Category</button>
  </form>
</div>

<table>
  <tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($foods as $f): ?>
  <tr>
    <td><?= e($f['icon']) ?></td><td><?= e($f['name']) ?></td><td><?= e($f['cname']) ?></td>
    <td><?= taka($f['price']) ?></td><td><?= $f['available'] ? 'Available' : 'Hidden' ?></td>
    <td>
      <a class="btn small" href="menu.php?edit=<?= $f['id'] ?>">Edit</a>
      <form method="post" class="inline" onsubmit="return confirm('Delete this item?')">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $f['id'] ?>">
        <button class="btn small danger">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>
