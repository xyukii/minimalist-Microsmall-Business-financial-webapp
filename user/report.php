<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireLogin(['user', 'admin']);

$user = currentUser();
$pdo = getDB();
$tables = userTableNames($user['username']);

// Get filter parameters
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month and year
$selectedMonth = max(1, min(12, $selectedMonth));
$selectedYear = max(2020, min(date('Y'), $selectedYear));

// Calculate date range
$startDate = sprintf("%d-%02d-01", $selectedYear, $selectedMonth);
$endDate = date('Y-m-t', strtotime($startDate));

// Get filtered summary - HANYA PENJUALAN
$summarySql = "
    SELECT 
        COALESCE(SUM(s.total), 0) as sales_total,
        COUNT(s.id) as sales_count,
        AVG(s.total) as avg_sales
    FROM " . $tables['sales'] . " s
    WHERE DATE(s.created_at) BETWEEN ? AND ?
";

$stmt = $pdo->prepare($summarySql);
$stmt->execute([$startDate, $endDate]);
$filterResult = $stmt->fetch();

$filteredSummary = [
    'sales_total' => (float)$filterResult['sales_total'],
    'sales_count' => (int)$filterResult['sales_count'],
    'avg_sales' => (float)$filterResult['avg_sales']
];

// Get filtered sales
$salesSql = "SELECT s.id, p.name, s.quantity, s.total, s.created_at 
             FROM " . $tables['sales'] . " s 
             LEFT JOIN " . $tables['products'] . " p ON p.id = s.product_id 
             WHERE DATE(s.created_at) BETWEEN ? AND ? 
             ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($salesSql);
$stmt->execute([$startDate, $endDate]);
$sales = $stmt->fetchAll();

// Get available years for dropdown
$yearsStmt = $pdo->query("SELECT DISTINCT strftime('%Y', created_at) as year FROM " . $tables['sales'] . " 
                          ORDER BY year DESC");
$years = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($years)) {
    $years = [date('Y')];
}
$years = array_unique($years);
sort($years, SORT_NUMERIC);
$years = array_reverse($years);

$monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Keuangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>    </style>
</head></head>
<body>
<div class="d-flex">
    <div class="sidebar bg-dark text-white">
        <div class="p-3 fw-bold border-bottom border-secondary">Panel Pengguna</div>
        <ul class="nav flex-column p-2">
            <li class="nav-item"><a class="nav-link text-white" href="/user/user_dashboard.php">Dasbor</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/products.php">Produk</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/sales.php">Penjualan</a></li>
            <li class="nav-item"><a class="nav-link text-white active" href="/user/report.php">Laporan</a></li>
            <?php if ($user['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link text-white" href="/admin/admin_dashboard.php">Panel Admin</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-white" href="/logout.php">Keluar</a></li>
        </ul>
    </div>
    <div class="flex-grow-1">
        <nav class="navbar navbar-light bg-light border-bottom px-3 d-flex justify-content-between align-items-center">
            <span class="navbar-brand">Laporan Penjualan</span>
        </nav>
        <main class="p-4">
            <!-- Filter Section -->
            <div class="card filter-card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3" style="margin-bottom: 0.5rem !important;">Filter Laporan</h5>
                    <form method="GET" class="row g-2">
                        <div class="col-md-3">
                            <label for="month" class="form-label" style="font-size: 0.85rem; margin-bottom: 0.3rem;">Bulan</label>
                            <select id="month" name="month" class="form-select form-select-sm">
                                <?php for($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m === $selectedMonth ? 'selected' : ''; ?>>
                                        <?php echo $monthNames[$m]; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label" style="font-size: 0.85rem; margin-bottom: 0.3rem;">Tahun</label>
                            <select id="year" name="year" class="form-select form-select-sm">
                                <?php foreach($years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y === $selectedYear ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Tampilkan</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Total Penjualan</div><div class="fs-4">Rp<?php echo number_format($filteredSummary['sales_total'], 0, ',', '.'); ?></div></div></div></div>
                <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Jumlah Transaksi</div><div class="fs-4"><?php echo $filteredSummary['sales_count']; ?></div></div></div></div>
                <div class="col-md-4"><div class="card shadow-sm"><div class="card-body text-center"><div class="text-muted">Rata-rata Penjualan</div><div class="fs-4">Rp<?php echo number_format($filteredSummary['avg_sales'], 0, ',', '.'); ?></div></div></div></div>
            </div>

            <div class="row g-3">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Laporan Penjualan - <?php echo $monthNames[$selectedMonth] . ' ' . $selectedYear; ?></h5>
                            <?php if (empty($sales)): ?>
                                <div class="alert alert-info">Tidak ada data penjualan untuk bulan ini.</div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead><tr><th>#</th><th>Produk</th><th>Qty</th><th>Total</th><th>Tanggal</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($sales as $s): ?>
                                        <tr>
                                            <td><?php echo (int) $s['id']; ?></td>
                                            <td><?php echo htmlspecialchars($s['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo (int) $s['quantity']; ?></td>
                                            <td>Rp<?php echo number_format((float) $s['total'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($s['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
