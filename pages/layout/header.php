<?php
// pages/layout/header.php
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?> — <?= APP_NAME ?></title>

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary:    #2563EB;
            --primary-dk: #1D4ED8;
            --accent:     #06B6D4;
            --success:    #10B981;
            --warning:    #F59E0B;
            --danger:     #EF4444;
            --sidebar-bg: #0F172A;
            --sidebar-w:  250px;
            --topbar-h:   64px;
            --body-bg:    #F1F5F9;
            --card-bg:    #FFFFFF;
            --text-dark:  #1E293B;
            --text-muted: #64748B;
            --border:     #E2E8F0;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--body-bg);
            color: var(--text-dark);
            margin: 0; padding: 0;
        }

        /* ── SIDEBAR ── */
        #sidebar {
            position: fixed; top: 0; left: 0;
            width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-brand .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #fff;
            margin-bottom: 8px;
        }
        .sidebar-brand h5 {
            color: #fff; font-weight: 800;
            margin: 0; font-size: 18px; letter-spacing: -.3px;
        }
        .sidebar-brand small {
            color: rgba(255,255,255,.4);
            font-size: 11px; font-weight: 500;
        }

        .sidebar-nav { padding: 16px 0; flex: 1; }

        .nav-section-title {
            padding: 8px 24px 4px;
            font-size: 10px; font-weight: 700;
            color: rgba(255,255,255,.3);
            text-transform: uppercase; letter-spacing: 1px;
        }

        .nav-item a {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 24px;
            color: rgba(255,255,255,.55);
            text-decoration: none;
            font-size: 13.5px; font-weight: 500;
            border-left: 3px solid transparent;
            transition: all .15s;
        }
        .nav-item a:hover {
            background: rgba(255,255,255,.05);
            color: rgba(255,255,255,.9);
        }
        .nav-item a.active {
            background: rgba(37,99,235,.15);
            color: #fff;
            border-left-color: var(--primary);
        }
        .nav-item a i {
            width: 18px; text-align: center;
            font-size: 14px;
        }

        .sidebar-footer {
            padding: 16px 24px;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-footer .admin-info {
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar-footer .avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; color: #fff; font-weight: 700;
        }
        .sidebar-footer .admin-name {
            color: rgba(255,255,255,.8);
            font-size: 13px; font-weight: 600;
        }
        .sidebar-footer .logout-btn {
            color: rgba(255,255,255,.35);
            font-size: 12px;
            text-decoration: none;
        }
        .sidebar-footer .logout-btn:hover { color: var(--danger); }

        /* ── TOPBAR ── */
        #topbar {
            position: fixed; top: 0;
            left: var(--sidebar-w); right: 0;
            height: var(--topbar-h);
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 28px;
            z-index: 999;
            gap: 16px;
        }
        .topbar-title {
            font-size: 16px; font-weight: 700;
            color: var(--text-dark); flex: 1;
        }
        .topbar-date {
            font-size: 12.5px; color: var(--text-muted);
        }

        /* ── MAIN CONTENT ── */
        #main-content {
            margin-left: var(--sidebar-w);
            margin-top: var(--topbar-h);
            padding: 28px;
            min-height: calc(100vh - var(--topbar-h));
        }

        /* ── CARDS ── */
        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            border-radius: 12px 12px 0 0 !important;
            padding: 16px 20px;
            font-weight: 700; font-size: 14px;
        }
        .card-body { padding: 20px; }

        /* ── STAT CARDS ── */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            display: flex; align-items: center; gap: 16px;
            transition: transform .15s, box-shadow .15s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
        }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-label { font-size: 12px; color: var(--text-muted); font-weight: 600; margin-bottom: 2px; }
        .stat-value { font-size: 22px; font-weight: 800; color: var(--text-dark); }

        /* ── TABLE ── */
        .table thead th {
            background: var(--body-bg);
            color: var(--text-muted);
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
            border-bottom: 1px solid var(--border);
            padding: 10px 14px;
        }
        .table td {
            vertical-align: middle;
            font-size: 13.5px;
            padding: 10px 14px;
            border-top: 1px solid var(--border);
        }
        .table tbody tr:hover { background: #F8FAFC; }

        /* ── BADGES ── */
        .badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
        .badge-warning  { background: #FEF3C7; color: #92400E; }
        .badge-info     { background: #CFFAFE; color: #155E75; }
        .badge-primary  { background: #DBEAFE; color: #1D4ED8; }
        .badge-success  { background: #D1FAE5; color: #065F46; }
        .badge-secondary{ background: #E2E8F0; color: #475569; }
        .badge-dark     { background: #1E293B; color: #F1F5F9; }

        /* ── BUTTONS ── */
        .btn { border-radius: 8px; font-weight: 600; font-size: 13px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dk); border-color: var(--primary-dk); }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .btn-wa { background: #25D366; color: #fff; border: none; }
        .btn-wa:hover { background: #1ebe5a; color: #fff; }

        /* ── FORMS ── */
        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: 13.5px;
            padding: 8px 12px;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }
        label { font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 4px; }

        /* ── FLASH MESSAGES ── */
        .flash-alert {
            border-radius: 10px; font-size: 13.5px;
            display: flex; align-items: center; gap: 10px;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 22px;
        }
        .page-header h4 {
            font-size: 20px; font-weight: 800;
            margin: 0; color: var(--text-dark);
        }
        .breadcrumb { background: none; padding: 0; margin: 0; font-size: 12px; }
        .breadcrumb-item.active { color: var(--text-muted); }

        /* ── KUOTA PROGRESS ── */
        .kuota-bar { height: 6px; border-radius: 3px; background: var(--border); }
        .kuota-fill { height: 6px; border-radius: 3px; transition: width .3s; }

        /* Scrollbar */
        #sidebar::-webkit-scrollbar { width: 4px; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fas fa-soap"></i></div>
        <h5><?= APP_NAME ?></h5>
        <small><?= APP_TAGLINE ?></small>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/index.php" class="<?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Manajemen</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/pelanggan.php" class="<?= ($activePage ?? '') === 'pelanggan' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Pelanggan
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/transaksi.php" class="<?= ($activePage ?? '') === 'transaksi' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-basket"></i> Transaksi
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/status.php" class="<?= ($activePage ?? '') === 'status' ? 'active' : '' ?>">
                    <i class="fas fa-tasks"></i> Status Cucian
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/antar_jemput.php" class="<?= ($activePage ?? '') === 'antar_jemput' ? 'active' : '' ?>">
                    <i class="fas fa-truck"></i> Antar–Jemput
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/membership.php" class="<?= ($activePage ?? '') === 'membership' ? 'active' : '' ?>">
                    <i class="fas fa-id-card"></i> Membership
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Keuangan</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/pages/laporan.php" class="<?= ($activePage ?? '') === 'laporan' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i> Laporan
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="avatar"><?= strtoupper(substr(getAdminName(), 0, 1)) ?></div>
            <div>
                <div class="admin-name"><?= getAdminName() ?></div>
                <a href="<?= BASE_URL ?>/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- TOPBAR -->
<div id="topbar">
    <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
    <div class="topbar-date">
        <i class="fas fa-calendar-alt mr-1"></i>
        <?= date('d F Y') ?>
    </div>
</div>

<!-- MAIN CONTENT -->
<div id="main-content">

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'warning') ?> flash-alert alert-dismissible fade show" role="alert">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= $flash['message'] ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
