<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

$pageTitle = 'Data User';
$message = '';
$error = '';

/* =========================
   PROSES DATA
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        /* TAMBAH / EDIT */
        if ($action === 'save') {

            $id       = (int) ($_POST['id'] ?? 0);
            $nama     = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role     = $_POST['role'] ?? 'kasir';

            if ($nama === '' || $username === '') {
                throw new Exception('Nama dan username wajib diisi.');
            }

            if (!in_array($role, ['admin', 'kasir'], true)) {
                throw new Exception('Role tidak valid.');
            }

            /* EDIT */
            if ($id) {

                if ($password !== '') {

                    $st = $pdo->prepare("
                        UPDATE users
                        SET nama = ?, username = ?, password = ?, role = ?
                        WHERE id = ?
                    ");

                    $st->execute([
                        $nama,
                        $username,
                        $password,
                        $role,
                        $id
                    ]);

                } else {

                    $st = $pdo->prepare("
                        UPDATE users
                        SET nama = ?, username = ?, role = ?
                        WHERE id = ?
                    ");

                    $st->execute([
                        $nama,
                        $username,
                        $role,
                        $id
                    ]);
                }

                $message = 'User berhasil diubah.';
            }

            /* TAMBAH */
            else {

                if ($password === '') {
                    throw new Exception(
                        'Password wajib diisi untuk user baru.'
                    );
                }

                $st = $pdo->prepare("
                    INSERT INTO users
                    (nama, username, password, role)
                    VALUES (?, ?, ?, ?)
                ");

                $st->execute([
                    $nama,
                    $username,
                    $password,
                    $role
                ]);

                $message = 'User berhasil ditambahkan.';
            }
        }

        /* HAPUS */
        elseif ($action === 'delete') {

            $id = (int) ($_POST['id'] ?? 0);

            if ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
                throw new Exception(
                    'User yang sedang login tidak boleh dihapus.'
                );
            }

            $st = $pdo->prepare(
                'DELETE FROM users WHERE id = ?'
            );

            $st->execute([$id]);

            $message = 'User berhasil dihapus.';
        }

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}


/* =========================
   MODE HALAMAN
========================= */

$showForm = isset($_GET['add']) || isset($_GET['edit']);

$edit = null;

if (isset($_GET['edit'])) {

    $st = $pdo->prepare(
        'SELECT * FROM users WHERE id = ?'
    );

    $st->execute([
        (int) $_GET['edit']
    ]);

    $edit = $st->fetch();

    if (!$edit) {
        $showForm = false;
        $error = 'Data user tidak ditemukan.';
    }
}


/* =========================
   DAFTAR USER
========================= */

$users = $pdo->query("
    SELECT id, nama, username, role
    FROM users
    ORDER BY id DESC
")->fetchAll();


include __DIR__ . '/../includes/header.php';
?>


<style>

/* =========================================================
   USERS PAGE - GREEN THEME
   ========================================================= */

.users-page {
    width: 100%;
}


/* =========================================================
   ALERT
   ========================================================= */

.alert {
    padding: 12px 15px;
    border-radius: 9px;
    margin-bottom: 18px;
    font-size: 13px;
    font-weight: 600;
}

.alert.success {
    background: #d8f3dc;
    border: 1px solid #b7e4c7;
    color: #24563f;
}

.alert.danger {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #b91c1c;
}


/* =========================================================
   PANEL
   ========================================================= */

.panel {
    width: 100%;
    background: #ffffff;
    border: 1px solid #dde7e1;
    border-radius: 14px;
    padding: 20px;
    box-sizing: border-box;
    box-shadow: 0 2px 8px rgba(31, 41, 55, .04);
}

.form-panel {
    max-width: 680px;
}


/* =========================================================
   USER HEADER
   ========================================================= */

.user-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 18px;
}

.user-header h2,
.panel-head h2 {
    margin: 0;
    color: #1f2937;
    font-size: 19px;
    font-weight: 800;
}

.user-header p,
.panel-head p {
    margin: 5px 0 0;
    color: #64748b;
    font-size: 13px;
}


/* =========================================================
   PANEL HEADER
   ========================================================= */

.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 17px;
    border-bottom: 1px solid #e5eee8;
}


