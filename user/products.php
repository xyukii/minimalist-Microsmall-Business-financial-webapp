<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireLogin(['user', 'admin']);

$user = currentUser();
$pdo = getDB();
$tables = userTableNames($user['username']);

$message = '';
$error = '';
$editProduct = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_product') {
        $name = trim($_POST['name'] ?? '');
        $price = parseNumberInput((string) ($_POST['price'] ?? '0'));
        $cost = parseNumberInput((string) ($_POST['cost'] ?? '0'));
        if ($name === '') {
            $error = 'Nama produk wajib diisi.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO ' . $tables['products'] . ' (name, price, cost) VALUES (:n, :p, :c)');
            $stmt->execute([':n' => $name, ':p' => $price, ':c' => $cost]);
            $message = 'Produk ditambahkan.';
        }
    }
    if ($action === 'update_product') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = parseNumberInput((string) ($_POST['price'] ?? '0'));
        $cost = parseNumberInput((string) ($_POST['cost'] ?? '0'));
        if ($name === '') {
            $error = 'Nama produk wajib diisi.';
        } else {
            $stmt = $pdo->prepare('UPDATE ' . $tables['products'] . ' SET name = :n, price = :p, cost = :c WHERE id = :id');
            $stmt->execute([':n' => $name, ':p' => $price, ':c' => $cost, ':id' => $id]);
            $message = 'Produk diperbarui.';
        }
    }
    if ($action === 'delete_product') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM ' . $tables['products'] . ' WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $message = 'Produk dihapus.';
    }
}

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM ' . $tables['products'] . ' WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editProduct = $stmt->fetch();
}

$products = $pdo->query('SELECT * FROM ' . $tables['products'] . ' ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <div class="sidebar bg-dark text-white">
        <div class="p-3 fw-bold border-bottom border-secondary">Panel Pengguna</div>
        <ul class="nav flex-column p-2">
            <li class="nav-item"><a class="nav-link text-white" href="/user/user_dashboard.php">Dasbor</a></li>
            <li class="nav-item"><a class="nav-link text-white active" href="/user/products.php">Produk</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/sales.php">Penjualan</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/report.php">Laporan</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link text-white" href="/admin/admin_dashboard.php">Panel Admin</a></li>
                <?php endif; ?>
            <li class="nav-item"><a class="nav-link text-white" href="/logout.php">Keluar</a></li>
        </ul>
    </div>
    <div class="flex-grow-1">
        <nav class="navbar navbar-light bg-light border-bottom px-3">
            <span class="navbar-brand">Produk</span>
        </nav>
        <main class="p-4">
            <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5><?php echo $editProduct ? 'Ubah Produk' : 'Tambah Produk'; ?></h5>
                            <form method="post">
                                <div class="mb-2">
                                    <label class="form-label">Nama</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editProduct['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Harga Jual (Rp)</label>
                                    <input type="text" inputmode="decimal" name="price" class="form-control" value="<?php echo htmlspecialchars($editProduct['price'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Biaya Pokok (Rp)</label>
                                    <input type="text" inputmode="decimal" name="cost" class="form-control" value="<?php echo htmlspecialchars($editProduct['cost'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <?php if ($editProduct): ?>
                                    <input type="hidden" name="id" value="<?php echo (int) $editProduct['id']; ?>">
                                    <input type="hidden" name="action" value="update_product">
                                    <button class="btn btn-primary w-100" type="submit">Simpan Perubahan</button>
                                    <a href="/user/products.php" class="btn btn-secondary w-100 mt-2">Batal</a>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="add_product">
                                    <button class="btn btn-success w-100" type="submit">Tambah</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-3">Daftar Produk</h5>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead><tr><th>#</th><th>Nama</th><th>Harga</th><th>HPP</th><th>Dibuat</th><th>Aksi</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($products as $p): ?>
                                        <tr>
                                            <td><?php echo (int) $p['id']; ?></td>
                                            <td><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>Rp<?php echo number_format((float) $p['price'], 0, ',', '.'); ?></td>
                                            <td>Rp<?php echo number_format((float) $p['cost'], 0, ',', '.'); ?></td>
                                            <td><?php echo htmlspecialchars($p['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary" href="/user/products.php?edit=<?php echo (int) $p['id']; ?>">Ubah</a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Hapus produk ini?');">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
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
