<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('kasir');

$pageTitle = 'Cetak Struk';

$uid = (int) $_SESSION['user']['id'];

$userNama = $_SESSION['user']['nama']
    ?? $_SESSION['user']['username']
    ?? 'Kasir';

$userRole = $_SESSION['user']['role']
    ?? 'kasir';

$todayLabel = date('d F Y');


/* =====================================================
   AMBIL TRANSAKSI
===================================================== */

$st = $pdo->prepare('
    SELECT *
    FROM transaksi
    WHERE id_user = ?
    ORDER BY id_transaksi DESC
');

$st->execute([$uid]);

$rows = $st->fetchAll();


/* =====================================================
   LOGOUT
===================================================== */

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


/* =====================================================
   DETAIL TRANSAKSI
===================================================== */

$detailTransaksi = [];

$detailStmt = $pdo->prepare('
    SELECT
        d.id_transaksi,
        d.id_barang,
        d.jumlah,
        d.harga,
        d.subtotal,
        b.nama_barang
    FROM detail_transaksi d
    INNER JOIN barang b
        ON b.id_barang = d.id_barang
    WHERE d.id_transaksi = ?
');

foreach ($rows as $trx) {

    $detailStmt->execute([
        $trx['id_transaksi']
    ]);

    $detailTransaksi[$trx['id_transaksi']]
        = $detailStmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= e($pageTitle) ?> - E-KOPERASI</title>

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


        /* BRAND */

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


        /* NAVIGATION */

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


        /* =====================================================
           ICON
        ===================================================== */

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

            background: white;

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 35px 0 28px;
        }


        .top-left {

            display: flex;

            align-items: center;

            gap: 18px;
        }


        .page-title {

            margin: 0;

            font-size: 25px;

            font-weight: 750;
        }


        .page-subtitle {

            margin: 5px 0 0;

            color: var(--muted);

            font-size: 13px;
        }


        .top-right {

            display: flex;

            align-items: center;

            gap: 20px;
        }


        .date-box {

            color: var(--muted);

            font-size: 13px;
        }


        .profile {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .profile-avatar {

            width: 42px;
            height: 42px;

            border-radius: 50%;

            background: var(--blue-light);

            color: var(--blue);

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 800;
        }


        .profile-name {

            font-size: 14px;

            font-weight: 700;
        }


        .profile-role {

            margin-top: 2px;

            font-size: 11px;

            color: var(--muted);
        }


        /* =====================================================
           MAIN
        ===================================================== */

        main {

            padding: 28px;
        }


        /* =====================================================
           PANEL
        ===================================================== */

        .panel {

            background: white;

            border: 1px solid var(--border);

            border-radius: 12px;

            box-shadow:
                0 2px 8px rgba(20,40,80,.04);
        }


        .panel-head {

            padding: 20px 22px;

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;
        }


        .panel-head h2 {

            margin: 0;

            font-size: 18px;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-wrap {

            width: 100%;

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 700px;
        }


        th {

            text-align: left;

            padding: 14px 18px;

            background: #f8fafc;

            border-bottom: 1px solid var(--border);

            color: #4b5563;

            font-size: 12px;

            font-weight: 700;
        }


        td {

            padding: 15px 18px;

            border-bottom: 1px solid #edf0f5;

            font-size: 13px;

            color: #374151;
        }


        tbody tr:hover {

            background: #fafcff;
        }


        tbody tr:last-child td {

            border-bottom: 0;
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .btn-cetak {

            border: 0;

            background: var(--blue);

            color: white;

            padding: 9px 15px;

            border-radius: 7px;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;
        }


        .btn-cetak:hover {

            background: var(--blue-dark);
        }


        .empty {

            text-align: center;

            padding: 35px !important;

            color: var(--muted);
        }


        /* =====================================================
           PRINT
        ===================================================== */

        .struk {

            display: none;
        }


        @media print {

            body * {

                visibility: hidden;
            }


            .struk,
            .struk * {

                visibility: visible;
            }


            .struk {

                display: block;

                position: absolute;

                left: 0;
                top: 0;

                width: 80mm;

                padding: 8px;

                background: white;

                color: black;

                font-family: Arial, sans-serif;
            }


            .struk h1 {

                text-align: center;

                font-size: 18px;

                margin: 0;
            }


            .struk p {

                text-align: center;

                font-size: 10px;

                margin: 4px 0;
            }


            .struk-line {

                border-top: 1px dashed black;

                margin: 8px 0;
            }


            .struk-info {

                font-size: 10px;

                line-height: 1.7;
            }


            .struk-table {

                width: 100%;

                border-collapse: collapse;

                font-size: 10px;
            }


            .struk-table td {

                padding: 3px 0;

                border: 0;
            }


            .text-right {

                text-align: right;
            }


            .struk-total {

                font-weight: bold;

                font-size: 11px;
            }


            .struk-footer {

                text-align: center;

                font-size: 10px;

                margin-top: 12px;
            }
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 800px) {

            .sidebar {

                width: 220px;
            }


            .content {

                width: calc(100% - 220px);

                margin-left: 220px;
            }


            .topbar {

                padding: 0 20px;
            }


            main {

                padding: 18px;
            }


            .date-box {

                display: none;
            }
        }


        @media (max-width: 600px) {

            .sidebar {

                position: relative;

                width: 100%;

                min-height: auto;
            }


            .content {

                width: 100%;

                margin-left: 0;
            }


            .app {

                display: block;
            }


            .topbar {

                height: auto;

                padding: 20px;
            }


            .top-right {

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

            <a
                class="nav-item"
                href="dashboard.php"
            >

                <span class="nav-icon icon-home"></span>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                class="nav-item"
                href="transaksi.php"
            >

                <span class="nav-icon icon-cart"></span>

                <span>
                    Transaksi
                </span>

            </a>


            <a
                class="nav-item"
                href="riwayat.php"
            >

                <span class="nav-icon icon-history"></span>

                <span>
                    Riwayat Transaksi
                </span>

            </a>


            <a
                class="nav-item active"
                href="cetak_struk.php"
            >

                <span class="nav-icon icon-receipt"></span>

                <span>
                    Cetak Struk
                </span>

            </a>

        </nav>


        <a
            class="nav-item logout"
            href="dashboard.php?logout=1"
        >

            <span class="nav-icon icon-logout"></span>

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

                <div>

                    <h2 class="page-title">
                        Cetak Struk
                    </h2>

                    <p class="page-subtitle">
                        Cetak bukti transaksi Anda
                    </p>

                </div>

            </div>


            <div class="top-right">

                <div class="date-box">
                    <?= e($todayLabel) ?>
                </div>


                <div class="profile">

                    <div class="profile-avatar">
                        <?= e(strtoupper(substr($userNama, 0, 1))) ?>
                    </div>

                    <div>

                        <div class="profile-name">
                            <?= e($userNama) ?>
                        </div>

                        <div class="profile-role">
                            <?= e($userRole) ?>
                        </div>

                    </div>

                </div>

            </div>

        </header>


        <!-- MAIN -->

        <main>

            <section class="panel">


                <div class="panel-head">

                    <h2>
                        Daftar Transaksi
                    </h2>

                </div>


                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Tanggal</th>

                                <th>Pembeli</th>

                                <th>Total</th>

                                <th>Bayar</th>

                                <th>Kembalian</th>

                                <th>Aksi</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    #<?= e($r['id_transaksi']) ?>
                                </td>

                                <td>
                                    <?= e($r['tanggal']) ?>
                                </td>

                                <td>
                                    <?= e($r['nama_pembeli']) ?>
                                </td>

                                <td>
                                    <?= rupiah($r['total']) ?>
                                </td>

                                <td>
                                    <?= rupiah($r['bayar']) ?>
                                </td>

                                <td>
                                    <?= rupiah($r['kembalian']) ?>
                                </td>

                                <td>

                                    <button
                                        type="button"
                                        class="btn-cetak"
                                        onclick="cetakStruk(<?= (int)$r['id_transaksi'] ?>)"
                                    >
                                        Cetak
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>


                        <?php if (!$rows): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="empty"
                                >
                                    Belum ada transaksi yang dapat
                                    dicetak.
                                </td>

                            </tr>

                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </section>

        </main>

    </section>

</div>


<!-- =====================================================
     STRUK UNTUK PRINT
====================================================== -->

<?php foreach ($rows as $trx): ?>

    <div
        class="struk"
        id="struk-<?= (int)$trx['id_transaksi'] ?>"
    >

        <h1>
            E-KOPERASI
        </h1>

        <p>
            Sistem Informasi Koperasi Sekolah
        </p>


        <div class="struk-line"></div>


        <div class="struk-info">

            ID Transaksi:
            #<?= e($trx['id_transaksi']) ?>

            <br>

            Tanggal:
            <?= e($trx['tanggal']) ?>

            <br>

            Pembeli:
            <?= e($trx['nama_pembeli']) ?>

            <br>

            Kasir:
            <?= e($userNama) ?>

        </div>


        <div class="struk-line"></div>


        <table class="struk-table">

            <tbody>

            <?php

            $details =
                $detailTransaksi[$trx['id_transaksi']]
                ?? [];

            foreach ($details as $detail):

            ?>

                <tr>

                    <td>

                        <?= e($detail['nama_barang']) ?>

                        <br>

                        <?= e($detail['jumlah']) ?>
                        x
                        <?= rupiah($detail['harga']) ?>

                    </td>

                    <td class="text-right">

                        <?= rupiah($detail['subtotal']) ?>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>


        <div class="struk-line"></div>


        <table class="struk-table">

            <tr>

                <td>
                    Total
                </td>

                <td class="text-right struk-total">
                    <?= rupiah($trx['total']) ?>
                </td>

            </tr>

            <tr>

                <td>
                    Bayar
                </td>

                <td class="text-right">
                    <?= rupiah($trx['bayar']) ?>
                </td>

            </tr>

            <tr>

                <td>
                    Kembalian
                </td>

                <td class="text-right">
                    <?= rupiah($trx['kembalian']) ?>
                </td>

            </tr>

        </table>


        <div class="struk-line"></div>


        <div class="struk-footer">

            Terima kasih telah berbelanja
            <br>

            di E-KOPERASI

        </div>

    </div>

<?php endforeach; ?>


<script>

function cetakStruk(id) {

    const struk = document.getElementById(
        'struk-' + id
    );

    if (!struk) {

        alert('Struk tidak ditemukan.');

        return;
    }


    document
        .querySelectorAll('.struk')
        .forEach(function(item) {

            item.style.display = 'none';

        });


    struk.style.display = 'block';


    window.print();


    setTimeout(function() {

        struk.style.display = 'none';

    }, 500);

}

</script>


</body>

</html>