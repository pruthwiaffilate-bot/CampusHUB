<?php
$fullName = (string) $user['full_name'];
$firstName = explode(' ', $fullName)[0];
$initials = strtoupper(substr($fullName, 0, 1));
$viewIcons = [
    'grid' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>',
    'users' => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'faculty' => '<svg viewBox="0 0 24 24"><path d="M12 3 2 8l10 5 10-5-10-5Z"/><path d="M6 10v6c3.5 3 8.5 3 12 0v-6"/><path d="M22 8v6"/></svg>',
    'building' => '<svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V5l7-3 7 3v16"/><path d="M9 9h.01M15 9h.01M9 13h.01M15 13h.01M10 21v-4h4v4"/></svg>',
    'bed' => '<svg viewBox="0 0 24 24"><path d="M3 7v14M3 17h18v4M21 12v5M3 12h18"/><path d="M7 12V8a2 2 0 0 1 2-2h3v6M12 8h5a4 4 0 0 1 4 4"/></svg>',
    'message' => '<svg viewBox="0 0 24 24"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/></svg>',
    'bell' => '<svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>',
    'chart' => '<svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-5 5"/></svg>',
    'database' => '<svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/></svg>',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8fc">
    <title><?= e($pageTitles[$view]) ?> · CampusHub</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="app-page">
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <a class="brand" href="index.php"><span class="brand-mark">C</span><span>Campus<span class="brand-accent">Hub</span></span></a>
        <div class="workspace-label">WORKSPACE</div>
        <nav class="main-nav" aria-label="Main navigation">
            <?php foreach ($visibleNav as [$key, $icon, $label]): ?>
                <a class="nav-link <?= $view === $key ? 'active' : '' ?>" href="index.php?view=<?= e($key) ?>">
                    <span class="nav-icon"><?= $viewIcons[$icon] ?></span><span><?= e($label) ?></span>
                    <?php if ($key === 'complaints' && $role !== 'student' && $unreadCount > 0): ?><span class="nav-count"><?= $unreadCount ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-spacer"></div>
        <div class="sidebar-help"><div class="help-icon">✳</div><b>Need a hand?</b><p>Our campus team is here to help you find your way.</p><a href="index.php?view=complaints">Get support <span>→</span></a><div class="help-orb help-orb-a"></div><div class="help-orb help-orb-b"></div></div>
        <div class="sidebar-bottom"><span class="secure-mark">✦</span><span>Made for campus life</span><span class="version-tag">v1.0</span></div>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <button type="button" class="mobile-menu" data-menu-toggle aria-label="Open menu"><span></span><span></span><span></span></button>
            <div class="breadcrumb"><span>CampusHub</span><i>/</i><b><?= e($pageTitles[$view]) ?></b></div>
            <div class="topbar-actions">
                <a class="top-icon" href="index.php?view=notices" aria-label="Notices"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><?php if ($unreadCount > 0 && $role !== 'student'): ?><span class="top-notification-dot"></span><?php endif; ?></a>
                <div class="top-divider"></div>
                <div class="user-menu"><span class="user-avatar"><?= e($initials) ?></span><span class="user-meta"><b><?= e($fullName) ?></b><small><?= e(user_role_label($role)) ?></small></span><span class="user-chevron">⌄</span>
                    <div class="user-dropdown"><a class="user-dropdown-link" href="index.php?view=profile">My profile <span>↗</span></a><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="logout"><button type="submit">Sign out <span>↗</span></button></form></div>
                </div>
            </div>
        </header>
        <?php if ($flashMessage): ?><div class="toast alert-<?= e($flashMessage['type']) ?>" role="status"><span><?= $flashMessage['type'] === 'error' ? '!' : '✓' ?></span><?= e($flashMessage['message']) ?><button type="button" aria-label="Dismiss" data-dismiss>×</button></div><?php endif; ?>
        <div class="page-content">
            <?php require __DIR__ . '/pages/' . $view . '.php'; ?>
        </div>
        <footer class="app-footer"><span>© <?= date('Y') ?> CampusHub</span><span>Made with care for your campus <i>✦</i></span></footer>
    </main>
</div>
<div class="mobile-overlay" data-mobile-overlay></div>
<script src="assets/app.js"></script>
</body>
</html>
