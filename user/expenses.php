<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireLogin(['user', 'admin']);

$user = currentUser();
$pdo = getDB();
$tables = userTableNames($user['username']);

$message = '';
$error = '';
$editExpense = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $desc = trim($_POST['description'] ?? '');
    $amount = parseNumberInput((string) ($_POST['amount'] ?? '0'));

    if ($action === 'add_expense') {
        if ($desc === '' || $amount <= 0) {
            $error = 'Deskripsi dan nilai positif wajib diisi.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO ' . $tables['expenses'] . ' (description, amount) VALUES (:d, :a)');
            $stmt->execute([':d' => $desc, ':a' => $amount]);
            $message = 'Biaya ditambahkan.';
        }
    }

    if ($action === 'update_expense') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($desc === '' || $amount <= 0) {
            $error = 'Deskripsi dan nilai positif wajib diisi.';
        } else {
            $stmt = $pdo->prepare('UPDATE ' . $tables['expenses'] . ' SET description = :d, amount = :a WHERE id = :id');
            $stmt->execute([':d' => $desc, ':a' => $amount, ':id' => $id]);
            $message = 'Biaya diperbarui.';
        }
    }

    if ($action === 'delete_expense') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM ' . $tables['expenses'] . ' WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $message = 'Biaya dihapus.';
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM ' . $tables['expenses'] . ' WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editExpense = $stmt->fetch();
}

$expenses = $pdo->query('SELECT * FROM ' . $tables['expenses'] . ' ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biaya</title>
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
            <li class="nav-item"><a class="nav-link text-white active" href="/user/expenses.php">Biaya</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/report.php">Laporan</a></li>
            <?php if ($user['role'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link text-white" href="/admin/admin_dashboard.php">Panel Admin</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-white" href="/logout.php">Keluar</a></li>
        </ul>
    </div>
    <div class="flex-grow-1">
        <nav class="navbar navbar-light bg-light border-bottom px-3">
            <span class="navbar-brand">Biaya</span>
        </nav>
        <main class="p-4">
            <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5><?php echo $editExpense ? 'Ubah Biaya' : 'Tambah Biaya'; ?></h5>
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($editExpense['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jumlah (Rp)</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($editExpense['amount'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <?php if ($editExpense): ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $editExpense['id']; ?>">
                                    <input type="hidden" name="action" value="update_expense">
                                    <button class="btn btn-primary w-100" type="submit">Simpan Perubahan</button>
                                    <a href="/user/expenses.php" class="btn btn-secondary w-100 mt-2">Batal</a>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="add_expense">
                                    <button class="btn btn-success w-100" type="submit">Simpan</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Riwayat Biaya</h5>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead><tr><th>#</th><th>Deskripsi</th><th>Jumlah</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($expenses as $e): ?>
                                        <tr>
                                            <td><?php echo (int) $e['id']; ?></td>
                                            <td><?php echo htmlspecialchars($e['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>Rp<?php echo number_format((float) $e['amount'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($e['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary" href="/user/expenses.php?edit=<?php echo (int) $e['id']; ?>">Ubah</a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Hapus biaya ini?');">
                                                    <input type="hidden" name="action" value="delete_expense">
                                                    <input type="hidden" name="id" value="<?php echo (int) $e['id']; ?>">
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
