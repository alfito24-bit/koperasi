<?php
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Koperasi';
$user = $_SESSION['user'] ?? null;
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($pageTitle) ?> - Koperasi</title>

    <link rel="stylesheet" href="/koperasi/assets/style.css">

    <style>
        .topbar {
            width: 100% !important;
            box-sizing: border-box !important;

            background: #ffffff !important;

            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;

            padding: 18px 22px !important;
            margin-bottom: 20px !important;

            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
        }

        .topbar .eyebrow {
            color: #64748b !important;
        }

        .topbar h1 {
            color: #1e293b !important;
            margin: 4px 0 0 !important;
        }

        .topbar .profile-pill {
            background: #f8fafc;
        }
    </style>
</head>

<body>

<div class="app-shell">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">

        <header class="topbar">

            <div>
                <div class="eyebrow">
                    Sistem Koperasi
                </div>

                <h1>
                    <?= e($pageTitle) ?>
                </h1>
            </div>


            <div class="profile-pill">

                <span class="avatar">
                    <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
                </span>

                <div>

                    <strong>
                        <?= e($user['nama'] ?? '') ?>
                    </strong>

                    <small>
                        <?= e(ucfirst($user['role'] ?? '')) ?>
                    </small>

                </div>

            </div>

        </header>