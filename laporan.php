<?php
$pageTitle = 'Laporan Penjualan';
require 'includes/header.php';
require_admin();
$pdo = db();
$f = laporan_filter();
[$rows, $s] = laporan_data($pdo, $f);
$cats = $pdo->query('SELECT id, nama_kategori FROM categories ORDER BY id')->fetchAll();
$qs = http_build_query($f);
?>
<form class="card filter" method="get">
  <label>Dari<input type="date" name="dari" value="<?= e($f['dari']) ?>"></label>
  <label>Sampai<input type="date" name="sampai" value="<?= e($f['sampai']) ?>"></label>
  <label>Kategori<select name="kategori"><option value="0">Semua</option><?php foreach ($cats as $c) echo '<option value="' . $c['id'] . '"' . ($f['kategori'] == $c['id'] ? ' selected' : '') . '>' . e($c['nama_kategori']) . '</option>'; ?></select></label>
  <label>Metode Bayar<select name="metode"><option value="">Semua</option><?php foreach (METODE as $m) echo '<option' . ($f['metode'] === $m ? ' selected' : '') . '>' . $m . '</option>'; ?></select></label>
  <button class="btn">Terapkan</button>
</form>
<div class="grid5">
  <div class="card kpi"><span>Omset Bruto</span><b class="sm"><?= rp($s['bruto']) ?></b></div>
  <div class="card kpi"><span>Total Diskon</span><b class="sm"><?= rp($s['diskon']) ?></b></div>
  <div class="card kpi"><span>Pajak Restoran</span><b class="sm"><?= rp($s['pajak']) ?></b></div>
  <div class="card kpi"><span>Omset Bersih</span><b class="sm"><?= rp($s['bersih']) ?></b></div>
  <div class="card kpi"><span>Rata-rata Transaksi</span><b class="sm"><?= rp($s['aov']) ?></b></div>
</div>
<div class="actions">
  <a class="btn" target="_blank" href="laporan_export.php?<?= $qs ?>&format=pdf">⬇ Ekspor PDF</a>
  <a class="btn ghost" href="laporan_export.php?<?= $qs ?>&format=xls">⬇ Excel (.xls)</a>
  <a class="btn ghost" href="laporan_export.php?<?= $qs ?>&format=csv">⬇ CSV</a>
</div>
<div class="card scroll"><table><tr><th>No. Faktur</th><th>Tanggal & Jam</th><th>Kasir</th><th>Tipe</th><th>Item</th><th>Subtotal</th><th>Pajak</th><th>Grand Total</th><th>Status</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['invoice_number']) ?></td><td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td><td><?= e($r['kasir']) ?></td><td><?= e($r['tipe_pesanan']) ?></td><td><?= (int)$r['item_terjual'] ?></td><td><?= rp($r['total_subtotal']) ?></td><td><?= rp($r['pajak_nominal']) ?></td><td><?= rp($r['grand_total']) ?></td><td><?= e($r['status']) ?></td></tr>
<?php endforeach; if (!$rows) echo '<tr><td colspan="9" class="muted">Tidak ada transaksi pada filter ini.</td></tr>'; ?></table></div>
<p class="muted">Laporan hanya menghitung transaksi berstatus <b>Selesai</b>.</p>
<?php require 'includes/footer.php';
