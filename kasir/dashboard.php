<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('kasir');

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/
if (isset($_GET['logout'])) {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header('Location: ../login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/
function e_html($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}


/*
|--------------------------------------------------------------------------
| DATA DASHBOARD
|--------------------------------------------------------------------------
*/

/* Total transaksi hari ini */
$stmt = $pdo->query("
    SELECT COUNT(*) AS total_transaksi
    FROM transaksi
    WHERE DATE(tanggal) = CURDATE()
");

$totalTransaksi = (int) ($stmt->fetch()['total_transaksi'] ?? 0);


/* Total penjualan hari ini */
$stmt = $pdo->query("
    SELECT COALESCE(SUM(total), 0) AS total_penjualan
    FROM transaksi
    WHERE DATE(tanggal) = CURDATE()
");

$totalPenjualan = (float) ($stmt->fetch()['total_penjualan'] ?? 0);


/* Total produk */
$stmt = $pdo->query("
    SELECT COUNT(*) AS total_barang
    FROM barang
");

$totalBarang = (int) ($stmt->fetch()['total_barang'] ?? 0);


/* Transaksi terbaru */
$stmt = $pdo->query("
    SELECT
        t.id_transaksi,
        t.tanggal,
        t.nama_pembeli,
        t.total
    FROM transaksi t
    ORDER BY t.id_transaksi DESC
    LIMIT 8
");

$transaksiTerbaru = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| DATA USER
|--------------------------------------------------------------------------
*/
$userNama = $_SESSION['user']['nama'] ?? 'Kasir';
$userRole = $_SESSION['user']['role'] ?? 'kasir';


/*
|--------------------------------------------------------------------------
| TANGGAL INDONESIA
|--------------------------------------------------------------------------
*/
$namaHari = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];

$namaBulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

$todayLabel =
    ($namaHari[date('l')] ?? date('l')) . ', ' .
    date('d') . ' ' .
    ($namaBulan[(int) date('n')] ?? date('F')) . ' ' .
    date('Y');

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard Kasir - E-KOPERASI</title>

    <style>

        * {
            box-sizing: border-box;
        }

        :root {

            --blue: #2D6A4F;
            --blue-2: #3B8060;
            --blue-dark: #24563F;
            --blue-light: #D8F3DC;

            --text: #111827;
            --muted: #738099;

            --border: #e1e7f0;
            --bg: #f7f9fc;
            --white: #ffffff;

            --green: #0e9f5a;
            --red: #e5484d;
            --orange: #e88918;

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

            background: var(--bg);
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


        /* =====================================================
           LAYOUT
        ===================================================== */

        .app {

            min-height: 100vh;
            display: flex;

        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            width: 263px;
            min-height: 100vh;

            position: fixed;
            inset: 0 auto 0 0;

            z-index: 30;

            display: flex;
            flex-direction: column;

            padding: 28px 15px 20px;

            background:
                linear-gradient(
                    180deg,
                    #2D6A4F 0%,
                    #285F47 55%,
                    #24563F 100%
                );

            color: white;

        }


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

            background: white;

            clip-path: polygon(
                50% 0,
                88% 22%,
                88% 75%,
                50% 100%,
                12% 75%,
                12% 22%
            );

        }


        .brand-cart {

            position: absolute;

            left: 15px;
            top: 20px;

            width: 21px;
            height: 15px;

            border: 2.5px solid var(--blue);

            border-top: 0;

            border-radius: 2px 2px 5px 5px;

        }


        .brand-cart::before {

            content: "";

            position: absolute;

            width: 12px;
            height: 2.5px;

            background: var(--blue);

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

            background: var(--blue);

            border-radius: 50%;

            left: 3px;
            bottom: -7px;

            box-shadow: 11px 0 0 var(--blue);

        }


        .brand-cart-line {

            position: absolute;

            width: 11px;
            height: 2px;

            background: var(--blue);

            left: 4px;
            top: 5px;

            box-shadow: 0 5px 0 var(--blue);

        }


        .brand-title {

            margin: 0;

            font-size: 25px;

            line-height: 1;

            font-weight: 800;

            letter-spacing: -.5px;

        }


        .brand-caption {

            margin: 6px 0 0;

            font-size: 9px;

            color: rgba(255,255,255,.86);

            white-space: nowrap;

        }


        /* =====================================================
           NAVIGATION
        ===================================================== */

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

            border-radius: 8px;

            color: rgba(255,255,255,.96);

            font-size: 14px;

            transition: .18s ease;

        }


        .nav-item:hover {

            background: rgba(255,255,255,.1);

        }


        .nav-item.active {

            background:
                linear-gradient(
                    135deg,
                    #3B8060,
                    #2D6A4F
                );

            box-shadow:
                inset 0 0 0 1px
                rgba(255,255,255,.05);

        }


        .nav-icon {

            width: 20px;
            height: 20px;

            position: relative;

            flex: 0 0 20px;

        }


        /* HOME */

        .icon-home::before {

            content: "";

            position: absolute;

            left: 3px;
            bottom: 2px;

            width: 14px;
            height: 11px;

            background: white;

            border-radius: 2px;

        }


        .icon-home::after {

            content: "";

            position: absolute;

            left: 2px;
            top: 1px;

            width: 13px;
            height: 13px;

            background: white;

            transform: rotate(45deg);

            border-radius: 2px;

        }


        /* CART */

        .icon-cart {

            border: 2px solid white;

            border-top: 0;

            width: 15px;
            height: 12px;

            left: 3px;
            top: 4px;

            border-radius: 2px 2px 4px 4px;

        }


        .icon-cart::before {

            content: "";

            position: absolute;

            width: 8px;
            height: 2px;

            background: white;

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

            background: white;

            left: 2px;
            bottom: -7px;

            box-shadow: 8px 0 0 white;

        }


        /* HISTORY */

        .icon-history {

            border: 2px solid white;

            border-radius: 50%;

            width: 17px;
            height: 17px;

            left: 2px;
            top: 2px;

        }


        .icon-history::before {

            content: "";

            position: absolute;

            width: 2px;
            height: 6px;

            background: white;

            left: 6px;
            top: 3px;

        }


        .icon-history::after {

            content: "";

            position: absolute;

            width: 5px;
            height: 2px;

            background: white;

            left: 6px;
            top: 8px;

            transform: rotate(25deg);

        }


        /* RECEIPT */

        .icon-receipt {

            border: 2px solid white;

            border-radius: 2px;

            width: 14px;
            height: 17px;

            left: 3px;
            top: 1px;

        }


        .icon-receipt::after {

            content: "";

            position: absolute;

            width: 8px;
            height: 2px;

            background: white;

            left: 1px;
            top: 5px;

            box-shadow:
                0 4px 0 white,
                0 8px 0 white;

        }


        /* LOGOUT */

        .icon-logout {

            border: 2px solid white;

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

            background: white;

        }


        .icon-logout::after {

            content: "";

            position: absolute;

            left: -7px;
            top: 4px;

            width: 7px;
            height: 7px;

            border-top: 2px solid white;
            border-right: 2px solid white;

            transform: rotate(45deg);

        }


        .logout {

            margin-top: 15px;

            min-height: 64px;

            background:
                rgba(20,67,47,.55);

        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {

            width: calc(100% - 263px);

            margin-left: 263px;

            min-height: 100vh;

        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            height: 100px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 35px 0 28px;

            background: white;

            border-bottom:
                1px solid #e6ebf2;

        }


        .top-left {

            display: flex;

            align-items: center;

            gap: 25px;

        }


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

            background: var(--blue);

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


        .page-title {

            margin: 0;

            font-size: 21px;

            line-height: 1.2;

            font-weight: 800;

        }


        .page-subtitle {

            margin: 3px 0 0;

            font-size: 12px;

            color: #53617a;

        }


        .top-right {

            display: flex;

            align-items: center;

            gap: 21px;

        }


        .date-chip {

            min-height: 45px;

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 0 16px;

            border:
                1px solid #dfe5ee;

            border-radius: 8px;

            color: #1d2a42;

            font-size: 12px;

        }


        .calendar-icon {

            width: 18px;
            height: 18px;

            position: relative;

            border:
                2px solid var(--blue);

            border-radius: 3px;

        }


        .calendar-icon::before {

            content: "";

            position: absolute;

            left: 2px;
            right: 2px;

            top: 4px;

            border-top:
                2px solid var(--blue);

        }


        .calendar-icon::after {

            content: "";

            position: absolute;

            width: 2px;
            height: 4px;

            left: 3px;
            top: -4px;

            background: var(--blue);

            box-shadow:
                8px 0 0 var(--blue);

        }


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

            background: #e2f3e8;

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
                var(--blue);

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
                var(--blue);

        }


        .profile-name {

            margin: 0;

            font-size: 14px;

            font-weight: 750;

        }


        .profile-role {

            margin: 2px 0 0;

            font-size: 10px;

            color: #69758b;

        }


        .profile-chevron {

            width: 8px;
            height: 8px;

            margin-left: 14px;

            border-right:
                2px solid #17233b;

            border-bottom:
                2px solid #17233b;

            transform:
                rotate(45deg)
                translateY(-3px);

        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {

            padding: 21px;

        }


        /* =====================================================
           WELCOME
        ===================================================== */

        .welcome {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 20px;

            padding: 20px 22px;

            margin-bottom: 20px;

            background: white;

            border:
                1px solid var(--border);

            border-radius: 10px;

            box-shadow:
                0 2px 8px
                rgba(22,42,75,.025);

        }


        .welcome h2 {

            margin: 0;

            font-size: 18px;

            font-weight: 800;

        }


        .welcome p {

            margin: 5px 0 0;

            color: var(--muted);

            font-size: 12px;

        }


        .btn-primary {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 10px 16px;

            border-radius: 7px;

            background:
                linear-gradient(
                    135deg,
                    #2D6A4F,
                    #3B8060
                );

            color: white;

            font-size: 12px;

            font-weight: 700;

            transition: .2s;

        }


        .btn-primary:hover {

            transform: translateY(-1px);

            box-shadow:
                0 5px 12px
                rgba(45,106,79,.25);

        }


        /* =====================================================
           STATISTICS
        ===================================================== */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0,1fr));

            gap: 14px;

            margin-bottom: 20px;

        }


        .stat-card {

            min-height: 145px;

            padding: 17px;

            display: grid;

            grid-template-columns:
                62px 1fr;

            column-gap: 14px;

            align-items: center;

            border:
                1px solid var(--border);

            border-radius: 10px;

            background: white;

            box-shadow:
                0 2px 8px
                rgba(22,42,75,.025);

        }


        .stat-icon {

            width: 61px;
            height: 61px;

            display: grid;

            place-items: center;

            border-radius: 50%;

            font-weight: 800;

            font-size: 16px;

        }


        .stat-icon.blue {

            background: #e2f3e8;

            color: #2D6A4F;

        }


        .stat-icon.green {

            background: #e5f7eb;

            color: #159447;

        }


        .stat-icon.orange {

            background: #fff3df;

            color: #e88918;

        }


        .stat-title {

            display: block;

            margin-bottom: 7px;

            color: var(--muted);

            font-size: 11px;

        }


        .stat-value {

            display: block;

            font-size: 19px;

            font-weight: 800;

            color: #111827;

        }


        /* =====================================================
           TABLE PANEL
        ===================================================== */

        .panel {

            padding: 20px;

            background: white;

            border:
                1px solid var(--border);

            border-radius: 10px;

            box-shadow:
                0 2px 8px
                rgba(22,42,75,.025);

        }


        .panel-head {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 15px;

            margin-bottom: 16px;

        }


        .panel-head h2 {

            margin: 0;

            font-size: 16px;

            font-weight: 800;

        }


        .panel-head p {

            margin: 4px 0 0;

            color: var(--muted);

            font-size: 11px;

        }


        .view-all {

            color: var(--blue);

            font-size: 12px;

            font-weight: 700;

        }


        .view-all:hover {

            text-decoration: underline;

        }


        .table-wrap {

            width: 100%;

            overflow-x: auto;

            border:
                1px solid #dbe3ef;

            border-radius: 8px;

        }


        table {

            width: 100%;

            min-width: 650px;

            border-collapse: collapse;

        }


        th {

            padding: 13px 15px;

            background: #f8fafc;

            color: #334155;

            font-size: 11px;

            font-weight: 700;

            text-align: left;

            border-bottom:
                1px solid #dbe3ef;

            border-right:
                1px solid #e2e8f0;

        }


        th:last-child {

            border-right: none;

        }


        td {

            padding: 13px 15px;

            background: white;

            color: #475569;

            font-size: 12px;

            border-bottom:
                1px solid #e2e8f0;

            border-right:
                1px solid #e2e8f0;

        }


        td:last-child {

            border-right: none;

        }


        tbody tr:last-child td {

            border-bottom: none;

        }


        tbody tr:hover td {

            background: #f8fafc;

        }


        .price {

            color: var(--blue);

            font-weight: 800;

        }


        .empty {

            padding: 35px !important;

            color: #94a3b8 !important;

            text-align: center;

        }



/* =====================================================
   AKSI CEPAT
===================================================== */

.quick-panel {
    margin-top: 20px;
}

.quick-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.quick-card {
    display: flex;
    align-items: center;
    gap: 15px;
    min-height: 82px;
    padding: 17px 18px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text);
    transition: .2s ease;
}

.quick-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 18px rgba(22,42,75,.08);
    border-color: #d3deed;
}

