<?php
require_once 'includes/functions.php';
require_login();
$pdo = db();
$old = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = $_POST;
    try {
        $id = simpan_order($pdo, $_POST);
        flash('success', 'Transaksi berhasil disimpan.');
        redirect('struk.php?id=' . $id . '&new=1');
    } catch (RuntimeException $ex) { flash('error', $ex->getMessage()); }
}
$pageTitle = 'Transaksi Baru';
$products = $pdo->query("SELECT p.id, p.nama_produk nama, p.harga_jual harga, p.stok, c.nama_kategori kat FROM products p JOIN categories c ON c.id=p.category_id WHERE c.status='aktif' ORDER BY c.id, p.nama_produk")->fetchAll();
$nextNo = $pdo->query("SELECT COALESCE(MAX(CAST(RIGHT(invoice_number,4) AS UNSIGNED)),0)+1 FROM orders WHERE invoice_number LIKE 'INV-POS-" . date('Ymd') . "-%'")->fetchColumn();
$invPreview = 'INV-POS-' . date('Ymd') . '-' . str_pad((string)$nextNo, 4, '0', STR_PAD_LEFT);
require 'includes/header.php';
?>
<form method="post" id="trxForm" class="grid-form">
  <?= csrf_field() ?>
  <div class="card">
    <h3>Data Pesanan <small class="muted">• <?= e($invPreview) ?> (nomor final dibuat saat disimpan)</small></h3>
    <div class="row3">
      <label>Tipe Pesanan<select name="tipe_pesanan" id="tipe"><option>Dine-In</option><option>Takeaway</option></select></label>
      <label id="mejaWrap">No. Meja<input name="nomor_meja" id="meja" maxlength="10" placeholder="mis. 5"></label>
      <label>Nama Pemesan / Catatan Meja<input name="nama_pemesan" maxlength="100"></label>
    </div>
    <h3>Item Pesanan</h3>
    <div class="scroll"><table id="items"><thead><tr><th>Menu</th><th style="width:90px">Qty</th><th>Catatan Khusus</th><th>Subtotal</th><th></th></tr></thead><tbody id="rows"></tbody></table></div>
    <button type="button" class="btn ghost" id="addRow">＋ Tambah Item</button>
  </div>
  <div class="card">
    <h3>Pembayaran</h3>
    <div class="row2">
      <label>Diskon<select name="diskon_tipe" id="dTipe"><option value="none">Tidak ada</option><option value="persen">Persen (%)</option><option value="nominal">Nominal (Rp)</option></select></label>
      <label>Nilai Diskon<input type="number" min="0" step="any" name="diskon_nilai" id="dNilai" value="0"></label>
    </div>
    <div class="sum"><div><span>Subtotal</span><b id="tSub">Rp 0</b></div><div><span>Diskon</span><b id="tDis">Rp 0</b></div>
      <div><span>PB1 Restoran (10%)</span><b id="tPjk">Rp 0</b></div><div class="grand"><span>Grand Total</span><b id="tGrand">Rp 0</b></div></div>
    <div class="row3">
      <label>Metode Pembayaran<select name="metode_bayar" id="metode"><?php foreach (METODE as $m) echo "<option>$m</option>"; ?></select></label>
      <label id="bayarWrap">Uang Dibayar (Rp)<input type="number" min="0" name="uang_dibayar" id="bayar"></label>
      <label>Status Pesanan<select name="status"><option>Diproses</option><option>Selesai</option></select></label>
    </div>
    <p id="kembali" class="kembali"></p>
    <button class="btn" id="submitBtn">Simpan & Cetak Struk</button>
  </div>
</form>
<script>window.PRODUCTS = <?= json_encode($products) ?>; window.OLD = <?= json_encode($old) ?>;</script>
<?php require 'includes/footer.php';
