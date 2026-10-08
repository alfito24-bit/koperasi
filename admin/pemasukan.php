<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pageTitle = 'Pemasukan (Admin)';

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/
$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSampai = $_GET['tanggal_sampai'] ?? '';

$where = [];
$params = [];

if ($tanggalMulai !== '') {
    $where[] = 'DATE(t.tanggal) >= :tanggal_mulai';
    $params[':tanggal_mulai'] = $tanggalMulai;
}

if ($tanggalSampai !== '') {
    $where[] = 'DATE(t.tanggal) <= :tanggal_sampai';
    $params[':tanggal_sampai'] = $tanggalSampai;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| Total pemasukan
| Sumber pemasukan = transaksi penjualan
|--------------------------------------------------------------------------
*/
$stmtTotal = $pdo->prepare("
    SELECT
        COUNT(*) AS jumlah_transaksi,
        COALESCE(SUM(t.total), 0) AS total_pemasukan
    FROM transaksi t
    $whereSql
");

$stmtTotal->execute($params);
$summary = $stmtTotal->fetch();

$totalPemasukan = (float) ($summary['total_pemasukan'] ?? 0);
$jumlahTransaksi = (int) ($summary['jumlah_transaksi'] ?? 0);

/*
|--------------------------------------------------------------------------
| Pengeluaran
|
| Database saat ini belum mempunyai tabel pengeluaran.
| Jadi jangan membuat angka palsu.
|--------------------------------------------------------------------------
*/
$totalPengeluaran = 0;
$saldoBersih = $totalPemasukan - $totalPengeluaran;

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/
$perPage = 10;

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

/*
|--------------------------------------------------------------------------
| Hitung total data
|--------------------------------------------------------------------------
*/
$stmtCount = $pdo->prepare("
    SELECT COUNT(*)
    FROM transaksi t
    $whereSql
");

$stmtCount->execute($params);

$totalData = (int) $stmtCount->fetchColumn();

$totalPages = max(1, (int) ceil($totalData / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Ambil data pemasukan
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        t.id_transaksi,
        t.tanggal,
        t.nama_pembeli,
        t.total,
        u.nama AS nama_kasir
    FROM transaksi t
    LEFT JOIN users u
        ON u.id = t.id_user
    $whereSql
    ORDER BY t.tanggal DESC, t.id_transaksi DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$dataPemasukan = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Helper URL pagination
|--------------------------------------------------------------------------
*/
function pageUrl(int $pageNumber): string
{
    $query = $_GET;
    $query['page'] = $pageNumber;

    return '?' . http_build_query($query);
}

include __DIR__ . '/../includes/header.php';
?>

<style>

/* =========================================================
   PEMASUKAN
========================================================= */

.pemasukan-page {
    width: 100%;
}


/* =========================================================
   ACTION
========================================================= */

.pemasukan-top-action {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 18px;
}

.btn-primary-pemasukan {
    display: inline-flex;
    align-items: center;
    gap: 9px;

    border: 0;
    border-radius: 9px;

    padding: 12px 18px;

    background: #2D6A4F;
    color: #FFFFFF;

    font-size: 14px;
    font-weight: 700;

    cursor: pointer;
    text-decoration: none;

    box-shadow:
        0 4px 10px rgba(45, 106, 79, 0.18);

    transition:
        background .18s ease,
        transform .18s ease,
        box-shadow .18s ease;
}

.btn-primary-pemasukan:hover {
    background: #24563F;

    transform: translateY(-1px);

    box-shadow:
        0 6px 14px rgba(45, 106, 79, 0.22);
}


/* =========================================================
   SUMMARY CARDS
========================================================= */

.pemasukan-summary {
    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 20px;

    margin-bottom: 22px;
}

.pemasukan-card {
    min-height: 132px;

    padding: 20px 22px;

    border:
        1px solid #DDE7E1;

    border-radius: 13px;

    background: #FFFFFF;

    display: flex;
    align-items: center;

    gap: 18px;

    box-sizing: border-box;

    box-shadow:
        0 3px 12px rgba(36, 86, 63, 0.04);

    transition:
        transform .18s ease,
        box-shadow .18s ease;
}

.pemasukan-card:hover {
    transform: translateY(-2px);

    box-shadow:
        0 7px 18px rgba(36, 86, 63, 0.08);
}


/* =========================================================
   SUMMARY ICON
========================================================= */

.pemasukan-card-icon {
    width: 58px;
    height: 58px;

    min-width: 58px;

    border-radius: 14px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 27px;
    font-weight: 800;
}


/* Pemasukan */

.pemasukan-card-icon.blue {
    color: #2D6A4F;
    background: #D8F3DC;

    position: relative;
}

.pemasukan-card-icon.blue::before {
    content: "↗";

    font-size: 27px;
}


/* Pengeluaran */

.pemasukan-card-icon.green {
    color: #2D6A4F;
    background: #EAF7EE;

    position: relative;
}

.pemasukan-card-icon.green::before {
    content: "↘";

    font-size: 27px;
}


/* Saldo */

.pemasukan-card-icon.purple {
    color: #24563F;
    background: #EDF7F0;

    position: relative;
}

.pemasukan-card-icon.purple::before {
    content: "Rp";

    font-size: 17px;
    font-weight: 800;
}


/* =========================================================
   SUMMARY CONTENT
========================================================= */

.pemasukan-card-content {
    min-width: 0;
}

.pemasukan-card-label {
    color: #334155;

    font-size: 14px;

    margin-bottom: 7px;

    font-weight: 600;
}

.pemasukan-card-value {
    color: #1F2937;

    font-size: 27px;

    line-height: 1.2;

    font-weight: 800;

    margin-bottom: 7px;

    word-break: break-word;
}

.pemasukan-card-desc {
    color: #64748B;

    font-size: 13px;
}


/* =========================================================
   MAIN PANEL
========================================================= */

.pemasukan-panel {
    border:
        1px solid #DDE7E1;

    border-radius: 13px;

    background: #FFFFFF;

    overflow: hidden;

    box-shadow:
        0 3px 12px rgba(36, 86, 63, 0.04);
}


/* =========================================================
   PANEL HEADER
========================================================= */

.pemasukan-panel-head {
    padding: 18px 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 18px;

    border-bottom:
        1px solid #E8F0EA;
}

.pemasukan-panel-title {
    margin: 0;

    color: #1F2937;

    font-size: 21px;

    font-weight: 800;

    white-space: nowrap;
}


/* =========================================================
   FILTER
========================================================= */

.pemasukan-filter {
    display: flex;

    align-items: center;

    gap: 10px;

    flex-wrap: wrap;
}

.filter-input,
.filter-select {
    height: 40px;

    border:
        1px solid #DDE7E1;

    border-radius: 8px;

    background: #FFFFFF;

    color: #334155;

    padding: 0 12px;

    font-family: inherit;

    font-size: 13px;

    outline: none;

    box-sizing: border-box;

    transition:
        border-color .18s ease,
        box-shadow .18s ease;
}

.filter-input:focus,
.filter-select:focus {
    border-color: #2D6A4F;

    box-shadow:
        0 0 0 3px rgba(45, 106, 79, 0.08);
}

.filter-input {
    width: 150px;
}

.filter-select {
    width: 145px;
}

.btn-filter {
    height: 40px;

    border: 0;

    border-radius: 8px;

    padding: 0 17px;

    background: #2D6A4F;

    color: #FFFFFF;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    transition:
        background .18s ease;
}

.btn-filter:hover {
    background: #24563F;
}

.btn-reset {
    height: 40px;

    border:
        1px solid #DDE7E1;

    border-radius: 8px;

    padding: 0 14px;

    background: #FFFFFF;

    color: #64748B;

    font-size: 13px;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    transition:
        background .18s ease,
        border-color .18s ease,
        color .18s ease;
}

.btn-reset:hover {
    background: #F1FAF3;

    border-color: #BFD6C8;

    color: #2D6A4F;
}


/* =========================================================
   TABLE
========================================================= */

.pemasukan-table-wrap {
    width: 100%;

    overflow-x: auto;
}

.pemasukan-table {
    width: 100%;

    border-collapse: collapse;

    min-width: 850px;
}

.pemasukan-table thead th {
    background: #F8FCF9;

    color: #334155;

    font-size: 12px;

    font-weight: 700;

    text-align: left;

    padding: 15px 16px;

    border-bottom:
        1px solid #DDE7E1;

    white-space: nowrap;
}

.pemasukan-table tbody td {
    padding: 14px 16px;

    color: #334155;

    font-size: 13px;

    border-bottom:
        1px solid #E8F0EA;

    vertical-align: middle;
}

.pemasukan-table tbody tr {
    transition:
        background .15s ease;
}

.pemasukan-table tbody tr:hover {
    background: #F8FCF9;
}

.pemasukan-table tbody tr:last-child td {
    border-bottom: 0;
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

.kategori-badge {
    display: inline-flex;

    align-items: center;

    padding: 5px 10px;

    border-radius: 999px;

    background: #D8F3DC;

    color: #24563F;

    font-size: 12px;

    font-weight: 700;
}

.keterangan {
    color: #334155;
}

.nominal {
    color: #2D6A4F !important;

    font-weight: 800;

    white-space: nowrap;
}

.empty-pemasukan {
    padding: 40px 20px !important;

    text-align: center !important;

    color: #64748B !important;
}


/* =========================================================
   PAGINATION
========================================================= */

.pemasukan-footer {
    padding: 16px 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    flex-wrap: wrap;

    border-top:
        1px solid #E8F0EA;
}

.pemasukan-info {
    color: #64748B;

    font-size: 13px;
}

.pemasukan-total {
    color: #334155;

    font-size: 14px;

    font-weight: 600;
}

.pemasukan-total strong {
    color: #2D6A4F;

    font-size: 17px;

    margin-left: 8px;
}

.pemasukan-pagination {
    display: flex;

    align-items: center;

    gap: 7px;
}

.page-btn {
    min-width: 40px;

    height: 40px;

    padding: 0 11px;

    border:
        1px solid #DDE7E1;

    border-radius: 8px;

    background: #FFFFFF;

    color: #334155;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    font-size: 13px;

    font-weight: 600;

    box-sizing: border-box;

    transition:
        border-color .18s ease,
        color .18s ease,
        background .18s ease;
}

.page-btn:hover {
    border-color: #2D6A4F;

    color: #2D6A4F;

    background: #F1FAF3;
}

.page-btn.active {
    background: #2D6A4F;

    border-color: #2D6A4F;

    color: #FFFFFF;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .pemasukan-summary {
        grid-template-columns: 1fr;
    }

    .pemasukan-panel-head {
        align-items: flex-start;

        flex-direction: column;
    }

    .pemasukan-filter {
        width: 100%;
    }

}


@media (max-width: 700px) {

    .pemasukan-card-value {
        font-size: 22px;
    }

    .pemasukan-footer {
        align-items: flex-start;

        flex-direction: column;
    }

    .pemasukan-top-action {
        justify-content: stretch;
    }

    .btn-primary-pemasukan {
        width: 100%;

        justify-content: center;
    }

}

</style>


<section class="pemasukan-page">


    <!-- =====================================================
         ACTION
    ====================================================== -->

    <div class="pemasukan-top-action">

        <button
            type="button"
            class="btn-primary-pemasukan"
            onclick="alert('Tambah pemasukan manual belum tersedia karena database belum memiliki tabel pemasukan.')"
        >
            <span style="font-size:18px;">+</span>

            Tambah Pemasukan
        </button>

    </div>


    <!-- =====================================================
         SUMMARY
    ====================================================== -->

    <div class="pemasukan-summary">


        <!-- TOTAL PEMASUKAN -->

        <div class="pemasukan-card">

            <div class="pemasukan-card-icon blue"></div>

            <div class="pemasukan-card-content">

                <div class="pemasukan-card-label">
                    Total Pemasukan
                </div>

                <div class="pemasukan-card-value">
                    <?= rupiah($totalPemasukan) ?>
                </div>

                <div class="pemasukan-card-desc">
                    Total dari seluruh transaksi
                </div>

            </div>

        </div>


        <!-- TOTAL PENGELUARAN -->

        <div class="pemasukan-card">

            <div class="pemasukan-card-icon green"></div>

            <div class="pemasukan-card-content">

                <div class="pemasukan-card-label">
                    Total Pengeluaran
                </div>

                <div class="pemasukan-card-value">
                    Rp 0
                </div>

                <div class="pemasukan-card-desc">
                    Belum ada tabel pengeluaran
                </div>

            </div>

        </div>


        <!-- SALDO -->

        <div class="pemasukan-card">

            <div class="pemasukan-card-icon purple"></div>

            <div class="pemasukan-card-content">

                <div class="pemasukan-card-label">
                    Saldo Bersih
                </div>

                <div class="pemasukan-card-value">
                    <?= rupiah($saldoBersih) ?>
                </div>

                <div class="pemasukan-card-desc">
                    Pemasukan - pengeluaran
                </div>

            </div>

        </div>


    </div>


    <!-- =====================================================
         DATA PEMASUKAN
    ====================================================== -->

    <section class="pemasukan-panel">


        <!-- PANEL HEADER -->

        <div class="pemasukan-panel-head">

            <h2 class="pemasukan-panel-title">
                Data Pemasukan
            </h2>


            <form
                method="get"
                class="pemasukan-filter"
            >

                <input
                    type="date"
                    name="tanggal_mulai"
                    class="filter-input"
                    value="<?= e($tanggalMulai) ?>"
                    title="Dari tanggal"
                >


                <span
                    style="
                        color:#64748B;
                        font-size:13px;
                    "
                >
                    s/d
                </span>


                <input
                    type="date"
                    name="tanggal_sampai"
                    class="filter-input"
                    value="<?= e($tanggalSampai) ?>"
                    title="Sampai tanggal"
                >


                <select
                    class="filter-select"
                    disabled
                >
                    <option>
                        Penjualan
                    </option>
                </select>


                <button
                    type="submit"
                    class="btn-filter"
                >
                    Filter
                </button>


                <?php if ($tanggalMulai !== '' || $tanggalSampai !== ''): ?>

                    <a
                        href="pemasukan.php"
                        class="btn-reset"
                    >
                        Reset
                    </a>

                <?php endif; ?>

            </form>

        </div>


        <!-- TABLE -->

        <div class="pemasukan-table-wrap">

            <table class="pemasukan-table">

                <thead>

                    <tr>

                        <th
                            class="text-center"
                            style="width:65px;"
                        >
                            No
                        </th>

                        <th style="width:150px;">
                            Tanggal
                        </th>

                        <th style="width:130px;">
                            Kategori
                        </th>

                        <th>
                            Keterangan
                        </th>

                        <th
                            class="text-right"
                            style="width:180px;"
                        >
                            Jumlah
                        </th>

                        <th style="width:150px;">
                            Kasir
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (empty($dataPemasukan)): ?>

                        <tr>

                            <td
                                colspan="6"
                                class="empty-pemasukan"
                            >
                                Belum ada data pemasukan.
                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($dataPemasukan as $index => $row): ?>


                            <?php

                            $nomor =
                                $offset + $index + 1;

                            $tanggal =
                                !empty($row['tanggal'])
                                    ? date(
                                        'd M Y',
                                        strtotime(
                                            $row['tanggal']
                                        )
                                    )
                                    : '-';

                            $keterangan =
                                !empty(
                                    $row['nama_pembeli']
                                )
                                    ? 'Penjualan - '
                                        . $row['nama_pembeli']
                                    : 'Penjualan';

                            ?>


                            <tr>


                                <td class="text-center">
                                    <?= $nomor ?>
                                </td>


                                <td>
                                    <?= e($tanggal) ?>
                                </td>


                                <td>

                                    <span
                                        class="kategori-badge"
                                    >
                                        Penjualan
                                    </span>

                                </td>


                                <td class="keterangan">
                                    <?= e($keterangan) ?>
                                </td>


                                <td
                                    class="text-right nominal"
                                >
                                    <?= rupiah($row['total']) ?>
                                </td>


                                <td>
                                    <?= e(
                                        $row['nama_kasir']
                                        ?? '-'
                                    ) ?>
                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>


        <!-- =================================================
             FOOTER
        ================================================== -->

        <div class="pemasukan-footer">


            <div class="pemasukan-info">

                <?php if ($totalData > 0): ?>

                    Menampilkan

                    <?= $offset + 1 ?>

                    -

                    <?= min(
                        $offset + $perPage,
                        $totalData
                    ) ?>

                    dari

                    <?= $totalData ?>

                    data

                <?php else: ?>

                    Menampilkan 0 data

                <?php endif; ?>

            </div>


            <div class="pemasukan-pagination">


                <?php if ($page > 1): ?>

                    <a
                        href="<?= e(
                            pageUrl($page - 1)
                        ) ?>"
                        class="page-btn"
                    >
                        ‹
                    </a>

                <?php endif; ?>


                <?php

                $startPage =
                    max(1, $page - 2);

                $endPage =
                    min(
                        $totalPages,
                        $page + 2
                    );

                for (
                    $i = $startPage;
                    $i <= $endPage;
                    $i++
                ):

                ?>


                    <a
                        href="<?= e(
                            pageUrl($i)
                        ) ?>"
                        class="page-btn
                            <?= $i === $page
                                ? 'active'
                                : '' ?>"
                    >
                        <?= $i ?>
                    </a>


                <?php endfor; ?>


                <?php if ($page < $totalPages): ?>

                    <a
                        href="<?= e(
                            pageUrl($page + 1)
                        ) ?>"
                        class="page-btn"
                    >
                        ›
                    </a>

                <?php endif; ?>


            </div>


            <div class="pemasukan-total">

                Total Pemasukan:

                <strong>
                    <?= rupiah($totalPemasukan) ?>
                </strong>

            </div>


        </div>


    </section>


</section>


<?php
include __DIR__ . '/../includes/footer.php';
?>