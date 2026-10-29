<?php
require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function ensureAuthTables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT NOT NULL AUTO_INCREMENT,
        full_name VARCHAR(120) NOT NULL,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','customer','student') NOT NULL DEFAULT 'customer',
        institution_id VARCHAR(80) NOT NULL DEFAULT '',
        email VARCHAR(160) NOT NULL DEFAULT '',
        email_verified TINYINT(1) NOT NULL DEFAULT 1,
        phone VARCHAR(40) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $pdo->exec("UPDATE users SET role = 'customer' WHERE role = 'cashier'");
    $pdo->exec("ALTER TABLE users MODIFY role ENUM('admin','customer','student') NOT NULL DEFAULT 'customer'");
    foreach ([
        "ALTER TABLE users ADD COLUMN institution_id VARCHAR(80) NOT NULL DEFAULT '' AFTER role",
        "ALTER TABLE users ADD COLUMN email VARCHAR(160) NOT NULL DEFAULT '' AFTER institution_id",
        "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 1 AFTER email",
        "ALTER TABLE users ADD COLUMN phone VARCHAR(40) NOT NULL DEFAULT '' AFTER email",
    ] as $migration) {
        try { $pdo->exec($migration); } catch (PDOException $exception) { }
    }
    $pdo->exec("UPDATE users SET role = 'customer' WHERE role = 'student'");

    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_password_reset_token_hash (token_hash),
        KEY idx_password_reset_user (user_id),
        CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS email_verification_codes (
        user_id INT NOT NULL,
        code_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        CONSTRAINT fk_email_verification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

}

function dashboardForRole(string $role): string
{
    return match ($role) {
        'admin' => 'dashboard.php',
        default => 'index.php',
    };
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (currentUser()['role'] !== 'admin') {
        http_response_code(403);
        exit('Access denied.');
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

ensureAuthTables($pdo);