.quick-icon {
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    font-size: 19px;
    font-weight: 800;
}

.quick-icon.blue {
    background: #e2f3e8;
    color: #2D6A4F;
}

.quick-icon.green {
    background: #e5f7eb;
    color: #159447;
}

.quick-icon.orange {
    background: #fff3df;
    color: #e88918;
}

.quick-info {
    min-width: 0;
}

.quick-info h3 {
    margin: 0 0 4px;
    font-size: 13px;
    font-weight: 800;
}

.quick-info p {
    margin: 0;
    font-size: 11px;
    color: var(--muted);
}

.quick-arrow {
    margin-left: auto;
    color: var(--blue);
    font-size: 20px;
    font-weight: 700;
}

@media (max-width: 1000px) {
    .quick-grid {
        grid-template-columns: 1fr;
    }
}


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .stats {

                grid-template-columns:
                    repeat(2, minmax(0,1fr));

            }

            .top-right .date-chip {

                display: none;

            }

        }


        @media (max-width: 850px) {

            .sidebar {

                width: 75px;

                padding-left: 10px;
                padding-right: 10px;

            }


            .brand {

                justify-content: center;

                padding: 0;

            }


            .brand > div:last-child {

                display: none;

            }


            .nav-item {

                justify-content: center;

                padding: 0;

            }


            .nav-item span:not(.nav-icon) {

                display: none;

            }


            .logout {

                justify-content: center;

            }


            .content {

                width:
                    calc(100% - 75px);

                margin-left: 75px;

            }

        }


        @media (max-width: 650px) {

            .stats {

                grid-template-columns: 1fr;

            }


            .topbar {

                padding:
                    0 15px;

            }


            .main {

                padding: 14px;

            }


            .welcome {

                align-items:
                    flex-start;

                flex-direction:
                    column;

            }


            .btn-primary {

                width: 100%;

            }


            .profile-name,
            .profile-role,
            .profile-chevron {

                display: none;

            }

        }

    </style>

