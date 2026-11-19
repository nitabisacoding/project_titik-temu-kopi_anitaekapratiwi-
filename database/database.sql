-- ============================================================
-- Database: pos_kafe  |  Sistem Kasir & Pemesanan Kafe "Titik Temu Kopi"
-- Import: mysql -u root -p < database/database.sql
-- Akun uji (password semua: "password" - di-hash Bcrypt):
--   admin / kasir1 / kasir2
-- ============================================================
CREATE DATABASE IF NOT EXISTS pos_kafe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_kafe;

DROP TABLE IF EXISTS order_details;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  role ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(60) NOT NULL UNIQUE,
  slug VARCHAR(80) NOT NULL UNIQUE,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  kode_produk VARCHAR(20) NOT NULL UNIQUE,
  nama_produk VARCHAR(100) NOT NULL,
  harga_jual DECIMAL(12,0) NOT NULL,
  harga_pokok DECIMAL(12,0) NOT NULL DEFAULT 0,
  stok INT NOT NULL DEFAULT 0,
  stok_minimum INT NOT NULL DEFAULT 10,
  unit VARCHAR(20) NOT NULL DEFAULT 'porsi',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(30) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  nama_pemesan VARCHAR(100) NULL,
  tipe_pesanan ENUM('Dine-In','Takeaway') NOT NULL,
  nomor_meja VARCHAR(10) NULL,
  total_subtotal DECIMAL(12,0) NOT NULL,
  pajak_nominal DECIMAL(12,0) NOT NULL,
  diskon_nominal DECIMAL(12,0) NOT NULL DEFAULT 0,
  grand_total DECIMAL(12,0) NOT NULL,
  metode_bayar ENUM('Tunai','QRIS','Transfer Bank','Kartu Debit') NOT NULL,
  uang_dibayar DECIMAL(12,0) NOT NULL DEFAULT 0,
  uang_kembali DECIMAL(12,0) NOT NULL DEFAULT 0,
  status ENUM('Diproses','Selesai','Dibatalkan') NOT NULL DEFAULT 'Diproses',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created (created_at),
  CONSTRAINT fk_ord_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_details (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  harga_satuan DECIMAL(12,0) NOT NULL,
  kuantiti INT NOT NULL,
  subtotal_baris DECIMAL(12,0) NOT NULL,
  catatan_khusus VARCHAR(150) NULL,
  CONSTRAINT fk_det_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_det_prod FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Hash Bcrypt dari kata "password"
INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES
('admin',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager Titik Temu', 'admin'),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rina Kasir', 'kasir'),
('kasir2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Doni Waiter', 'kasir');

INSERT INTO categories (nama_kategori, slug) VALUES ('Coffee','coffee'),('Non-Coffee','non-coffee'),('Food','food'),('Pastry','pastry');

INSERT INTO products (category_id, kode_produk, nama_produk, harga_jual, harga_pokok, stok, stok_minimum, unit) VALUES
(1,'CF001','Espresso',15000,5000,80,15,'cup'),
(1,'CF002','Americano',18000,6000,80,15,'cup'),
(1,'CF003','Cafe Latte',25000,9000,70,15,'cup'),
(1,'CF004','Cappuccino',25000,9000,70,15,'cup'),
(1,'CF005','Kopi Susu Titik Temu',22000,8000,100,20,'cup'),
(1,'CF006','V60 Manual Brew',28000,10000,12,15,'cup'),
(2,'NC001','Matcha Latte',27000,11000,50,10,'cup'),
(2,'NC002','Chocolate Ice',24000,9000,50,10,'cup'),
(2,'NC003','Lemon Tea',16000,4000,60,10,'cup'),
(2,'NC004','Strawberry Smoothie',28000,12000,8,10,'cup'),
(3,'FD001','Nasi Goreng Kampung',32000,14000,30,8,'porsi'),
(3,'FD002','Mie Goreng Spesial',30000,13000,30,8,'porsi'),
(3,'FD003','Chicken Katsu Rice',35000,16000,25,8,'porsi'),
(3,'FD004','French Fries',20000,7000,40,10,'porsi'),
(4,'PS001','Butter Croissant',22000,9000,25,8,'pcs'),
(4,'PS002','Chocolate Donut',15000,5000,30,8,'pcs'),
(4,'PS003','Banana Bread',18000,7000,20,6,'slice'),
(4,'PS004','Cheesecake',30000,13000,6,6,'slice');
