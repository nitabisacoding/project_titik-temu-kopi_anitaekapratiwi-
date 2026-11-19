<?php
require_once 'includes/functions.php';
require_login();
$pdo = db();
$st = $pdo->prepare('SELECT o.*, u.nama_lengkap kasir FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?');
$st->execute([(int)($_GET['id'] ?? 0)]); $o = $st->fetch();
if (!$o || (!is_admin() && $o['user_id'] != user()['id'])) { flash('error', 'Struk tidak ditemukan.'); redirect('transaksi_list.php'); }
$st = $pdo->prepare('SELECT d.*, p.nama_produk FROM order_details d JOIN products p ON p.id=d.product_id WHERE d.order_id=?');
$st->execute([$o['id']]); $items = $st->fetchAll();
$size = in_array($_GET['size'] ?? '', ['58', '80', 'a4'], true) ? $_GET['size'] : '80';
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Struk <?= e($o['invoice_number']) ?></title>
<link rel="stylesheet" href="assets/style.css">
<style>@page{margin:4mm;<?= $size === 'a4' ? 'size:A4' : "size:{$size}mm auto" ?>}</style></head>
<body class="strukpage"><div class="noprint toolbar">
<a class="btn ghost" href="transaksi.php">＋ Transaksi Baru</a><a class="btn ghost" href="transaksi_list.php">Riwayat</a>
<?php foreach (['58' => 'Thermal 58mm', '80' => 'Thermal 80mm', 'a4' => 'A4'] as $k => $l) echo '<a class="btn ' . ($size === $k ? '' : 'ghost') . '" href="?id=' . $o['id'] . '&size=' . $k . '">' . $l . '</a>'; ?>
<button class="btn" onclick="window.print()">🖨 Cetak</button></div>
<div class="struk s<?= $size ?>">
<h2 style="text-align:center;margin:0">☕ <?= APP_NAME ?></h2><p class="c">Struk Pembayaran</p><hr>
<p>No: <?= e($o['invoice_number']) ?><br>Tgl: <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?><br>Kasir: <?= e($o['kasir']) ?><br>
<?= e($o['tipe_pesanan']) ?><?= $o['nomor_meja'] ? ' - Meja ' . e($o['nomor_meja']) : '' ?><?= $o['nama_pemesan'] ? '<br>Pemesan: ' . e($o['nama_pemesan']) : '' ?></p><hr>
<table class="plain"><?php foreach ($items as $i): ?>
<tr><td colspan="2"><?= e($i['nama_produk']) ?></td></tr>
<tr><td><?= (int)$i['kuantiti'] ?> x <?= number_format($i['harga_satuan'], 0, ',', '.') ?></td><td class="r"><?= number_format($i['subtotal_baris'], 0, ',', '.') ?></td></tr>
<?php if ($i['catatan_khusus']) echo '<tr><td colspan="2"><i>* ' . e($i['catatan_khusus']) . '</i></td></tr>'; endforeach; ?></table><hr>
<table class="plain">
<tr><td>Subtotal</td><td class="r"><?= rp($o['total_subtotal']) ?></td></tr>
<?php if ($o['diskon_nominal'] > 0): ?><tr><td>Diskon</td><td class="r">-<?= rp($o['diskon_nominal']) ?></td></tr><?php endif; ?>
<tr><td>PB1 (10%)</td><td class="r"><?= rp($o['pajak_nominal']) ?></td></tr>
<tr><td><b>TOTAL</b></td><td class="r"><b><?= rp($o['grand_total']) ?></b></td></tr>
<tr><td><?= e($o['metode_bayar']) ?></td><td class="r"><?= rp($o['uang_dibayar']) ?></td></tr>
<tr><td>Kembali</td><td class="r"><?= rp($o['uang_kembali']) ?></td></tr></table><hr>
<p class="c">Status: <?= e($o['status']) ?><br>Terima kasih atas kunjungan Anda!</p></div>
<?php if (isset($_GET['new'])) echo '<script>window.addEventListener("load",()=>setTimeout(()=>window.print(),400))</script>'; ?>
</body></html>