</head>


<body>

<div class="app">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

    <div class="brand">

        <div class="brand-mark">

            <span class="brand-cart">
                <span class="brand-cart-line"></span>
            </span>

        </div>

        <div>

            <h1 class="brand-title">
                E-KOPERASI
            </h1>

            <p class="brand-caption">
                Sistem Informasi Koperasi Sekolah
            </p>

        </div>

    </div>


    <nav class="nav">

        <!-- DASHBOARD -->
        <a
            class="nav-item active"
            href="dashboard.php"
        >
            <span class="nav-icon icon-home"></span>

            <span>
                Dashboard
            </span>
        </a>


        <!-- TRANSAKSI -->
        <a
            class="nav-item"
            href="transaksi.php"
        >

            <span
                class="nav-icon icon-cart"
            ></span>

            <span>
                Transaksi
            </span>

        </a>


        <!-- RIWAYAT -->
        <a
            class="nav-item"
            href="riwayat.php"
        >

            <span
                class="nav-icon icon-history"
            ></span>

            <span>
                Riwayat Transaksi
            </span>

        </a>


        <!-- CETAK STRUK -->
        <a
            class="nav-item"
            href="cetak_struk.php"
        >

            <span
                class="nav-icon icon-receipt"
            ></span>

            <span>
                Cetak Struk
            </span>

        </a>

    </nav>


    <!-- LOGOUT -->
    <a
        class="nav-item logout"
        href="dashboard.php?logout=1"
    >

        <span
            class="nav-icon icon-logout"
        ></span>

        <span>
            Logout
        </span>

    </a>

