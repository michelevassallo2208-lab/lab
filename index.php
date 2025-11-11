<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';

if (Auth::user()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Inserisci sia l\'utente che la password.';
    } elseif (Auth::login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Credenziali non valide. Riprova con admin / admin oppure contatta l\'amministratore.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Portal | Accesso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="noise-overlay"></div>
    <div class="login-wrapper">
        <section class="login-card">
            <div class="logo-placeholder">
                <img src="logo.png" alt="Logo" onerror="this.style.display='none'">
            </div>
            <h1>Vision Portal</h1>
            <p class="tagline">Accedi per entrare nella nuova esperienza gestionale futuristica.</p>

            <?php if ($error): ?>
                <div class="error-message">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="form-group">
                    <label for="username">Utente</label>
                    <input type="text" id="username" name="username" placeholder="admin" autocomplete="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••" autocomplete="current-password" required>
                </div>
                <div class="button-wrapper">
                    <button type="submit">Accedi</button>
                    <div class="glow-border"></div>
                </div>
            </form>

            <div class="meta-info">
                <span>Accesso demo: <strong>admin / admin</strong></span>
                <span>Sicurezza avanzata e UI immersiva</span>
            </div>
        </section>
    </div>
</body>
</html>
