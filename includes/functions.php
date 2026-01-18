<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function sanitizeTablePrefix(string $username): string
{
    $slug = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($username));
    $slug = trim($slug, '_');
    if ($slug === '') {
        throw new InvalidArgumentException('Username results in empty table prefix.');
    }
    return $slug;
}

function userTableNames(string $username): array
{
    $prefix = sanitizeTablePrefix($username);
    return [
        'products' => $prefix . '_products',
        'sales' => $prefix . '_sales',
        'expenses' => $prefix . '_expenses',
    ];
}

function createUserTables(PDO $pdo, string $username): void
{
    $tables = userTableNames($username);

    $pdo->exec("CREATE TABLE IF NOT EXISTS {$tables['products']} (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        price REAL NOT NULL DEFAULT 0,
        cost REAL NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS {$tables['sales']} (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        unit_price REAL NOT NULL,
        total REAL NOT NULL,
        cogs REAL NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS {$tables['expenses']} (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        description TEXT NOT NULL,
        amount REAL NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
}

function createUserWithTables(string $username, string $password, string $role = 'user'): int
{
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (:u, :p, :r)');
        $stmt->execute([
            ':u' => $username,
            ':p' => password_hash($password, PASSWORD_DEFAULT),
            ':r' => $role,
        ]);

        createUserTables($pdo, $username);
        $pdo->commit();
        return (int) $pdo->lastInsertId();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function deleteUserAndTables(int $userId): void
{
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $user = getUserById($userId);
        if (!$user) {
            throw new RuntimeException('User not found.');
        }
        $tables = userTableNames($user['username']);
        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS ' . $table);
        }
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getUserByUsername(string $username): ?array
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getUserById(int $id): ?array
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function ensureDefaultAdmin(): void
{
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin'");
    $row = $stmt->fetch();
    if ((int) ($row['cnt'] ?? 0) === 0) {
        createUserWithTables('admin', 'admin123', 'admin');
    }
}

function userFinancialSummary(string $username): array
{
    $pdo = getDB();
    $tables = userTableNames($username);

    $productCount = (int) $pdo->query('SELECT COUNT(*) AS c FROM ' . $tables['products'])->fetch()['c'] ?? 0;
    $salesTotal = (float) $pdo->query('SELECT COALESCE(SUM(total),0) AS s FROM ' . $tables['sales'])->fetch()['s'] ?? 0.0;
    $salesCogs = (float) $pdo->query('SELECT COALESCE(SUM(cogs),0) AS s FROM ' . $tables['sales'])->fetch()['s'] ?? 0.0;
    $expensesTotal = (float) $pdo->query('SELECT COALESCE(SUM(amount),0) AS s FROM ' . $tables['expenses'])->fetch()['s'] ?? 0.0;

    $netProfit = $salesTotal - $salesCogs - $expensesTotal;

    return [
        'products' => $productCount,
        'sales_total' => $salesTotal,
        'cogs_total' => $salesCogs,
        'expenses_total' => $expensesTotal,
        'net_profit' => $netProfit,
    ];
}

function parseNumberInput(string $value): float
{
    // Normalize common Indonesian currency inputs like "10.000" or "10.000,50"
    $clean = preg_replace('/[^0-9.,-]/', '', trim($value));

    if ($clean === '' || $clean === '-' || $clean === '.' || $clean === ',') {
        return 0.0;
    }

    $hasComma = strpos($clean, ',') !== false;
    $hasDot = strpos($clean, '.') !== false;

    if ($hasComma && $hasDot) {
        // Assume dot is thousands and comma is decimal (e.g., 10.000,75)
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);
    } elseif ($hasDot && !$hasComma) {
        // Assume dot is thousands (e.g., 10.000)
        $clean = str_replace('.', '', $clean);
    } elseif ($hasComma && !$hasDot) {
        // Comma used as decimal
        $clean = str_replace(',', '.', $clean);
    }

    return (float) $clean;
}
