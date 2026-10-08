<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

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
function rupiah_short($value): string
{
    $value = (float) $value;

    if ($value >= 1000000000) {
        return 'Rp ' . number_format($value / 1000000000, 1, ',', '.') . ' M';
    }

    if ($value >= 1000000) {
        return 'Rp ' . number_format($value / 1000000, 1, ',', '.') . ' Jt';
    }

    if ($value >= 1000) {
        return 'Rp ' . number_format($value / 1000, 0, ',', '.') . ' Rb';
    }

    return 'Rp ' . number_format($value, 0, ',', '.');
}

function e_html($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| Data dashboard
|--------------------------------------------------------------------------
*/

/* Total transaksi hari ini */
$stmt = $pdo->query("
    SELECT
        COUNT(*) AS jumlah_transaksi,
        COALESCE(SUM(total), 0) AS total_penjualan
    FROM transaksi
    WHERE DATE(tanggal) = CURDATE()
");
$today = $stmt->fetch();

$totalTransaksiHariIni = (int) ($today['jumlah_transaksi'] ?? 0);
$totalPenjualanHariIni = (float) ($today['total_penjualan'] ?? 0);

/* Total barang */
$totalBarang = (int) $pdo->query("
    SELECT COUNT(*) FROM barang
")->fetchColumn();

/* Total user */
$totalUser = (int) $pdo->query("
    SELECT COUNT(*) FROM users
")->fetchColumn();

/*
 * Stok menipis:
 * Karena database tidak punya kolom batas stok minimum,
 * dashboard memakai batas tampilan <= 10.
 */
$stokMinimum = 10;

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM barang
    WHERE stok <= :stok_minimum
");
$stmt->execute([':stok_minimum' => $stokMinimum]);
$totalStokMenipis = (int) $stmt->fetchColumn();

/* Daftar stok menipis */
$stmt = $pdo->prepare("
    SELECT id_barang, nama_barang, kategori, stok, harga
    FROM barang
    WHERE stok <= :stok_minimum
    ORDER BY stok ASC, nama_barang ASC
    LIMIT 6
");
$stmt->execute([':stok_minimum' => $stokMinimum]);
$stokMenipis = $stmt->fetchAll();

/* Transaksi terbaru */
$stmt = $pdo->query("
    SELECT
        t.id_transaksi,
        t.tanggal,
        t.nama_pembeli,
        t.total,
        u.nama AS kasir
    FROM transaksi t
    LEFT JOIN users u ON u.id = t.id_user
    ORDER BY t.tanggal DESC, t.id_transaksi DESC
    LIMIT 6
");
$transaksiTerbaru = $stmt->fetchAll();

/* Penjualan 7 hari terakhir */
$stmt = $pdo->query("
    SELECT
        DATE(tanggal) AS tanggal,
        COUNT(*) AS jumlah_transaksi,
        COALESCE(SUM(total), 0) AS total
    FROM transaksi
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(tanggal)
    ORDER BY tanggal ASC
");
$penjualanRaw = $stmt->fetchAll();

$penjualanMap = [];

foreach ($penjualanRaw as $row) {
    $penjualanMap[$row['tanggal']] = [
        'jumlah_transaksi' => (int) $row['jumlah_transaksi'],
        'total' => (float) $row['total']
    ];
}

$chartData = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));

    $chartData[] = [
        'tanggal' => $date,
        'label' => date('d M', strtotime($date)),
        'jumlah_transaksi' => $penjualanMap[$date]['jumlah_transaksi'] ?? 0,
        'total' => $penjualanMap[$date]['total'] ?? 0
    ];
}

$chartMax = 0;

foreach ($chartData as $item) {
    $chartMax = max($chartMax, $item['total']);
}

if ($chartMax <= 0) {
    $chartMax = 100000;
}

/* Barang terlaris berdasarkan jumlah item terjual */
$stmt = $pdo->query("
    SELECT
        b.id_barang,
        b.nama_barang,
        b.stok,
        COALESCE(SUM(dt.jumlah), 0) AS terjual
    FROM detail_transaksi dt
    INNER JOIN barang b ON b.id_barang = dt.id_barang
    GROUP BY b.id_barang, b.nama_barang, b.stok
    HAVING terjual > 0
    ORDER BY terjual DESC, b.nama_barang ASC
    LIMIT 6
");
$barangTerlaris = $stmt->fetchAll();

$userNama = $_SESSION['user']['nama'] ?? 'Admin';
$userRole = $_SESSION['user']['role'] ?? 'admin';

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

/*
|--------------------------------------------------------------------------
| SVG chart
|--------------------------------------------------------------------------
*/
$chartWidth = 760;
$chartHeight = 255;
$chartLeft = 58;
$chartRight = 18;
$chartTop = 20;
$chartBottom = 45;

$plotWidth = $chartWidth - $chartLeft - $chartRight;
$plotHeight = $chartHeight - $chartTop - $chartBottom;

$points = [];

foreach ($chartData as $index => $item) {
    $x = $chartLeft + (($plotWidth / 6) * $index);
    $ratio = $item['total'] / $chartMax;
    $y = $chartTop + $plotHeight - ($ratio * $plotHeight);

    $points[] = [
        'x' => $x,
        'y' => $y,
        'label' => $item['label'],
        'total' => $item['total']
    ];
}

$polylinePoints = implode(
    ' ',
    array_map(
        fn($point) => round($point['x'], 2) . ',' . round($point['y'], 2),
        $points
    )
);

$areaPoints = $polylinePoints . ' ' .
    round($chartWidth - $chartRight, 2) . ',' . ($chartHeight - $chartBottom) . ' ' .
    $chartLeft . ',' . ($chartHeight - $chartBottom);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - E-KOPERASI</title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --blue: #2D6A4F;
            --blue-2: #3B8060;
            --blue-dark: #24563F;
            --blue-light: #D8F3DC;
            --text: #1F2937;
            --muted: #64748B;
            --border: #DDE7E1;
            --bg: #F1FAF3;
            --white: #ffffff;
            --green: #2D6A4F;
            --red: #DC2626;
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

        /* =========================================================
           LAYOUT
        ========================================================= */

        .app {
            min-height: 100vh;
            display: flex;
        }

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
                linear-gradient(180deg, #2D6A4F 0%, #286047 55%, #24563F 100%);
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

        .nav {
            flex: 1;
            overflow-y: auto;
            padding-right: 2px;
        }

        .nav-section {
            margin: 0 10px 9px;
            color: rgba(255,255,255,.68);
            font-size: 9px;
            font-weight: 600;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .nav-section:not(:first-child) {
            margin-top: 25px;
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
            background: linear-gradient(135deg, #3B8060, #2D6A4F);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.05);
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            position: relative;
            flex: 0 0 20px;
        }

        /* CSS icons sidebar */
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

        .icon-grid {
            border: 2px solid white;
            border-radius: 3px;
        }

        .icon-grid::before {
            content: "";
            position: absolute;
            inset: 4px;
            background:
                linear-gradient(90deg, white 2px, transparent 2px 5px, white 5px 7px, transparent 7px),
                linear-gradient(white 2px, transparent 2px 5px, white 5px 7px, transparent 7px);
        }

        .icon-box {
            border: 2px solid white;
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
            border: 2px solid white;
            border-bottom: 0;
        }

        .icon-layers::before,
        .icon-layers::after {
            content: "";
            position: absolute;
            left: 2px;
            width: 16px;
            height: 7px;
            border: 2px solid white;
            transform: skewY(-25deg);
            border-radius: 2px;
        }

        .icon-layers::before {
            top: 3px;
        }

        .icon-layers::after {
            top: 9px;
        }

        .icon-arrow {
            border: 2px solid #74C69D;
            border-radius: 4px;
        }

        .icon-arrow::after {
            content: "";
            position: absolute;
            width: 8px;
            height: 8px;
            border-top: 2px solid #74C69D;
            border-right: 2px solid #74C69D;
            right: 2px;
            top: 2px;
        }

        .icon-report {
            border: 2px solid white;
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
            background: white;
            left: 2px;
            top: 6px;
            box-shadow: 0 4px 0 white;
        }

        .icon-users::before,
        .icon-users::after {
            content: "";
            position: absolute;
            border: 2px solid white;
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
            border-radius: 8px 8px 3px 3px;
        }

        .icon-gear {
            border: 3px solid white;
            border-radius: 50%;
            width: 14px;
            height: 14px;
            left: 3px;
            top: 3px;
        }

        .icon-gear::after {
            content: "";
            position: absolute;
            width: 5px;
            height: 5px;
            left: 2px;
            top: 2px;
            border-radius: 50%;
            background: white;
        }

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
            background: rgba(0, 42, 110, .3);
        }

        /* =========================================================
           CONTENT
        ========================================================= */

        .content {
            width: calc(100% - 263px);
            margin-left: 263px;
            min-height: 100vh;
        }

        .topbar {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px 0 28px;
            background: white;
            border-bottom: 1px solid #e6ebf2;
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
            border: 1px solid #dfe5ee;
            border-radius: 8px;
            color: #1d2a42;
            font-size: 12px;
        }

        .calendar-icon {
            width: 18px;
            height: 18px;
            position: relative;
            border: 2px solid var(--blue);
            border-radius: 3px;
        }

        .calendar-icon::before {
            content: "";
            position: absolute;
            left: 2px;
            right: 2px;
            top: 4px;
            border-top: 2px solid var(--blue);
        }

        .calendar-icon::after {
            content: "";
            position: absolute;
            width: 2px;
            height: 4px;
            left: 3px;
            top: -4px;
            background: var(--blue);
            box-shadow: 8px 0 0 var(--blue);
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
            background: #D8F3DC;
        }

        .profile-avatar::before {
            content: "";
            position: absolute;
            width: 11px;
            height: 11px;
            left: 50%;
            top: 7px;
            transform: translateX(-50%);
            border-radius: 50%;
            background: var(--blue);
        }

        .profile-avatar::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 11px;
            left: 50%;
            bottom: 7px;
            transform: translateX(-50%);
            border-radius: 10px 10px 4px 4px;
            background: var(--blue);
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
            border-right: 2px solid #17233b;
            border-bottom: 2px solid #17233b;
            transform: rotate(45deg) translateY(-3px);
        }

        .main {
            padding: 21px;
        }

        /* =========================================================
           STAT CARDS
        ========================================================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .stat-card {
            min-height: 145px;
            padding: 17px 17px 14px;
            display: grid;
            grid-template-columns: 62px 1fr;
            column-gap: 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(22, 42, 75, .025);
        }

        .stat-icon {
            width: 61px;
            height: 61px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            position: relative;
        }

        .stat-icon.blue {
            background: #e8f1ff;
        }

        .stat-icon.green {
            background: #D8F3DC;
        }

        .stat-icon.purple {
            background: #EEEAF7;
        }

        .stat-icon.red {
            background: #FEE2E2;
        }

        .stat-icon.yellow {
            background: #FEF3C7;
        }

        .stat-icon .small-bag {
            width: 22px;
            height: 19px;
            position: relative;
            border: 2px solid currentColor;
            border-radius: 3px;
        }

        .stat-icon .small-bag::before {
            content: "";
            position: absolute;
            width: 10px;
            height: 7px;
            left: 4px;
            top: -7px;
            border: 2px solid currentColor;
            border-bottom: 0;
            border-radius: 7px 7px 0 0;
        }

        .stat-icon .rupiah {
            width: 28px;
            height: 28px;
            display: grid;
            place-items: center;
            border: 2px solid currentColor;
            border-radius: 50%;
            font-size: 13px;
            font-weight: 800;
        }

        .stat-icon .warning {
            width: 0;
            height: 0;
            border-left: 14px solid transparent;
            border-right: 14px solid transparent;
            border-bottom: 25px solid currentColor;
            position: relative;
        }

        .stat-icon .warning::after {
            content: "";
            position: absolute;
            width: 3px;
            height: 9px;
            background: white;
            left: -1.5px;
            top: 7px;
            border-radius: 3px;
        }

        .stat-icon.blue .small-bag { color: #2D6A4F; }
        .stat-icon.green .rupiah { color: #2D6A4F; }
        .stat-icon.purple .small-bag { color: #6B5CA5; }
        .stat-icon.red .warning { color: #DC2626; }
        .stat-icon.yellow .small-bag { color: #B7791F; }

        .stat-body {
            min-width: 0;
        }

        .stat-title {
            margin: 3px 0 7px;
            color: #13203a;
            font-size: 11px;
            line-height: 1.35;
            font-weight: 600;
        }

        .stat-value {
            margin: 0;
            color: #101a30;
            font-size: 23px;
            line-height: 1.1;
            font-weight: 800;
        }

        .stat-label {
            margin: 6px 0 0;
            font-size: 12px;
            color: #17213a;
        }

        .stat-foot {
            grid-column: 1 / -1;
            align-self: end;
            margin-top: 10px;
            font-size: 11px;
            color: var(--green);
        }

        .stat-foot.blue {
            color: var(--blue);
        }

        .stat-foot.red {
            color: #DC2626;
        }

        /* =========================================================
           PANELS
        ========================================================= */

        .panel-grid {
            display: grid;
            gap: 14px;
            margin-top: 14px;
        }

        .middle-grid {
            grid-template-columns: minmax(0, 1.3fr) minmax(380px, .9fr);
        }

        .bottom-grid {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }

        .panel {
            min-width: 0;
            background: white;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(22, 42, 75, .025);
        }

        .panel-head {
            min-height: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 14px;
        }

        .panel-title {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
        }

        .panel-link {
            color: var(--blue);
            font-size: 11px;
            white-space: nowrap;
        }

        .period {
            height: 31px;
            padding: 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #dce3ed;
            border-radius: 7px;
            color: #17233b;
            font-size: 11px;
        }

        .period-chevron {
            width: 6px;
            height: 6px;
            border-right: 1.5px solid #26334b;
            border-bottom: 1.5px solid #26334b;
            transform: rotate(45deg) translateY(-2px);
        }

        /* =========================================================
           CHART
        ========================================================= */

        .chart-wrap {
            width: 100%;
            overflow: hidden;
        }

        .chart {
            width: 100%;
            min-width: 560px;
            height: 270px;
            display: block;
        }

        .chart-grid-line {
            stroke: #e5eaf1;
            stroke-width: 1;
        }

        .chart-axis-text {
            fill: #68758c;
            font-size: 10px;
        }

        .chart-area {
            fill: rgba(45, 106, 79, .10);
        }

        .chart-line {
            fill: none;
            stroke: #2D6A4F;
            stroke-width: 2.4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .chart-point {
            fill: #2D6A4F;
            stroke: white;
            stroke-width: 2;
        }

        /* =========================================================
           TABLE
        ========================================================= */

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            height: 39px;
            padding: 0 10px;
            background: #f8fafc;
            color: #19233a;
            text-align: left;
            font-size: 10px;
            font-weight: 750;
            white-space: nowrap;
        }

        td {
            height: 40px;
            padding: 0 10px;
            border-bottom: 1px solid #edf0f5;
            color: #162139;
            font-size: 11px;
            white-space: nowrap;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .stock-status {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 0 9px;
            border-radius: 999px;
            background: #fff2df;
            color: #ef7c00;
            font-size: 10px;
            font-weight: 650;
        }

        .empty {
            padding: 28px 10px;
            text-align: center;
            color: #8994a8;
            font-size: 12px;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1250px) {
            .sidebar {
                width: 235px;
            }

            .content {
                width: calc(100% - 235px);
                margin-left: 235px;
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .middle-grid,
            .bottom-grid {
                grid-template-columns: 1fr;
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
            .nav-section,
            .nav-item span:not(.nav-icon),
            .logout span:not(.nav-icon) {
                display: none;
            }

            .nav-item {
                justify-content: center;
                padding: 0;
            }

            .content {
                width: calc(100% - 72px);
                margin-left: 72px;
            }

            .topbar {
                padding: 0 18px;
            }

            .profile-name,
            .profile-role,
            .profile-chevron {
                display: none;
            }

            .main {
                padding: 14px;
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

            .stats {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 14px;
            }
        }
    </style>
</head>

<body>
<div class="app">

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->
    <aside class="sidebar">

        <div class="brand">
            <div class="brand-mark" aria-hidden="true">
                <span class="brand-cart">
                    <span class="brand-cart-line"></span>
                </span>
            </div>

            <div>
                <h1 class="brand-title">E-KOPERASI</h1>
                <p class="brand-caption">Sistem Informasi Koperasi Sekolah</p>
            </div>
        </div>

      <nav class="nav">

    <a class="nav-item active" href="dashboard.php">
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

    <a class="nav-item" href="stok.php">
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

<a class="nav-item logout" href="dashboard.php?logout=1">
    <span class="nav-icon icon-logout"></span>
    <span>Logout</span>
</a>
    </aside>

    <!-- =========================================================
         CONTENT
    ========================================================== -->
    <section class="content">

        <header class="topbar">

            <div class="top-left">
                <button class="menu-button" type="button" aria-label="Menu">
                    <span></span>
                </button>

                <div>
                    <h2 class="page-title">Dashboard</h2>
                    <p class="page-subtitle">
                        Selamat datang, <?= e_html($userNama) ?>!
                    </p>
                </div>
            </div>

            <div class="top-right">

                <div class="date-chip">
                    <span class="calendar-icon" aria-hidden="true"></span>
                    <span><?= e_html($todayLabel) ?></span>
                </div>

                <div class="profile">
                    <div class="profile-avatar" aria-hidden="true"></div>

                    <div>
                        <p class="profile-name"><?= e_html($userNama) ?></p>
                        <p class="profile-role"><?= ucfirst(e_html($userRole)) ?></p>
                    </div>

                    <span class="profile-chevron" aria-hidden="true"></span>
                </div>

            </div>

        </header>

        <main class="main">

            <!-- =================================================
                 STATISTICS
            ================================================== -->
            <section class="stats">

                <article class="stat-card">
                    <div class="stat-icon blue">
                        <span class="small-bag"></span>
                    </div>

                    <div class="stat-body">
                        <p class="stat-title">Total Transaksi Hari Ini</p>
                        <p class="stat-value"><?= number_format($totalTransaksiHariIni, 0, ',', '.') ?></p>
                        <p class="stat-label">Transaksi</p>
                    </div>

                    <div class="stat-foot blue">
                        Data hari ini
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon green">
                        <span class="rupiah">Rp</span>
                    </div>

                    <div class="stat-body">
                        <p class="stat-title">Total Penjualan Hari Ini</p>
                        <p class="stat-value" style="font-size:20px;">
                            <?= e_html(rupiah_short($totalPenjualanHariIni)) ?>
                        </p>
                        <p class="stat-label">Penjualan</p>
                    </div>

                    <div class="stat-foot">
                        Dari transaksi hari ini
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon purple">
                        <span class="small-bag"></span>
                    </div>

                    <div class="stat-body">
                        <p class="stat-title">Total Barang</p>
                        <p class="stat-value"><?= number_format($totalBarang, 0, ',', '.') ?></p>
                        <p class="stat-label">Jenis Barang</p>
                    </div>

                    <div class="stat-foot blue">
                        Data barang
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon red">
                        <span class="warning"></span>
                    </div>

                    <div class="stat-body">
                        <p class="stat-title">Stok Menipis</p>
                        <p class="stat-value"><?= number_format($totalStokMenipis, 0, ',', '.') ?></p>
                        <p class="stat-label">Barang</p>
                    </div>

                    <div class="stat-foot red">
                        Batas stok ≤ <?= $stokMinimum ?>
                    </div>
                </article>

            </section>

            <!-- =================================================
                 CHART + RECENT TRANSACTIONS
            ================================================== -->
            <section class="panel-grid middle-grid">

                <article class="panel">

                    <div class="panel-head">
                        <h3 class="panel-title">Grafik Penjualan</h3>

                        <div class="period">
                            7 Hari Terakhir
                            <span class="period-chevron"></span>
                        </div>
                    </div>

                    <div class="chart-wrap">
                        <svg
                            class="chart"
                            viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>"
                            preserveAspectRatio="none"
                            role="img"
                            aria-label="Grafik penjualan 7 hari terakhir"
                        >
                            <?php
                            $gridValues = [1, .75, .5, .25, 0];
                            foreach ($gridValues as $gridRatio):
                                $gridY = $chartTop + $plotHeight - ($gridRatio * $plotHeight);
                            ?>
                                <line
                                    x1="<?= $chartLeft ?>"
                                    y1="<?= $gridY ?>"
                                    x2="<?= $chartWidth - $chartRight ?>"
                                    y2="<?= $gridY ?>"
                                    class="chart-grid-line"
                                />

                                <text
                                    x="2"
                                    y="<?= $gridY + 4 ?>"
                                    class="chart-axis-text"
                                >
                                    <?= e_html(rupiah_short($chartMax * $gridRatio)) ?>
                                </text>
                            <?php endforeach; ?>

                            <polygon
                                points="<?= e_html($areaPoints) ?>"
                                class="chart-area"
                            />

                            <polyline
                                points="<?= e_html($polylinePoints) ?>"
                                class="chart-line"
                            />

                            <?php foreach ($points as $point): ?>
                                <circle
                                    cx="<?= $point['x'] ?>"
                                    cy="<?= $point['y'] ?>"
                                    r="5"
                                    class="chart-point"
                                />

                                <text
                                    x="<?= $point['x'] ?>"
                                    y="<?= $chartHeight - 13 ?>"
                                    text-anchor="middle"
                                    class="chart-axis-text"
                                >
                                    <?= e_html($point['label']) ?>
                                </text>
                            <?php endforeach; ?>
                        </svg>
                    </div>

                </article>

                <article class="panel">

                    <div class="panel-head">
                        <h3 class="panel-title">Transaksi Terakhir</h3>
                        <a class="panel-link" href="transaksi.php">Lihat semua</a>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Transaksi</th>
                                    <th>Waktu</th>
                                    <th>Total</th>
                                    <th>Kasir</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (!$transaksiTerbaru): ?>
                                <tr>
                                    <td colspan="5" class="empty">Belum ada transaksi.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksiTerbaru as $index => $trx): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td>TRX-<?= str_pad((string) $trx['id_transaksi'], 5, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= e_html(date('d/m/Y H:i', strtotime($trx['tanggal']))) ?></td>
                                        <td><?= e_html(rupiah_short($trx['total'])) ?></td>
                                        <td><?= e_html($trx['kasir'] ?: '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </article>

            </section>

            <!-- =================================================
                 STOCK + BEST SELLING
            ================================================== -->
            <section class="panel-grid bottom-grid">

                <article class="panel">

                    <div class="panel-head">
                        <h3 class="panel-title">Stok Menipis</h3>
                        <a class="panel-link" href="barang.php">Lihat semua</a>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Stok</th>
                                    <th>Kategori</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (!$stokMenipis): ?>
                                <tr>
                                    <td colspan="4" class="empty">
                                        Tidak ada barang dengan stok menipis.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stokMenipis as $barang): ?>
                                    <tr>
                                        <td><?= e_html($barang['nama_barang']) ?></td>
                                        <td><?= number_format((int) $barang['stok'], 0, ',', '.') ?></td>
                                        <td><?= e_html($barang['kategori']) ?></td>
                                        <td>
                                            <span class="stock-status">Menipis</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </article>

                <article class="panel">

                    <div class="panel-head">
                        <h3 class="panel-title">Barang Terlaris</h3>
                        <a class="panel-link" href="barang.php">Lihat semua</a>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Nama Barang</th>
                                    <th>Terjual</th>
                                    <th>Stok</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php if (!$barangTerlaris): ?>
                                <tr>
                                    <td colspan="4" class="empty">
                                        Belum ada data penjualan barang.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($barangTerlaris as $index => $barang): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= e_html($barang['nama_barang']) ?></td>
                                        <td><?= number_format((int) $barang['terjual'], 0, ',', '.') ?></td>
                                        <td><?= number_format((int) $barang['stok'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </article>

            </section>

        </main>

    </section>
</div>

<script>
    /*
     * Sidebar pada desktop tetap terbuka.
     * Tombol menu disiapkan untuk interaksi mobile.
     */
    const menuButton = document.querySelector('.menu-button');
    const sidebar = document.querySelector('.sidebar');

    if (menuButton && sidebar) {
        menuButton.addEventListener('click', function () {
            sidebar.classList.toggle('mobile-open');
        });
    }
</script>

<style>
    @media (max-width: 850px) {
        .sidebar.mobile-open {
            width: 235px;
        }

        .sidebar.mobile-open .brand > div:last-child,
        .sidebar.mobile-open .nav-section,
        .sidebar.mobile-open .nav-item span:not(.nav-icon),
        .sidebar.mobile-open .logout span:not(.nav-icon) {
            display: block;
        }

        .sidebar.mobile-open .nav-item {
            justify-content: flex-start;
            padding: 0 14px;
        }
    }
</style>

</body>
</html>
