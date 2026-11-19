<?php
$pageTitle = 'Riwayat Transaksi';
require 'includes/header.php';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0); $new = $_POST['status'] ?? '';
    if (!in_array($new, ['Selesai', 'Dibatalkan'], true)) { flash('error', 'Status tidak valid.'); redirect('transaksi_list.php'); }
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE'); $st->execute([$id]); $o = $st->fetch();
        if (!$o || (!is_admin() && $o['user_id'] != $u['id'])) throw new RuntimeException('Transaksi tidak ditemukan.');
        if ($o['status'] === 'Dibatalkan') throw new RuntimeException('Transaksi yang dibatalkan tidak bisa diubah.');
        if ($new === 'Dibatalkan') // kembalikan stok
            $pdo->prepare('UPDATE products p JOIN order_details d ON d.product_id=p.id SET p.stok = p.stok + d.kuantiti WHERE d.order_id=?')->execute([$id]);
        $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$new, $id]);
        $pdo->commit(); flash('success', "Status {$o['invoice_number']} diubah menjadi $new.");
    } catch (RuntimeException $ex) { $pdo->rollBack(); flash('error', $ex->getMessage()); }
    redirect('transaksi_list.php');
}
$q = trim($_GET['q'] ?? ''); $tgl = $_GET['tgl'] ?? '';
$w = ['1=1']; $p = [];
if (!is_admin()) { $w[] = 'o.user_id=?'; $p[] = $u['id']; }
if ($q !== '') { $w[] = '(o.invoice_number LIKE ? OR o.nama_pemesan LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) { $w[] = 'DATE(o.created_at)=?'; $p[] = $tgl; }
$st = $pdo->prepare('SELECT o.*, us.nama_lengkap kasir FROM orders o JOIN users us ON us.id=o.user_id WHERE ' . implode(' AND ', $w) . ' ORDER BY o.created_at DESC LIMIT 100');
$st->execute($p); $rows = $st->fetchAll();
?>
<form class="card filter" method="get"><input name="q" placeholder="Cari invoice / nama" value="<?= e($q) ?>"><input type="date" name="tgl" value="<?= e($tgl) ?>"><button class="btn">Cari</button><a class="btn ghost" href="transaksi_list.php">Reset</a></form>
<div class="card scroll"><table><tr><th>Invoice</th><th>Waktu</th><th>Kasir</th><th>Tipe</th><th>Total</th><th>Bayar</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?><tr>
<td><?= e($r['invoice_number']) ?></td><td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td><td><?= e($r['kasir']) ?></td>
<td><?= e($r['tipe_pesanan']) ?><?= $r['nomor_meja'] ? ' #' . e($r['nomor_meja']) : '' ?></td><td><?= rp($r['grand_total']) ?></td><td><?= e($r['metode_bayar']) ?></td>
<td><span class="badge <?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td>
<td class="nowrap"><a class="btn sm" href="struk.php?id=<?= $r['id'] ?>">Struk</a>
<?php if ($r['status'] !== 'Dibatalkan'): ?>
<?php if ($r['status'] === 'Diproses'): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="status" value="Selesai"><button class="btn sm ok">Selesai</button></form><?php endif; ?>
<form method="post" class="inline" onsubmit="return confirm('Batalkan transaksi ini? Stok akan dikembalikan.')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="status" value="Dibatalkan"><button class="btn sm danger">Batal</button></form>
<?php endif; ?></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="8" class="muted">Tidak ada data.</td></tr>'; ?></table></div>
<?php require 'includes/footer.php';
