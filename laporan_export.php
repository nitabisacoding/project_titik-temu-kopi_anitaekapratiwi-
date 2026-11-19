<?php
require_once 'includes/functions.php';
require_admin();
$pdo = db();
$f = laporan_filter();
[$rows, $s] = laporan_data($pdo, $f);
$fmt = $_GET['format'] ?? 'pdf';
$head = ['No. Faktur', 'Tanggal & Jam', 'Kasir', 'Tipe Pesanan', 'Item Terjual', 'Subtotal', 'Pajak', 'Grand Total', 'Status'];
$line = fn($r) => [$r['invoice_number'], $r['created_at'], $r['kasir'], $r['tipe_pesanan'], $r['item_terjual'], $r['total_subtotal'], $r['pajak_nominal'], $r['grand_total'], $r['status']];
$name = 'laporan_' . $f['dari'] . '_sd_' . $f['sampai'];

if ($fmt === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=$name.csv");
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, $head);
    foreach ($rows as $r) fputcsv($o, $line($r));
    fputcsv($o, []); fputcsv($o, ['Omset Bruto', $s['bruto']]); fputcsv($o, ['Total Diskon', $s['diskon']]);
    fputcsv($o, ['Total Pajak', $s['pajak']]); fputcsv($o, ['Omset Bersih', $s['bersih']]); fputcsv($o, ['Rata-rata Transaksi', $s['aov']]);
    exit;
}
$summary = '<p>Periode: ' . e($f['dari']) . ' s/d ' . e($f['sampai']) . '</p><table class="rep"><tr><td>Omset Bruto</td><td>' . rp($s['bruto']) . '</td><td>Total Diskon</td><td>' . rp($s['diskon']) . '</td><td>Pajak</td><td>' . rp($s['pajak']) . '</td><td>Omset Bersih</td><td>' . rp($s['bersih']) . '</td><td>Rata-rata</td><td>' . rp($s['aov']) . '</td></tr></table><br>';
$table = '<table class="rep" border="1" cellspacing="0" cellpadding="4"><tr>' . implode('', array_map(fn($h) => "<th>$h</th>", $head)) . '</tr>';
foreach ($rows as $r) $table .= '<tr>' . implode('', array_map(fn($v) => '<td>' . e($v) . '</td>', $line($r))) . '</tr>';
$table .= '</table>';
if ($fmt === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=$name.xls");
    echo "\xEF\xBB\xBF<html><meta charset='utf-8'><body><h3>Laporan Penjualan - " . APP_NAME . "</h3>$summary$table</body></html>";
    exit;
}
// PDF: halaman siap cetak -> otomatis membuka dialog cetak (pilih "Save as PDF")
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title><?= e($name) ?></title>
<style>body{font:12px Arial,sans-serif;margin:20px}table.rep{border-collapse:collapse;width:100%}table.rep th{background:#6f4e37;color:#fff}table.rep td,table.rep th{border:1px solid #999;padding:4px 6px;text-align:left}@page{size:A4 landscape;margin:10mm}</style></head>
<body onload="window.print()"><h2>Laporan Penjualan - <?= APP_NAME ?></h2><?= $summary . $table ?>
<p>Dicetak: <?= date('d/m/Y H:i') ?> oleh <?= e(user()['nama_lengkap']) ?></p></body></html>
