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

// Get filtered sales data
$salesSql = "SELECT s.id, p.name, s.quantity, s.total, s.created_at 
             FROM " . $tables['sales'] . " s 
             LEFT JOIN " . $tables['products'] . " p ON p.id = s.product_id 
             WHERE DATE(s.created_at) BETWEEN ? AND ? 
             ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($salesSql);
$stmt->execute([$startDate, $endDate]);
$sales = $stmt->fetchAll();

// Get summary
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

$monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Set header untuk download file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan-penjualan-' . $selectedYear . '-' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) . '.csv"');

// Buka output stream
$output = fopen('php://output', 'w');

// BOM untuk UTF-8 agar bisa baca karakter Indonesia dengan benar di Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header laporan
fputcsv($output, ['LAPORAN PENJUALAN', $monthNames[$selectedMonth] . ' ' . $selectedYear]);
fputcsv($output, ['Periode', $startDate . ' s/d ' . $endDate]);
fputcsv($output, ['Pengguna', $user['username']]);
fputcsv($output, ['Tanggal Export', date('d-m-Y H:i:s')]);
fputcsv($output, []);

// Ringkasan
fputcsv($output, ['RINGKASAN']);
fputcsv($output, ['Total Penjualan', 'Rp' . number_format((float)$filterResult['sales_total'], 0, ',', '.')]);
fputcsv($output, ['Jumlah Transaksi', (int)$filterResult['sales_count']]);
fputcsv($output, ['Rata-rata Penjualan', 'Rp' . number_format((float)$filterResult['avg_sales'], 0, ',', '.')]);
fputcsv($output, []);

// Header tabel
fputcsv($output, ['#', 'Produk', 'Qty', 'Total (Rp)', 'Tanggal']);

// Data penjualan
foreach ($sales as $s) {
    fputcsv($output, [
        (int)$s['id'],
        $s['name'] ?? 'N/A',
        (int)$s['quantity'],
        number_format((float)$s['total'], 0, ',', '.'),
        $s['created_at']
    ]);
}

fclose($output);
exit;
?>
