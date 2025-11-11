<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';

Auth::requireLogin();

$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Portal | Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="assets/js/dashboard.js" defer></script>
</head>
<body>
    <header class="app-header">
        <div class="branding">
            <div class="logo">
                <img src="logo.png" alt="Logo" style="width: 60%; height: 60%; object-fit: contain;" onerror="this.style.display='none'">
            </div>
            <h1>Vision Portal</h1>
        </div>
        <div class="user-menu">
            <span>Benvenuto, <strong><?= htmlspecialchars($user['display_name'] ?? $user['username'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
            <a class="logout-link" href="logout.php">Esci</a>
        </div>
    </header>

    <aside class="sidebar">
        <div>
            <p class="section-title">Navigazione</p>
            <nav class="nav-links">
                <a href="dashboard.php" class="active">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7m-9 3v8m-4 0h8m-4-8v8" />
                    </svg>
                    <span>Overview</span>
                </a>
                <a href="#analytics">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l3-3 2 2 4-4" />
                    </svg>
                    <span>Analytics</span>
                </a>
                <a href="#team">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1m-6 5v-1a4 4 0 014-4h0a4 4 0 014 4v1m-6 0H3v-1a4 4 0 014-4h1m0 5v-1a4 4 0 00-4-4H3m9-4a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 100-8 4 4 0 000 8z" />
                    </svg>
                    <span>Team</span>
                </a>
                <a href="#settings">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.066c1.543-.89 3.31.878 2.42 2.42a1.724 1.724 0 001.065 2.592c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.591c.89 1.543-.877 3.31-2.42 2.42a1.724 1.724 0 00-2.592 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.591-1.066c-1.543.89-3.31-.877-2.42-2.42a1.724 1.724 0 00-1.065-2.592c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.591c-.89-1.543.877-3.31 2.42-2.42.996.575 2.28.021 2.591-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Impostazioni</span>
                </a>
            </nav>
        </div>
        <div>
            <p class="section-title">Prossime azioni</p>
            <div class="nav-links">
                <a href="#create">Crea nuova pagina</a>
                <a href="#import">Importa dati</a>
                <a href="#support">Supporto rapido</a>
            </div>
        </div>
    </aside>

    <main class="dashboard-content">
        <section class="cards-grid">
            <article class="card">
                <h3>Benvenuto nella tua dashboard</h3>
                <p>Utilizza il menu laterale per navigare tra le sezioni. Qui potremo inserire widget e statistiche personalizzate.</p>
            </article>
            <article class="card">
                <h3>Stato sistemi</h3>
                <p>Tutti i servizi sono operativi. Gli aggiornamenti verranno mostrati in tempo reale.</p>
            </article>
            <article class="card">
                <h3>Attività recenti</h3>
                <p>Presto visualizzeremo le ultime operazioni del team per mantenere il controllo delle attività.</p>
            </article>
            <article class="card">
                <h3>Piano di sviluppo</h3>
                <p>In questa sezione potremo inserire roadmap, integrazioni e note per il team di progetto.</p>
            </article>
        </section>

        <section id="analytics" class="card">
            <h3>Analytics</h3>
            <p>Spazio dedicato a grafici, KPI e reportistica avanzata.</p>
        </section>

        <section id="team" class="card">
            <h3>Team</h3>
            <p>Qui potremo gestire i membri del team, ruoli e permessi.</p>
        </section>

        <section id="settings" class="card">
            <h3>Impostazioni</h3>
            <p>Personalizza il portale con le opzioni che verranno implementate prossimamente.</p>
        </section>
    </main>
</body>
</html>
