<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pdf.php';
requireLogin(['user', 'admin']);

$user = currentUser();
$pdo = getDB();
$tables = userTableNames($user['username']);

$summary = userFinancialSummary($user['username']);
$sales = $pdo->query('SELECT s.id, p.name, s.quantity, s.total, s.cogs, s.created_at FROM ' . $tables['sales'] . ' s LEFT JOIN ' . $tables['products'] . ' p ON p.id = s.product_id ORDER BY s.created_at DESC LIMIT 30')->fetchAll();
$expenses = $pdo->query('SELECT * FROM ' . $tables['expenses'] . ' ORDER BY created_at DESC LIMIT 30')->fetchAll();

function rupiah(float $value): string
{
    return 'Rp' . number_format($value, 0, ',', '.');
}

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetTitle('Laporan Keuangan - ' . $user['username']);
$pdf->SetAuthor('Multi-User Finance');
$pdf->AddPage();

$pdf->SetFont('Helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Laporan Keuangan', 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(0, 6, 'Pengguna: ' . $user['username'], 0, 1, 'L');
$pdf->Cell(0, 6, 'Tanggal: ' . date('d-m-Y H:i'), 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Helvetica', 'B', 11);
$pdf->Cell(50, 8, 'Penjualan', 1, 0, 'C');
$pdf->Cell(50, 8, 'HPP', 1, 0, 'C');
$pdf->Cell(50, 8, 'Biaya', 1, 0, 'C');
$pdf->Cell(40, 8, 'Laba Bersih', 1, 1, 'C');
$pdf->SetFont('Helvetica', '', 11);
$pdf->Cell(50, 8, rupiah($summary['sales_total']), 1, 0, 'R');
$pdf->Cell(50, 8, rupiah($summary['cogs_total']), 1, 0, 'R');
$pdf->Cell(50, 8, rupiah($summary['expenses_total']), 1, 0, 'R');
$pdf->Cell(40, 8, rupiah($summary['net_profit']), 1, 1, 'R');
$pdf->Ln(6);

$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Penjualan Terbaru', 0, 1, 'L');
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(12, 7, '#', 1, 0, 'C');
$pdf->Cell(60, 7, 'Produk', 1, 0, 'L');
$pdf->Cell(18, 7, 'Qty', 1, 0, 'C');
$pdf->Cell(30, 7, 'Total', 1, 0, 'R');
$pdf->Cell(30, 7, 'HPP', 1, 0, 'R');
$pdf->Cell(40, 7, 'Tanggal', 1, 1, 'L');
$pdf->SetFont('Helvetica', '', 10);
foreach ($sales as $s) {
    $pdf->Cell(12, 7, (string) $s['id'], 1, 0, 'C');
    $pdf->Cell(60, 7, substr($s['name'] ?? 'N/A', 0, 30), 1, 0, 'L');
    $pdf->Cell(18, 7, (string) $s['quantity'], 1, 0, 'C');
    $pdf->Cell(30, 7, rupiah((float) $s['total']), 1, 0, 'R');
    $pdf->Cell(30, 7, rupiah((float) $s['cogs']), 1, 0, 'R');
    $pdf->Cell(40, 7, substr((string) $s['created_at'], 0, 16), 1, 1, 'L');
}
$pdf->Ln(6);

$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Biaya Terbaru', 0, 1, 'L');
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(12, 7, '#', 1, 0, 'C');
$pdf->Cell(98, 7, 'Deskripsi', 1, 0, 'L');
$pdf->Cell(40, 7, 'Jumlah', 1, 0, 'R');
$pdf->Cell(40, 7, 'Tanggal', 1, 1, 'L');
$pdf->SetFont('Helvetica', '', 10);
foreach ($expenses as $e) {
    $pdf->Cell(12, 7, (string) $e['id'], 1, 0, 'C');
    $pdf->Cell(98, 7, substr($e['description'], 0, 50), 1, 0, 'L');
    $pdf->Cell(40, 7, rupiah((float) $e['amount']), 1, 0, 'R');
    $pdf->Cell(40, 7, substr((string) $e['created_at'], 0, 16), 1, 1, 'L');
}

$pdf->Output('I', 'laporan.pdf');
exit;
