<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireLogin(['user', 'admin']);

$user = currentUser();
$pdo = getDB();
$tables = userTableNames($user['username']);

$message = '';
$error = '';
$editSale = null;

$products = $pdo->query('SELECT id, name, price, cost FROM ' . $tables['products'] . ' ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));

    $stmt = $pdo->prepare('SELECT * FROM ' . $tables['products'] . ' WHERE id = :id');
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if ($action !== 'delete_sale' && !$product) {
        $error = 'Produk tidak ditemukan.';
    } else {
        if ($action === 'add_sale') {
            $unitPrice = (float) $product['price'];
            $unitCost = (float) $product['cost'];
            $total = $unitPrice * $qty;
            $cogs = $unitCost * $qty;

            $insert = $pdo->prepare('INSERT INTO ' . $tables['sales'] . ' (product_id, quantity, unit_price, total, cogs) VALUES (:pid, :q, :up, :t, :c)');
            $insert->execute([
                ':pid' => $productId,
                ':q' => $qty,
                ':up' => $unitPrice,
                ':t' => $total,
                ':c' => $cogs,
            ]);
            $message = 'Penjualan dicatat.';
        }

        if ($action === 'update_sale') {
            $saleId = (int) ($_POST['id'] ?? 0);
            $unitPrice = (float) $product['price'];
            $unitCost = (float) $product['cost'];
            $total = $unitPrice * $qty;
            $cogs = $unitCost * $qty;

            $upd = $pdo->prepare('UPDATE ' . $tables['sales'] . ' SET product_id = :pid, quantity = :q, unit_price = :up, total = :t, cogs = :c WHERE id = :id');
            $upd->execute([
                ':pid' => $productId,
                ':q' => $qty,
                ':up' => $unitPrice,
                ':t' => $total,
                ':c' => $cogs,
                ':id' => $saleId,
            ]);
            $message = 'Penjualan diperbarui.';
        }

        if ($action === 'delete_sale') {
            $saleId = (int) ($_POST['id'] ?? 0);
            $del = $pdo->prepare('DELETE FROM ' . $tables['sales'] . ' WHERE id = :id');
            $del->execute([':id' => $saleId]);
            $message = 'Penjualan dihapus.';
        }
    }
}

if (isset($_GET['edit'])) {
    $saleId = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT s.*, p.name FROM ' . $tables['sales'] . ' s LEFT JOIN ' . $tables['products'] . ' p ON p.id = s.product_id WHERE s.id = :id');
    $stmt->execute([':id' => $saleId]);
    $editSale = $stmt->fetch();
}

$sales = $pdo->query('SELECT s.*, p.name FROM ' . $tables['sales'] . ' s LEFT JOIN ' . $tables['products'] . ' p ON p.id = s.product_id ORDER BY s.created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Penjualan</title>
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
            <li class="nav-item"><a class="nav-link text-white active" href="/user/sales.php">Penjualan</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/report.php">Laporan</a></li>
            <?php if ($user['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link text-white" href="/admin/admin_dashboard.php">Panel Admin</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-white" href="/logout.php">Keluar</a></li>
        </ul>
    </div>
    <div class="flex-grow-1">
        <nav class="navbar navbar-light bg-light border-bottom px-3">
            <span class="navbar-brand">Penjualan</span>
        </nav>
        <main class="p-4">
            <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5><?php echo $editSale ? 'Ubah Penjualan' : 'Catat Penjualan'; ?></h5>
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Produk</label>
                                    <select name="product_id" class="form-select" required>
                                        <option value="">Pilih produk</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?php echo (int) $p['id']; ?>" <?php echo ($editSale['product_id'] ?? null) == $p['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?> (Rp<?php echo number_format((float) $p['price'], 0, ',', '.'); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah</label>
                                    <input type="number" name="quantity" min="1" value="<?php echo htmlspecialchars($editSale['quantity'] ?? '1', ENT_QUOTES, 'UTF-8'); ?>" class="form-control" required>
                                </div>
                                <?php if ($editSale): ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $editSale['id']; ?>">
                                    <input type="hidden" name="action" value="update_sale">
                                    <button class="btn btn-primary w-100" type="submit">Simpan Perubahan</button>
                                    <a href="/user/sales.php" class="btn btn-secondary w-100 mt-2">Batal</a>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="add_sale">
                                    <button class="btn btn-success w-100" type="submit">Simpan</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Riwayat Penjualan</h5>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead><tr><th>#</th><th>Produk</th><th>Qty</th><th>Harga Satuan</th><th>Total</th><th>HPP</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($sales as $s): ?>
                                        <tr>
                                            <td><?php echo (int) $s['id']; ?></td>
                                            <td><?php echo htmlspecialchars($s['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo (int) $s['quantity']; ?></td>
                                            <td>Rp<?php echo number_format((float) $s['unit_price'], 0, ',', '.'); ?></td>
                                            <td>Rp<?php echo number_format((float) $s['total'], 0, ',', '.'); ?></td>
                                            <td>Rp<?php echo number_format((float) $s['cogs'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($s['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary" href="/user/sales.php?edit=<?php echo (int) $s['id']; ?>">Ubah</a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Hapus penjualan ini?');">
                                                    <input type="hidden" name="action" value="delete_sale">
                                                    <input type="hidden" name="id" value="<?php echo (int) $s['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                                </form>
                                            </td>
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
