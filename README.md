# Sistem Kasir & Pemesanan Kafe "Titik Temu Kopi" (MED-WEB-01)

Aplikasi web Point of Sales (Dine-In & Takeaway) dengan **PHP Native (PDO)** + **MySQL/MariaDB**.

## Instalasi

1. Salin folder ke web root (mis. `htdocs/pos-kafe` di XAMPP).
2. Import database: buka phpMyAdmin → Import → `database/database.sql`
   (atau `mysql -u root -p < database/database.sql`). Database bernama **`pos_kafe`**.
3. Sesuaikan kredensial di `config/config.php` bila perlu (default: root, tanpa password).
4. (Opsional) Isi data transaksi contoh: `php database/transaksi.php`.
5. Buka `http://localhost/pos-kafe/`.

## Akun Uji Coba

| Role                    | Username | Password   |
| ----------------------- | -------- | ---------- |
| Administrator / Manager | `admin`  | `password` |
| Kasir / Waiter          | `kasir1` | `password` |
| Kasir / Waiter          | `kasir2` | `password` |

## Fitur per Modul

1. **Autentikasi & Hak Akses** – login tervalidasi, password Bcrypt, guard di setiap halaman, CSRF token, logout menghancurkan sesi, role admin vs kasir.
2. **Dashboard** – 4 KPI (omset hari ini, jumlah pesanan, menu terlaris, stok rendah), grafik 7 hari & distribusi kategori, 8 transaksi terbaru, tombol aksi cepat. Kasir hanya melihat data transaksinya sendiri.
3. **Transaksi** – invoice `INV-POS-YYYYMMDD-XXXX` otomatis & unik, Dine-In/Takeaway, item dinamis + catatan khusus, PB1 10%, diskon (% / nominal), kembalian otomatis, validasi stok & uang tunai, `beginTransaction/commit/rollback`, struk 58mm / 80mm / A4, ubah status (Diproses/Selesai/Dibatalkan; pembatalan mengembalikan stok).
4. **Laporan** (admin) – filter tanggal/kategori/metode bayar, summary bar, tabel laporan, ekspor PDF (halaman cetak → _Save as PDF_), Excel (.xls) dan CSV.
5. **Tambahan admin** – kelola menu/kategori/stok dan akun pengguna.

## Struktur Folder

config/ koneksi & konstanta includes/ functions, header, footer
assets/ style.css, app.js database/ database.sql, seed_demo.php
\*.php halaman (login, dashboard, transaksi, struk, laporan, produk, users)

Isi Tabel: `users`, `categories`, `products`, `orders`, `order_details`.
