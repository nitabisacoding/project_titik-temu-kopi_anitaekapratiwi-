<?php
require_once __DIR__ . '/functions.php';
require_login();
$u = user();
$nav = [['dashboard.php', 'Dashboard', 'all'], ['transaksi.php', 'Transaksi Baru', 'all'], ['transaksi_list.php', 'Riwayat Transaksi', 'all'],
        ['laporan.php', 'Laporan', 'admin'], ['produk.php', 'Menu & Stok', 'admin'], ['users.php', 'Pengguna', 'admin']];
$cur = basename($_SERVER['SCRIPT_NAME']);
?><!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? 'POS') ?> - <?= APP_NAME ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<aside class="side" id="side">
  <div class="brand">☕ <?= APP_NAME ?></div>
  <nav><?php foreach ($nav as [$href, $label, $acc]) { if ($acc === 'admin' && !is_admin()) continue;
      echo '<a href="' . $href . '"' . ($cur === $href ? ' class="on"' : '') . '>' . $label . '</a>'; } ?></nav>
  <div class="me"><b><?= e($u['nama_lengkap']) ?></b><small><?= $u['role'] === 'admin' ? 'Administrator' : 'Kasir / Waiter' ?></small>
    <a class="btn ghost" href="logout.php">Logout</a></div>
</aside>
<main class="main">
  <header class="top"><button class="burger" onclick="document.getElementById('side').classList.toggle('open')">☰</button><h1><?= e($pageTitle ?? '') ?></h1></header>
  <?php show_flash(); ?>
