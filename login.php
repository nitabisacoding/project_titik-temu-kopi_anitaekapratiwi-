<?php
require 'includes/functions.php';
if (user()) redirect('dashboard.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $un = trim($_POST['username'] ?? ''); $pw = $_POST['password'] ?? '';
    if ($un === '' || $pw === '') $err = 'Username dan password wajib diisi.';
    else {
        $st = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $st->execute([$un]);
        $row = $st->fetch();
        if (!$row) $err = 'Username tidak ditemukan.';
        elseif (!password_verify($pw, $row['password_hash'])) $err = 'Password tidak cocok.';
        elseif ($row['status'] !== 'aktif') $err = 'Akun Anda dinonaktifkan. Hubungi manager.';
        else {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$row['id'], 'username' => $row['username'], 'nama_lengkap' => $row['nama_lengkap'], 'role' => $row['role']];
            redirect('dashboard.php');
        }
    }
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login - <?= APP_NAME ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body class="loginbg"><form class="card login" method="post" autocomplete="off">
<h2>☕ <?= APP_NAME ?></h2><p class="muted">Masuk ke sistem kasir</p>
<?php show_flash(); if ($err) echo '<div class="alert error">' . e($err) . '</div>'; ?>
<?= csrf_field() ?>
<label>Username<input name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus></label>
<label>Password<input type="password" name="password" required></label>
<button class="btn" style="width:100%">Masuk</button>
</form></body></html>
