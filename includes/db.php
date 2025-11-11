<?php

declare(strict_types=1);

class Database
{
    private const DB_HOST = 'localhost';
    private const DB_PORT = 3306;
    private const DB_NAME = 'vision_portal';
    private const DB_USER = 'root';
    private const DB_PASS = '';

    public static function getConnection(): \PDO
    {
        self::createDatabaseIfMissing();

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            self::DB_HOST,
            self::DB_PORT,
            self::DB_NAME
        );

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new \PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            self::ensureSchema($pdo);

            return $pdo;
        } catch (\PDOException $exception) {
            exit('Errore di connessione al database: ' . $exception->getMessage());
        }
    }

    private static function createDatabaseIfMissing(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            self::DB_HOST,
            self::DB_PORT
        );

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ];

        try {
            $pdo = new \PDO($dsn, self::DB_USER, self::DB_PASS, $options);
            $pdo->exec(
                sprintf(
                    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                    self::DB_NAME
                )
            );
        } catch (\PDOException $exception) {
            exit('Impossibile creare il database: ' . $exception->getMessage());
        }
    }

    private static function ensureSchema(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                display_name VARCHAR(100) NOT NULL,
                role ENUM("admin", "user") NOT NULL DEFAULT "user",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS pages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(50) NOT NULL UNIQUE,
                label VARCHAR(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS user_page_permissions (
                user_id INT UNSIGNED NOT NULL,
                page_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (user_id, page_id),
                CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_user_permissions_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $defaultPages = [
            ['slug' => 'dashboard', 'label' => 'Dashboard'],
            ['slug' => 'analytics', 'label' => 'Analytics'],
            ['slug' => 'team', 'label' => 'Team'],
            ['slug' => 'settings', 'label' => 'Impostazioni'],
            ['slug' => 'user-management', 'label' => 'Gestione utenze'],
        ];

        $insertPage = $pdo->prepare('INSERT IGNORE INTO pages (slug, label) VALUES (:slug, :label)');

        foreach ($defaultPages as $page) {
            $insertPage->execute($page);
        }

        $statement = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => 'admin']);
        $adminId = $statement->fetchColumn();

        if (!$adminId) {
            $insertAdmin = $pdo->prepare(
                'INSERT INTO users (username, password_hash, display_name, role) VALUES (:username, :password_hash, :display_name, :role)'
            );
            $insertAdmin->execute([
                'username' => 'admin',
                'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
                'display_name' => 'Administrator',
                'role' => 'admin',
            ]);

            $adminId = (int) $pdo->lastInsertId();
        }

        if ($adminId) {
            $pageIds = $pdo->query('SELECT id FROM pages')->fetchAll(\PDO::FETCH_COLUMN);
            $insertPermission = $pdo->prepare(
                'INSERT IGNORE INTO user_page_permissions (user_id, page_id) VALUES (:user_id, :page_id)'
            );

            foreach ($pageIds as $pageId) {
                $insertPermission->execute([
                    'user_id' => $adminId,
                    'page_id' => $pageId,
                ]);
            }
        }
    }
}
