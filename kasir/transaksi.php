<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('kasir');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* =========================
   LOGOUT
========================= */
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();

    header('Location: ../login.php');
    exit;
}

/* =========================
   CETAK STRUK
========================= */
if (isset($_GET['cetak'])) {
    $idTransaksi = (int) $_GET['cetak'];

    $stmt = $pdo->prepare("
        SELECT 
            t.id_transaksi,
            t.tanggal,
            t.nama_pembeli,
            t.total,
            t.bayar,
            t.kembalian,
            u.nama AS nama_kasir
        FROM transaksi t
        LEFT JOIN users u ON u.id = t.id_user
        WHERE t.id_transaksi = ?
        LIMIT 1
    ");
    $stmt->execute([$idTransaksi]);
    $transaksiCetak = $stmt->fetch();

    if (!$transaksiCetak) {
        die('Transaksi tidak ditemukan.');
    }

    $stmtDetail = $pdo->prepare("
        SELECT 
            dt.id_barang,
            dt.jumlah,
            dt.harga,
            dt.subtotal,
            b.nama_barang
        FROM detail_transaksi dt
        LEFT JOIN barang b ON b.id_barang = dt.id_barang
        WHERE dt.id_transaksi = ?
        ORDER BY dt.id ASC
    ");
    $stmtDetail->execute([$idTransaksi]);
    $detailCetak = $stmtDetail->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Struk #<?= (int)$transaksiCetak['id_transaksi'] ?></title>

        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                padding: 20px;
                background: #f3f4f6;
                font-family: Arial, sans-serif;
                color: #111827;
            }

            .receipt {
                width: 320px;
                margin: 0 auto;
                background: #fff;
                padding: 20px;
                border-radius: 8px;
            }

            .receipt h2 {
                margin: 0;
                text-align: center;
                color: #2D6A4F;
            }

            .subtitle {
                text-align: center;
                font-size: 12px;
                color: #64748B;
                margin-top: 4px;
            }

            .line {
                border-top: 1px dashed #9ca3af;
                margin: 14px 0;
            }

            .info {
                font-size: 12px;
                line-height: 1.7;
            }

            .item {
                margin-bottom: 10px;
                font-size: 12px;
            }

            .item-name {
                font-weight: 600;
            }

            .item-detail {
                display: flex;
                justify-content: space-between;
                color: #475569;
            }

            .total-row {
                display: flex;
                justify-content: space-between;
                font-size: 13px;
                margin: 6px 0;
            }

            .grand-total {
                font-size: 16px;
                font-weight: bold;
                color: #2D6A4F;
            }

            .footer {
                text-align: center;
                font-size: 11px;
                color: #64748B;
                margin-top: 18px;
            }

            @media print {
                body {
                    padding: 0;
                    background: #fff;
                }

                .receipt {
                    width: 100%;
                    padding: 10px;
                    border-radius: 0;
                }
            }
        </style>
    </head>

    <body onload="window.print()">

        <div class="receipt">

            <h2>E-KOPERASI</h2>
            <div class="subtitle">Struk Pembelian</div>

            <div class="line"></div>

            <div class="info">
                <div>
                    <strong>No. Transaksi:</strong>
                    #<?= (int)$transaksiCetak['id_transaksi'] ?>
                </div>

                <div>
                    <strong>Tanggal:</strong>
                    <?= date('d/m/Y H:i', strtotime($transaksiCetak['tanggal'])) ?>
                </div>

                <div>
                    <strong>Pembeli:</strong>
                    <?= e($transaksiCetak['nama_pembeli']) ?>
                </div>

                <div>
                    <strong>Kasir:</strong>
                    <?= e($transaksiCetak['nama_kasir'] ?? '-') ?>
                </div>
            </div>

            <div class="line"></div>

            <?php foreach ($detailCetak as $item): ?>
                <div class="item">
                    <div class="item-name">
                        <?= e($item['nama_barang'] ?? 'Barang') ?>
                    </div>

                    <div class="item-detail">
                        <span>
                            <?= (int)$item['jumlah'] ?>
                            ×
                            <?= rupiah($item['harga']) ?>
                        </span>

                        <span>
                            <?= rupiah($item['subtotal']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="line"></div>

            <div class="total-row">
                <span>Total</span>
                <strong><?= rupiah($transaksiCetak['total']) ?></strong>
            </div>

            <div class="total-row">
                <span>Bayar</span>
                <strong><?= rupiah($transaksiCetak['bayar']) ?></strong>
            </div>

            <div class="total-row grand-total">
                <span>Kembalian</span>
                <span><?= rupiah($transaksiCetak['kembalian']) ?></span>
            </div>

            <div class="line"></div>

            <div class="footer">
                Terima kasih telah berbelanja<br>
                di E-KOPERASI
            </div>

        </div>

    </body>
    </html>
    <?php
    exit;
}

/* =========================
   CART
========================= */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* =========================
   TAMBAH CART
========================= */
if (isset($_POST['add_cart'])) {

    $idBarang = (int)($_POST['id_barang'] ?? 0);
    $jumlah   = (int)($_POST['jumlah'] ?? 0);

    if ($idBarang > 0 && $jumlah > 0) {

        $stmt = $pdo->prepare("
            SELECT id_barang, nama_barang, harga, stok
            FROM barang
            WHERE id_barang = ?
            LIMIT 1
        ");

        $stmt->execute([$idBarang]);
        $barang = $stmt->fetch();

        if ($barang) {

            $jumlahSekarang =
                $_SESSION['cart'][$idBarang]['jumlah'] ?? 0;

            $jumlahBaru = $jumlahSekarang + $jumlah;

            if ($jumlahBaru <= (int)$barang['stok']) {

                $_SESSION['cart'][$idBarang] = [
                    'id_barang'   => (int)$barang['id_barang'],
                    'nama_barang' => $barang['nama_barang'],
                    'harga'       => (float)$barang['harga'],
                    'jumlah'      => $jumlahBaru,
                    'stok'        => (int)$barang['stok']
                ];
            }
        }
    }

    header('Location: transaksi.php');
    exit;
}

/* =========================
   HAPUS CART
========================= */
if (isset($_GET['remove'])) {

    $idBarang = (int)$_GET['remove'];

    unset($_SESSION['cart'][$idBarang]);

    header('Location: transaksi.php');
    exit;
}

/* =========================
   CLEAR CART
========================= */
if (isset($_GET['clear_cart'])) {

    $_SESSION['cart'] = [];

    header('Location: transaksi.php');
    exit;
}

/* =========================
   CHECKOUT
========================= */
$success = $_SESSION['success_transaksi'] ?? '';
unset($_SESSION['success_transaksi']);

$lastTransactionId =
    $_SESSION['last_transaction_id'] ?? null;

unset($_SESSION['last_transaction_id']);

$error = '';

if (isset($_POST['checkout'])) {

    $namaPembeli = trim($_POST['nama_pembeli'] ?? '');
    $bayar = (float)($_POST['bayar'] ?? 0);

    $cart = $_SESSION['cart'] ?? [];

    if ($namaPembeli === '') {

        $error = 'Nama pembeli wajib diisi.';

    } elseif (empty($cart)) {

        $error = 'Keranjang masih kosong.';

    } else {

        $total = 0;

        foreach ($cart as $item) {
            $total += $item['harga'] * $item['jumlah'];
        }

        if ($bayar < $total) {

            $error = 'Nominal pembayaran kurang.';

        } else {

            try {

                $pdo->beginTransaction();

                $idUser =
                    $_SESSION['user']['id']
                    ?? $_SESSION['user']['id_user']
                    ?? null;

                if (!$idUser) {
                    throw new Exception('User tidak ditemukan.');
                }

                foreach ($cart as $item) {

                    $stmt = $pdo->prepare("
                        SELECT id_barang, nama_barang, harga, stok
                        FROM barang
                        WHERE id_barang = ?
                        FOR UPDATE
                    ");

                    $stmt->execute([
                        $item['id_barang']
                    ]);

                    $barang = $stmt->fetch();

                    if (!$barang) {
                        throw new Exception(
                            'Barang ' .
                            $item['nama_barang'] .
                            ' tidak ditemukan.'
                        );
                    }

                    if (
                        (int)$barang['stok']
                        <
                        (int)$item['jumlah']
                    ) {
                        throw new Exception(
                            'Stok ' .
                            $barang['nama_barang'] .
                            ' tidak mencukupi.'
                        );
                    }
                }

                $kembalian = $bayar - $total;

                $stmt = $pdo->prepare("
                    INSERT INTO transaksi
                    (
                        tanggal,
                        nama_pembeli,
                        id_user,
                        total,
                        bayar,
                        kembalian
                    )
                    VALUES
                    (
                        NOW(),
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $namaPembeli,
                    $idUser,
                    $total,
                    $bayar,
                    $kembalian
                ]);

                $idTransaksi =
                    (int)$pdo->lastInsertId();

                foreach ($cart as $item) {

                    $subtotal =
                        $item['harga'] *
                        $item['jumlah'];

                    $stmt = $pdo->prepare("
                        INSERT INTO detail_transaksi
                        (
                            id_transaksi,
                            id_barang,
                            jumlah,
                            harga,
                            subtotal
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?
                        )
                    ");

                    $stmt->execute([
                        $idTransaksi,
                        $item['id_barang'],
                        $item['jumlah'],
                        $item['harga'],
                        $subtotal
                    ]);

                    $stmt = $pdo->prepare("
                        UPDATE barang
                        SET stok = stok - ?
                        WHERE id_barang = ?
                    ");

                    $stmt->execute([
                        $item['jumlah'],
                        $item['id_barang']
                    ]);
                }

                $pdo->commit();

                $_SESSION['cart'] = [];

                $_SESSION['last_transaction_id'] =
                    $idTransaksi;

                $_SESSION['success_transaksi'] =
                    'Transaksi berhasil disimpan.';

                header('Location: transaksi.php');
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = $e->getMessage();
            }
        }
    }
}

/* =========================
   SEARCH BARANG
========================= */
$q = trim($_GET['q'] ?? '');

if ($q !== '') {

    $stmt = $pdo->prepare("
        SELECT
            id_barang,
            nama_barang,
            kategori,
            harga,
            stok,
            foto
        FROM barang
        WHERE stok > 0
          AND nama_barang LIKE ?
        ORDER BY nama_barang ASC
        LIMIT 50
    ");

    $stmt->execute([
        '%' . $q . '%'
    ]);

} else {

    $stmt = $pdo->query("
        SELECT
            id_barang,
            nama_barang,
            kategori,
            harga,
            stok,
            foto
        FROM barang
        WHERE stok > 0
        ORDER BY nama_barang ASC
        LIMIT 50
    ");
}

$barangList = $stmt->fetchAll();

/* =========================
   TOTAL CART
========================= */
$totalCart = 0;
$totalItem = 0;

foreach ($_SESSION['cart'] as $item) {

    $totalCart +=
        $item['harga'] *
        $item['jumlah'];

    $totalItem +=
        $item['jumlah'];
}

$userNama =
    $_SESSION['user']['nama'] ?? 'Kasir';

$userRole =
    $_SESSION['user']['role'] ?? 'kasir';

$todayLabel = date('d F Y');
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Transaksi Kasir - E-KOPERASI</title>

<style>

:root {
    --green: #2D6A4F;
    --green-2: #3B8060;
    --green-dark: #24563F;
    --green-light: #D8F3DC;

    --text: #111827;
    --muted: #738099;
    --border: #e1e7f0;
    --bg: #f7f9fc;
    --white: #ffffff;

    --success: #0e9f5a;
    --red: #e5484d;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: var(--bg);
    color: var(--text);
}

/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 263px;
    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #2D6A4F 0%,
            #285F47 55%,
            #24563F 100%
        );

    padding: 28px 15px 20px;

    z-index: 1000;

    display: flex;
    flex-direction: column;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 0 10px;
    margin-bottom: 30px;
}

.brand-icon {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;
}

.brand-icon svg {
    width: 42px;
    height: 42px;
}

.brand-text h2 {
    margin: 0;
    color: #fff;

    font-size: 19px;
    font-weight: 800;
}

.brand-text span {
    display: block;

    color: rgba(255,255,255,.72);

    font-size: 10px;
    margin-top: 3px;
}

.nav-menu {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.nav-item {
    width: 100%;
    min-height: 49px;

    padding: 0 14px;

    border-radius: 8px;

    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;

    gap: 15px;

    color: rgba(255,255,255,.82);

    text-decoration: none;

    font-size: 14px;
    font-weight: 600;

    transition: .2s ease;
}

.nav-item:hover {
    color: #fff;
    background: rgba(255,255,255,.10);
}

.nav-item.active {
    color: #fff;

    background:
        linear-gradient(
            135deg,
            #3B8060,
            #2D6A4F
        );

    box-shadow:
        0 5px 14px rgba(20,67,47,.25);
}

.nav-icon {
    width: 21px;
    height: 21px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;
}

.nav-icon svg {
    width: 21px;
    height: 21px;

    stroke: currentColor;
    fill: none;

    stroke-width: 1.8;

    stroke-linecap: round;
    stroke-linejoin: round;
}

.sidebar-bottom {
    margin-top: auto;
}

.logout {
    background: rgba(20,67,47,.55);
}

.logout:hover {
    background: rgba(20,67,47,.75);
}

/* =========================
   MAIN
========================= */

.main {
    margin-left: 263px;
    min-height: 100vh;
}

/* =========================
   TOPBAR
========================= */

.topbar {
    height: 82px;

    background: #fff;

    border-bottom:
        1px solid var(--border);

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 30px;
}

.topbar-left h1 {
    margin: 0;

    font-size: 23px;
    color: #1f2937;
}

.topbar-left p {
    margin: 5px 0 0;

    color: var(--muted);

    font-size: 12px;
}

.profile {
    display: flex;
    align-items: center;

    gap: 10px;
}

.profile-avatar {
    width: 40px;
    height: 40px;

    border-radius: 50%;

    background: var(--green-light);
    color: var(--green);

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: 800;
}

.profile-info strong {
    display: block;

    font-size: 13px;
}

.profile-info span {
    display: block;

    color: var(--muted);

    font-size: 11px;

    margin-top: 2px;
}

/* =========================
   CONTENT
========================= */

.content {
    padding: 22px 30px;
}

/* =========================
   ALERT
========================= */

.alert {
    padding: 13px 16px;

    border-radius: 8px;

    margin-bottom: 18px;

    font-size: 13px;
}

.alert-error {
    background: #fff1f2;
    color: #be123c;

    border: 1px solid #fecdd3;
}

.alert-success {
    background: #ecfdf5;
    color: #047857;

    border: 1px solid #a7f3d0;
}

.print-receipt-btn {
    display: inline-block;

    margin-left: 10px;

    padding: 7px 12px;

    border-radius: 6px;

    background: var(--green);

    color: #fff;

    text-decoration: none;

    font-size: 12px;
    font-weight: 700;
}

/* =========================
   TRANSACTION LAYOUT
========================= */

.transaction-layout {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        360px;

    gap: 20px;

    align-items: start;
}

/* =========================
   LEFT
========================= */

.products-section {
    min-width: 0;
}

/* =========================
   SEARCH
========================= */

.search-box {
    display: flex;

    gap: 10px;

    margin-bottom: 18px;
}

.search-box input {
    flex: 1;

    height: 44px;

    border:
        1px solid var(--border);

    border-radius: 8px;

    padding: 0 14px;

    outline: none;

    background: #fff;
}

.search-box input:focus {
    border-color: var(--green);

    box-shadow:
        0 0 0 3px
        rgba(45,106,79,.08);
}

.btn-search {
    height: 44px;

    padding: 0 20px;

    border: none;

    border-radius: 8px;

    background: var(--green);

    color: #fff;

    font-weight: 700;

    cursor: pointer;
}

.btn-search:hover {
    background: var(--green-dark);
}

/* =========================
   PRODUCT GRID
========================= */

.product-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 14px;
}

.product-card {
    background: #fff;

    border:
        1px solid var(--border);

    border-radius: 9px;

    padding: 15px;
}

.product-name {
    font-size: 13px;
    font-weight: 700;

    margin-bottom: 5px;
}

.product-category {
    font-size: 10px;

    color: var(--muted);
}

.product-price {
    margin-top: 10px;

    color: var(--green);

    font-size: 14px;

    font-weight: 800;
}

.product-stock {
    font-size: 10px;

    color: var(--muted);

    margin-top: 4px;
}

.btn-add {
    width: 100%;

    height: 36px;

    margin-top: 12px;

    border: none;

    border-radius: 7px;

    background: var(--green);

    color: #fff;

    font-weight: 700;

    cursor: pointer;
}

.btn-add:hover {
    background: var(--green-dark);
}

/* =========================
   RIGHT CART
========================= */

.cart-box {
    background: #fff;

    border:
        1px solid var(--border);

    border-radius: 10px;

    padding: 20px;

    position: sticky;

    top: 20px;
}

.cart-title {
    font-size: 17px;

    font-weight: 800;

    margin-bottom: 16px;
}

.cart-empty {
    color: var(--muted);

    font-size: 13px;

    padding: 20px 0;
}

.cart-item {
    display: flex;

    align-items: flex-start;
    justify-content: space-between;

    gap: 10px;

    padding: 12px 0;

    border-bottom:
        1px solid #eef1f5;
}

.cart-item-name {
    font-size: 13px;

    font-weight: 700;
}

.cart-item-detail {
    font-size: 11px;

    color: var(--muted);

    margin-top: 4px;
}

.cart-item-right {
    text-align: right;

    white-space: nowrap;
}

.cart-item-price {
    font-size: 12px;

    font-weight: 700;
}

.remove-btn {
    display: block;

    color: var(--red);

    text-decoration: none;

    font-size: 10px;

    margin-top: 5px;
}

/* =========================
   SUMMARY
========================= */

.summary {
    margin-top: 16px;

    padding-top: 15px;

    border-top:
        1px solid var(--border);
}

.summary-total {
    display: flex;

    justify-content: space-between;

    font-size: 18px;

    font-weight: 800;

    color: var(--green);

    margin-bottom: 18px;
}

/* =========================
   FORM
========================= */

.form-group {
    margin-bottom: 13px;
}

.form-label {
    display: block;

    margin-bottom: 6px;

    font-size: 12px;

    font-weight: 700;
}

.form-input {
    width: 100%;

    height: 42px;

    border:
        1px solid var(--border);

    border-radius: 7px;

    padding: 0 12px;

    outline: none;
}

.form-input:focus {
    border-color: var(--green);

    box-shadow:
        0 0 0 3px
        rgba(45,106,79,.08);
}

.checkout-button {
    width: 100%;

    height: 44px;

    border: none;

    border-radius: 8px;

    background: var(--success);

    color: #fff;

    font-weight: 800;

    cursor: pointer;
}

.checkout-button:hover {
    opacity: .9;
}

.clear-cart {
    display: block;

    text-align: center;

    margin-top: 12px;

    color: var(--red);

    font-size: 11px;

    text-decoration: none;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .transaction-layout {
        grid-template-columns:
            minmax(0, 1fr)
            320px;
    }

    .product-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {

    .transaction-layout {
        grid-template-columns: 1fr;
    }

    .cart-box {
        position: static;
    }

    .product-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 850px) {

    .sidebar {
        width: 72px;

        padding-left: 10px;
        padding-right: 10px;
    }

    .main {
        margin-left: 72px;
    }

    .brand-text,
    .nav-item span {
        display: none;
    }

    .brand {
        justify-content: center;

        padding: 0;
    }

    .nav-item {
        justify-content: center;

        padding: 0;
    }

    .topbar {
        padding: 0 20px;
    }

    .content {
        padding: 20px;
    }
}

@media (max-width: 600px) {

    .product-grid {
        grid-template-columns: 1fr;
    }

    .content {
        padding: 16px 14px;
    }

    .topbar-left h1 {
        font-size: 20px;
    }

    .profile-info {
        display: none;
    }
}

</style>

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="brand">

        <div class="brand-icon">

            <svg viewBox="0 0 48 48">

                <path
                    d="M24 3L40 12V30L24 39L8 30V12L24 3Z"
                    stroke="white"
                    stroke-width="2.5"
                    fill="none"
                />

                <path
                    d="M15 16H20L22 27H34L37 19H21"
                    stroke="white"
                    stroke-width="2"
                    fill="none"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />

                <circle
                    cx="24"
                    cy="31"
                    r="2"
                    fill="white"
                />

                <circle
                    cx="33"
                    cy="31"
                    r="2"
                    fill="white"
                />

            </svg>

        </div>

        <div class="brand-text">

            <h2>E-KOPERASI</h2>

            <span>Sistem Koperasi</span>

        </div>

    </div>


    <nav class="nav-menu">

        <!-- DASHBOARD -->
        <a
            href="dashboard.php"
            class="nav-item"
        >

            <div class="nav-icon">

                <svg viewBox="0 0 24 24">

                    <rect
                        x="3"
                        y="3"
                        width="7"
                        height="7"
                    />

                    <rect
                        x="14"
                        y="3"
                        width="7"
                        height="7"
                    />

                    <rect
                        x="3"
                        y="14"
                        width="7"
                        height="7"
                    />

                    <rect
                        x="14"
                        y="14"
                        width="7"
                        height="7"
                    />

                </svg>

            </div>

            <span>Dashboard</span>

        </a>


        <!-- TRANSAKSI -->
        <a
            href="transaksi.php"
            class="nav-item active"
        >

            <div class="nav-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M3 4H5L7 15H18L21 7H6"/>

                    <circle
                        cx="9"
                        cy="19"
                        r="1.5"
                    />

                    <circle
                        cx="17"
                        cy="19"
                        r="1.5"
                    />

                </svg>

            </div>

            <span>Transaksi</span>

        </a>

        <!-- RIWAYAT -->
        <a
            href="riwayat.php"
            class="nav-item"
        >

            <div class="nav-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M3 12A9 9 0 1 0 6 5"/>

                    <path d="M3 4V9H8"/>

                    <path d="M12 7V12L15 14"/>

                </svg>

            </div>

            <span>Riwayat</span>

        </a>

           <!-- CETAK STRUK -->
        <a
            href="cetak_struk.php"
            class="nav-item"
        >

            <div class="nav-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M6 9V3H18V9"/>

                    <path
                        d="M6 18H4C3.4 18 3 17.6 3 17V10C3 9.4 3.4 9 4 9H20C20.6 9 21 9.4 21 10V17C21 17.6 20.6 18 20 18H18"
                    />

                    <rect
                        x="6"
                        y="14"
                        width="12"
                        height="7"
                    />

                    <path d="M17 12H17.01"/>

                </svg>

            </div>

            <span>Cetak Struk</span>

        </a>

    </nav>


    <div class="sidebar-bottom">

        <a
            href="?logout=1"
            class="nav-item logout"
        >

            <div class="nav-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M10 17L15 12L10 7"/>

                    <path d="M15 12H3"/>

                    <path d="M21 3V21"/>

                </svg>

            </div>

            <span>Logout</span>

        </a>

    </div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">

    <!-- TOPBAR -->
    <header class="topbar">

        <div class="topbar-left">

            <h1>Transaksi</h1>

            <p>
                Kelola transaksi penjualan koperasi
            </p>

        </div>


        <div class="profile">

            <div class="profile-avatar">
                <?= strtoupper(
                    substr($userNama, 0, 1)
                ) ?>
            </div>

            <div class="profile-info">

                <strong>
                    <?= e($userNama) ?>
                </strong>

                <span>
                    <?= e(
                        ucfirst($userRole)
                    ) ?>
                </span>

            </div>

        </div>

    </header>


    <section class="content">

        <!-- ALERT ERROR -->
        <?php if ($error): ?>

            <div class="alert alert-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <!-- ALERT SUCCESS -->
        <?php if ($success): ?>

            <div class="alert alert-success">

                <?= e($success) ?>

                <?php if ($lastTransactionId): ?>

                    <a
                        href="?cetak=<?= (int)$lastTransactionId ?>"
                        target="_blank"
                        class="print-receipt-btn"
                    >
                        Cetak Struk
                    </a>

                <?php endif; ?>

            </div>

        <?php endif; ?>


        <!-- =========================
             KIRI + KANAN
        ========================= -->

        <div class="transaction-layout">


            <!-- =====================
                 KIRI: DAFTAR BARANG
            ====================== -->

            <div class="products-section">

                <form
                    method="get"
                    class="search-box"
                >

                    <input
                        type="text"
                        name="q"
                        value="<?= e($q) ?>"
                        placeholder="Cari nama barang..."
                    >

                    <button
                        type="submit"
                        class="btn-search"
                    >
                        Cari
                    </button>

                </form>


                <div class="product-grid">

                    <?php if (empty($barangList)): ?>

                        <div
                            style="
                                grid-column:1/-1;
                                background:#fff;
                                border:1px solid #e1e7f0;
                                border-radius:9px;
                                padding:30px;
                                text-align:center;
                                color:#738099;
                                font-size:13px;
                            "
                        >
                            Barang tidak ditemukan.
                        </div>

                    <?php endif; ?>


                    <?php foreach ($barangList as $barang): ?>

                        <div class="product-card">

                            <div class="product-name">
                                <?= e(
                                    $barang['nama_barang']
                                ) ?>
                            </div>

                            <div class="product-category">
                                <?= e(
                                    $barang['kategori'] ?? '-'
                                ) ?>
                            </div>

                            <div class="product-price">
                                <?= rupiah(
                                    $barang['harga']
                                ) ?>
                            </div>

                            <div class="product-stock">
                                Stok:
                                <?= (int)$barang['stok'] ?>
                            </div>


                            <form method="post">

                                <input
                                    type="hidden"
                                    name="id_barang"
                                    value="<?= (int)$barang['id_barang'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="jumlah"
                                    value="1"
                                >

                                <button
                                    type="submit"
                                    name="add_cart"
                                    class="btn-add"
                                >
                                    + Tambah
                                </button>

                            </form>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>


            <!-- =====================
                 KANAN: CHECKOUT
            ====================== -->

            <div class="cart-box">

                <div class="cart-title">
                    Keranjang
                    (<?= (int)$totalItem ?> item)
                </div>


                <?php if (empty($_SESSION['cart'])): ?>

                    <div class="cart-empty">
                        Belum ada barang di keranjang.
                    </div>

                <?php else: ?>


                    <!-- CART ITEMS -->

                    <?php foreach (
                        $_SESSION['cart']
                        as $item
                    ): ?>

                        <div class="cart-item">

                            <div>

                                <div class="cart-item-name">
                                    <?= e(
                                        $item['nama_barang']
                                    ) ?>
                                </div>

                                <div class="cart-item-detail">

                                    <?= (int)$item['jumlah'] ?>

                                    ×

                                    <?= rupiah(
                                        $item['harga']
                                    ) ?>

                                </div>

                            </div>


                            <div class="cart-item-right">

                                <div class="cart-item-price">

                                    <?= rupiah(
                                        $item['harga']
                                        *
                                        $item['jumlah']
                                    ) ?>

                                </div>

                                <a
                                    href="?remove=<?= (int)$item['id_barang'] ?>"
                                    class="remove-btn"
                                >
                                    Hapus
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>


                    <!-- SUMMARY -->

                    <div class="summary">

                        <div class="summary-total">

                            <span>
                                Total
                            </span>

                            <span>
                                <?= rupiah($totalCart) ?>
                            </span>

                        </div>


                        <!-- CHECKOUT FORM -->

                        <form
                            method="post"
                        >

                            <div class="form-group">

                                <label class="form-label">
                                    Nama Pembeli
                                </label>

                                <input
                                    type="text"
                                    name="nama_pembeli"
                                    class="form-input"
                                    placeholder="Nama pembeli"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label class="form-label">
                                    Uang Bayar
                                </label>

                                <input
                                    type="number"
                                    name="bayar"
                                    class="form-input"
                                    min="0"
                                    step="100"
                                    placeholder="Masukkan uang bayar"
                                    required
                                >

                            </div>


                            <button
                                type="submit"
                                name="checkout"
                                class="checkout-button"
                            >
                                Proses Transaksi
                            </button>

                        </form>


                        <a
                            href="?clear_cart=1"
                            class="clear-cart"
                        >
                            Kosongkan Keranjang
                        </a>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>

</main>

</body>
</html>