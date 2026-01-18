<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireLogin(['user', 'admin']);

$user = currentUser();
$summary = userFinancialSummary($user['username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dasbor Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <div class="sidebar bg-dark text-white">
        <div class="p-3 fw-bold border-bottom border-secondary">Panel Pengguna</div>
        <ul class="nav flex-column p-2">
            <li class="nav-item"><a class="nav-link text-white" href="/user/user_dashboard.php">Dasbor</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/products.php">Produk</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/sales.php">Penjualan</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/expenses.php">Biaya</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/report.php">Laporan</a></li>
            <?php if ($user['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link text-white" href="/admin/admin_dashboard.php">Panel Admin</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-white" href="/logout.php">Keluar</a></li>
        </ul>
    </div>
    <div class="flex-grow-1">
        <nav class="navbar navbar-light bg-light border-bottom px-3">
            <span class="navbar-brand">Halo, <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>
        <main class="p-4">
            <h3 class="mb-3">Ringkasan</h3>
            <div class="row g-3">
                <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Produk</div><div class="fs-3"><?php echo $summary['products']; ?></div></div></div></div>
                <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Penjualan</div><div class="fs-3">Rp<?php echo number_format($summary['sales_total'], 0, ',', '.'); ?></div></div></div></div>
                <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">HPP</div><div class="fs-3">Rp<?php echo number_format($summary['cogs_total'], 0, ',', '.'); ?></div></div></div></div>
                <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Laba Bersih</div><div class="fs-3 text-success">Rp<?php echo number_format($summary['net_profit'], 0, ',', '.'); ?></div></div></div></div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
