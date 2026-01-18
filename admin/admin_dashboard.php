<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$pdo = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';

        if (strlen($username) < 3 || strlen($password) < 4) {
            $error = 'Username atau kata sandi terlalu pendek.';
        } elseif (getUserByUsername($username)) {
            $error = 'Username sudah digunakan.';
        } else {
            try {
                createUserWithTables($username, $password, $role);
                $message = 'Pengguna berhasil dibuat.';
            } catch (Throwable $e) {
                $error = 'Gagal membuat pengguna: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'delete_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId === (int) currentUser()['id']) {
            $error = 'Tidak bisa menghapus akun Anda sendiri saat login.';
        } else {
            try {
                deleteUserAndTables($userId);
                $message = 'Pengguna dan tabel terkait dihapus.';
            } catch (Throwable $e) {
                $error = 'Gagal menghapus: ' . $e->getMessage();
            }
        }
    }
}

$users = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$stats = [
    'total' => (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'] ?? 0,
    'admins' => (int) $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")->fetch()['c'] ?? 0,
    'standard' => (int) $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'user'")->fetch()['c'] ?? 0,
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dasbor Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <div class="sidebar bg-dark text-white">
        <div class="p-3 fw-bold border-bottom border-secondary">Panel Admin</div>
        <ul class="nav flex-column p-2">
            <li class="nav-item"><a class="nav-link text-white" href="/admin/admin_dashboard.php">Dasbor Admin</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/user/user_dashboard.php">Panel Pengguna</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="/logout.php">Keluar</a></li>
        </ul>
    </div>
    <div class="flex-grow-1">
        <nav class="navbar navbar-light bg-light border-bottom px-3">
            <span class="navbar-brand">Halo, <?php echo htmlspecialchars(currentUser()['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        </nav>
        <main class="p-4">
            <h3 class="mb-3">Kelola Pengguna</h3>
            <?php if ($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5>Buat Pengguna Baru</h5>
                            <form method="post">
                                <input type="hidden" name="action" value="create_user">
                                <div class="mb-2">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Kata Sandi</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Peran</label>
                                    <select name="role" class="form-select">
                                        <option value="user">Pengguna</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="mb-3">Statistik</h5>
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="stat-box bg-primary text-white p-3 rounded">Total Pengguna<br><span class="fs-4"><?php echo $stats['total']; ?></span></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box bg-success text-white p-3 rounded">Admin<br><span class="fs-4"><?php echo $stats['admins']; ?></span></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="stat-box bg-info text-white p-3 rounded">Pengguna Biasa<br><span class="fs-4"><?php echo $stats['standard']; ?></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Daftar Pengguna</h5>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr><th>ID</th><th>Username</th><th>Peran</th><th>Dibuat</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo (int) $u['id']; ?></td>
                                    <td><?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Hapus pengguna beserta semua tabelnya?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>">
                                            <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
