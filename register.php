<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$message = 'Pendaftaran pengguna telah dinonaktifkan. Silakan hubungi admin untuk membuat akun baru.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pendaftaran Nonaktif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-3">Pendaftaran Nonaktif</h3>
                        <div class="alert alert-info" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="text-center">
                            <a class="btn btn-primary" href="/login.php">Kembali ke halaman masuk</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
