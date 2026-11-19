<?php
$pageTitle = 'Manajemen Pengguna';
require 'includes/header.php';
require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    try {
        if ($act === 'tambah') {
            $un = trim($_POST['username'] ?? ''); $nm = trim($_POST['nama_lengkap'] ?? ''); $pw = $_POST['password'] ?? '';
            $role = in_array($_POST['role'] ?? '', ['admin', 'kasir'], true) ? $_POST['role'] : 'kasir';
            if (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $un) || $nm === '') throw new RuntimeException('Username (3-30 huruf/angka) dan nama wajib diisi.');
            if (strlen($pw) < 6) throw new RuntimeException('Password minimal 6 karakter.');
            $pdo->prepare('INSERT INTO users (username,password_hash,nama_lengkap,role) VALUES (?,?,?,?)')->execute([$un, password_hash($pw, PASSWORD_BCRYPT), $nm, $role]);
            flash('success', 'Pengguna ditambahkan.');
        } elseif ($act === 'status') {
            $id = (int)$_POST['id'];
            if ($id === (int)$u['id']) throw new RuntimeException('Anda tidak bisa menonaktifkan akun sendiri.');
            $pdo->prepare("UPDATE users SET status = IF(status='aktif','nonaktif','aktif') WHERE id=?")->execute([$id]);
            flash('success', 'Status pengguna diubah.');
        } elseif ($act === 'reset') {
            $pw = $_POST['password'] ?? '';
            if (strlen($pw) < 6) throw new RuntimeException('Password minimal 6 karakter.');
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_BCRYPT), (int)$_POST['id']]);
            flash('success', 'Password direset.');
        }
    } catch (PDOException $ex) { flash('error', $ex->getCode() == 23000 ? 'Username sudah dipakai.' : 'Kesalahan database.');
    } catch (RuntimeException $ex) { flash('error', $ex->getMessage()); }
    redirect('users.php');
}
$users = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();
?>
<form class="card" method="post"><h3>Tambah Pengguna</h3><?= csrf_field() ?><input type="hidden" name="act" value="tambah">
<div class="row3"><label>Username<input name="username" required></label><label>Nama Lengkap<input name="nama_lengkap" required></label>
<label>Role<select name="role"><option value="kasir">Kasir / Waiter</option><option value="admin">Administrator</option></select></label></div>
<label>Password<input type="password" name="password" minlength="6" required></label><button class="btn">Tambah</button></form>
<div class="card scroll"><table><tr><th>Username</th><th>Nama</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($users as $r): ?><tr><td><?= e($r['username']) ?></td><td><?= e($r['nama_lengkap']) ?></td><td><?= e($r['role']) ?></td><td><span class="badge <?= $r['status'] === 'aktif' ? 'selesai' : 'dibatalkan' ?>"><?= e($r['status']) ?></span></td>
<td class="nowrap"><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="act" value="status"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm">Aktif/Nonaktif</button></form>
<form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="act" value="reset"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="password" name="password" placeholder="Password baru" minlength="6" required style="width:130px"><button class="btn sm">Reset</button></form></td></tr>
<?php endforeach; ?></table></div>
<?php require 'includes/footer.php';