</aside>

    <!-- =====================================================
         CONTENT
    ====================================================== -->

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
                        Dashboard
                    </h2>

                    <p class="page-subtitle">
                        Selamat datang,
                        <?= e_html($userNama) ?>!
                    </p>

                </div>

            </div>


            <div class="top-right">

                <div class="date-chip">

                    <span
                        class="calendar-icon"
                    ></span>

                    <span>
                        <?= e_html($todayLabel) ?>
                    </span>

                </div>


                <div class="profile">

                    <div
                        class="profile-avatar"
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
                    ></span>

                </div>

            </div>

        </header>


        <!-- MAIN -->

        <main class="main">


            <!-- WELCOME -->

            <section class="welcome">

                <div>

                    <h2>
                        Dashboard Kasir
                    </h2>

                    <p>
                        Kelola transaksi penjualan
                        koperasi dengan mudah dan cepat.
                    </p>

                </div>


                <a
                    href="transaksi.php"
                    class="btn-primary"
                >
                    + Transaksi Baru
                </a>

            </section>


            <!-- STATISTICS -->

            <section class="stats">


                <!-- PENJUALAN -->

                <article class="stat-card">

                    <div class="stat-icon blue">
                        Rp
                    </div>


                    <div>

                        <span class="stat-title">
                            Penjualan Hari Ini
                        </span>

                        <strong class="stat-value">
                            <?= rupiah($totalPenjualan) ?>
                        </strong>

                    </div>

                </article>


                <!-- TRANSAKSI -->

                <article class="stat-card">

                    <div class="stat-icon green">
                        #
                    </div>


                    <div>

                        <span class="stat-title">
                            Transaksi Hari Ini
                        </span>

                        <strong class="stat-value">
                            <?= number_format($totalTransaksi, 0, ',', '.') ?>
                        </strong>

                    </div>

                </article>


                <!-- BARANG -->

                <article class="stat-card">

                    <div class="stat-icon orange">
                        □
                    </div>


                    <div>

                        <span class="stat-title">
                            Total Barang
                        </span>

                        <strong class="stat-value">
                            <?= number_format($totalBarang, 0, ',', '.') ?>
                        </strong>

                    </div>

                </article>


            </section>


            <!-- TRANSAKSI TERBARU -->

            <section class="panel">

                <div class="panel-head">

                    <div>

                        <h2>
                            Transaksi Terbaru
                        </h2>

                        <p>
                            Daftar transaksi terakhir
                            yang tercatat.
                        </p>

                    </div>


                    <a
                        href="riwayat.php"
                        class="view-all"
                    >
                        Lihat Semua
                    </a>

                </div>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    ID Transaksi
                                </th>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Pembeli
                                </th>

                                <th>
                                    Total
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (!$transaksiTerbaru): ?>

                            <tr>

                                <td
                                    colspan="4"
                                    class="empty"
                                >
                                    Belum ada transaksi.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach (
                                $transaksiTerbaru
                                as $transaksi
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            #<?= e_html(
                                                $transaksi['id_transaksi']
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= e_html(
                                            $transaksi['tanggal']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e_html(
                                            $transaksi['nama_pembeli']
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong class="price">

                                            <?= rupiah(
                                                $transaksi['total']
                                            ) ?>

                                        </strong>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </section>


            <!-- AKSI CEPAT -->
            <section class="panel quick-panel">

                <div class="panel-head">
                    <div>
                        <h2>Aksi Cepat</h2>
                        <p>Akses fitur kasir yang sering digunakan.</p>
                    </div>
                </div>

                <div class="quick-grid">

                    <a href="transaksi.php" class="quick-card">
                        <div class="quick-icon blue">🛒</div>

                        <div class="quick-info">
                            <h3>Transaksi Baru</h3>
                            <p>Mulai transaksi penjualan baru.</p>
                        </div>

                        <span class="quick-arrow">›</span>
                    </a>

                    <a href="riwayat.php" class="quick-card">
                        <div class="quick-icon green">↻</div>

                        <div class="quick-info">
                            <h3>Riwayat Transaksi</h3>
                            <p>Lihat transaksi yang sudah dilakukan.</p>
                        </div>

                        <span class="quick-arrow">›</span>
                    </a>

                    <a href="cetak_struk.php" class="quick-card">
                        <div class="quick-icon orange">🧾</div>

                        <div class="quick-info">
                            <h3>Cetak Struk</h3>
                            <p>Cetak bukti transaksi pelanggan.</p>
                        </div>

                        <span class="quick-arrow">›</span>
                    </a>

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