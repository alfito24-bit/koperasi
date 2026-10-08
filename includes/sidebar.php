<?php

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<aside class="sidebar">

    <!-- ==========================================
         BRAND / E-KOPERASI
         ========================================== -->
    <div class="sidebar-brand">

        <div class="brand-logo">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor"
                 stroke-width="1.8"
                 stroke-linecap="round"
                 stroke-linejoin="round">

                <!-- Hexagon -->
                <path d="M12 2.8L19.8 7.4V16.6L12 21.2L4.2 16.6V7.4L12 2.8Z"></path>

                <!-- Cart -->
                <path d="M7.2 9.2H9L10.2 14.8H17.2L18.5 10.8H9.2"></path>

                <!-- Cart wheels -->
                <circle cx="11.2" cy="17.2" r="0.9"></circle>
                <circle cx="16" cy="17.2" r="0.9"></circle>

            </svg>
        </div>

        <div class="brand-text">
            <strong>E-KOPERASI</strong>
            <span>Management System</span>
        </div>

    </div>


    <!-- ==========================================
         MENU
         ========================================== -->
    <nav class="sidebar-menu">

        <!-- DASHBOARD -->
        <a href="dashboard.php"
           class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">

            <span class="menu-icon icon-home">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>

                </svg>

            </span>

            <span>Dashboard</span>

        </a>


        <!-- TRANSAKSI -->
        <a href="transaksi.php"
           class="<?= $currentPage === 'transaksi.php' ? 'active' : '' ?>">

            <span class="menu-icon icon-cart">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M4 5H6L8 16H18L20 8H7"></path>

                    <circle cx="9" cy="19" r="1.5"></circle>
                    <circle cx="17" cy="19" r="1.5"></circle>

                    <path d="M10 11H17"></path>
                    <path d="M11 8H18"></path>

                </svg>

            </span>

            <span>Transaksi</span>

        </a>


        <!-- DATA BARANG -->
        <a href="barang.php"
           class="<?= $currentPage === 'barang.php' ? 'active' : '' ?>">

            <span class="menu-icon icon-grid">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>

                </svg>

            </span>

            <span>Data Barang</span>

        </a>


        <!-- STOK BARANG -->
        <a href="stok.php"
           class="<?= $currentPage === 'stok.php' ? 'active' : '' ?>">

            <span class="menu-icon icon-box">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M3 7L12 3L21 7L12 11L3 7Z"></path>
                    <path d="M3 7V17L12 21L21 17V7"></path>
                    <path d="M12 11V21"></path>

                </svg>

            </span>

            <span>Stok Barang</span>

        </a>


        <!-- PEMASUKAN -->
        <a href="pemasukan.php"
           class="<?= $currentPage === 'pemasukan.php' ? 'active' : '' ?>">

            <span class="menu-icon icon-arrow icon-income">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M12 19V5"></path>
                    <path d="M6 11L12 5L18 11"></path>

                </svg>

            </span>

            <span>Pemasukan</span>

        </a>


        <!-- PENGELUARAN -->
        <a href="pengeluaran.php"
           class="<?= $currentPage === 'pengeluaran.php' ? 'active' : '' ?>">

            <span class="menu-icon icon-arrow icon-expense">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M12 5V19"></path>
                    <path d="M6 13L12 19L18 13"></path>

                </svg>

            </span>

            <span>Pengeluaran</span>

        </a>


        <!-- LAPORAN -->
        <a href="laporan.php"
           class="<?= $currentPage === 'laporan.php' ? 'active' : '' ?>">

            <span class="menu-icon icon-report">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M5 3H19V21H5V3Z"></path>

                    <path d="M8 7H16"></path>
                    <path d="M8 11H16"></path>
                    <path d="M8 15H13"></path>

                </svg>

            </span>

            <span>Laporan</span>

        </a>


        <!-- MANAJEMEN USER -->
        <a href="users.php"
           class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">

            <span class="menu-icon icon-users">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <!-- User utama -->
                    <circle cx="12" cy="8" r="3.5"></circle>
                    <path d="M5 20C5.5 16.5 8 14.5 12 14.5C16 14.5 18.5 16.5 19 20"></path>

                    <!-- User kecil -->
                    <path d="M5.5 10.5C4.2 10.1 3.5 9 3.5 7.7C3.5 6 4.8 4.7 6.5 4.7"></path>
                    <path d="M18.5 10.5C19.8 10.1 20.5 9 20.5 7.7C20.5 6 19.2 4.7 17.5 4.7"></path>

                </svg>

            </span>

            <span>Manajemen User</span>

        </a>

    </nav>


    <!-- ==========================================
         LOGOUT
         ========================================== -->
    <div class="sidebar-bottom">

        <a href="/koperasi/logout.php" class="logout-menu">

            <span class="menu-icon icon-logout">

                <svg viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round">

                    <path d="M10 17L15 12L10 7"></path>
                    <path d="M15 12H3"></path>
                    <path d="M21 4V20"></path>

                </svg>

            </span>

            <span>Logout</span>

        </a>

    </div>

</aside>


<style>

