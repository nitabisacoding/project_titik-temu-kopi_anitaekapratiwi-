<?php
require_once __DIR__ . '/../config/config.php';
ob_start(); // buffer agar redirect aman setelah header HTML
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            die('Koneksi database gagal. Periksa config/config.php dan pastikan database.sql sudah diimpor.');
        }
    }
    return $pdo;
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function show_flash(): void {
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) echo '<div class="alert ' . e($t) . '">' . e($m) . '</div>';
    unset($_SESSION['flash']);
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        die('Token keamanan tidak valid. Muat ulang halaman.');
    }
}

// ---------- Autentikasi & hak akses ----------
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) { flash('error', 'Silakan login terlebih dahulu.'); redirect('login.php'); } }
function require_admin(): void {
    require_login();
    if (user()['role'] !== 'admin') { http_response_code(403); flash('error', 'Akses ditolak: hanya untuk Administrator.'); redirect('dashboard.php'); }
}
function is_admin(): bool { return (user()['role'] ?? '') === 'admin'; }

// ---------- Transaksi ----------
const METODE = ['Tunai', 'Transfer Bank', 'Kartu Debit'];

function hitung_total(int $subtotal, string $diskonTipe, float $diskonNilai): array {
    $diskon = 0;
    if ($diskonTipe === 'persen') $diskon = (int)round($subtotal * min(max($diskonNilai, 0), 100) / 100);
    elseif ($diskonTipe === 'nominal') $diskon = (int)min(max($diskonNilai, 0), $subtotal);
    $pajak = (int)round(($subtotal - $diskon) * PB1_RATE);
    return [$diskon, $pajak, $subtotal - $diskon + $pajak];
}

