<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pageTitle = 'Laporan (Admin)';

/*
|--------------------------------------------------------------------------
| FILTER TANGGAL
|--------------------------------------------------------------------------
*/
$tanggalMulai  = $_GET['tanggal_mulai'] ?? '';
$tanggalSampai = $_GET['tanggal_sampai'] ?? '';

/*
|--------------------------------------------------------------------------
| KONDISI TRANSAKSI
|--------------------------------------------------------------------------
*/
$whereTransaksi = [];
$paramsTransaksi = [];

if ($tanggalMulai !== '') {
    $whereTransaksi[] = 'DATE(t.tanggal) >= :transaksi_mulai';
    $paramsTransaksi[':transaksi_mulai'] = $tanggalMulai;
}

if ($tanggalSampai !== '') {
    $whereTransaksi[] = 'DATE(t.tanggal) <= :transaksi_sampai';
    $paramsTransaksi[':transaksi_sampai'] = $tanggalSampai;
}

$whereTransaksiSql = '';

if (!empty($whereTransaksi)) {
    $whereTransaksiSql = 'WHERE ' . implode(' AND ', $whereTransaksi);
}

/*
|--------------------------------------------------------------------------
| KONDISI PENGELUARAN
|--------------------------------------------------------------------------
*/
$wherePengeluaran = [];
$paramsPengeluaran = [];

if ($tanggalMulai !== '') {
    $wherePengeluaran[] = 'p.tanggal >= :pengeluaran_mulai';
    $paramsPengeluaran[':pengeluaran_mulai'] = $tanggalMulai;
}

if ($tanggalSampai !== '') {
    $wherePengeluaran[] = 'p.tanggal <= :pengeluaran_sampai';
    $paramsPengeluaran[':pengeluaran_sampai'] = $tanggalSampai;
}

$wherePengeluaranSql = '';

if (!empty($wherePengeluaran)) {
    $wherePengeluaranSql = 'WHERE ' . implode(' AND ', $wherePengeluaran);
}

