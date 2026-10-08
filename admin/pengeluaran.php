<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pageTitle = 'Pengeluaran (Admin)';

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/
$tanggalMulai = $_GET['tanggal_mulai'] ?? '';
$tanggalSampai = $_GET['tanggal_sampai'] ?? '';
$kategori = $_GET['kategori'] ?? '';

$where = [];
$params = [];

if ($tanggalMulai !== '') {
    $where[] = 'p.tanggal >= :tanggal_mulai';
    $params[':tanggal_mulai'] = $tanggalMulai;
}

if ($tanggalSampai !== '') {
    $where[] = 'p.tanggal <= :tanggal_sampai';
    $params[':tanggal_sampai'] = $tanggalSampai;
}

if ($kategori !== '') {
    $where[] = 'p.kategori = :kategori';
    $params[':kategori'] = $kategori;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

/*
|--------------------------------------------------------------------------
| Ambil kategori
|--------------------------------------------------------------------------
*/
$kategoriRows = $pdo->query("
    SELECT DISTINCT kategori
    FROM pengeluaran
    WHERE kategori IS NOT NULL
      AND kategori <> ''
    ORDER BY kategori ASC
")->fetchAll();

$kategoriList = array_column($kategoriRows, 'kategori');

/*
|--------------------------------------------------------------------------
| Total pemasukan
|--------------------------------------------------------------------------
*/
$stmtPemasukan = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM transaksi
");

$totalPemasukan = (float) $stmtPemasukan->fetchColumn();

/*
|--------------------------------------------------------------------------
| Total pengeluaran
|--------------------------------------------------------------------------
*/
$stmtTotal = $pdo->prepare("
    SELECT
        COUNT(*) AS jumlah_data,
        COALESCE(SUM(p.jumlah), 0) AS total_pengeluaran
    FROM pengeluaran p
    $whereSql
");

$stmtTotal->execute($params);

$summary = $stmtTotal->fetch();

$totalPengeluaran = (float) ($summary['total_pengeluaran'] ?? 0);
$jumlahData = (int) ($summary['jumlah_data'] ?? 0);

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

$stmtCount = $pdo->prepare("
    SELECT COUNT(*)
    FROM pengeluaran p
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
| Ambil data pengeluaran
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        p.id_pengeluaran,
        p.tanggal,
        p.kategori,
        p.keterangan,
        p.jumlah,
        u.nama AS nama_user
    FROM pengeluaran p
    LEFT JOIN users u
        ON u.id = p.id_user
    $whereSql
    ORDER BY p.tanggal DESC, p.id_pengeluaran DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$dataPengeluaran = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| URL pagination
|--------------------------------------------------------------------------
*/
function pageUrl(int $pageNumber): string
{
    $query = $_GET;
    $query['page'] = $pageNumber;

    return '?' . http_build_query($query);
}

/*
|--------------------------------------------------------------------------
| Hapus data
|--------------------------------------------------------------------------
*/
if (isset($_GET['hapus'])) {

    $idHapus = (int) $_GET['hapus'];

    if ($idHapus > 0) {

        $stmtDelete = $pdo->prepare("
            DELETE FROM pengeluaran
            WHERE id_pengeluaran = ?
        ");

        $stmtDelete->execute([$idHapus]);
    }

    $query = $_GET;
    unset($query['hapus']);

    $redirect = 'pengeluaran.php';

    if (!empty($query)) {
        $redirect .= '?' . http_build_query($query);
    }

    header('Location: ' . $redirect);
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<style>
/* =========================================================
   PENGELUARAN - GREEN THEME
   ========================================================= */

.pengeluaran-page {
    width: 100%;
}

/* =========================================================
   ACTION
   ========================================================= */

.pengeluaran-top-action {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
}

.btn-primary-pengeluaran {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    min-height: 42px;
    padding: 0 17px;

    border: 0;
    border-radius: 9px;

    background: #2D6A4F;
    color: #fff;

    font-size: 13px;
    font-weight: 600;

    text-decoration: none;

    box-shadow: 0 4px 10px rgba(45, 106, 79, .16);

    transition:
        background .2s ease,
        transform .2s ease,
        box-shadow .2s ease;
}

.btn-primary-pengeluaran:hover {
    background: #24563F;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(45, 106, 79, .20);
}

.btn-primary-pengeluaran .plus-icon {
    width: 20px;
    height: 20px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    border-radius: 6px;

    background: rgba(255,255,255,.15);

    font-size: 17px;
    line-height: 1;
}

/* =========================================================
   SUMMARY
   ========================================================= */

.pengeluaran-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;

    margin-bottom: 22px;
}

.pengeluaran-card {
    min-height: 132px;

    padding: 20px;

    border: 1px solid #DDE7E1;
    border-radius: 14px;

    background: #fff;

    display: flex;
    align-items: center;
    gap: 17px;

    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
}

.pengeluaran-card-icon {
    width: 56px;
    height: 56px;
    min-width: 56px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 13px;

    font-size: 25px;
    font-weight: 700;
}

/* Pemasukan */
.pengeluaran-card-icon.blue {
    color: #2D6A4F;
    background: #D8F3DC;
}

/* Pengeluaran */
.pengeluaran-card-icon.green {
    color: #B45309;
    background: #FEF3C7;
}

/* Saldo */
.pengeluaran-card-icon.purple {
    color: #315C47;
    background: #EAF4ED;
}

.pengeluaran-card-content {
    min-width: 0;
}

.pengeluaran-card-label {
    margin-bottom: 6px;

    color: #64748B;

    font-size: 13px;
    font-weight: 500;
}

.pengeluaran-card-value {
    margin-bottom: 6px;

    color: #1F2937;

    font-size: 23px;
    line-height: 1.2;
    font-weight: 700;

    word-break: break-word;
}

.pengeluaran-card-desc {
    color: #94A3B8;
    font-size: 12px;
}

/* =========================================================
   PANEL
   ========================================================= */

.pengeluaran-panel {
    border: 1px solid #DDE7E1;
    border-radius: 14px;

    background: #fff;

    overflow: hidden;

    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
}

.pengeluaran-panel-head {
    padding: 17px 20px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 18px;

    border-bottom: 1px solid #E8EFEA;
}

.pengeluaran-panel-title {
    margin: 0;

    color: #1F2937;

    font-size: 19px;
    font-weight: 700;

    white-space: nowrap;
}

/* =========================================================
   FILTER
   ========================================================= */

.pengeluaran-filter {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-input,
.filter-select {
    height: 38px;

    border: 1px solid #DDE7E1;
    border-radius: 8px;

    background: #fff;
    color: #334155;

    padding: 0 11px;

    font-family: inherit;
    font-size: 12px;

    outline: none;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;
}

.filter-input:focus,
.filter-select:focus {
    border-color: #2D6A4F;
    box-shadow: 0 0 0 3px rgba(45,106,79,.08);
}

.filter-input {
    width: 140px;
}

.filter-select {
    width: 140px;
}

.btn-filter {
    height: 38px;

    border: 0;
    border-radius: 8px;

    padding: 0 15px;

    background: #2D6A4F;
    color: #fff;

    font-size: 12px;
    font-weight: 600;

    cursor: pointer;

    transition: background .2s ease;
}

.btn-filter:hover {
    background: #24563F;
}

.btn-reset {
    height: 38px;

    border: 1px solid #DDE7E1;
    border-radius: 8px;

    padding: 0 13px;

    background: #fff;
    color: #64748B;

    font-size: 12px;
    text-decoration: none;

    display: inline-flex;
    align-items: center;
}

.btn-reset:hover {
    background: #F1FAF3;
    color: #2D6A4F;
}

/* =========================================================
   TABLE
   ========================================================= */

.pengeluaran-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.pengeluaran-table {
    width: 100%;
    min-width: 900px;

    border-collapse: collapse;
}

.pengeluaran-table thead th {
    background: #F1FAF3;

    color: #475569;

    font-size: 12px;
    font-weight: 700;

    text-align: left;

    padding: 14px 16px;

    border-bottom: 1px solid #DDE7E1;

    white-space: nowrap;
}

.pengeluaran-table tbody td {
    padding: 14px 16px;

    color: #475569;

    font-size: 13px;

    border-bottom: 1px solid #EDF2EF;

    vertical-align: middle;
}

.pengeluaran-table tbody tr:hover {
    background: #FAFCFB;
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

    border-radius: 7px;

    background: #FEF3C7;
    color: #92400E;

    font-size: 11px;
    font-weight: 600;

    white-space: nowrap;
}

.nominal {
    color: #1F2937 !important;
    font-weight: 700 !important;
    white-space: nowrap;
}

.empty-pengeluaran {
    padding: 45px 20px !important;

    text-align: center !important;

    color: #94A3B8 !important;
}

/* =========================================================
   TABLE ACTION
   ========================================================= */

.table-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}

.action-btn {
    width: 34px;
    height: 34px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    border-radius: 8px;

    text-decoration: none;

    font-size: 15px;

    box-sizing: border-box;

    transition:
        background .2s ease,
        transform .2s ease;
}

.action-btn:hover {
    transform: translateY(-1px);
}

.action-edit {
    color: #2D6A4F;
    background: #EAF4ED;
    border: 1px solid #CFE4D6;
}

.action-edit:hover {
    background: #D8F3DC;
}

.action-delete {
    color: #DC2626;
    background: #FEF2F2;
    border: 1px solid #FECACA;
}

.action-delete:hover {
    background: #FEE2E2;
}

/* =========================================================
   FOOTER
   ========================================================= */

.pengeluaran-footer {
    padding: 16px 20px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;
    flex-wrap: wrap;
}

.pengeluaran-info {
    color: #64748B;
    font-size: 12px;
}

.pengeluaran-total {
    color: #475569;
    font-size: 13px;
    font-weight: 600;
}

.pengeluaran-total strong {
    color: #2D6A4F;
    font-size: 16px;
    margin-left: 7px;
}

.pengeluaran-pagination {
    display: flex;
    align-items: center;
    gap: 6px;
}

.page-btn {
    min-width: 36px;
    height: 36px;

    padding: 0 10px;

    border: 1px solid #DDE7E1;
    border-radius: 8px;

    background: #fff;
    color: #475569;

    text-decoration: none;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    font-size: 12px;
    font-weight: 600;

    box-sizing: border-box;
}

.page-btn:hover {
    border-color: #2D6A4F;
    color: #2D6A4F;
    background: #F1FAF3;
}

.page-btn.active {
    background: #2D6A4F;
    border-color: #2D6A4F;
    color: #fff;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1100px) {

    .pengeluaran-summary {
        grid-template-columns: 1fr;
    }

    .pengeluaran-panel-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .pengeluaran-filter {
        width: 100%;
    }
}

@media (max-width: 700px) {

    .pengeluaran-top-action {
        justify-content: stretch;
    }

    .btn-primary-pengeluaran {
        width: 100%;
        justify-content: center;
    }

    .pengeluaran-card-value {
        font-size: 21px;
    }

    .pengeluaran-footer {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<section class="pengeluaran-page">

    <!-- TOMBOL TAMBAH -->
    <div class="pengeluaran-top-action">

        <a href="pengeluaran_form.php" class="btn-primary-pengeluaran">
            <span class="plus-icon">+</span>
            Tambah Pengeluaran
        </a>

    </div>


    <!-- SUMMARY -->
    <div class="pengeluaran-summary">

        <!-- TOTAL PEMASUKAN -->
        <div class="pengeluaran-card">

            <div class="pengeluaran-card-icon blue">
                ↓
            </div>

            <div class="pengeluaran-card-content">

                <div class="pengeluaran-card-label">
                    Total Pemasukan
                </div>

                <div class="pengeluaran-card-value">
                    <?= rupiah($totalPemasukan) ?>
                </div>

                <div class="pengeluaran-card-desc">
                    Total seluruh transaksi
                </div>

            </div>

        </div>


        <!-- TOTAL PENGELUARAN -->
        <div class="pengeluaran-card">

            <div class="pengeluaran-card-icon green">
                ↗
            </div>

            <div class="pengeluaran-card-content">

                <div class="pengeluaran-card-label">
                    Total Pengeluaran
                </div>

                <div class="pengeluaran-card-value">
                    <?= rupiah($totalPengeluaran) ?>
                </div>

                <div class="pengeluaran-card-desc">
                    Total seluruh pengeluaran
                </div>

            </div>

        </div>


        <!-- SALDO BERSIH -->
        <div class="pengeluaran-card">

            <div class="pengeluaran-card-icon purple">
                ▣
            </div>

            <div class="pengeluaran-card-content">

                <div class="pengeluaran-card-label">
                    Saldo Bersih
                </div>

                <div class="pengeluaran-card-value">
                    <?= rupiah($saldoBersih) ?>
                </div>

                <div class="pengeluaran-card-desc">
                    Pemasukan - pengeluaran
                </div>

            </div>

        </div>

    </div>


    <!-- DATA PENGELUARAN -->
    <section class="pengeluaran-panel">

        <div class="pengeluaran-panel-head">

            <h2 class="pengeluaran-panel-title">
                Data Pengeluaran
            </h2>

            <form method="get" class="pengeluaran-filter">

                <input
                    type="date"
                    name="tanggal_mulai"
                    class="filter-input"
                    value="<?= e($tanggalMulai) ?>"
                    title="Dari tanggal"
                >

                <span style="color:#64748B;font-size:12px;">
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
                    name="kategori"
                    class="filter-select"
                >

                    <option value="">
                        Semua Kategori
                    </option>

                    <?php foreach ($kategoriList as $item): ?>

                        <option
                            value="<?= e($item) ?>"
                            <?= $kategori === $item ? 'selected' : '' ?>
                        >
                            <?= e($item) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <button type="submit" class="btn-filter">
                    Tampilkan
                </button>

                <?php if ($tanggalMulai !== '' || $tanggalSampai !== '' || $kategori !== ''): ?>

                    <a href="pengeluaran.php" class="btn-reset">
                        Reset
                    </a>

                <?php endif; ?>

            </form>

        </div>


        <div class="pengeluaran-table-wrap">

            <table class="pengeluaran-table">

                <thead>

                    <tr>

                        <th class="text-center" style="width:65px;">
                            No
                        </th>

                        <th style="width:150px;">
                            Tanggal
                        </th>

                        <th style="width:150px;">
                            Kategori
                        </th>

                        <th>
                            Keterangan
                        </th>

                        <th class="text-right" style="width:180px;">
                            Jumlah
                        </th>

                        <th style="width:140px;">
                            Admin
                        </th>

                        <th class="text-center" style="width:120px;">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (empty($dataPengeluaran)): ?>

                        <tr>

                            <td colspan="7" class="empty-pengeluaran">
                                Belum ada data pengeluaran.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($dataPengeluaran as $index => $row): ?>

                            <?php
                            $nomor = $offset + $index + 1;

                            $tanggal = !empty($row['tanggal'])
                                ? date('d M Y', strtotime($row['tanggal']))
                                : '-';
                            ?>

                            <tr>

                                <td class="text-center">
                                    <?= $nomor ?>
                                </td>

                                <td>
                                    <?= e($tanggal) ?>
                                </td>

                                <td>
                                    <span class="kategori-badge">
                                        <?= e($row['kategori']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= e($row['keterangan']) ?>
                                </td>

                                <td class="text-right nominal">
                                    <?= rupiah($row['jumlah']) ?>
                                </td>

                                <td>
                                    <?= e($row['nama_user'] ?? '-') ?>
                                </td>

                                <td>

                                    <div class="table-actions">

                                        <a
                                            href="pengeluaran_form.php?edit=<?= (int) $row['id_pengeluaran'] ?>"
                                            class="action-btn action-edit"
                                            title="Edit"
                                        >
                                            ✎
                                        </a>

                                        <a
                                            href="pengeluaran.php?hapus=<?= (int) $row['id_pengeluaran'] ?>"
                                            class="action-btn action-delete"
                                            title="Hapus"
                                            onclick="return confirm('Yakin ingin menghapus pengeluaran ini?')"
                                        >
                                            🗑
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>


        <!-- FOOTER -->
        <div class="pengeluaran-footer">

            <div class="pengeluaran-info">

                <?php if ($totalData > 0): ?>

                    Menampilkan
                    <?= $offset + 1 ?>
                    -
                    <?= min($offset + $perPage, $totalData) ?>
                    dari
                    <?= $totalData ?>
                    data

                <?php else: ?>

                    Menampilkan 0 data

                <?php endif; ?>

            </div>


            <div class="pengeluaran-pagination">

                <?php if ($page > 1): ?>

                    <a
                        href="<?= e(pageUrl($page - 1)) ?>"
                        class="page-btn"
                    >
                        ‹
                    </a>

                <?php endif; ?>


                <?php

                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                for ($i = $startPage; $i <= $endPage; $i++):
                ?>

                    <a
                        href="<?= e(pageUrl($i)) ?>"
                        class="page-btn <?= $i === $page ? 'active' : '' ?>"
                    >
                        <?= $i ?>
                    </a>

                <?php endfor; ?>


                <?php if ($page < $totalPages): ?>

                    <a
                        href="<?= e(pageUrl($page + 1)) ?>"
                        class="page-btn"
                    >
                        ›
                    </a>

                <?php endif; ?>

            </div>


            <div class="pengeluaran-total">

                Total Pengeluaran:

                <strong>
                    <?= rupiah($totalPengeluaran) ?>
                </strong>

            </div>

        </div>

    </section>

</section>

<?php
include __DIR__ . '/../includes/footer.php';
?>