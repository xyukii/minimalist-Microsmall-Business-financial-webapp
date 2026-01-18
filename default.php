<?php
declare(strict_types=1);

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /user/user_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>yukii Sales Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-peach: #F5E6D3;
            --color-blue-primary: #1E40AF;
            --color-blue-bright: #3B82F6;
            --color-navy: #0F172A;
            --color-dark-blue: #1E3A8A;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--color-peach) 0%, #f0ddc8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-hero {
            max-width: 600px;
            text-align: center;
            padding: 2rem;
        }

        .logo-section {
            margin-bottom: 3rem;
        }

        .logo-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--color-navy);
            margin-bottom: 0.5rem;
        }

        .logo-section p {
            font-size: 1.1rem;
            color: #64748b;
            font-weight: 500;
        }

        .hero-description {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(30, 64, 175, 0.1);
            margin-bottom: 3rem;
        }

        .hero-description h2 {
            color: var(--color-navy);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero-description p {
            color: #64748b;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 0;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .btn-custom {
            padding: 1.2rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-login {
            background: linear-gradient(90deg, var(--color-blue-primary) 0%, var(--color-blue-bright) 100%);
            color: white;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(30, 64, 175, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-request {
            background-color: #10b981;
            color: white;
        }

        .btn-request:hover {
            background-color: #059669;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            color: white;
            text-decoration: none;
        }

        .footer-info {
            margin-top: 3rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container-hero {
                padding: 1rem;
            }

            .logo-section h1 {
                font-size: 2rem;
            }

            .hero-description {
                padding: 1.5rem;
            }

            .hero-description h2 {
                font-size: 1.5rem;
            }

            .btn-custom {
                padding: 1rem 1.5rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-hero">
        <div class="logo-section">
            <h1>💰 Sales Management</h1>
            <p>Private Purposes Only!</p>
        </div>

        <div class="hero-description">
            <h2>Selamat Datang!</h2>
            <p>Aplikasi Sales Management ini dibuat sama hafizh (Yukii) buat ngebantu mama mengelola penjualan dan laporan keuangan bisnis dengan sistem yang simple dan terintegrasi.</p>
        </div>

        <div class="button-group">
            <a href="/login.php" class="btn-custom btn-login">
                🔑 Masuk ke Akun
            </a>
            <a href="https://wa.me/6285179878917?text=Saya%20ingin%20request%20akses%20aplikasi%20Financial%20Management" 
               class="btn-custom btn-request" target="_blank">
                💬 Request Akses via WhatsApp
            </a>
        </div>

        <div class="footer-info">
            <p>© 2026 Financial Management. Semua hak dilindungi.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
