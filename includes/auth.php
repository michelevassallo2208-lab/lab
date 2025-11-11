<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

class Auth
{
    public static function login(string $username, string $password): bool
    {
        $pdo = Database::getConnection();

        $statement = $pdo->prepare('SELECT id, username, password_hash, display_name, role FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role'],
            ];

            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];
        session_destroy();
    }

    public static function user(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        return $_SESSION['user'] ?? null;
    }

    public static function requireLogin(): void
    {
        if (!self::user()) {
            header('Location: index.php');
            exit;
        }
    }
}
