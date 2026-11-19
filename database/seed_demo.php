<?php
// OPSIONAL: isi data transaksi contoh 7 hari terakhir agar dashboard & laporan tidak kosong.
// Jalankan SEKALI: php database/seed_demo.php   (atau buka via browser)
require __DIR__ . '/../includes/functions.php';
$pdo = db();
if ($pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() > 0) die("Tabel orders sudah berisi data. Seeder dibatalkan.\n");
$prods = $pdo->query('SELECT id, harga_jual FROM products')->fetchAll();
$methods = METODE; $n = 0;
$pdo->beginTransaction();
for ($d = 6; $d >= 0; $d--) {
    $date = date('Y-m-d', strtotime("-$d day"));
    $count = random_int(5, 11);
    for ($i = 1; $i <= $count; $i++) {
        $time = sprintf('%s %02d:%02d:00', $date, random_int(8, 20), random_int(0, 59));
        if ($d === 0 && $time > date('Y-m-d H:i:s')) $time = date('Y-m-d H:i:s');
        $sub = 0; $items = [];
        foreach (array_rand($prods, random_int(1, 3)) as $k) { $q = random_int(1, 3); $items[] = [$prods[$k], $q]; $sub += $prods[$k]['harga_jual'] * $q; }
        [$dis, $pjk, $grand] = hitung_total($sub, random_int(1, 6) === 1 ? 'persen' : 'none', 10);
        $m = $methods[array_rand($methods)]; $dine = random_int(0, 1);
        $inv = sprintf('INV-POS-%s-%04d', str_replace('-', '', $date), $i);
        $pdo->prepare('INSERT INTO orders (invoice_number,user_id,nama_pemesan,tipe_pesanan,nomor_meja,total_subtotal,pajak_nominal,diskon_nominal,grand_total,metode_bayar,uang_dibayar,uang_kembali,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$inv, random_int(2, 3), 'Pelanggan ' . $i, $dine ? 'Dine-In' : 'Takeaway', $dine ? (string)random_int(1, 12) : null, $sub, $pjk, $dis, $grand, $m, $grand, 0, 'Selesai', $time]);
        $oid = $pdo->lastInsertId();
        foreach ($items as [$p, $q]) $pdo->prepare('INSERT INTO order_details (order_id,product_id,harga_satuan,kuantiti,subtotal_baris) VALUES (?,?,?,?,?)')->execute([$oid, $p['id'], $p['harga_jual'], $q, $p['harga_jual'] * $q]);
        $n++;
    }
}
$pdo->commit();
echo "Berhasil membuat $n transaksi contoh.\n";
