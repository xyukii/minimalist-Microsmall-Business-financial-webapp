<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireLogin(['user', 'admin']);

$user = currentUser();
$pdo = getDB();
$tables = userTableNames($user['username']);

$summary = userFinancialSummary($user['username']);
$sales = $pdo->query('SELECT s.id, p.name, s.quantity, s.total, s.cogs, s.created_at FROM ' . $tables['sales'] . ' s LEFT JOIN ' . $tables['products'] . ' p ON p.id = s.product_id ORDER BY s.created_at DESC LIMIT 20')->fetchAll();
$expenses = $pdo->query('SELECT * FROM ' . $tables['expenses'] . ' ORDER BY created_at DESC LIMIT 20')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Keuangan</title>
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
            <li class="nav-item"><a class="nav-link text-white active" href="/user/report.php">Laporan</a></li>
            <?php if ($user['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link text-white" href="/admin/admin_dashboard.php">Panel Admin</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-white" href="/logout.php">Keluar</a></li>
        </ul>
    </div>
    <div class="flex-grow-1">
        <nav class="navbar navbar-light bg-light border-bottom px-3 d-flex justify-content-between align-items-center">
            <span class="navbar-brand">Laporan Keuangan</span>
            <a class="btn btn-outline-primary" href="/user/report_pdf.php" target="_blank">Unduh PDF</a>
        </nav>
        <main class="p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Penjualan</div><div class="fs-4">Rp<?php echo number_format($summary['sales_total'], 0, ',', '.'); ?></div></div></div></div>
                <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">HPP</div><div class="fs-4">Rp<?php echo number_format($summary['cogs_total'], 0, ',', '.'); ?></div></div></div></div>
                <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Biaya</div><div class="fs-4">Rp<?php echo number_format($summary['expenses_total'], 0, ',', '.'); ?></div></div></div></div>
                <div class="col-md-3"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Laba Bersih</div><div class="fs-4 text-success">Rp<?php echo number_format($summary['net_profit'], 0, ',', '.'); ?></div></div></div></div>
            </div>

            <div class="row g-3">
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Penjualan Terbaru</h5>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead><tr><th>#</th><th>Produk</th><th>Qty</th><th>Total</th><th>HPP</th><th>Tanggal</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($sales as $s): ?>
                                        <tr>
                                            <td><?php echo (int) $s['id']; ?></td>
                                            <td><?php echo htmlspecialchars($s['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo (int) $s['quantity']; ?></td>
                                            <td>Rp<?php echo number_format((float) $s['total'], 0, ',', '.'); ?></td>
                                            <td>Rp<?php echo number_format((float) $s['cogs'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($s['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Biaya Terbaru</h5>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead><tr><th>#</th><th>Deskripsi</th><th>Jumlah</th><th>Tanggal</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($expenses as $e): ?>
                                        <tr>
                                            <td><?php echo (int) $e['id']; ?></td>
                                            <td><?php echo htmlspecialchars($e['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>Rp<?php echo number_format((float) $e['amount'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($e['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
