<?php
// ====== SETTINGS (change these) ======
define('BKASH_NUMBER', '01700-000000');   // your restaurant's bKash personal/merchant number
define('SITE_NAME', 'FoodHub');
// Database (SQLite) + helper functions. Auto-creates database on first run.
if (session_status() === PHP_SESSION_NONE) session_start();
$base = $base ?? '';

$dbFile = __DIR__ . '/../data/app.db';
if (!is_dir(dirname($dbFile))) mkdir(dirname($dbFile), 0777, true);
$isNew = !file_exists($dbFile);

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if ($isNew) {
    $pdo->exec("
    CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE, password TEXT,
        role TEXT DEFAULT 'customer', phone TEXT, address TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE categories(id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE);
    CREATE TABLE foods(id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER, name TEXT, description TEXT,
        price REAL, icon TEXT DEFAULT '🍽️', available INTEGER DEFAULT 1);
    CREATE TABLE orders(id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, total REAL, status TEXT DEFAULT 'Pending',
        address TEXT, phone TEXT, payment TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE order_items(id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, food_id INTEGER, name TEXT, price REAL, qty INTEGER);
    ");
    $pdo->prepare("INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)")
        ->execute(['Admin', 'admin@food.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    $pdo->prepare("INSERT INTO users(name,email,password,role,phone,address) VALUES(?,?,?,?,?,?)")
        ->execute(['Demo Customer', 'user@food.com', password_hash('user123', PASSWORD_DEFAULT), 'customer', '01700000000', 'Dhaka']);
    foreach (['Burgers', 'Pizza', 'Rice & Curry', 'Drinks', 'Desserts'] as $c)
        $pdo->prepare("INSERT INTO categories(name) VALUES(?)")->execute([$c]);
    $foods = [
        [1, 'Classic Beef Burger', 'Juicy beef patty, cheese, lettuce, special sauce', 280, '🍔'],
        [1, 'Chicken Zinger', 'Crispy fried chicken with mayo', 240, '🍗'],
        [2, 'Margherita Pizza', 'Tomato, mozzarella and fresh basil', 450, '🍕'],
        [2, 'BBQ Chicken Pizza', 'BBQ sauce, chicken, onion, cheese', 550, '🍕'],
        [3, 'Kacchi Biryani', 'Traditional mutton kacchi with borhani', 380, '🍛'],
        [3, 'Chicken Curry & Rice', 'Home style chicken curry with steamed rice', 220, '🍚'],
        [4, 'Cold Coffee', 'Iced coffee with cream', 150, '☕'],
        [4, 'Fresh Lemonade', 'Mint lemonade', 80, '🍹'],
        [5, 'Chocolate Brownie', 'Warm brownie with chocolate sauce', 180, '🍫'],
        [5, 'Vanilla Ice Cream', 'Two scoops of vanilla', 120, '🍨'],
    ];
    $st = $pdo->prepare("INSERT INTO foods(category_id,name,description,price,icon) VALUES(?,?,?,?,?)");
    foreach ($foods as $f) $st->execute($f);
}

// Auto-upgrade older database (adds payment columns if missing)
$cols = array_column($pdo->query("PRAGMA table_info(orders)")->fetchAll(), 'name');
if (!in_array('payment_status', $cols)) $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status TEXT DEFAULT 'Unpaid'");
if (!in_array('trx_id', $cols)) $pdo->exec("ALTER TABLE orders ADD COLUMN trx_id TEXT");

function luhn_ok($n) {
    $sum = 0; $alt = false;
    for ($i = strlen($n) - 1; $i >= 0; $i--) {
        $d = (int)$n[$i];
        if ($alt) { $d *= 2; if ($d > 9) $d -= 9; }
        $sum += $d; $alt = !$alt;
    }
    return $sum % 10 === 0;
}
function slug($s) { return strtolower(str_replace(' ', '-', $s)); }

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function user() { return $_SESSION['user'] ?? null; }
function flash($msg, $type = 'ok') { $_SESSION['flash'] = [$msg, $type]; }
function go($url) { header('Location: ' . $url); exit; }
function require_login() { global $base; if (!user()) { flash('Please login first.', 'err'); go($base . 'login.php'); } }
function require_admin() { global $base; if (!user() || user()['role'] !== 'admin') { flash('Admin access only.', 'err'); go($base . 'login.php'); } }
function taka($n) { return '৳' . number_format((float)$n, 0); }
