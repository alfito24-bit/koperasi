<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

function e_html($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$stokMinimum = 10;

$stmt = $pdo->query("
    SELECT id_barang, nama_barang, kategori, harga, stok, foto
    FROM barang
    ORDER BY nama_barang ASC
");
$barang = $stmt->fetchAll();

$userNama = $_SESSION['user']['nama'] ?? 'Admin';
$userRole = $_SESSION['user']['role'] ?? 'admin';

$namaHari = [
    'Sunday'    => 'Minggu',
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu'
];

$namaBulan = [
    1  => 'Januari',
    2  => 'Februari',
    3  => 'Maret',
    4  => 'April',
    5  => 'Mei',
    6  => 'Juni',
    7  => 'Juli',
    8  => 'Agustus',
    9  => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

$todayLabel =
    ($namaHari[date('l')] ?? date('l'))
    . ', ' . date('d')
    . ' ' . ($namaBulan[(int)date('n')] ?? date('F'))
    . ' ' . date('Y');
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Stok Barang - E-KOPERASI</title>

<style>

/* =========================================================
   RESET & VARIABLE
========================================================= */

* {
    box-sizing: border-box;
}

:root {
    --primary: #2D6A4F;
    --primary-dark: #24563F;
    --primary-light: #D8F3DC;

    --background: #F1FAF3;
    --card: #FFFFFF;

    --text: #1F2937;
    --muted: #64748B;

    --border: #DDE7E1;

    --danger: #DC2626;

    --warning-bg: #FFF7E6;
    --warning-text: #A16207;

    --shadow: 0 4px 16px rgba(36, 86, 63, 0.06);
}

html,
body {
    margin: 0;
    min-height: 100%;
}

body {
    min-height: 100vh;

    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    background: var(--background);
    color: var(--text);
}

button,
a {
    font: inherit;
}

a {
    text-decoration: none;
    color: inherit;
}


/* =========================================================
   APP LAYOUT
========================================================= */

.app {
    min-height: 100vh;
    display: flex;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
    width: 263px;
    min-height: 100vh;

    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;

    z-index: 30;

    display: flex;
    flex-direction: column;

    padding: 28px 15px 20px;

    background:
        linear-gradient(
            180deg,
            #2D6A4F 0%,
            #276247 55%,
            #24563F 100%
        );

    color: #FFFFFF;
}


/* =========================================================
   BRAND
========================================================= */

.brand {
    display: flex;
    align-items: center;

    gap: 13px;

    padding: 0 12px;

    margin-bottom: 34px;
}

.brand-mark {
    width: 50px;
    height: 55px;

    position: relative;

    flex: 0 0 auto;
}

.brand-mark::before {
    content: "";

    position: absolute;

    inset: 0;

    background: #FFFFFF;

    clip-path:
        polygon(
            50% 0,
            88% 22%,
            88% 75%,
            50% 100%,
            12% 75%,
            12% 22%
        );
}


/* Logo cart */

.brand-cart {
    position: absolute;

    left: 15px;
    top: 20px;

    width: 21px;
    height: 15px;

    border:
        2.5px solid var(--primary);

    border-top: 0;

    border-radius:
        2px 2px 5px 5px;
}

.brand-cart::before {
    content: "";

    position: absolute;

    width: 12px;
    height: 2.5px;

    background: var(--primary);

    left: -6px;
    top: -7px;

    transform: rotate(12deg);

    border-radius: 5px;
}

.brand-cart::after {
    content: "";

    position: absolute;

    width: 4px;
    height: 4px;

    background: var(--primary);

    border-radius: 50%;

    left: 3px;
    bottom: -7px;

    box-shadow:
        11px 0 0 var(--primary);
}

.brand-cart-line {
    position: absolute;

    width: 11px;
    height: 2px;

    background: var(--primary);

    left: 4px;
    top: 5px;

    box-shadow:
        0 5px 0 var(--primary);
}


.brand-title {
    margin: 0;

    font-size: 25px;
    line-height: 1;

    font-weight: 800;

    letter-spacing: -0.5px;
}

.brand-caption {
    margin: 6px 0 0;

    font-size: 9px;

    color:
        rgba(255, 255, 255, 0.86);

    white-space: nowrap;
}


/* =========================================================
   NAVIGATION
========================================================= */

.nav {
    flex: 1;

    overflow-y: auto;

    padding-right: 2px;
}

.nav-item {
    min-height: 49px;

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 0 14px;

    margin-bottom: 5px;

    border-radius: 10px;

    color:
        rgba(255, 255, 255, 0.96);

    font-size: 14px;

    transition:
        background 0.18s ease,
        transform 0.18s ease;
}

.nav-item:hover {
    background:
        rgba(255, 255, 255, 0.10);

    transform: translateX(2px);
}

.nav-item.active {
    background:
        linear-gradient(
            135deg,
            #40916C,
            #2D6A4F
        );

    box-shadow:
        inset 0 0 0 1px
        rgba(255, 255, 255, 0.08),

        0 4px 12px
        rgba(0, 0, 0, 0.08);
}

.nav-icon {
    width: 20px;
    height: 20px;

    position: relative;

    flex: 0 0 20px;
}


/* =========================================================
   SIDEBAR ICONS
========================================================= */

/* Home */

.icon-home::before {
    content: "";

    position: absolute;

    left: 3px;
    bottom: 2px;

    width: 14px;
    height: 11px;

    background: #FFFFFF;

    border-radius: 2px;
}

.icon-home::after {
    content: "";

    position: absolute;

    left: 2px;
    top: 1px;

    width: 13px;
    height: 13px;

    background: #FFFFFF;

    transform: rotate(45deg);

    border-radius: 2px;
}


/* Cart */

.icon-cart {
    border:
        2px solid #FFFFFF;

    border-top: 0;

    width: 15px;
    height: 12px;

    left: 3px;
    top: 4px;

    border-radius:
        2px 2px 4px 4px;
}

.icon-cart::before {
    content: "";

    position: absolute;

    width: 8px;
    height: 2px;

    background: #FFFFFF;

    left: -5px;
    top: -5px;

    transform: rotate(10deg);
}

.icon-cart::after {
    content: "";

    position: absolute;

    width: 3px;
    height: 3px;

    border-radius: 50%;

    background: #FFFFFF;

    left: 2px;
    bottom: -7px;

    box-shadow:
        8px 0 0 #FFFFFF;
}


/* Box */

.icon-box {
    border:
        2px solid #FFFFFF;

    border-radius: 3px;

    width: 15px;
    height: 14px;

    left: 3px;
    top: 3px;
}

.icon-box::before {
    content: "";

    position: absolute;

    width: 11px;
    height: 3px;

    left: 0;
    top: -5px;

    border:
        2px solid #FFFFFF;

    border-bottom: 0;
}


/* Layers */

.icon-layers::before,
.icon-layers::after {
    content: "";

    position: absolute;

    left: 2px;

    width: 16px;
    height: 7px;

    border:
        2px solid #FFFFFF;

    transform: skewY(-25deg);

    border-radius: 2px;
}

.icon-layers::before {
    top: 3px;
}

.icon-layers::after {
    top: 9px;
}


/* Income / expense */

.icon-arrow {
    border:
        2px solid #95D5B2;

    border-radius: 4px;
}

.icon-arrow::after {
    content: "";

    position: absolute;

    width: 8px;
    height: 8px;

    border-top:
        2px solid #95D5B2;

    border-right:
        2px solid #95D5B2;

    right: 2px;
    top: 2px;
}


/* Report */

.icon-report {
    border:
        2px solid #FFFFFF;

    border-radius: 3px;

    width: 14px;
    height: 17px;

    left: 3px;
    top: 1px;
}

.icon-report::after {
    content: "";

    position: absolute;

    width: 8px;
    height: 2px;

    background: #FFFFFF;

    left: 2px;
    top: 6px;

    box-shadow:
        0 4px 0 #FFFFFF;
}


/* Users */

.icon-users::before,
.icon-users::after {
    content: "";

    position: absolute;

    border:
        2px solid #FFFFFF;

    border-radius: 50%;
}

.icon-users::before {
    width: 7px;
    height: 7px;

    left: 2px;
    top: 2px;
}

.icon-users::after {
    width: 12px;
    height: 7px;

    left: 0;
    bottom: 1px;

    border-radius:
        8px 8px 3px 3px;
}


/* Logout */

.icon-logout {
    border:
        2px solid #FFFFFF;

    border-left: 0;

    border-radius: 2px;

    width: 15px;
    height: 17px;

    left: 3px;
    top: 1px;
}

.icon-logout::before {
    content: "";

    position: absolute;

    left: -7px;
    top: 7px;

    width: 12px;
    height: 2px;

    background: #FFFFFF;
}

.icon-logout::after {
    content: "";

    position: absolute;

    left: -7px;
    top: 4px;

    width: 7px;
    height: 7px;

    border-top:
        2px solid #FFFFFF;

    border-right:
        2px solid #FFFFFF;

    transform: rotate(45deg);
}


/* =========================================================
   LOGOUT
========================================================= */

.logout {
    margin-top: 15px;

    min-height: 64px;

    background:
        rgba(10, 48, 34, 0.34) !important;
}

.logout:hover {
    background:
        rgba(10, 48, 34, 0.50) !important;

    transform: none;
}


/* =========================================================
   MAIN CONTENT
========================================================= */

.content {
    width:
        calc(100% - 263px);

    margin-left: 263px;

    min-height: 100vh;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {
    height: 100px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 35px 0 28px;

    background: #FFFFFF;

    border-bottom:
        1px solid var(--border);
}

.top-left {
    display: flex;

    align-items: center;

    gap: 25px;
}


/* Menu button */

.menu-button {
    width: 25px;
    height: 25px;

    position: relative;

    border: 0;

    background: transparent;

    cursor: pointer;
}

.menu-button::before,
.menu-button::after,
.menu-button span {
    content: "";

    position: absolute;

    left: 1px;

    width: 21px;
    height: 3px;

    background: var(--primary);

    border-radius: 5px;
}

.menu-button::before {
    top: 4px;
}

.menu-button span {
    top: 11px;
}

.menu-button::after {
    top: 18px;
}


/* Page title */

.page-title {
    margin: 0;

    font-size: 21px;

    line-height: 1.2;

    font-weight: 800;
}

.page-subtitle {
    margin: 3px 0 0;

    font-size: 12px;

    color: var(--muted);
}


/* =========================================================
   TOP RIGHT
========================================================= */

.top-right {
    display: flex;

    align-items: center;

    gap: 21px;
}


/* Date */

.date-chip {
    min-height: 45px;

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 0 16px;

    border:
        1px solid var(--border);

    border-radius: 9px;

    color: var(--text);

    font-size: 12px;

    background: #FFFFFF;
}

.calendar-icon {
    width: 18px;
    height: 18px;

    position: relative;

    border:
        2px solid var(--primary);

    border-radius: 3px;
}

.calendar-icon::before {
    content: "";

    position: absolute;

    left: 2px;
    right: 2px;
    top: 4px;

    border-top:
        2px solid var(--primary);
}

.calendar-icon::after {
    content: "";

    position: absolute;

    width: 2px;
    height: 4px;

    left: 3px;
    top: -4px;

    background: var(--primary);

    box-shadow:
        8px 0 0 var(--primary);
}


/* Profile */

.profile {
    display: flex;

    align-items: center;

    gap: 11px;
}

.profile-avatar {
    width: 38px;
    height: 38px;

    position: relative;

    border-radius: 50%;

    background:
        var(--primary-light);
}

.profile-avatar::before {
    content: "";

    position: absolute;

    width: 11px;
    height: 11px;

    left: 50%;
    top: 7px;

    transform:
        translateX(-50%);

    border-radius: 50%;

    background:
        var(--primary);
}

.profile-avatar::after {
    content: "";

    position: absolute;

    width: 20px;
    height: 11px;

    left: 50%;
    bottom: 7px;

    transform:
        translateX(-50%);

    border-radius:
        10px 10px 4px 4px;

    background:
        var(--primary);
}

.profile-name {
    margin: 0;

    font-size: 14px;

    font-weight: 750;
}

.profile-role {
    margin: 2px 0 0;

    font-size: 10px;

    color: var(--muted);
}

.profile-chevron {
    width: 8px;
    height: 8px;

    margin-left: 14px;

    border-right:
        2px solid #334155;

    border-bottom:
        2px solid #334155;

    transform:
        rotate(45deg)
        translateY(-3px);
}


/* =========================================================
   STOCK CONTENT
========================================================= */

.main {
    padding: 21px;
}

.stock-page {
    padding: 28px;
}

.stock-panel {
    background: var(--card);

    border:
        1px solid var(--border);

    border-radius: 14px;

    padding: 24px;

    box-shadow: var(--shadow);
}

.stock-head {
    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 16px;

    margin-bottom: 22px;
}

.stock-title {
    margin: 0;

    font-size: 24px;

    font-weight: 800;

    letter-spacing: -0.3px;
}

.stock-subtitle {
    margin: 6px 0 0;

    color: var(--muted);

    font-size: 13px;
}


/* =========================================================
   TABLE
========================================================= */

.stock-table-wrap {
    overflow-x: auto;

    border:
        1px solid var(--border);

    border-radius: 10px;
}

.stock-table {
    width: 100%;

    border-collapse: collapse;

    min-width: 760px;
}

.stock-table th {
    text-align: left;

    padding: 14px 12px;

    background: #F8FCF9;

    color: #334155;

    font-size: 12px;

    font-weight: 700;

    border-bottom:
        1px solid var(--border);

    white-space: nowrap;
}

.stock-table td {
    padding: 15px 12px;

    border-bottom:
        1px solid #E8F0EA;

    font-size: 14px;

    vertical-align: middle;
}

.stock-table tbody tr {
    transition:
        background 0.15s ease;
}

.stock-table tbody tr:hover {
    background: #F8FCF9;
}

.stock-table tbody tr:last-child td {
    border-bottom: 0;
}

.stock-table td strong {
    font-weight: 700;
}


/* =========================================================
   STOCK STATUS
========================================================= */

.stock-status {
    display: inline-flex;

    align-items: center;

    padding:
        6px 10px;

    border-radius: 999px;

    font-size: 12px;

    font-weight: 700;
}

.stock-status.aman {
    background:
        var(--primary-light);

    color:
        var(--primary-dark);
}

.stock-status.menipis {
    background:
        var(--warning-bg);

    color:
        var(--warning-text);
}

.stock-status.habis {
    background:
        #FEF2F2;

    color:
        var(--danger);
}


/* Stock number */

.stock-number {
    font-weight: 700;

    color: var(--text);
}


/* Edit */

.panel-link {
    color:
        var(--primary);

    font-size: 13px;

    font-weight: 700;
}

.panel-link:hover {
    color:
        var(--primary-dark);

    text-decoration: underline;
}


/* Empty */

.empty {
    padding: 30px !important;

    text-align: center;

    color: var(--muted);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1250px) {

    .sidebar {
        width: 235px;
    }

    .content {
        width:
            calc(100% - 235px);

        margin-left: 235px;
    }

}


@media (max-width: 850px) {

    .sidebar {
        width: 72px;

        padding-left: 10px;
        padding-right: 10px;
    }

    .brand {
        justify-content: center;

        padding: 0;
    }

    .brand > div:last-child,
    .nav-item span:not(.nav-icon),
    .logout span:not(.nav-icon) {
        display: none;
    }

    .nav-item {
        justify-content: center;

        padding: 0;
    }

    .content {
        width:
            calc(100% - 72px);

        margin-left: 72px;
    }

    .topbar {
        padding:
            0 18px;
    }

    .profile-name,
    .profile-role,
    .profile-chevron {
        display: none;
    }

    .main,
    .stock-page {
        padding: 14px;
    }

    .stock-panel {
        padding: 14px;
    }

    .stock-head {
        align-items: flex-start;

        flex-direction: column;
    }

}


@media (max-width: 600px) {

    .topbar {
        height: 78px;
    }

    .top-right {
        gap: 8px;
    }

    .date-chip {
        display: none;
    }

    .top-left {
        gap: 13px;
    }

    .page-title {
        font-size: 18px;
    }

}

</style>
</head>

<body>

<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">

            <div class="brand-mark" aria-hidden="true">
                <span class="brand-cart">
                    <span class="brand-cart-line"></span>
                </span>
            </div>

            <div>
                <h1 class="brand-title">E-KOPERASI</h1>
                <p class="brand-caption">
                    Sistem Informasi Koperasi Sekolah
                </p>
            </div>

        </div>


        <nav class="nav">

            <a class="nav-item" href="dashboard.php">
                <span class="nav-icon icon-home"></span>
                <span>Dashboard</span>
            </a>

            <a class="nav-item" href="transaksi.php">
                <span class="nav-icon icon-cart"></span>
                <span>Transaksi</span>
            </a>

            <a class="nav-item" href="barang.php">
                <span class="nav-icon icon-box"></span>
                <span>Data Barang</span>
            </a>

            <a class="nav-item active" href="stok.php">
                <span class="nav-icon icon-layers"></span>
                <span>Stok Barang</span>
            </a>

            <a class="nav-item" href="pemasukan.php">
                <span class="nav-icon icon-arrow"></span>
                <span>Pemasukan</span>
            </a>

            <a class="nav-item" href="pengeluaran.php">
                <span class="nav-icon icon-arrow"></span>
                <span>Pengeluaran</span>
            </a>

            <a class="nav-item" href="laporan.php">
                <span class="nav-icon icon-report"></span>
                <span>Laporan</span>
            </a>

            <a class="nav-item" href="users.php">
                <span class="nav-icon icon-users"></span>
                <span>Manajemen User</span>
            </a>

        </nav>


        <a
            class="nav-item logout"
            href="/koperasi/logout.php"
        >
            <span class="nav-icon icon-logout"></span>
            <span>Logout</span>
        </a>

    </aside>


    <!-- CONTENT -->
    <section class="content">


        <!-- TOPBAR -->
        <header class="topbar">

            <div class="top-left">

                <button
                    class="menu-button"
                    type="button"
                    aria-label="Menu"
                >
                    <span></span>
                </button>


                <div>

                    <h2 class="page-title">
                        Stok Barang
                    </h2>

                    <p class="page-subtitle">
                        Kelola dan pantau stok barang koperasi.
                    </p>

                </div>

            </div>


            <div class="top-right">

                <div class="date-chip">

                    <span
                        class="calendar-icon"
                        aria-hidden="true"
                    ></span>

                    <span>
                        <?= e_html($todayLabel) ?>
                    </span>

                </div>


                <div class="profile">

                    <div
                        class="profile-avatar"
                        aria-hidden="true"
                    ></div>


                    <div>

                        <p class="profile-name">
                            <?= e_html($userNama) ?>
                        </p>

                        <p class="profile-role">
                            <?= ucfirst(e_html($userRole)) ?>
                        </p>

                    </div>


                    <span
                        class="profile-chevron"
                        aria-hidden="true"
                    ></span>

                </div>

            </div>

        </header>


        <!-- MAIN -->
        <main class="main stock-page">

            <section class="stock-panel">

                <div class="stock-head">

                    <div>

                        <h1 class="stock-title">
                            STOK BARANG (ADMIN)
                        </h1>

                        <p class="stock-subtitle">
                            Data stok diambil langsung dari tabel barang.
                        </p>

                    </div>

                </div>


                <div class="stock-table-wrap">

                    <table class="stock-table">

                        <thead>

                            <tr>

                                <th>No</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (!$barang): ?>

                                <tr>

                                    <td
                                        colspan="7"
                                        class="empty"
                                    >
                                        Belum ada data barang.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach ($barang as $i => $item): ?>

                                    <?php

                                    $stok = (int) $item['stok'];

                                    $status =
                                        $stok <= 0
                                            ? 'Habis'
                                            : (
                                                $stok <= $stokMinimum
                                                    ? 'Menipis'
                                                    : 'Aman'
                                            );

                                    $statusClass =
                                        strtolower($status);

                                    ?>

                                    <tr>

                                        <td>
                                            <?= $i + 1 ?>
                                        </td>


                                        <td>
                                            <strong>
                                                <?= e_html($item['nama_barang']) ?>
                                            </strong>
                                        </td>


                                        <td>
                                            <?= e_html($item['kategori']) ?>
                                        </td>


                                        <td>
                                            Rp
                                            <?= number_format(
                                                (float) $item['harga'],
                                                0,
                                                ',',
                                                '.'
                                            ) ?>
                                        </td>


                                        <td class="stock-number">

                                            <?= number_format(
                                                $stok,
                                                0,
                                                ',',
                                                '.'
                                            ) ?>

                                        </td>


                                        <td>

                                            <span
                                                class="stock-status <?= $statusClass ?>"
                                            >
                                                <?= $status ?>
                                            </span>

                                        </td>


                                        <td>

                                            <a
                                                class="panel-link"
                                                href="barang.php?edit=<?= (int) $item['id_barang'] ?>"
                                            >
                                                Edit
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </main>

    </section>

</div>


<script>

const menuButton =
    document.querySelector('.menu-button');

const sidebar =
    document.querySelector('.sidebar');

if (menuButton && sidebar) {

    menuButton.addEventListener(
        'click',
        function () {

            sidebar.classList.toggle(
                'mobile-open'
            );

        }
    );

}

</script>

</body>
</html>