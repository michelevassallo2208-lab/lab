-- Schema Vision Portal
CREATE DATABASE IF NOT EXISTS `vision_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vision_portal`;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_page_permissions (
    user_id INT UNSIGNED NOT NULL,
    page_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, page_id),
    CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_permissions_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO pages (slug, label) VALUES
    ('dashboard', 'Dashboard'),
    ('analytics', 'Analytics'),
    ('team', 'Team'),
    ('settings', 'Impostazioni'),
    ('user-management', 'Gestione utenze')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO users (username, password_hash, display_name, role)
VALUES ('admin', '$2y$12$nsz1UhJ0AKVtKkwM6jsatu10w9GGBOr59hw1LmTe48W5Q.21/PytW', 'Administrator', 'admin')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO user_page_permissions (user_id, page_id)
SELECT u.id, p.id
FROM users u
CROSS JOIN pages p
WHERE u.username = 'admin'
ON DUPLICATE KEY UPDATE user_id = user_id;
