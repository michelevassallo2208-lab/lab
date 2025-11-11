<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

Auth::requireLogin();
Auth::requireRole('admin');

$pdo = Database::getConnection();

$pages = $pdo->query('SELECT id, slug, label FROM pages ORDER BY label')->fetchAll();

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $selectedPages = $_POST['pages'] ?? [];
        if (!is_array($selectedPages)) {
            $selectedPages = [$selectedPages];
        }
        $selectedPages = array_map('strval', $selectedPages);

        if ($username === '' || $displayName === '' || $password === '') {
            $errors[] = 'Compila tutti i campi obbligatori per creare un utente.';
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]{3,}$/', $username)) {
            $errors[] = 'L\'utente deve avere almeno 3 caratteri e contenere solo lettere, numeri o ._-';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Le password non coincidono.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $statement = $pdo->prepare('INSERT INTO users (username, password_hash, display_name, role) VALUES (:username, :password_hash, :display_name, :role)');
                $statement->execute([
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'display_name' => $displayName,
                    'role' => $role,
                ]);

                $newUserId = (int) $pdo->lastInsertId();

                $permissionStatement = $pdo->prepare('INSERT INTO user_page_permissions (user_id, page_id) VALUES (:user_id, :page_id)');

                if ($role === 'admin') {
                    foreach ($pages as $page) {
                        $permissionStatement->execute([
                            'user_id' => $newUserId,
                            'page_id' => $page['id'],
                        ]);
                    }
                } else {
                    $selectedPages = array_unique(array_merge($selectedPages, ['dashboard']));

                    foreach ($pages as $page) {
                        if (in_array($page['slug'], $selectedPages, true)) {
                            $permissionStatement->execute([
                                'user_id' => $newUserId,
                                'page_id' => $page['id'],
                            ]);
                        }
                    }
                }

                $pdo->commit();
                $success = 'Utente creato con successo!';
            } catch (\PDOException $exception) {
                $pdo->rollBack();
                $errors[] = 'Errore durante la creazione dell\'utente: ' . $exception->getMessage();
            }
        }
    } elseif ($action === 'update') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
        $selectedPages = $_POST['pages'] ?? [];
        if (!is_array($selectedPages)) {
            $selectedPages = [$selectedPages];
        }
        $selectedPages = array_map('strval', $selectedPages);

        if ($userId > 0) {
            try {
                $pdo->beginTransaction();

                $updateRole = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
                $updateRole->execute([
                    'role' => $role,
                    'id' => $userId,
                ]);

                $deletePermissions = $pdo->prepare('DELETE FROM user_page_permissions WHERE user_id = :user_id');
                $deletePermissions->execute(['user_id' => $userId]);

                $permissionStatement = $pdo->prepare('INSERT INTO user_page_permissions (user_id, page_id) VALUES (:user_id, :page_id)');

                if ($role === 'admin') {
                    foreach ($pages as $page) {
                        $permissionStatement->execute([
                            'user_id' => $userId,
                            'page_id' => $page['id'],
                        ]);
                    }
                } else {
                    $selectedPages = array_unique(array_merge($selectedPages, ['dashboard']));

                    foreach ($pages as $page) {
                        if (in_array($page['slug'], $selectedPages, true)) {
                            $permissionStatement->execute([
                                'user_id' => $userId,
                                'page_id' => $page['id'],
                            ]);
                        }
                    }
                }

                $pdo->commit();
                $success = 'Permessi aggiornati con successo!';

                if (Auth::user()['id'] === $userId) {
                    $_SESSION['user']['role'] = $role;
                    Auth::refreshPermissions();
                }
            } catch (\PDOException $exception) {
                $pdo->rollBack();
                $errors[] = 'Impossibile aggiornare i permessi: ' . $exception->getMessage();
            }
        }
    }
}

$users = $pdo->query(
    'SELECT u.id, u.username, u.display_name, u.role, GROUP_CONCAT(p.slug ORDER BY p.slug) AS granted
     FROM users u
     LEFT JOIN user_page_permissions upp ON upp.user_id = u.id
     LEFT JOIN pages p ON p.id = upp.page_id
     GROUP BY u.id
     ORDER BY u.created_at DESC'
)->fetchAll();

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Portal | Gestione utenze</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/manage-users.css">
</head>
<body class="management-body">
<header class="app-header intense">
    <div class="branding">
        <div class="logo">
            <img src="logo.png" alt="Logo" onerror="this.style.display='none'">
        </div>
        <h1>Vision Portal</h1>
    </div>
    <div class="user-menu">
        <span>Area amministrazione</span>
        <a class="logout-link" href="dashboard.php">Torna alla dashboard</a>
    </div>
</header>

<main class="management-layout">
    <section class="neon-card">
        <div class="card-header">
            <div>
                <h2>Nuova utenza</h2>
                <p>Configura accessi personalizzati con un tocco di luce neon.</p>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="feedback feedback-error">
                <ul>
                    <?php foreach ($errors as $message): ?>
                        <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif ($success): ?>
            <div class="feedback feedback-success">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="user-form">
            <input type="hidden" name="action" value="create">
            <div class="form-grid">
                <label>
                    <span>Username</span>
                    <input type="text" name="username" placeholder="utente.vision" required>
                </label>
                <label>
                    <span>Nome visibile</span>
                    <input type="text" name="display_name" placeholder="Nome completo" required>
                </label>
                <label>
                    <span>Password</span>
                    <input type="password" name="password" placeholder="••••••" required>
                </label>
                <label>
                    <span>Conferma password</span>
                    <input type="password" name="confirm_password" placeholder="••••••" required>
                </label>
                <label>
                    <span>Ruolo</span>
                    <select name="role">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </label>
            </div>

            <fieldset class="permissions">
                <legend>Pagine abilitate</legend>
                <p class="hint">Se scegli "Admin" le pagine verranno abilitate automaticamente.</p>
                <div class="chips">
                    <?php foreach ($pages as $page): ?>
                        <label class="chip">
                            <input type="checkbox" name="pages[]" value="<?= htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span><?= htmlspecialchars($page['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <button type="submit" class="primary-button">Crea utente</button>
        </form>
    </section>

    <section class="neon-card">
        <div class="card-header">
            <div>
                <h2>Utenze esistenti</h2>
                <p>Gestisci ruoli, pagine abilitate e mantieni il controllo.</p>
            </div>
        </div>

        <div class="users-grid">
            <?php foreach ($users as $user): ?>
                <form method="post" class="user-tile">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="<?= (int) $user['id']; ?>">
                    <header>
                        <h3><?= htmlspecialchars($user['display_name'] ?: $user['username'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <span class="username">@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </header>
                    <div class="role-select">
                        <label>Ruolo
                            <select name="role">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </label>
                    </div>
                    <?php $granted = $user['granted'] ? explode(',', $user['granted']) : []; ?>
                    <div class="permissions">
                        <span class="legend">Pagine abilitate</span>
                        <div class="chips">
                            <?php foreach ($pages as $page): ?>
                                <label class="chip">
                                    <input type="checkbox" name="pages[]" value="<?= htmlspecialchars($page['slug'], ENT_QUOTES, 'UTF-8'); ?>" <?= in_array($page['slug'], $granted, true) ? 'checked' : ''; ?>>
                                    <span><?= htmlspecialchars($page['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="secondary-button">Aggiorna</button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
</main>
</body>
</html>