/* =========================================================
   BUTTON
   ========================================================= */

.btn {
    border: none;
    border-radius: 8px;
    padding: 9px 14px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    transition:
        background .2s ease,
        color .2s ease,
        transform .2s ease;
    box-sizing: border-box;
}

.btn.primary {
    background: #2d6a4f;
    color: #ffffff;
}

.btn.primary:hover {
    background: #24563f;
    transform: translateY(-1px);
}

.btn.secondary {
    background: #f1faf3;
    color: #475569;
    border: 1px solid #d5e1da;
}

.btn.secondary:hover {
    background: #d8f3dc;
    color: #24563f;
}

.add-user-btn {
    padding: 10px 14px;
    font-size: 12px;
}


/* =========================================================
   TOTAL USER
   ========================================================= */

.user-info {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: #d8f3dc;
    border: 1px solid #b7e4c7;
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 15px;
    color: #24563f;
    font-size: 12px;
    font-weight: 600;
}

.user-info::before {
    content: '';
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2d6a4f;
}

.user-info strong {
    font-size: 13px;
}


/* =========================================================
   TABLE
   ========================================================= */

.table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #dce7df;
    border-radius: 10px;
}

.table-wrap table {
    width: 100%;
    min-width: 700px;
    border-collapse: collapse;
}

.table-wrap th {
    background: #f1faf3;
    color: #24563f;
    font-size: 12px;
    font-weight: 800;
    text-align: left;
    padding: 13px 15px;
    border-bottom: 1px solid #dce9df;
    white-space: nowrap;
}

.table-wrap td {
    padding: 13px 15px;
    color: #475569;
    font-size: 13px;
    border-bottom: 1px solid #e8f0eb;
    background: #ffffff;
    vertical-align: middle;
}

.table-wrap tbody tr:last-child td {
    border-bottom: none;
}

.table-wrap tbody tr:hover td {
    background: #f8fcf9;
}

.table-wrap td strong {
    color: #1f2937;
}


/* =========================================================
   ROLE BADGE
   ========================================================= */

.role-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
}

.role-badge.admin {
    background: #d8f3dc;
    color: #24563f;
}

.role-badge.kasir {
    background: #ecfdf3;
    color: #16803c;
}


/* =========================================================
   ACTION
   ========================================================= */

.actions {
    display: flex;
    align-items: center;
    gap: 7px;
}

.actions form {
    margin: 0;
}

.btn.small {
    padding: 6px 10px;
    font-size: 11px;
}

.edit-btn {
    background: #d8f3dc;
    color: #24563f;
}

.edit-btn:hover {
    background: #b7e4c7;
}

.delete-btn {
    background: #fee2e2;
    color: #dc2626;
}

.delete-btn:hover {
    background: #fecaca;
}


/* =========================================================
   FORM
   ========================================================= */

