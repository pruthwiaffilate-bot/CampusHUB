<?php
$flashMessage = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f6f7fb">
    <title>Sign in · CampusHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
<main class="login-shell">
    <section class="login-story">
        <a class="brand brand-light" href="index.php"><span class="brand-mark">C</span><span>Campus<span class="brand-accent">Hub</span></span></a>
        <div class="story-copy">
            <span class="eyebrow eyebrow-light"><span class="live-dot"></span> YOUR CAMPUS, CONNECTED</span>
            <h1>One place for<br>everything <span>campus.</span></h1>
            <p>Stay in the loop, find your people, and get things done — all from one calm, connected space.</p>
        </div>
        <div class="story-art" aria-hidden="true">
            <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
            <div class="art-center"><span>CH</span></div>
            <div class="art-card art-card-one"><span class="art-icon art-purple">⌂</span><span><b>Room A-101</b><small>Your campus home</small></span><i class="art-check">✓</i></div>
            <div class="art-card art-card-two"><span class="art-icon art-coral">✳</span><span><b>You're all caught up</b><small>Campus notices · 2 new</small></span></div>
            <div class="art-sparkle sparkle-one">✦</div><div class="art-sparkle sparkle-two">✳</div>
        </div>
        <div class="story-footer"><span>Thoughtfully designed for campus life</span><span>01 — 04</span></div>
    </section>
    <section class="login-panel">
        <div class="login-top-note">New to CampusHub? <span>Ask your administrator for access</span></div>
        <div class="login-form-wrap">
            <div class="login-heading">
                <span class="eyebrow">YOUR CAMPUS IS WAITING</span>
                <h2>Welcome back<span class="heading-dot">.</span></h2>
                <p>Sign in to pick up right where you left off.</p>
            </div>
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
            <?php endif; ?>
            <form method="post" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="login">
                <label class="field-label" for="username">Username</label>
                <div class="input-wrap"><span class="input-icon">◎</span><input id="username" name="username" autocomplete="username" placeholder="Enter your username" required autofocus></div>
                <label class="field-label" for="password">Password</label>
                <div class="input-wrap"><span class="input-icon">⌑</span><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required><button class="password-toggle" type="button" aria-label="Show password" data-password-toggle>◉</button></div>
                <button class="button button-primary login-submit" type="submit">Sign in <span>→</span></button>
            </form>
            <div class="demo-box">
                <div class="demo-heading"><span class="demo-sparkle">✦</span><div><b>Take a quick look around</b><small>Use a demo account to explore</small></div></div>
                <div class="demo-accounts">
                    <button type="button" data-demo-user="admin" data-demo-password="admin123"><span class="demo-avatar avatar-violet">A</span><span>Admin</span><span class="demo-arrow">↗</span></button>
                    <button type="button" data-demo-user="student" data-demo-password="student123"><span class="demo-avatar avatar-blue">S</span><span>Student</span><span class="demo-arrow">↗</span></button>
                    <button type="button" data-demo-user="faculty" data-demo-password="faculty123"><span class="demo-avatar avatar-amber">F</span><span>Faculty</span><span class="demo-arrow">↗</span></button>
                    <button type="button" data-demo-user="warden" data-demo-password="warden123"><span class="demo-avatar avatar-green">W</span><span>Warden</span><span class="demo-arrow">↗</span></button>
                </div>
            </div>
            <div class="login-legal">By continuing, you agree to use CampusHub responsibly.<br><span>© <?= date('Y') ?> CampusHub · Made for campus life</span></div>
        </div>
    </section>
</main>
<script src="assets/app.js"></script>
</body>
</html>
