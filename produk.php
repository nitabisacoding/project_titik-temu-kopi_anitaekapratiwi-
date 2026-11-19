<?php
$pageTitle = 'Menu, Kategori & Stok';
require 'includes/header.php';
require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    try {
        if ($act === 'kategori') {
            $n = trim($_POST['nama_kategori'] ?? '');
            if ($n === '') throw new RuntimeException('Nama kategori wajib diisi.');
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $n), '-'));
            $pdo->prepare('INSERT INTO categories (nama_kategori, slug) VALUES (?,?)')->execute([$n, $slug]);
            flash('success', 'Kategori ditambahkan.');
        } elseif ($act === 'simpan') {
            $id = (int)($_POST['id'] ?? 0);
            $d = [(int)$_POST['category_id'], trim($_POST['kode_produk']), trim($_POST['nama_produk']), (int)$_POST['harga_jual'], (int)$_POST['harga_pokok'], (int)$_POST['stok'], (int)$_POST['stok_minimum'], trim($_POST['unit']) ?: 'porsi'];
            if ($d[1] === '' || $d[2] === '' || $d[3] <= 0 || $d[5] < 0) throw new RuntimeException('Kode, nama, harga (>0) dan stok (>=0) wajib valid.');
            if ($id) { $pdo->prepare('UPDATE products SET category_id=?,kode_produk=?,nama_produk=?,harga_jual=?,harga_pokok=?,stok=?,stok_minimum=?,unit=? WHERE id=?')->execute([...$d, $id]); }
            else { $pdo->prepare('INSERT INTO products (category_id,kode_produk,nama_produk,harga_jual,harga_pokok,stok,stok_minimum,unit) VALUES (?,?,?,?,?,?,?,?)')->execute($d); }
            flash('success', 'Menu disimpan.');
        } elseif ($act === 'hapus') {
            $pdo->prepare('DELETE FROM products WHERE id=?')->execute([(int)$_POST['id']]);
            flash('success', 'Menu dihapus.');
        }
    } catch (PDOException $ex) {
        flash('error', $ex->getCode() == 23000 ? 'Gagal: kode produk sudah dipakai, atau menu sudah ada di transaksi (tidak bisa dihapus).' : 'Terjadi kesalahan database.');
    } catch (RuntimeException $ex) { flash('error', $ex->getMessage()); }
    redirect('produk.php');
}
$cats = $pdo->query('SELECT * FROM categories ORDER BY id')->fetchAll();
$edit = null;
if (isset($_GET['edit'])) { $st = $pdo->prepare('SELECT * FROM products WHERE id=?'); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch() ?: null; }
$prods = $pdo->query('SELECT p.*, c.nama_kategori FROM products p JOIN categories c ON c.id=p.category_id ORDER BY c.id, p.nama_produk')->fetchAll();
$v = fn($k, $d = '') => e($edit[$k] ?? $d);
?>
<div class="grid2">
<form class="card" method="post"><h3><?= $edit ? 'Edit Menu' : 'Tambah Menu' ?></h3><?= csrf_field() ?><input type="hidden" name="act" value="simpan"><input type="hidden" name="id" value="<?= $v('id', 0) ?>">
<div class="row2"><label>Kategori<select name="category_id"><?php foreach ($cats as $c) echo '<option value="' . $c['id'] . '"' . (($edit['category_id'] ?? 0) == $c['id'] ? ' selected' : '') . '>' . e($c['nama_kategori']) . '</option>'; ?></select></label>
<label>Kode<input name="kode_produk" value="<?= $v('kode_produk') ?>" required></label></div>
<label>Nama Menu<input name="nama_produk" value="<?= $v('nama_produk') ?>" required></label>
<div class="row2"><label>Harga Jual<input type="number" min="1" name="harga_jual" value="<?= $v('harga_jual') ?>" required></label><label>Harga Pokok<input type="number" min="0" name="harga_pokok" value="<?= $v('harga_pokok', 0) ?>"></label></div>
<div class="row3"><label>Stok<input type="number" min="0" name="stok" value="<?= $v('stok', 0) ?>" required></label><label>Stok Minimum<input type="number" min="0" name="stok_minimum" value="<?= $v('stok_minimum', 10) ?>"></label><label>Satuan<input name="unit" value="<?= $v('unit', 'porsi') ?>"></label></div>
<button class="btn">Simpan</button> <?php if ($edit) echo '<a class="btn ghost" href="produk.php">Batal</a>'; ?></form>
<form class="card" method="post"><h3>Tambah Kategori</h3><?= csrf_field() ?><input type="hidden" name="act" value="kategori"><label>Nama Kategori<input name="nama_kategori" required></label><button class="btn">Tambah</button>
<p class="muted">Kategori saat ini: <?= e(implode(', ', array_column($cats, 'nama_kategori'))) ?></p></form></div>
<div class="card scroll"><table><tr><th>Kode</th><th>Menu</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr>
<?php foreach ($prods as $p): ?><tr><td><?= e($p['kode_produk']) ?></td><td><?= e($p['nama_produk']) ?></td><td><?= e($p['nama_kategori']) ?></td><td><?= rp($p['harga_jual']) ?></td>
<td class="<?= $p['stok'] <= $p['stok_minimum'] ? 'red' : '' ?>"><?= (int)$p['stok'] ?> <?= e($p['unit']) ?></td>
<td class="nowrap"><a class="btn sm" href="?edit=<?= $p['id'] ?>">Edit</a><form method="post" class="inline" onsubmit="return confirm('Hapus menu ini?')"><?= csrf_field() ?><input type="hidden" name="act" value="hapus"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button class="btn sm danger">Hapus</button></form></td></tr>
<?php endforeach; ?></table></div>
<?php require 'includes/footer.php';