.user-form {
    display: flex;
    flex-direction: column;
    gap: 17px;
    margin-top: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-group label {
    color: #334155;
    font-size: 13px;
    font-weight: 700;
}

.form-group input,
.form-group select {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 12px;
    border: 1px solid #d5e1da;
    border-radius: 8px;
    background: #ffffff;
    color: #1f2937;
    font-size: 13px;
    outline: none;
    transition:
        border-color .2s ease,
        box-shadow .2s ease;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #2d6a4f;
    box-shadow: 0 0 0 3px rgba(45, 106, 79, .10);
}

.form-group small {
    color: #64748b;
    font-size: 11px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding-top: 5px;
}


/* =========================================================
   EMPTY
   ========================================================= */

.empty {
    text-align: center;
    color: #94a3b8;
    padding: 35px !important;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 700px) {

    .panel {
        padding: 16px;
    }

    .user-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .add-user-btn {
        width: 100%;
    }

    .panel-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .panel-head .btn {
        width: 100%;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .form-actions .btn {
        width: 100%;
    }
}

</style>


<div class="users-page">

    <!-- ALERT -->

    <?php if ($message): ?>

        <div class="alert success">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert danger">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <?php if ($showForm): ?>

        <!-- =================================================
             FORM TAMBAH / EDIT
             ================================================= -->

        <section class="panel form-panel">

            <div class="panel-head">

                <div>

                    <h2>
                        <?= $edit ? 'Edit User' : 'Tambah User' ?>
                    </h2>

                    <p>
                        <?= $edit
                            ? 'Perbarui informasi akun user.'
                            : 'Tambahkan akun baru ke sistem koperasi.'
                        ?>
                    </p>

                </div>


                <a
                    href="users.php"
                    class="btn secondary"
                >
                    Kembali
                </a>

            </div>


            <form
                method="post"
                class="user-form"
            >

                <input
                    type="hidden"
                    name="action"
                    value="save"
                >

                <input
                    type="hidden"
                    name="id"
                    value="<?= e($edit['id'] ?? '') ?>"
                >


                <div class="form-group">

                    <label>
                        Nama Lengkap
                    </label>

                    <input
                        type="text"
                        name="nama"
                        required
                        value="<?= e($edit['nama'] ?? '') ?>"
                        placeholder="Masukkan nama lengkap"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        required
                        value="<?= e($edit['username'] ?? '') ?>"
                        placeholder="Masukkan username"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        <?= $edit ? '' : 'required' ?>
                        placeholder="<?= $edit
                            ? 'Kosongkan jika tidak diubah'
                            : 'Masukkan password'
                        ?>"
                    >

                    <?php if ($edit): ?>

                        <small>
                            Kosongkan jika password tidak ingin diubah.
                        </small>

                    <?php endif; ?>

                </div>


                <div class="form-group">

                    <label>
                        Role
                    </label>

                    <select name="role">

                        <option
                            value="admin"
                            <?= ($edit['role'] ?? '') === 'admin'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Admin
                        </option>

                        <option
                            value="kasir"
                            <?= ($edit['role'] ?? 'kasir') === 'kasir'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Kasir
                        </option>

                    </select>

                </div>


                <div class="form-actions">

                    <a
                        href="users.php"
                        class="btn secondary"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="btn primary"
                    >
                        <?= $edit
                            ? 'Simpan Perubahan'
                            : 'Tambah User'
                        ?>
                    </button>

                </div>

            </form>

        </section>


    <?php else: ?>


        <!-- =================================================
             DAFTAR USER
             ================================================= -->

        <section class="panel">

            <div class="user-header">

                <div>

                    <h2>
                        Daftar User
                    </h2>

                    <p>
                        Kelola akun yang memiliki akses ke sistem.
                    </p>

                </div>


                <a
                    href="users.php?add=1"
                    class="btn primary add-user-btn"
                >
                    + Tambah User
                </a>

            </div>


            <div class="user-info">

                <span>
                    Total User
                </span>

                <strong>
                    <?= count($users) ?>
                </strong>

            </div>


            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$users): ?>

                        <tr>

                            <td
                                colspan="5"
                                class="empty"
                            >
                                Belum ada data user.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($users as $u): ?>

                            <tr>

                                <td>
                                    #<?= e($u['id']) ?>
                                </td>


                                <td>

                                    <strong>
                                        <?= e($u['nama']) ?>
                                    </strong>

                                </td>


                                <td>
                                    <?= e($u['username']) ?>
                                </td>


                                <td>

                                    <span
                                        class="role-badge <?= e($u['role']) ?>"
                                    >
                                        <?= ucfirst(e($u['role'])) ?>
                                    </span>

                                </td>


                                <td>

                                    <div class="actions">

                                        <a
                                            href="?edit=<?= e($u['id']) ?>"
                                            class="btn small edit-btn"
                                        >
                                            Edit
                                        </a>


                                        <?php if (
                                            (int) $u['id'] !==
                                            (int) ($_SESSION['user']['id'] ?? 0)
                                        ): ?>

                                            <form
                                                method="post"
                                                onsubmit="return confirm('Yakin ingin menghapus user ini?')"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= e($u['id']) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn small delete-btn"
                                                >
                                                    Hapus
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


    <?php endif; ?>

</div>


<?php
include __DIR__ . '/../includes/footer.php';
?>