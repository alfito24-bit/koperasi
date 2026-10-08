<?php
require_once __DIR__ . '/config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* Kalau sudah login, langsung masuk sesuai role */
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? '';

    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    }

    if ($role === 'kasir') {
        header('Location: kasir/dashboard.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($username === '' || $password === '' || !in_array($role, ['admin', 'kasir'], true)) {
        $error = 'Username, password, dan role wajib diisi.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, nama, username, password, role
            FROM users
            WHERE username = :username
              AND role = :role
            LIMIT 1
        ");

        $stmt->execute([
            ':username' => $username,
            ':role'     => $role
        ]);

        $user = $stmt->fetch();

        /*
         * Database project saat ini menggunakan password plaintext.
         * Jadi perbandingan dibuat langsung agar cocok dengan data yang sudah ada.
         */
        if ($user && $user['password'] === $password) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'       => $user['id'],
                'nama'     => $user['nama'],
                'username' => $user['username'],
                'role'     => $user['role']
            ];

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            }

            header('Location: kasir/dashboard.php');
            exit;
        }

        $error = 'Username, password, atau role tidak sesuai.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-KOPERASI</title>

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --blue: #2D6A4F;
            --blue-dark: #24563F;
            --blue-soft: #D8F3DC;
            --navy: #1F4938;
            --text: #111827;
            --muted: #77839b;
            --border: #d7deea;
            --white: #ffffff;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
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
            background: #F1FAF3;
            color: var(--text);
        }

        button,
        input,
        select {
            font: inherit;
        }

        /* =========================
           MAIN
        ========================= */

        .login-page {
            min-height: 100vh;
            padding: 0;
            display: flex;
            align-items: stretch;
            background: #F1FAF3;
            overflow: hidden;
        }

        .login-shell {
            width: 100%;
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 52.2%) minmax(540px, 47.8%);
            position: relative;
            overflow: hidden;
            border: 1px solid #DDE7E1;
        }

        /* =========================
           LEFT SIDE
        ========================= */

        .brand-side {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(
                    circle at 48% 12%,
                    rgba(255,255,255,.68) 0,
                    rgba(255,255,255,.36) 18%,
                    rgba(255,255,255,0) 43%
                ),
                linear-gradient(
                    135deg,
                    #F4FAF6 0%,
                    #EEF8F1 52%,
                    #E3F3E7 100%
                );
        }

        .brand-content {
            position: relative;
            z-index: 5;
            height: 100%;
            padding: 48px 7.2% 0 6.2%;
        }

        /* =========================
           LOGO
        ========================= */

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 23px;
        }

        .logo-mark {
            width: 76px;
            height: 82px;
            position: relative;
            flex: 0 0 auto;
        }

        .logo-mark .hex {
            position: absolute;
            inset: 0;
            background: linear-gradient(145deg, #3B8061, #2D6A4F);
            clip-path: polygon(
                50% 0%,
                88% 23%,
                88% 73%,
                50% 100%,
                12% 73%,
                12% 23%
            );
            filter: drop-shadow(0 8px 14px rgba(45,106,79,.12));
        }

        /* Logo cart dibuat dengan CSS */
        .cart-icon {
            position: absolute;
            width: 31px;
            height: 21px;
            left: 22px;
            top: 30px;
            border: 3px solid white;
            border-top: 0;
            border-radius: 2px 2px 7px 7px;
        }

        .cart-icon::before {
            content: "";
            position: absolute;
            width: 17px;
            height: 3px;
            background: white;
            left: -7px;
            top: -8px;
            transform: rotate(12deg);
            border-radius: 4px;
        }

        .cart-icon::after {
            content: "";
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: white;
            left: 4px;
            bottom: -10px;
            box-shadow: 18px 0 0 white;
        }

        .cart-line {
            position: absolute;
            width: 15px;
            height: 3px;
            background: white;
            left: 5px;
            top: 8px;
            box-shadow: 0 6px 0 rgba(255,255,255,.95);
        }

        .brand-name {
            margin: 0;
            color: #24563F;
            font-size: clamp(35px, 3.05vw, 53px);
            line-height: 1;
            font-weight: 800;
            letter-spacing: -.8px;
        }

        .brand-subtitle {
            margin: 10px 0 0;
            color: #647B70;
            font-size: clamp(17px, 1.38vw, 23px);
            line-height: 1.25;
            font-weight: 400;
        }

        /* =========================
           LEFT TITLE
        ========================= */

        .intro {
            margin-top: 72px;
            max-width: 650px;
        }

        .intro h1 {
            margin: 0;
            color: #24563F;
            font-size: clamp(39px, 3.55vw, 60px);
            line-height: 1.17;
            font-weight: 750;
            letter-spacing: -1.8px;
        }

        .intro-line {
            width: 78px;
            height: 4px;
            margin-top: 25px;
            background: #3B8061;
            border-radius: 999px;
        }

        .intro p {
            margin: 22px 0 0;
            max-width: 590px;
            color: #52665C;
            font-size: clamp(16px, 1.3vw, 21px);
            line-height: 1.75;
            font-weight: 400;
        }

        /* =========================
           SKY DETAILS
        ========================= */

        .sky-cloud {
            position: absolute;
            z-index: 2;
            width: 105px;
            height: 35px;
            opacity: .62;
        }

        .sky-cloud::before,
        .sky-cloud::after {
            content: "";
            position: absolute;
            bottom: 0;
            background: rgba(255,255,255,.95);
            border-radius: 999px 999px 0 0;
        }

        .sky-cloud::before {
            width: 56px;
            height: 28px;
            left: 17px;
        }

        .sky-cloud::after {
            width: 48px;
            height: 22px;
            right: 6px;
        }

        .cloud-a {
            left: 72%;
            top: 31%;
            transform: scale(.85);
        }

        .cloud-b {
            left: 76%;
            top: 44%;
            transform: scale(.75);
        }

        /* =========================
           BUILDING ILLUSTRATION
           ========================= */

        .illustration {
            position: absolute;
            z-index: 3;
            left: 0;
            right: 0;
            bottom: 208px;
            height: 355px;
            pointer-events: none;
        }

        .city {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 55px;
            height: 220px;
            opacity: .6;
        }

        .building {
            position: absolute;
            bottom: 31px;
            left: 34%;
            width: 49%;
            height: 225px;
            background: linear-gradient(180deg, #79B497, #4F8F70);
            border: 2px solid rgba(45,106,79,.25);
            box-shadow: 0 8px 0 rgba(45,106,79,.08);
        }

        .building::before {
            content: "";
            position: absolute;
            left: -30px;
            right: -30px;
            top: -39px;
            height: 55px;
            background: linear-gradient(180deg, #3B8061, #2D6A4F);
            clip-path: polygon(0 45%, 50% 0, 100% 45%, 100% 100%, 0 100%);
            border-bottom: 5px solid #24563F;
        }

        .building::after {
            content: "";
            position: absolute;
            left: 50%;
            top: -7px;
            width: 38%;
            height: 42px;
            transform: translateX(-50%);
            background: #eaf3ff;
            border: 4px solid #2D6A4F;
            box-shadow: inset 0 -12px #d3e7ff;
        }

        .building-main {
            position: absolute;
            left: 25%;
            right: 25%;
            bottom: 0;
            height: 137px;
            background: #EAF6EE;
            border: 2px solid rgba(45,106,79,.20);
        }

        .building-door {
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 31%;
            height: 74px;
            transform: translateX(-50%);
            background: linear-gradient(90deg, #0757bc 50%, #0c67d5 50%);
            border: 5px solid #D8EDE0;
        }

        .building-door::after {
            content: "";
            position: absolute;
            top: 30px;
            left: 50%;
            width: 3px;
            height: 3px;
            background: #D8EDE0;
            box-shadow: 11px 0 0 #D8EDE0;
        }

        .building-window {
            position: absolute;
            top: 57px;
            width: 21%;
            height: 51px;
            background:
                linear-gradient(90deg, transparent 47%, #C8E3D0 47%, #C8E3D0 53%, transparent 53%),
                linear-gradient(0deg, transparent 46%, #C8E3D0 46%, #C8E3D0 54%, transparent 54%),
                #3B8061;
            border: 4px solid #D8EDE0;
        }

        .window-left {
            left: 7%;
        }

        .window-right {
            right: 7%;
        }

        .koperasi-sign {
            position: absolute;
            z-index: 2;
            left: 50%;
            top: 29px;
            transform: translateX(-50%);
            width: 138px;
            height: 39px;
            display: grid;
            place-items: center;
            background: #24563F;
            color: white;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: .5px;
            border: 3px solid rgba(255,255,255,.45);
        }

        .tree {
            position: absolute;
            bottom: 35px;
            width: 65px;
            height: 135px;
        }

        .tree::before {
            content: "";
            position: absolute;
            left: 29px;
            bottom: 0;
            width: 7px;
            height: 55px;
            background: #4E7862;
            border-radius: 10px;
        }

        .tree::after {
            content: "";
            position: absolute;
            left: 5px;
            top: 10px;
            width: 56px;
            height: 76px;
            border-radius: 50% 50% 45% 45%;
            background: linear-gradient(145deg, #72A88D, #3B8061);
            box-shadow:
                16px -18px 0 -5px #5B9A7A,
                -13px 8px 0 -8px #80B69A;
        }

        .tree-one {
            left: 24%;
        }

        .tree-two {
            left: 79%;
        }

        /* =========================
           BLUE BOTTOM WAVE
        ========================= */

        .green-wave {
            position: absolute;
            z-index: 4;
            left: -4%;
            right: -4%;
            bottom: -2px;
            height: 255px;
            background: #24563F;
            border-radius: 50% 58% 0 0 / 37% 42% 0 0;
            transform: rotate(1.5deg);
        }

        .green-wave::before {
            content: "";
            position: absolute;
            left: -7%;
            top: -54px;
            width: 66%;
            height: 90px;
            background: #E4F2E8;
            border-radius: 50%;
            transform: rotate(-5deg);
        }

        .green-wave::after {
            content: "";
            position: absolute;
            right: -6%;
            top: -14px;
            width: 42%;
            height: 76px;
            background: #7BAE92;
            border-radius: 50% 0 0 0;
            opacity: .7;
        }

        /* =========================
           FEATURES
        ========================= */

        .features {
            position: absolute;
            z-index: 8;
            left: 6.2%;
            right: 6.2%;
            bottom: 42px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 38px;
            color: white;
        }

        .feature {
            min-width: 0;
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            margin-bottom: 13px;
            display: grid;
            place-items: center;
            position: relative;
            background: white;
            border-radius: 17px;
        }

        .feature h3 {
            margin: 0;
            font-size: 17px;
            line-height: 1.3;
            font-weight: 750;
        }

        .feature p {
            margin: 7px 0 0;
            font-size: 14px;
            line-height: 1.6;
            color: rgba(255,255,255,.96);
        }

        /* CSS feature icons */
        .chart-icon {
            width: 24px;
            height: 22px;
            position: relative;
            border-bottom: 3px solid #2D6A4F;
            border-left: 3px solid #2D6A4F;
        }

        .chart-icon::before {
            content: "";
            position: absolute;
            left: 4px;
            bottom: 0;
            width: 4px;
            height: 9px;
            background: #2D6A4F;
            box-shadow: 7px -6px 0 #2D6A4F, 14px -12px 0 #2D6A4F;
            border-radius: 2px 2px 0 0;
        }

        .shield-icon {
            width: 21px;
            height: 25px;
            background: #2D6A4F;
            clip-path: polygon(50% 0, 92% 15%, 86% 68%, 50% 100%, 14% 68%, 8% 15%);
            position: relative;
        }

        .shield-icon::after {
            content: "";
            position: absolute;
            width: 7px;
            height: 4px;
            border-left: 2px solid white;
            border-bottom: 2px solid white;
            transform: rotate(-45deg);
            left: 7px;
            top: 8px;
        }

        .doc-icon {
            width: 19px;
            height: 25px;
            border: 2px solid #2D6A4F;
            border-radius: 2px;
            position: relative;
        }

        .doc-icon::before {
            content: "";
            position: absolute;
            left: 4px;
            top: 6px;
            width: 8px;
            height: 2px;
            background: #2D6A4F;
            box-shadow: 0 5px 0 #2D6A4F, 0 10px 0 #2D6A4F;
        }

        /* =========================
           RIGHT SIDE
        ========================= */

        .form-side {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 46px 7.5%;
            background: #F7FBF8;
        }

        .login-card {
            width: min(100%, 665px);
            min-height: 825px;
            padding: 72px 58px 40px;
            border-radius: 19px;
            background: rgba(255,255,255,.97);
            box-shadow:
                0 12px 42px rgba(45, 75, 120, .11),
                0 2px 5px rgba(45, 75, 120, .05);
            display: flex;
            flex-direction: column;
        }

        .login-heading {
            text-align: center;
        }

        .lock-circle {
            width: 89px;
            height: 89px;
            margin: 0 auto 22px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #D8F3DC;
        }

        /* Gembok full CSS, tanpa emoji/icon font */
        .lock {
            width: 28px;
            height: 26px;
            position: relative;
            border: 3px solid #2D6A4F;
            border-radius: 4px;
            margin-top: 8px;
        }

        .lock::before {
            content: "";
            position: absolute;
            width: 16px;
            height: 17px;
            left: 50%;
            top: -17px;
            transform: translateX(-50%);
            border: 3px solid #2D6A4F;
            border-bottom: 0;
            border-radius: 12px 12px 0 0;
        }

        .lock::after {
            content: "";
            position: absolute;
            width: 5px;
            height: 8px;
            left: 50%;
            top: 8px;
            transform: translateX(-50%);
            border-radius: 5px;
            background: #2D6A4F;
        }

        .login-heading h2 {
            margin: 0;
            font-size: clamp(25px, 2vw, 31px);
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -.5px;
            color: #101318;
        }

        .login-heading p {
            margin: 13px 0 0;
            color: #738277;
            font-size: 17px;
            line-height: 1.5;
        }

        .login-form {
            margin-top: 36px;
        }

        .form-group {
            margin-bottom: 23px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #111827;
            font-size: 16px;
            font-weight: 700;
        }

        .input-wrap {
            position: relative;
        }

        .input {
            width: 100%;
            height: 56px;
            padding: 0 53px 0 59px;
            border: 1.5px solid #D5E0D8;
            border-radius: 9px;
            outline: none;
            background: #fff;
            color: #24342B;
            font-size: 16px;
            transition:
                border-color .18s ease,
                box-shadow .18s ease;
        }

        .input::placeholder {
            color: #9AA79F;
        }

        .input:focus {
            border-color: #2D6A4F;
            box-shadow: 0 0 0 4px rgba(23,102,207,.09);
        }

        /* =========================
           FORM CSS ICONS
        ========================= */

        .field-icon {
            position: absolute;
            z-index: 2;
            left: 19px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            pointer-events: none;
        }

        /* User */
        .user-icon::before {
            content: "";
            position: absolute;
            width: 8px;
            height: 8px;
            left: 50%;
            top: 1px;
            transform: translateX(-50%);
            border: 2px solid #52665C;
            border-radius: 50%;
        }

        .user-icon::after {
            content: "";
            position: absolute;
            width: 15px;
            height: 9px;
            left: 50%;
            bottom: 1px;
            transform: translateX(-50%);
            border: 2px solid #52665C;
            border-radius: 9px 9px 3px 3px;
        }

        /* Lock */
        .field-lock::before {
            content: "";
            position: absolute;
            width: 15px;
            height: 13px;
            left: 4px;
            top: 8px;
            border: 2px solid #52665C;
            border-radius: 3px;
        }

        .field-lock::after {
            content: "";
            position: absolute;
            width: 9px;
            height: 9px;
            left: 7px;
            top: 2px;
            border: 2px solid #52665C;
            border-bottom: 0;
            border-radius: 7px 7px 0 0;
        }

        /* Users / role */
        .people-icon::before,
        .people-icon::after {
            content: "";
            position: absolute;
            border: 2px solid #52665C;
            border-radius: 50%;
        }

        .people-icon::before {
            width: 7px;
            height: 7px;
            left: 3px;
            top: 2px;
            box-shadow: 9px 2px 0 -1px white, 9px 2px 0 1px #52665C;
        }

        .people-icon::after {
            width: 15px;
            height: 8px;
            left: 0;
            bottom: 2px;
            border-radius: 8px 8px 3px 3px;
        }

        .select-chevron {
            position: absolute;
            right: 20px;
            top: 50%;
            width: 9px;
            height: 9px;
            transform: translateY(-65%) rotate(45deg);
            border-right: 2px solid #52665C;
            border-bottom: 2px solid #52665C;
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            width: 27px;
            height: 22px;
            transform: translateY(-50%);
            border: 0;
            padding: 0;
            background: transparent;
            cursor: pointer;
        }

        /* Mata CSS */
        .eye-icon {
            position: absolute;
            left: 1px;
            top: 4px;
            width: 24px;
            height: 14px;
            border: 2px solid #52665C;
            border-radius: 80% 20% 80% 20%;
            transform: rotate(45deg);
        }

        .eye-icon::after {
            content: "";
            position: absolute;
            width: 5px;
            height: 5px;
            left: 7px;
            top: 2px;
            border-radius: 50%;
            background: #52665C;
        }

        /* =========================
           SELECT
        ========================= */

        .select-wrap .input {
            appearance: none;
            cursor: pointer;
            padding-right: 52px;
        }

        /* =========================
           CHECKBOX
        ========================= */

        .remember {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: -3px;
            color: #52665C;
            font-size: 15px;
        }

        .remember input {
            appearance: none;
            width: 20px;
            height: 20px;
            margin: 0;
            border: 1.5px solid #A9B9AF;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            position: relative;
        }

        .remember input:checked {
            background: #2D6A4F;
            border-color: #2D6A4F;
        }

        .remember input:checked::after {
            content: "";
            position: absolute;
            width: 5px;
            height: 9px;
            left: 6px;
            top: 2px;
            border-right: 2px solid white;
            border-bottom: 2px solid white;
            transform: rotate(45deg);
        }

        .remember label {
            cursor: pointer;
        }

        /* =========================
           ERROR
        ========================= */

        .error-box {
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #f1b7b7;
            border-radius: 8px;
            background: #fff4f4;
            color: #b42318;
            font-size: 14px;
            line-height: 1.45;
        }

        /* =========================
           LOGIN BUTTON
        ========================= */

        .login-button {
            width: 100%;
            height: 57px;
            margin-top: 27px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border: 0;
            border-radius: 8px;
            background: linear-gradient(135deg, #2D6A4F, #0c52bb);
            color: white;
            font-size: 17px;
            font-weight: 750;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(45,106,79,.16);
            transition:
                transform .16s ease,
                box-shadow .16s ease,
                filter .16s ease;
        }

        .login-button:hover {
            filter: brightness(1.04);
            transform: translateY(-1px);
            box-shadow: 0 11px 23px rgba(45,106,79,.22);
        }

        .login-button:active {
            transform: translateY(0);
        }

        /* Login arrow icon */
        .login-icon {
            width: 19px;
            height: 19px;
            position: relative;
            border: 2px solid white;
            border-left: 0;
            border-radius: 2px;
        }

        .login-icon::before {
            content: "";
            position: absolute;
            left: -8px;
            top: 7px;
            width: 13px;
            height: 2px;
            background: white;
        }

        .login-icon::after {
            content: "";
            position: absolute;
            left: -1px;
            top: 4px;
            width: 7px;
            height: 7px;
            border-top: 2px solid white;
            border-right: 2px solid white;
            transform: rotate(45deg);
        }

        .copyright {
            margin-top: 38px;
            text-align: center;
            color: #8993a6;
            font-size: 14px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1250px) {
            .login-shell {
                grid-template-columns: 51% 49%;
            }

            .brand-content {
                padding-left: 5%;
            }

            .features {
                left: 5%;
                right: 5%;
                gap: 20px;
            }

            .login-card {
                padding-left: 45px;
                padding-right: 45px;
            }
        }

        @media (max-width: 1050px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .brand-side {
                min-height: 620px;
            }

            .form-side {
                min-height: auto;
                padding: 50px 25px;
            }

            .login-card {
                min-height: auto;
            }
        }

        @media (max-width: 680px) {
            .brand-side {
                min-height: 590px;
            }

            .brand-content {
                padding: 28px 24px 0;
            }

            .brand-logo {
                gap: 14px;
            }

            .logo-mark {
                width: 56px;
                height: 61px;
            }

            .cart-icon {
                transform: scale(.76);
                transform-origin: top left;
                left: 17px;
                top: 22px;
            }

            .brand-name {
                font-size: 30px;
            }

            .brand-subtitle {
                font-size: 14px;
                margin-top: 6px;
            }

            .intro {
                margin-top: 42px;
            }

            .intro h1 {
                font-size: 36px;
                letter-spacing: -1px;
            }

            .intro p {
                font-size: 15px;
                line-height: 1.6;
            }

            .illustration {
                bottom: 185px;
                height: 250px;
            }

            .building {
                left: 28%;
                width: 58%;
                height: 155px;
            }

            .building::before {
                left: -18px;
                right: -18px;
                top: -29px;
                height: 42px;
            }

            .building-window {
                top: 43px;
                height: 35px;
            }

            .building-door {
                height: 53px;
            }

            .koperasi-sign {
                top: 21px;
                width: 100px;
                height: 29px;
                font-size: 11px;
            }

            .tree {
                transform: scale(.75);
                transform-origin: bottom;
            }

            .green-wave {
                height: 210px;
            }

            .features {
                bottom: 25px;
                left: 24px;
                right: 24px;
                gap: 12px;
            }

            .feature-icon {
                width: 45px;
                height: 45px;
                border-radius: 13px;
                margin-bottom: 9px;
            }

            .feature h3 {
                font-size: 13px;
            }

            .feature p {
                display: none;
            }

            .login-card {
                padding: 45px 25px 35px;
                border-radius: 16px;
            }

            .lock-circle {
                width: 76px;
                height: 76px;
            }

            .login-heading h2 {
                font-size: 24px;
            }

            .login-heading p {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
<div class="login-page">
    <main class="login-shell">

        <!-- =========================
             KIRI
        ========================== -->
        <section class="brand-side">

            <div class="brand-content">

                <div class="brand-logo">
                    <div class="logo-mark" aria-hidden="true">
                        <div class="hex"></div>
                        <div class="cart-icon">
                            <span class="cart-line"></span>
                        </div>
                    </div>

                    <div>
                        <h2 class="brand-name">E-KOPERASI</h2>
                        <p class="brand-subtitle">Sistem Informasi Koperasi Sekolah</p>
                    </div>
                </div>

                <div class="intro">
                    <h1>
                        Kelola Koperasi<br>
                        Lebih Mudah &amp; Teratur
                    </h1>

                    <div class="intro-line"></div>

                    <p>
                        E-KOPERASI membantu pengelolaan data barang, stok,
                        transaksi, pemasukan, pengeluaran, dan laporan
                        secara terintegrasi dan akurat.
                    </p>
                </div>

            </div>

            <div class="sky-cloud cloud-a"></div>
            <div class="sky-cloud cloud-b"></div>

            <!-- Ilustrasi koperasi dibuat langsung di CSS/HTML -->
            <div class="illustration" aria-hidden="true">

                <div class="city">
                    <div style="
                        position:absolute;
                        left:5%;
                        bottom:0;
                        width:80px;
                        height:135px;
                        background:#DDEEE2;
                        opacity:.65;
                    "></div>

                    <div style="
                        position:absolute;
                        left:15%;
                        bottom:0;
                        width:70px;
                        height:105px;
                        background:#E3F1E7;
                        opacity:.75;
                    "></div>

                    <div style="
                        position:absolute;
                        left:25%;
                        bottom:0;
                        width:90px;
                        height:150px;
                        background:#D7EADF;
                        opacity:.65;
                    "></div>

                    <div style="
                        position:absolute;
                        right:19%;
                        bottom:0;
                        width:72px;
                        height:170px;
                        background:#D1E6D9;
                        opacity:.65;
                    "></div>

                    <div style="
                        position:absolute;
                        right:7%;
                        bottom:0;
                        width:82px;
                        height:120px;
                        background:#E0EEE4;
                        opacity:.7;
                    "></div>
                </div>

                <div class="tree tree-one"></div>
                <div class="tree tree-two"></div>

                <div class="building">
                    <div class="building-main"></div>
                    <div class="building-window window-left"></div>
                    <div class="building-window window-right"></div>
                    <div class="building-door"></div>
                    <div class="koperasi-sign">KOPERASI</div>
                </div>
            </div>

            <div class="green-wave"></div>

            <!-- FEATURES -->
            <div class="features">

                <div class="feature">
                    <div class="feature-icon">
                        <span class="chart-icon"></span>
                    </div>
                    <h3>Data Akurat</h3>
                    <p>Informasi tersimpan dengan akurat dan aman</p>
                </div>

                <div class="feature">
                    <div class="feature-icon">
                        <span class="shield-icon"></span>
                    </div>
                    <h3>Akses Terbatas</h3>
                    <p>Hak akses berbeda untuk kasir dan admin</p>
                </div>

                <div class="feature">
                    <div class="feature-icon">
                        <span class="doc-icon"></span>
                    </div>
                    <h3>Laporan Lengkap</h3>
                    <p>Laporan pemasukan, pengeluaran, dan transaksi kapan saja</p>
                </div>

            </div>
        </section>

        <!-- =========================
             KANAN
        ========================== -->
        <section class="form-side">

            <div class="login-card">

                <div class="login-heading">

                    <div class="lock-circle">
                        <span class="lock" aria-hidden="true"></span>
                    </div>

                    <h2>Selamat Datang Kembali!</h2>
                    <p>Silakan login untuk melanjutkan</p>

                </div>

                <?php if ($error !== ''): ?>
                    <div class="error-box">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="POST" autocomplete="on">

                    <!-- USERNAME -->
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>

                        <div class="input-wrap">
                            <span class="field-icon user-icon" aria-hidden="true"></span>

                            <input
                                class="input"
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Masukkan username"
                                autocomplete="username"
                                value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- PASSWORD -->
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>

                        <div class="input-wrap">
                            <span class="field-icon field-lock" aria-hidden="true"></span>

                            <input
                                class="input"
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                class="password-toggle"
                                type="button"
                                id="passwordToggle"
                                aria-label="Tampilkan password"
                                title="Tampilkan password"
                            >
                                <span class="eye-icon" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>

                    <!-- ROLE -->
                    <div class="form-group">
                        <label class="form-label" for="role">Pilih Role</label>

                        <div class="input-wrap select-wrap">
                            <span class="field-icon people-icon" aria-hidden="true"></span>

                            <select class="input" id="role" name="role" required>
                                <option value="" disabled <?= empty($_POST['role']) ? 'selected' : '' ?>>
                                    Pilih Role
                                </option>
                                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>
                                    Admin
                                </option>
                                <option value="kasir" <?= (($_POST['role'] ?? '') === 'kasir') ? 'selected' : '' ?>>
                                    Kasir
                                </option>
                            </select>

                            <span class="select-chevron" aria-hidden="true"></span>
                        </div>
                    </div>

                    <!-- REMEMBER -->
                    <div class="remember">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Ingat saya</label>
                    </div>

                    <!-- LOGIN -->
                    <button class="login-button" type="submit">
                        <span class="login-icon" aria-hidden="true"></span>
                        <span>Login</span>
                    </button>

                </form>

                <div class="copyright">
                    © 2026 E-KOPERASI. Semua hak dilindungi.
                </div>

            </div>

        </section>

    </main>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');

    passwordToggle.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';

        this.setAttribute(
            'aria-label',
            isPassword ? 'Sembunyikan password' : 'Tampilkan password'
        );

        this.setAttribute(
            'title',
            isPassword ? 'Sembunyikan password' : 'Tampilkan password'
        );
    });
</script>

</body>
</html>