function simpan_order(PDO $pdo, array $in): int {
    $tipe = $in['tipe_pesanan'] ?? '';
    if (!in_array($tipe, ['Dine-In', 'Takeaway'], true)) throw new RuntimeException('Tipe pesanan tidak valid.');
    $meja = trim($in['nomor_meja'] ?? '');
    if ($tipe === 'Dine-In' && $meja === '') throw new RuntimeException('Nomor meja wajib diisi untuk Dine-In.');
    if ($tipe === 'Takeaway') $meja = null;
    $nama = trim($in['nama_pemesan'] ?? '');
    $metode = $in['metode_bayar'] ?? '';
    if (!in_array($metode, METODE, true)) throw new RuntimeException('Metode pembayaran tidak valid.');
    $status = in_array($in['status'] ?? '', ['Diproses', 'Selesai'], true) ? $in['status'] : 'Diproses';

    $lines = []; $need = [];
    foreach (($in['product_id'] ?? []) as $i => $pid) {
        $pid = (int)$pid; $q = (int)($in['qty'][$i] ?? 0);
        if ($pid <= 0 || $q <= 0) continue;
        $lines[] = ['pid' => $pid, 'qty' => $q, 'note' => mb_substr(trim($in['catatan'][$i] ?? ''), 0, 150)];
        $need[$pid] = ($need[$pid] ?? 0) + $q;
    }
    if (!$lines) throw new RuntimeException('Tambahkan minimal satu item pesanan.');

    $pdo->beginTransaction();
    try {
        $ph = implode(',', array_fill(0, count($need), '?'));
        $st = $pdo->prepare("SELECT id, nama_produk, harga_jual, stok FROM products WHERE id IN ($ph) FOR UPDATE");
        $st->execute(array_keys($need));
        $prod = [];
        foreach ($st->fetchAll() as $r) $prod[$r['id']] = $r;
        $subtotal = 0;
        foreach ($need as $pid => $q) {
            if (!isset($prod[$pid])) throw new RuntimeException('Menu tidak ditemukan.');
            if ($prod[$pid]['stok'] < $q) throw new RuntimeException("Stok {$prod[$pid]['nama_produk']} tidak cukup (tersisa {$prod[$pid]['stok']}).");
        }
        foreach ($lines as $l) $subtotal += (int)$prod[$l['pid']]['harga_jual'] * $l['qty'];
        [$diskon, $pajak, $grand] = hitung_total($subtotal, $in['diskon_tipe'] ?? 'none', (float)($in['diskon_nilai'] ?? 0));

        $bayar = $grand; $kembali = 0;
        if ($metode === 'Tunai') {
            $bayar = (int)($in['uang_dibayar'] ?? 0);
            if ($bayar < $grand) throw new RuntimeException('Uang tunai kurang dari total tagihan (' . rp($grand) . ').');
            $kembali = $bayar - $grand;
        }

        $prefix = 'INV-POS-' . date('Ymd') . '-';
        $st = $pdo->prepare("SELECT COALESCE(MAX(CAST(RIGHT(invoice_number,4) AS UNSIGNED)),0)+1 FROM orders WHERE invoice_number LIKE ? FOR UPDATE");
        $st->execute([$prefix . '%']);
        $invoice = $prefix . str_pad((string)$st->fetchColumn(), 4, '0', STR_PAD_LEFT);

        $pdo->prepare("INSERT INTO orders (invoice_number,user_id,nama_pemesan,tipe_pesanan,nomor_meja,total_subtotal,pajak_nominal,diskon_nominal,grand_total,metode_bayar,uang_dibayar,uang_kembali,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$invoice, user()['id'], $nama, $tipe, $meja, $subtotal, $pajak, $diskon, $grand, $metode, $bayar, $kembali, $status]);
        $orderId = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare("INSERT INTO order_details (order_id,product_id,harga_satuan,kuantiti,subtotal_baris,catatan_khusus) VALUES (?,?,?,?,?,?)");
        foreach ($lines as $l) {
            $h = (int)$prod[$l['pid']]['harga_jual'];
            $ins->execute([$orderId, $l['pid'], $h, $l['qty'], $h * $l['qty'], $l['note']]);
        }
        $upd = $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
        foreach ($need as $pid => $q) $upd->execute([$q, $pid]);
        $pdo->commit();
        return $orderId;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $ex instanceof RuntimeException ? $ex : new RuntimeException('Gagal menyimpan transaksi, data di-rollback.');
    }
}

// ---------- Laporan ----------
function laporan_filter(): array {
    $d = fn($v, $def) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v) ? $v : $def;
    $dari = $d($_GET['dari'] ?? '', date('Y-m-01'));
    $sampai = $d($_GET['sampai'] ?? '', date('Y-m-d'));
    if ($dari > $sampai) [$dari, $sampai] = [$sampai, $dari];
    $metode = in_array($_GET['metode'] ?? '', METODE, true) ? $_GET['metode'] : '';
    return ['dari' => $dari, 'sampai' => $sampai, 'kategori' => (int)($_GET['kategori'] ?? 0), 'metode' => $metode];
}
function laporan_data(PDO $pdo, array $f): array {
    $w = ["o.status='Selesai'", 'DATE(o.created_at) BETWEEN ? AND ?']; $p = [$f['dari'], $f['sampai']];
    if ($f['kategori']) { $w[] = 'EXISTS(SELECT 1 FROM order_details d JOIN products pr ON pr.id=d.product_id WHERE d.order_id=o.id AND pr.category_id=?)'; $p[] = $f['kategori']; }
    if ($f['metode']) { $w[] = 'o.metode_bayar=?'; $p[] = $f['metode']; }
    $st = $pdo->prepare("SELECT o.*, u.nama_lengkap AS kasir, (SELECT COALESCE(SUM(kuantiti),0) FROM order_details d WHERE d.order_id=o.id) AS item_terjual FROM orders o JOIN users u ON u.id=o.user_id WHERE " . implode(' AND ', $w) . " ORDER BY o.created_at DESC");
    $st->execute($p);
    $rows = $st->fetchAll();
    $s = ['bruto' => 0, 'diskon' => 0, 'pajak' => 0, 'grand' => 0, 'jumlah' => count($rows)];
    foreach ($rows as $r) { $s['bruto'] += $r['total_subtotal']; $s['diskon'] += $r['diskon_nominal']; $s['pajak'] += $r['pajak_nominal']; $s['grand'] += $r['grand_total']; }
    $s['bersih'] = $s['bruto'] - $s['diskon'];
    $s['aov'] = $s['jumlah'] ? (int)round($s['grand'] / $s['jumlah']) : 0;
    return [$rows, $s];
}