/*
|--------------------------------------------------------------------------
| TOTAL PEMASUKAN
|--------------------------------------------------------------------------
*/
$stmtPemasukan = $pdo->prepare("
    SELECT
        COUNT(*) AS jumlah_transaksi,
        COALESCE(SUM(t.total), 0) AS total_pemasukan
    FROM transaksi t
    $whereTransaksiSql
");

$stmtPemasukan->execute($paramsTransaksi);

$dataPemasukan = $stmtPemasukan->fetch();

$totalPemasukan = (float) ($dataPemasukan['total_pemasukan'] ?? 0);
$jumlahTransaksi = (int) ($dataPemasukan['jumlah_transaksi'] ?? 0);

/*
|--------------------------------------------------------------------------
| TOTAL PENGELUARAN
|--------------------------------------------------------------------------
*/
$stmtPengeluaran = $pdo->prepare("
    SELECT
        COUNT(*) AS jumlah_pengeluaran,
        COALESCE(SUM(p.jumlah), 0) AS total_pengeluaran
    FROM pengeluaran p
    $wherePengeluaranSql
");

$stmtPengeluaran->execute($paramsPengeluaran);

$dataPengeluaran = $stmtPengeluaran->fetch();

$totalPengeluaran = (float) ($dataPengeluaran['total_pengeluaran'] ?? 0);
$jumlahPengeluaran = (int) ($dataPengeluaran['jumlah_pengeluaran'] ?? 0);

/*
|--------------------------------------------------------------------------
| SALDO
|--------------------------------------------------------------------------
*/
$saldoBersih = $totalPemasukan - $totalPengeluaran;

/*
|--------------------------------------------------------------------------
| REKAP TRANSAKSI
|--------------------------------------------------------------------------
*/
$stmtTransaksi = $pdo->prepare("
    SELECT
        t.id_transaksi,
        t.tanggal,
        t.nama_pembeli,
        t.total,
        u.nama AS kasir
    FROM transaksi t
    LEFT JOIN users u
        ON u.id = t.id_user
    $whereTransaksiSql
    ORDER BY t.tanggal DESC, t.id_transaksi DESC
    LIMIT 50
");

$stmtTransaksi->execute($paramsTransaksi);

$transaksi = $stmtTransaksi->fetchAll();

/*
|--------------------------------------------------------------------------
| REKAP PENGELUARAN
|--------------------------------------------------------------------------
*/
$stmtPengeluaranDetail = $pdo->prepare("
    SELECT
        p.id_pengeluaran,
        p.tanggal,
        p.kategori,
        p.keterangan,
        p.jumlah,
        u.nama AS admin
    FROM pengeluaran p
    LEFT JOIN users u
        ON u.id = p.id_user
    $wherePengeluaranSql
    ORDER BY p.tanggal DESC, p.id_pengeluaran DESC
    LIMIT 50
");

$stmtPengeluaranDetail->execute($paramsPengeluaran);

$pengeluaran = $stmtPengeluaranDetail->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<style>
/* =========================================================
   LAPORAN - GREEN THEME
   ========================================================= */

.laporan-page {
    width: 100%;
}

/* =========================================================
   FILTER
   ========================================================= */

.laporan-filter-panel {
    background: #ffffff;
    border: 1px solid #dde7e1;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(31, 41, 55, 0.04);
}

.laporan-filter-form {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

.laporan-filter-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.laporan-filter-group label {
    color: #475569;
    font-size: 12px;
    font-weight: 700;
}

.laporan-filter-input {
    width: 165px;
    height: 42px;
    padding: 0 12px;
    border: 1px solid #d5e1da;
    border-radius: 8px;
    background: #ffffff;
    color: #1f2937;
    font-family: inherit;
    font-size: 13px;
    box-sizing: border-box;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.laporan-filter-input:focus {
    outline: none;
    border-color: #2d6a4f;
    box-shadow: 0 0 0 3px rgba(45, 106, 79, .10);
}

.btn-laporan {
    height: 42px;
    padding: 0 18px;
    border: 0;
    border-radius: 8px;
    background: #2d6a4f;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s ease, transform .2s ease;
}

.btn-laporan:hover {
    background: #24563f;
    transform: translateY(-1px);
}

.btn-reset-laporan {
    height: 42px;
    padding: 0 16px;
    border: 1px solid #d5e1da;
    border-radius: 8px;
    background: #ffffff;
    color: #475569;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    box-sizing: border-box;
}

.btn-reset-laporan:hover {
    background: #f1faf3;
    color: #2d6a4f;
}

/* =========================================================
   CETAK
   ========================================================= */

.btn-cetak {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 42px;
    padding: 0 15px;
    border: 1px solid #d5e1da;
    border-radius: 8px;
    background: #ffffff;
    color: #475569;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    box-sizing: border-box;
    transition: background .2s ease, color .2s ease;
}

.btn-cetak:hover {
    background: #f1faf3;
    color: #2d6a4f;
}

/* =========================================================
   SUMMARY
   ========================================================= */

.laporan-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 20px;
}

.laporan-card {
    position: relative;
    background: #ffffff;
    border: 1px solid #dde7e1;
    border-radius: 14px;
    padding: 21px;
    min-height: 135px;
    box-sizing: border-box;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(31, 41, 55, 0.04);
}

.laporan-card::after {
    content: '';
    position: absolute;
    width: 70px;
    height: 70px;
    right: -25px;
    bottom: -25px;
    border-radius: 50%;
    background: rgba(45, 106, 79, .05);
}

.laporan-card-label {
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 9px;
}

.laporan-card-value {
    color: #1f2937;
    font-size: 25px;
    line-height: 1.2;
    font-weight: 800;
    margin-bottom: 8px;
}

.laporan-card-info {
    color: #94a3b8;
    font-size: 12px;
}

.laporan-card.pemasukan {
    border-top: 4px solid #2d6a4f;
}

.laporan-card.pengeluaran {
    border-top: 4px solid #d97706;
}

.laporan-card.saldo {
    border-top: 4px solid #16803c;
}

/* =========================================================
   PANEL
   ========================================================= */

.laporan-panel {
    background: #ffffff;
    border: 1px solid #dde7e1;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(31, 41, 55, 0.04);
}

.laporan-panel-head {
    padding: 18px 20px;
    border-bottom: 1px solid #e5eee8;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.laporan-panel-title {
    margin: 0;
    color: #1f2937;
    font-size: 18px;
    font-weight: 800;
}

.laporan-panel-subtitle {
    margin: 5px 0 0;
    color: #94a3b8;
    font-size: 12px;
}

/* =========================================================
   TABLE
   ========================================================= */

.laporan-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.laporan-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

.laporan-table th {
    padding: 14px 16px;
    background: #f1faf3;
    color: #24563f;
    border-bottom: 1px solid #dce9df;
    text-align: left;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

.laporan-table td {
    padding: 13px 16px;
    color: #475569;
    border-bottom: 1px solid #edf2ee;
    font-size: 13px;
}

.laporan-table tbody tr {
    transition: background .15s ease;
}

.laporan-table tbody tr:hover {
    background: #f8fcf9;
}

.text-center {
    text-align: center !important;
}

.text-right {
    text-align: right !important;
}

/* =========================================================
   BADGE
   ========================================================= */

.badge-pemasukan {
    display: inline-flex;
    align-items: center;
    padding: 5px 9px;
    border-radius: 7px;
    background: #d8f3dc;
    color: #24563f;
    font-size: 11px;
    font-weight: 800;
}

.badge-pengeluaran {
    display: inline-flex;
    align-items: center;
    padding: 5px 9px;
    border-radius: 7px;
    background: #fff4df;
    color: #a85d00;
    font-size: 11px;
    font-weight: 800;
}

/* =========================================================
   EMPTY
   ========================================================= */

.laporan-empty {
    text-align: center !important;
    padding: 38px 20px !important;
    color: #94a3b8 !important;
}

/* =========================================================
   TOTAL ROW
   ========================================================= */

.laporan-total-row td {
    background: #f1faf3;
    color: #24563f;
    font-weight: 800;
    border-bottom: 0;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1100px) {

    .laporan-summary {
        grid-template-columns: 1fr;
    }

    .laporan-card {
        min-height: auto;
    }
}

@media (max-width: 700px) {

    .laporan-filter-form {
        align-items: stretch;
        flex-direction: column;
    }

    .laporan-filter-input {
        width: 100%;
    }

    .btn-laporan,
    .btn-reset-laporan,
    .btn-cetak {
        width: 100%;
        justify-content: center;
    }

    .laporan-filter-panel {
        padding: 16px;
    }

    .laporan-card {
        padding: 18px;
    }

    .laporan-card-value {
        font-size: 22px;
    }
}

/* =========================================================
   PRINT
   ========================================================= */

@media print {

    .sidebar,
    .topbar,
    .laporan-filter-panel,
    .btn-cetak {
        display: none !important;
    }

    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }

    .laporan-page {
        padding: 0 !important;
    }

    .laporan-panel,
    .laporan-card {
        border: 1px solid #ccc;
        box-shadow: none !important;
        break-inside: avoid;
    }

    body {
        background: #ffffff !important;
    }
}
</style>

<section class="laporan-page">

    <!-- FILTER LAPORAN -->
    <section class="laporan-filter-panel">

        <form method="get" class="laporan-filter-form">

            <div class="laporan-filter-group">

                <label for="tanggal_mulai">
                    Dari Tanggal
                </label>

                <input
                    type="date"
                    id="tanggal_mulai"
                    name="tanggal_mulai"
                    class="laporan-filter-input"
                    value="<?= e($tanggalMulai) ?>"
                >

            </div>


            <div class="laporan-filter-group">

                <label for="tanggal_sampai">
                    Sampai Tanggal
                </label>

                <input
                    type="date"
                    id="tanggal_sampai"
                    name="tanggal_sampai"
                    class="laporan-filter-input"
                    value="<?= e($tanggalSampai) ?>"
                >

            </div>


            <button
                type="submit"
                class="btn-laporan"
            >
                Tampilkan Laporan
            </button>


            <?php if ($tanggalMulai !== '' || $tanggalSampai !== ''): ?>

                <a
                    href="laporan.php"
                    class="btn-reset-laporan"
                >
                    Reset
                </a>

            <?php endif; ?>


            <button
                type="button"
                class="btn-cetak"
                onclick="window.print()"
            >
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M6 9V2h12v7"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8" rx="1"/>
                </svg>

                Cetak Laporan
            </button>

        </form>

    </section>


    <!-- SUMMARY -->
    <div class="laporan-summary">

        <div class="laporan-card pemasukan">

            <div class="laporan-card-label">
                Total Pemasukan
            </div>

            <div class="laporan-card-value">
                <?= rupiah($totalPemasukan) ?>
            </div>

            <div class="laporan-card-info">
                <?= $jumlahTransaksi ?> transaksi penjualan
            </div>

        </div>


        <div class="laporan-card pengeluaran">

            <div class="laporan-card-label">
                Total Pengeluaran
            </div>

            <div class="laporan-card-value">
                <?= rupiah($totalPengeluaran) ?>
            </div>

            <div class="laporan-card-info">
                <?= $jumlahPengeluaran ?> data pengeluaran
            </div>

        </div>


        <div class="laporan-card saldo">

            <div class="laporan-card-label">
                Saldo Bersih
            </div>

            <div class="laporan-card-value">
                <?= rupiah($saldoBersih) ?>
            </div>

            <div class="laporan-card-info">
                Pemasukan - pengeluaran
            </div>

        </div>

    </div>


    <!-- REKAP TRANSAKSI -->
    <section class="laporan-panel">

        <div class="laporan-panel-head">

            <div>

                <h2 class="laporan-panel-title">
                    Rekap Pemasukan
                </h2>

                <p class="laporan-panel-subtitle">
                    Data transaksi penjualan
                </p>

            </div>

        </div>


        <div class="laporan-table-wrap">

            <table class="laporan-table">

                <thead>

                    <tr>

                        <th class="text-center">
                            No
                        </th>

                        <th>
                            Tanggal
                        </th>

                        <th>
                            ID Transaksi
                        </th>

                        <th>
                            Pembeli
                        </th>

                        <th>
                            Kasir
                        </th>

                        <th class="text-right">
                            Total
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (empty($transaksi)): ?>

                        <tr>
                            <td
                                colspan="6"
                                class="laporan-empty"
                            >
                                Belum ada data transaksi.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($transaksi as $index => $row): ?>

                            <tr>

                                <td class="text-center">
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <?= !empty($row['tanggal'])
                                        ? e(date('d M Y', strtotime($row['tanggal'])))
                                        : '-' ?>
                                </td>

                                <td>

                                    <span class="badge-pemasukan">
                                        #<?= (int) $row['id_transaksi'] ?>
                                    </span>

                                </td>

                                <td>
                                    <?= e($row['nama_pembeli'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= e($row['kasir'] ?? '-') ?>
                                </td>

                                <td class="text-right">
                                    <?= rupiah($row['total']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>


                        <tr class="laporan-total-row">

                            <td
                                colspan="5"
                                class="text-right"
                            >
                                Total Pemasukan
                            </td>

                            <td class="text-right">
                                <?= rupiah($totalPemasukan) ?>
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- REKAP PENGELUARAN -->
    <section class="laporan-panel">

        <div class="laporan-panel-head">

            <div>

                <h2 class="laporan-panel-title">
                    Rekap Pengeluaran
                </h2>

                <p class="laporan-panel-subtitle">
                    Data pengeluaran koperasi
                </p>

            </div>

        </div>


        <div class="laporan-table-wrap">

            <table class="laporan-table">

                <thead>

                    <tr>

                        <th class="text-center">
                            No
                        </th>

                        <th>
                            Tanggal
                        </th>

                        <th>
                            Kategori
                        </th>

                        <th>
                            Keterangan
                        </th>

                        <th>
                            Admin
                        </th>

                        <th class="text-right">
                            Jumlah
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (empty($pengeluaran)): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="laporan-empty"
                            >
                                Belum ada data pengeluaran.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($pengeluaran as $index => $row): ?>

                            <tr>

                                <td class="text-center">
                                    <?= $index + 1 ?>
                                </td>

                                <td>
                                    <?= !empty($row['tanggal'])
                                        ? e(date('d M Y', strtotime($row['tanggal'])))
                                        : '-' ?>
                                </td>

                                <td>

                                    <span class="badge-pengeluaran">
                                        <?= e($row['kategori']) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= e($row['keterangan']) ?>
                                </td>

                                <td>
                                    <?= e($row['admin'] ?? '-') ?>
                                </td>

                                <td class="text-right">
                                    <?= rupiah($row['jumlah']) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>


                        <tr class="laporan-total-row">

                            <td
                                colspan="5"
                                class="text-right"
                            >
                                Total Pengeluaran
                            </td>

                            <td class="text-right">
                                <?= rupiah($totalPengeluaran) ?>
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</section>

<?php
include __DIR__ . '/../includes/footer.php';
?>