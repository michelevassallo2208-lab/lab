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

            $permissions = self::fetchPermissions((int) $user['id']);

            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role'],
                'pages' => $permissions,
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

    public static function requireRole(string $role): void
    {
        $user = self::user();

        if (!$user || ($user['role'] ?? null) !== $role) {
            header('Location: dashboard.php');
            exit;
        }
    }

    public static function hasAccess(string $pageSlug): bool
    {
        $user = self::user();

        if (!$user) {
            return false;
        }

        if (($user['role'] ?? null) === 'admin') {
            return true;
        }

        return in_array($pageSlug, $user['pages'] ?? [], true);
    }

    public static function refreshPermissions(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            return;
        }

        $_SESSION['user']['pages'] = self::fetchPermissions((int) $_SESSION['user']['id']);
    }

    private static function fetchPermissions(int $userId): array
    {
        $pdo = Database::getConnection();

        $statement = $pdo->prepare(
            'SELECT p.slug
             FROM user_page_permissions upp
             INNER JOIN pages p ON p.id = upp.page_id
             WHERE upp.user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    public static function requireLogin(): void
    {
        if (!self::user()) {
            header('Location: index.php');
            exit;
        }
    }
}
