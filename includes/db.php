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
                role VARCHAR(20) NOT NULL DEFAULT "user",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $statement = $pdo->prepare('SELECT COUNT(*) AS count FROM users WHERE username = :username');
        $statement->execute(['username' => 'admin']);
        $exists = (int) $statement->fetchColumn();

        if ($exists === 0) {
            $insert = $pdo->prepare(
                'INSERT INTO users (username, password_hash, display_name, role) VALUES (:username, :password_hash, :display_name, :role)'
            );
            $insert->execute([
                'username' => 'admin',
                'password_hash' => password_hash('admin', PASSWORD_DEFAULT),
                'display_name' => 'Administrator',
                'role' => 'admin',
            ]);
        }
    }
}
