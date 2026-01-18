<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(string|array|null $roles = null): void
{
    $user = currentUser();
    // Idle timeout: 30 minutes inactivity
    $timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $timeout) {
        logoutUser();
    } else {
        $_SESSION['last_activity'] = time();
    }
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
    // Regenerate session ID at privilege change
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];
    $_SESSION['last_activity'] = time();
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