/* ==========================================
   SIDEBAR
   ========================================== */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;

    width: 263px;
    min-width: 263px;
    height: 100vh;

    display: flex;
    flex-direction: column;

    padding: 28px 15px 20px;

    box-sizing: border-box;

    background: linear-gradient(
        180deg,
        #2D6A4F 0%,
        #24563F 100%
    );

    color: #ffffff;

    z-index: 9999;

    overflow-y: auto;
    overflow-x: hidden;

    box-shadow: 4px 0 18px rgba(36, 86, 63, 0.12);
}


/* ==========================================
   BRAND
   ========================================== */

.sidebar-brand {
    display: flex;
    align-items: center;

    gap: 12px;

    padding: 0 8px 28px;
}

.brand-logo {
    width: 42px;
    height: 42px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    background: rgba(255,255,255,.14);

    border: 1px solid rgba(255,255,255,.18);

    border-radius: 12px;
}

.brand-logo svg {
    width: 24px;
    height: 24px;

    color: #ffffff;
}

.brand-text {
    display: flex;
    flex-direction: column;

    gap: 3px;
}

.brand-text strong {
    color: #ffffff;

    font-size: 16px;
    font-weight: 700;

    letter-spacing: .5px;
}

.brand-text span {
    color: rgba(255,255,255,.68);

    font-size: 10px;
}


/* ==========================================
   MENU
   ========================================== */

.sidebar-menu {
    display: flex;
    flex-direction: column;

    gap: 5px;
}

.sidebar-menu a {
    display: flex;
    align-items: center;

    gap: 15px;

    width: 100%;
    min-height: 49px;

    padding: 0 14px;

    box-sizing: border-box;

    color: rgba(255,255,255,.82);

    text-decoration: none;

    border-radius: 8px;

    font-size: 14px;
    font-weight: 500;

    transition:
        background .2s ease,
        color .2s ease,
        transform .2s ease;
}


/* Hover */

.sidebar-menu a:hover {
    background: rgba(255,255,255,.10);

    color: #ffffff;
}


/* Active */

.sidebar-menu a.active {
    background: linear-gradient(
        135deg,
        #3B8060,
        #2D6A4F
    );

    color: #ffffff;

    font-weight: 600;

    box-shadow:
        inset 3px 0 0 #D8F3DC,
        0 3px 10px rgba(0,0,0,.08);
}


/* ==========================================
   ICON
   ========================================== */

.menu-icon {
    width: 22px;
    height: 22px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    color: rgba(255,255,255,.78);
}

.menu-icon svg {
    width: 19px;
    height: 19px;

    display: block;
}

.sidebar-menu a.active .menu-icon {
    color: #D8F3DC;
}


/* ==========================================
   PEMASUKAN
   ========================================== */

.icon-income {
    color: #95D5B2;
}


/* ==========================================
   PENGELUARAN
   ========================================== */

.icon-expense {
    color: #FFD6A5;
}


/* ==========================================
   LOGOUT
   ========================================== */

.sidebar-bottom {
    margin-top: auto;

    padding-top: 18px;
}

.logout-menu {
    display: flex;
    align-items: center;

    gap: 15px;

    width: 100%;
    min-height: 49px;

    padding: 0 14px;

    box-sizing: border-box;

    background: rgba(20,67,47,.82);

    color: #ffffff;

    border-radius: 8px;

    text-decoration: none;

    font-size: 14px;
    font-weight: 500;

    transition:
        background .2s ease,
        transform .2s ease;
}

.logout-menu:hover {
    background: rgba(15,52,37,.96);

    color: #ffffff;

    transform: translateY(-1px);
}

.logout-menu .menu-icon {
    color: #ffffff;
}


/* ==========================================
   SCROLLBAR
   ========================================== */

.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,.18);

    border-radius: 10px;
}


/* ==========================================
   RESPONSIVE
   ========================================== */

@media (max-width: 1250px) {

    .sidebar {
        width: 235px;
        min-width: 235px;
    }

}


@media (max-width: 850px) {

    .sidebar {
        width: 72px;
        min-width: 72px;

        padding: 28px 10px 20px;
    }

    .sidebar-brand {
        justify-content: center;

        padding-left: 0;
        padding-right: 0;
    }

    .brand-text {
        display: none;
    }

    .sidebar-menu a {
        justify-content: center;

        padding: 0;

        gap: 0;
    }

    .sidebar-menu a > span:last-child {
        display: none;
    }

    .logout-menu {
        justify-content: center;

        padding: 0;
    }

    .logout-menu > span:last-child {
        display: none;
    }

}


@media (max-width: 600px) {

    .sidebar {
        width: 100%;
        min-width: 0;

        height: auto;

        position: relative;

        padding: 18px 15px;
    }

    .brand-text {
        display: flex;
    }

    .sidebar-menu a {
        justify-content: flex-start;

        padding: 0 14px;

        gap: 15px;
    }

    .sidebar-menu a > span:last-child {
        display: inline;
    }

    .logout-menu {
        justify-content: flex-start;

        padding: 0 14px;
    }

    .logout-menu > span:last-child {
        display: inline;
    }

    .sidebar-bottom {
        margin-top: 12px;
    }

}

</style>