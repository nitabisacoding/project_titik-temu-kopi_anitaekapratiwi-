<?php
$pageTitle = 'Dashboard Eksekutif';
require 'includes/header.php';
$pdo = db();
// Admin melihat seluruh data, kasir hanya transaksinya sendiri
$sc = is_admin() ? '' : ' AND o.user_id = ' . (int)$u['id'];

$k = $pdo->query("SELECT COALESCE(SUM(grand_total),0) omset, COUNT(*) jml FROM orders o WHERE status='Selesai' AND DATE(created_at)=CURDATE() $sc")->fetch();
$top = $pdo->query("SELECT p.nama_produk, SUM(d.kuantiti) q FROM order_details d JOIN orders o ON o.id=d.order_id JOIN products p ON p.id=d.product_id WHERE o.status='Selesai' AND DATE(o.created_at)=CURDATE() $sc GROUP BY p.id ORDER BY q DESC LIMIT 1")->fetch();
$low = $pdo->query("SELECT nama_produk, stok, stok_minimum FROM products WHERE stok <= stok_minimum ORDER BY stok")->fetchAll();

$tren = array_fill_keys(array_map(fn($i) => date('Y-m-d', strtotime("-$i day")), range(6, 0)), 0);
foreach ($pdo->query("SELECT DATE(created_at) d, SUM(grand_total) t FROM orders o WHERE status='Selesai' AND created_at >= CURDATE() - INTERVAL 6 DAY $sc GROUP BY d") as $r) $tren[$r['d']] = (int)$r['t'];
$kat = $pdo->query("SELECT c.nama_kategori n, SUM(d.subtotal_baris) t FROM order_details d JOIN orders o ON o.id=d.order_id JOIN products p ON p.id=d.product_id JOIN categories c ON c.id=p.category_id WHERE o.status='Selesai' AND o.created_at >= CURDATE() - INTERVAL 6 DAY $sc GROUP BY c.id")->fetchAll();
$recent = $pdo->query("SELECT o.* FROM orders o WHERE 1=1 $sc ORDER BY o.created_at DESC LIMIT 8")->fetchAll();
?>
<div class="actions"><a class="btn" href="transaksi.php">＋ Buat Transaksi Baru</a></div>
<div class="grid4">
  <div class="card kpi"><span>Total Omset Hari Ini</span><b><?= rp($k['omset']) ?></b></div>
  <div class="card kpi"><span>Jumlah Pesanan (Selesai)</span><b><?= (int)$k['jml'] ?></b></div>
  <div class="card kpi"><span>Menu Terlaris Hari Ini</span><b class="sm"><?= $top ? e($top['nama_produk']) . ' (' . (int)$top['q'] . 'x)' : '-' ?></b></div>
  <div class="card kpi <?= count($low) ? 'warn' : '' ?>"><span>Peringatan Stok Rendah</span><b><?= count($low) ?> item</b></div>
</div>
<div class="grid2">
  <div class="card"><h3>Tren Omset 7 Hari Terakhir</h3><canvas id="chTren" height="150"></canvas></div>
  <div class="card"><h3>Distribusi Kategori (7 Hari)</h3><canvas id="chKat" height="150"></canvas></div>
</div>
<div class="grid2">
  <div class="card"><h3>Transaksi Terbaru</h3>
    <div class="scroll"><table><tr><th>Invoice</th><th>Waktu</th><th>Total</th><th>Status</th></tr>
    <?php foreach ($recent as $r): ?><tr><td><a href="struk.php?id=<?= $r['id'] ?>"><?= e($r['invoice_number']) ?></a></td><td><?= date('d/m H:i', strtotime($r['created_at'])) ?></td><td><?= rp($r['grand_total']) ?></td><td><span class="badge <?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td></tr>
    <?php endforeach; if (!$recent) echo '<tr><td colspan="4" class="muted">Belum ada transaksi.</td></tr>'; ?></table></div></div>
  <div class="card"><h3>Stok di Bawah Batas Minimum</h3>
    <div class="scroll"><table><tr><th>Item</th><th>Stok</th><th>Minimum</th></tr>
    <?php foreach ($low as $l): ?><tr><td><?= e($l['nama_produk']) ?></td><td class="red"><?= (int)$l['stok'] ?></td><td><?= (int)$l['stok_minimum'] ?></td></tr>
    <?php endforeach; if (!$low) echo '<tr><td colspan="3" class="muted">Semua stok aman ✓</td></tr>'; ?></table></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const tren = <?= json_encode($tren) ?>, kat = <?= json_encode($kat) ?>;
if (window.Chart) {
  new Chart(document.getElementById('chTren'), {type:'bar', data:{labels:Object.keys(tren).map(d=>d.slice(8)+'/'+d.slice(5,7)), datasets:[{label:'Omset (Rp)', data:Object.values(tren), backgroundColor:'#b9803f', borderRadius:6}]}, options:{plugins:{legend:{display:false}}}});
  new Chart(document.getElementById('chKat'), {type:'doughnut', data:{labels:kat.map(x=>x.n), datasets:[{data:kat.map(x=>+x.t), backgroundColor:['#6f4e37','#b9803f','#3f7d58','#d9a066']}]}});
} else document.querySelectorAll('canvas').forEach(c=>c.outerHTML='<p class="muted">Grafik memerlukan koneksi internet (Chart.js CDN).</p>');
</script>
<?php require 'includes/footer.php';
