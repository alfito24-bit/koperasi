<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pageTitle = 'Data Barang';

$message = '';
$error = '';

$uploadDir = __DIR__ . '/../uploads/';

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

/*
|--------------------------------------------------------------------------
| PROSES FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        /* SIMPAN / UPDATE */
        if ($action === 'save') {

            $id       = (int)($_POST['id_barang'] ?? 0);
            $nama     = trim($_POST['nama_barang'] ?? '');
            $kategori = trim($_POST['kategori'] ?? '');
            $harga    = (int)($_POST['harga'] ?? 0);
            $stok     = (int)($_POST['stok'] ?? 0);

            if ($nama === '') {
                throw new Exception('Nama barang wajib diisi.');
            }

            if ($kategori === '') {
                throw new Exception('Kategori wajib diisi.');
            }

            if ($harga < 0) {
                throw new Exception('Harga tidak boleh kurang dari 0.');
            }

            if ($stok < 0) {
                throw new Exception('Stok tidak boleh kurang dari 0.');
            }

            $fotoLama = '';

            if ($id > 0) {

                $st = $pdo->prepare(
                    'SELECT foto FROM barang WHERE id_barang = ?'
                );

                $st->execute([$id]);

                $oldData = $st->fetch();

                if ($oldData) {
                    $fotoLama = $oldData['foto'] ?? '';
                }
            }

            $foto = $fotoLama;

            /* UPLOAD FOTO */

            if (
                isset($_FILES['foto']) &&
                $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Foto gagal diupload.');
                }

                $tmpName = $_FILES['foto']['tmp_name'];
                $fileSize = $_FILES['foto']['size'];

                if ($fileSize > 2 * 1024 * 1024) {
                    throw new Exception('Ukuran foto maksimal 2 MB.');
                }

                $imageInfo = @getimagesize($tmpName);

                if ($imageInfo === false) {
                    throw new Exception('File yang dipilih bukan gambar.');
                }

                $allowedMime = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                $mime = $imageInfo['mime'];

                if (!isset($allowedMime[$mime])) {
                    throw new Exception(
                        'Format foto harus JPG, PNG, atau WEBP.'
                    );
                }

                $extension = $allowedMime[$mime];

                $newFileName =
                    'barang_' .
                    date('YmdHis') .
                    '_' .
                    bin2hex(random_bytes(4)) .
                    '.' .
                    $extension;

                $destination = $uploadDir . $newFileName;

                if (!move_uploaded_file($tmpName, $destination)) {
                    throw new Exception('Foto gagal disimpan.');
                }

                $foto = $newFileName;

                if (
                    $fotoLama !== '' &&
                    file_exists($uploadDir . $fotoLama)
                ) {
                    @unlink($uploadDir . $fotoLama);
                }
            }

            /* UPDATE */

            if ($id > 0) {

                $st = $pdo->prepare(
                    'UPDATE barang
                     SET nama_barang = ?,
                         kategori = ?,
                         harga = ?,
                         stok = ?,
                         foto = ?
                     WHERE id_barang = ?'
                );

                $st->execute([
                    $nama,
                    $kategori,
                    $harga,
                    $stok,
                    $foto,
                    $id
                ]);

                $message = 'Data barang berhasil diubah.';

            /* INSERT */

            } else {

                $st = $pdo->prepare(
                    'INSERT INTO barang
                    (nama_barang, kategori, harga, stok, foto)
                    VALUES (?, ?, ?, ?, ?)'
                );

                $st->execute([
                    $nama,
                    $kategori,
                    $harga,
                    $stok,
                    $foto
                ]);

                $message = 'Barang berhasil ditambahkan.';
            }
        }

        /* HAPUS */

        elseif ($action === 'delete') {

            $id = (int)($_POST['id_barang'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID barang tidak valid.');
            }

            $st = $pdo->prepare(
                'SELECT foto FROM barang WHERE id_barang = ?'
            );

            $st->execute([$id]);

            $barang = $st->fetch();

            if (!$barang) {
                throw new Exception('Barang tidak ditemukan.');
            }

            $st = $pdo->prepare(
                'DELETE FROM barang WHERE id_barang = ?'
            );

            $st->execute([$id]);

            if (
                !empty($barang['foto']) &&
                file_exists($uploadDir . $barang['foto'])
            ) {
                @unlink($uploadDir . $barang['foto']);
            }

            $message = 'Barang berhasil dihapus.';
        }

    } catch (Throwable $e) {

        $error = $e->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| MODE EDIT
|--------------------------------------------------------------------------
*/

$edit = null;

if (isset($_GET['edit'])) {

    $editId = (int)$_GET['edit'];

    if ($editId > 0) {

        $st = $pdo->prepare(
            'SELECT * FROM barang WHERE id_barang = ?'
        );

        $st->execute([$editId]);

        $edit = $st->fetch();
    }
}


/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$q = trim($_GET['q'] ?? '');
$kategoriFilter = trim($_GET['kategori'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');


/*
|--------------------------------------------------------------------------
| KATEGORI
|--------------------------------------------------------------------------
*/

$kategoriList = $pdo
    ->query(
        'SELECT DISTINCT kategori
         FROM barang
         WHERE kategori IS NOT NULL
         AND kategori <> ""
         ORDER BY kategori'
    )
    ->fetchAll();


/*
|--------------------------------------------------------------------------
| DATA BARANG
|--------------------------------------------------------------------------
*/

$sql = '
    SELECT *
    FROM barang
    WHERE 1=1
';

$params = [];

if ($q !== '') {

    $sql .= '
        AND (
            nama_barang LIKE ?
            OR kategori LIKE ?
        )
    ';

    $like = '%' . $q . '%';

    $params[] = $like;
    $params[] = $like;
}

if ($kategoriFilter !== '') {

    $sql .= ' AND kategori = ?';

    $params[] = $kategoriFilter;
}

if ($statusFilter === 'aman') {

    $sql .= ' AND stok > 10';

} elseif ($statusFilter === 'menipis') {

    $sql .= ' AND stok <= 10';
}

$sql .= ' ORDER BY id_barang ASC';

$st = $pdo->prepare($sql);

$st->execute($params);

$items = $st->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<style>
.app-shell > .sidebar{display:none!important}.main-content{margin-left:260px!important}
.modern-sidebar{position:fixed;inset:0 auto 0 0;width:260px;padding:22px 14px;box-sizing:border-box;background:linear-gradient(180deg,#2D6A4F,#24563F);z-index:9999;display:flex;flex-direction:column;box-shadow:4px 0 18px rgba(36,86,63,.12)}
.modern-brand{display:flex;align-items:center;gap:11px;padding:4px 10px 22px;border-bottom:1px solid rgba(255,255,255,.15)}
.modern-brand-mark{width:40px;height:40px;border-radius:11px;background:rgba(255,255,255,.16);color:#fff;display:flex;align-items:center;justify-content:center;font-size:21px;font-weight:800}.modern-brand-text strong{display:block;color:#fff;font-size:16px}.modern-brand-text small{display:block;color:rgba(255,255,255,.7);font-size:9.5px;margin-top:3px}
.modern-nav{margin-top:20px;display:flex;flex-direction:column;gap:5px;flex:1}.modern-nav a{min-height:45px;padding:0 13px;border-radius:10px;display:flex;align-items:center;gap:12px;color:rgba(255,255,255,.82);text-decoration:none;font-size:13px;font-weight:500;transition:.2s}.modern-nav a:hover{background:rgba(255,255,255,.1);color:#fff;transform:translateX(2px)}.modern-nav a.active{background:#D8F3DC;color:#24563F;font-weight:700}.modern-nav-icon{width:21px;height:21px;flex:0 0 21px;display:inline-flex;align-items:center;justify-content:center}.modern-nav-icon svg{width:19px;height:19px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.modern-logout{margin-top:auto!important;background:rgba(12,45,31,.38)!important;color:#fff!important}.modern-logout:hover{background:rgba(12,45,31,.58)!important;transform:none!important}
.data-barang-page{padding-bottom:30px}.data-alert{padding:13px 16px;border-radius:10px;margin-bottom:18px;font-size:13px;font-weight:600}.data-alert.success{background:#D8F3DC;color:#24563F;border:1px solid #b9e6c2}.data-alert.danger{background:#FEF2F2;color:#DC2626;border:1px solid #FECACA}.data-card,.data-form-card{background:#fff;border:1px solid #DDE7E1;border-radius:14px;box-shadow:0 5px 20px rgba(36,86,63,.05)}.data-card{overflow:hidden}.data-card-top{padding:23px 20px 16px}.data-description{margin:0 0 18px;font-size:13px;color:#334155}.data-filter{display:grid;grid-template-columns:minmax(230px,1.6fr) minmax(160px,.8fr) minmax(160px,.8fr) auto auto;gap:12px;align-items:end}.data-field{display:flex;flex-direction:column;gap:7px}.data-field label,.data-form-group label{font-size:12px;font-weight:600;color:#334155}.data-input,.data-select{height:44px;width:100%;box-sizing:border-box;border:1px solid #DDE7E1;border-radius:8px;background:#fff;color:#1F2937;font-size:13px;padding:0 13px;outline:none}.data-input:focus,.data-select:focus{border-color:#2D6A4F;box-shadow:0 0 0 3px rgba(45,106,79,.1)}.search-box{position:relative}.search-box .data-input{padding-left:42px}.search-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:17px;color:#64748B}.data-btn{height:44px;border-radius:8px;border:1px solid #DDE7E1;background:#fff;padding:0 16px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;white-space:nowrap}.data-btn-primary{color:#fff;background:#2D6A4F;border-color:#2D6A4F}.data-btn-primary:hover{background:#24563F}.data-btn-excel{color:#24563F;border-color:#C8D8CE}.data-btn-excel span{color:#2D6A4F;font-size:17px}.data-table-wrap{padding:0 20px 18px;overflow-x:auto}.data-table{width:100%;min-width:950px;border-collapse:collapse}.data-table thead th{background:#F1FAF3;color:#1F2937;font-size:12px;font-weight:700;text-align:left;padding:15px 12px}.data-table tbody td{padding:12px;border-bottom:1px solid #E8F0EA;font-size:13px;color:#1F2937;vertical-align:middle}.data-table tbody tr:hover{background:#F8FCF9}.text-center{text-align:center!important}.product-photo{width:48px;height:48px;object-fit:contain;border-radius:7px}.no-product-photo{width:48px;height:48px;border-radius:7px;background:#F3F7F4;border:1px dashed #C8D8CE;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:10px;text-align:center}.category-badge{display:inline-flex;padding:5px 11px;border-radius:999px;background:#FFF7E6;color:#A16207;font-size:11px;font-weight:600}.stock-safe{color:#2D6A4F!important;font-weight:700}.stock-low{color:#DC2626!important;font-weight:700}.status-badge{display:inline-flex;justify-content:center;min-width:65px;padding:5px 10px;border-radius:999px;font-size:11px;font-weight:600}.status-safe{background:#D8F3DC;color:#24563F}.status-low{background:#FFF7E6;color:#A16207}.action-group{display:flex;gap:8px}.action-btn{width:38px;height:38px;border-radius:8px;border:1px solid #C8D8CE;background:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;text-decoration:none;font-size:17px}.action-btn.edit{color:#2D6A4F}.action-btn.delete{color:#DC2626}.action-form{margin:0}.data-table-footer{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:14px 0 0}.data-count{color:#64748B;font-size:12px}.pagination{display:flex;gap:5px}.page-btn{min-width:34px;height:34px;border:1px solid #DDE7E1;border-radius:7px;background:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:12px}.page-btn.active{background:#2D6A4F;border-color:#2D6A4F;color:#fff}.data-form-card{margin-top:20px;padding:22px}.data-form-title{margin:0 0 20px;font-size:18px;color:#1F2937}.data-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.data-form-group{display:flex;flex-direction:column;gap:7px}.data-form-group.full{grid-column:1/-1}.data-file{border:1px dashed #C8D8CE;border-radius:8px;padding:12px;background:#F8FCF9;font-size:12px}.current-photo{width:70px;height:70px;object-fit:contain;border-radius:8px;border:1px solid #DDE7E1;margin-top:8px}.data-form-actions{margin-top:20px;display:flex;justify-content:flex-end;gap:10px}@media(max-width:1100px){.data-filter{grid-template-columns:1fr 1fr}.data-filter>.data-btn{width:100%}}@media(max-width:700px){.main-content{margin-left:0!important}.modern-sidebar{display:none}.data-filter,.data-form-grid{grid-template-columns:1fr}.data-form-group.full{grid-column:auto}.data-table-footer{align-items:flex-start;flex-direction:column}}
</style>


<aside class="modern-sidebar"><div class="modern-brand"><div class="modern-brand-mark">E</div><div class="modern-brand-text"><strong>E-KOPERASI</strong><small>Sistem Informasi Koperasi Sekolah</small></div></div><nav class="modern-nav">
<a href="dashboard.php"><span class="modern-nav-icon"><svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/></svg></span>Dashboard</a>
<a href="transaksi.php"><span class="modern-nav-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M3 4h2l2.2 11h10.9l2-8H6"/></svg></span>Transaksi</a>
<a href="barang.php" class="active"><span class="modern-nav-icon"><svg viewBox="0 0 24 24"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="M4 7.5 12 12l8-4.5M12 12v9"/></svg></span>Data Barang</a>
<a href="stok.php"><span class="modern-nav-icon"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg></span>Stok Barang</a>
<a href="pemasukan.php"><span class="modern-nav-icon"><svg viewBox="0 0 24 24"><path d="M12 19V5M6 11l6-6 6 6"/></svg></span>Pemasukan</a>
<a href="pengeluaran.php"><span class="modern-nav-icon"><svg viewBox="0 0 24 24"><path d="M12 5v14M6 13l6 6 6-6"/></svg></span>Pengeluaran</a>
<a href="laporan.php"><span class="modern-nav-icon"><svg viewBox="0 0 24 24"><path d="M5 3h10l4 4v14H5zM15 3v5h5M8 13h8M8 17h6"/></svg></span>Laporan</a>
<a href="users.php"><span class="modern-nav-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20c.8-3.2 2.8-5 6-5s5.2 1.8 6 5M16 5.5a3 3 0 0 1 0 5.5M17 15c2 .6 3.3 2.1 4 5"/></svg></span>Manajemen User</a>
<a href="/koperasi/logout.php" class="modern-logout"><span class="modern-nav-icon"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M14 4h5v16h-5"/></svg></span>Logout</a>
</nav></aside>

<!-- =========================================================
     CONTENT DATA BARANG
========================================================= -->

<div class="data-barang-page">

    <?php if ($message): ?>

        <div class="data-alert success">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="data-alert danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <?php if ($edit): ?>

        <div class="data-form-card">

            <h2 class="data-form-title">
                Edit Barang
            </h2>

            <form
                method="post"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="action"
                    value="save"
                >

                <input
                    type="hidden"
                    name="id_barang"
                    value="<?= e($edit['id_barang']) ?>"
                >

                <div class="data-form-grid">

                    <div class="data-form-group">

                        <label>Nama Barang</label>

                        <input
                            class="data-input"
                            type="text"
                            name="nama_barang"
                            required
                            value="<?= e($edit['nama_barang']) ?>"
                        >

                    </div>


                    <div class="data-form-group">

                        <label>Kategori</label>

                        <input
                            class="data-input"
                            type="text"
                            name="kategori"
                            required
                            value="<?= e($edit['kategori']) ?>"
                        >

                    </div>


                    <div class="data-form-group">

                        <label>Harga</label>

                        <input
                            class="data-input"
                            type="number"
                            min="0"
                            name="harga"
                            required
                            value="<?= e($edit['harga']) ?>"
                        >

                    </div>


                    <div class="data-form-group">

                        <label>Stok</label>

                        <input
                            class="data-input"
                            type="number"
                            min="0"
                            name="stok"
                            required
                            value="<?= e($edit['stok']) ?>"
                        >

                    </div>


                    <div class="data-form-group full">

                        <label>Foto Barang</label>

                        <input
                            class="data-file"
                            type="file"
                            name="foto"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <?php if (!empty($edit['foto'])): ?>

                            <img
                                class="current-photo"
                                src="/koperasi/uploads/<?= e($edit['foto']) ?>"
                                alt="Foto barang"
                            >

                        <?php endif; ?>

                    </div>

                </div>


                <div class="data-form-actions">

                    <a
                        class="data-btn"
                        href="/koperasi/admin/barang.php"
                    >
                        Batal
                    </a>

                    <button
                        class="data-btn data-btn-primary"
                        type="submit"
                    >
                        Simpan Perubahan
                    </button>

                </div>

            </form>

        </div>

    <?php else: ?>

        <div class="data-card">

            <div class="data-card-top">

                <p class="data-description">
                    Kelola semua data barang yang tersedia di koperasi.
                </p>


                <form
                    class="data-filter"
                    method="get"
                >

                    <div class="data-field">

                        <label>&nbsp;</label>

                        <div class="search-box">

                            <span class="search-icon">⌕</span>

                            <input
                                class="data-input"
                                type="text"
                                name="q"
                                placeholder="Cari nama / kategori barang..."
                                value="<?= e($q) ?>"
                            >

                        </div>

                    </div>


                    <div class="data-field">

                        <label>Kategori</label>

                        <select
                            class="data-select"
                            name="kategori"
                        >

                            <option value="">
                                Semua Kategori
                            </option>

                            <?php foreach ($kategoriList as $kat): ?>

                                <option
                                    value="<?= e($kat['kategori']) ?>"
                                    <?= $kategoriFilter === $kat['kategori'] ? 'selected' : '' ?>
                                >
                                    <?= e($kat['kategori']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="data-field">

                        <label>Status Barang</label>

                        <select
                            class="data-select"
                            name="status"
                        >

                            <option value="">
                                Semua Status
                            </option>

                            <option
                                value="aman"
                                <?= $statusFilter === 'aman' ? 'selected' : '' ?>
                            >
                                Aman
                            </option>

                            <option
                                value="menipis"
                                <?= $statusFilter === 'menipis' ? 'selected' : '' ?>
                            >
                                Menipis
                            </option>

                        </select>

                    </div>


                    <a
                        class="data-btn data-btn-excel"
                        href="?q=<?= urlencode($q) ?>&kategori=<?= urlencode($kategoriFilter) ?>&status=<?= urlencode($statusFilter) ?>"
                        onclick="alert('Fitur export Excel kita kerjakan setelah tampilan Data Barang selesai.'); return false;"
                    >
                        <span>▣</span>
                        Export Excel
                    </a>


                    <a
                        class="data-btn data-btn-primary"
                        href="?edit=0#form"
                        onclick="document.getElementById('barang-form').style.display='block'; return false;"
                    >
                        <span style="font-size:18px;">+</span>
                        Tambah Barang
                    </a>

                </form>

            </div>


            <div class="data-table-wrap">

                <table class="data-table">

                    <thead>

                        <tr>
                            <th>No.</th>
                            <th>Foto</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Harga Jual</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!$items): ?>

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center"
                                    style="padding:35px;"
                                >
                                    Tidak ada data barang.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($items as $index => $item): ?>

                                <?php
                                    $stok = (int)$item['stok'];
                                    $isLow = $stok <= 10;
                                ?>

                                <tr>

                                    <td>
                                        <?= $index + 1 ?>
                                    </td>


                                    <td>

                                        <?php if (!empty($item['foto'])): ?>

                                            <img
                                                class="product-photo"
                                                src="/koperasi/uploads/<?= e($item['foto']) ?>"
                                                alt="<?= e($item['nama_barang']) ?>"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                            >

                                            <div
                                                class="no-product-photo"
                                                style="display:none;"
                                            >
                                                Foto tidak tersedia
                                            </div>

                                        <?php else: ?>

                                            <div class="no-product-photo">
                                                Belum ada foto
                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <td>
                                        <strong>
                                            <?= e($item['nama_barang']) ?>
                                        </strong>
                                    </td>


                                    <td>

                                        <span class="category-badge">
                                            <?= e($item['kategori']) ?>
                                        </span>

                                    </td>


                                    <td>
                                        <?= rupiah($item['harga']) ?>
                                    </td>


                                    <td
                                        class="<?= $isLow ? 'stock-low' : 'stock-safe' ?>"
                                    >
                                        <?= e($item['stok']) ?>
                                    </td>


                                    <td>

                                        <?php if ($isLow): ?>

                                            <span class="status-badge status-low">
                                                Menipis
                                            </span>

                                        <?php else: ?>

                                            <span class="status-badge status-safe">
                                                Aman
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <div class="action-group">

                                            <a
                                                class="action-btn edit"
                                                href="?edit=<?= e($item['id_barang']) ?>"
                                                title="Edit barang"
                                            >
                                                ✎
                                            </a>


                                            <form
                                                class="action-form"
                                                method="post"
                                                onsubmit="return confirm('Hapus barang ini?')"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id_barang"
                                                    value="<?= e($item['id_barang']) ?>"
                                                >

                                                <button
                                                    class="action-btn delete"
                                                    type="submit"
                                                    title="Hapus barang"
                                                >
                                                    🗑
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>


                <div class="data-table-footer">

                    <div class="data-count">
                        Menampilkan
                        <strong><?= count($items) ?></strong>
                        data barang
                    </div>


                    <div class="pagination">

                        <span class="page-btn">‹</span>
                        <span class="page-btn active">1</span>
                        <span class="page-btn">2</span>
                        <span class="page-btn">3</span>
                        <span class="page-btn">…</span>
                        <span class="page-btn">›</span>

                    </div>

                </div>

            </div>

        </div>

    <?php endif; ?>

</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>