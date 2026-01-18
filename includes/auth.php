<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(string|array|null $roles = null): void
{
    $user = currentUser();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }

    if ($roles !== null) {
        $allowed = is_array($roles) ? $roles : [$roles];
        if (!in_array($user['role'], $allowed, true)) {
            redirectByRole($user['role']);
        }
    }
}

function redirectByRole(string $role): void
{
    if ($role === 'admin') {
        header('Location: /admin/admin_dashboard.php');
    } else {
        header('Location: /user/user_dashboard.php');
    }
    exit;
}

function loginUser(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: /login.php');
    exit;
}
