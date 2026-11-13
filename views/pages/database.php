<?php
$tableInfo = [
    ['users', 'Login identity, role, account status, and password hash.'],
    ['students', 'Student ID, course, year, and linked user account.'],
    ['faculty', 'Department, designation, and linked user account.'],
    ['rooms', 'Hostel room, building, capacity, and current status.'],
    ['allocations', 'Student-room assignments and their active/history state.'],
    ['complaints', 'Student requests, descriptions, status, and timestamps.'],
    ['notices', 'Published campus messages and audience.'],
];
$databaseTables = [];
foreach ($tableInfo as [$tableName, $description]) {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM "' . $tableName . '"')->fetchColumn();
    $databaseTables[] = ['name' => $tableName, 'description' => $description, 'count' => $count];
}
?>
<section class="page-heading-row"><div><span class="eyebrow">YOUR LOCAL DATA, CONNECTED</span><h1>Database & backup<span class="heading-dot">.</span></h1><p>Review the app's tables and download a complete local database backup.</p></div><span class="report-live"><i></i> Connected</span></section>
<section class="database-hero"><div class="database-hero-copy"><span class="eyebrow eyebrow-light">LOCAL SQLITE DATABASE</span><h2>Campus records,<br>in one place.</h2><p>CampusHub reads and writes to this database whenever you use its forms. Changes are saved automatically on this computer.</p><div class="database-location"><span>⌂</span><code>storage/campushub.sqlite</code></div></div><div class="database-hero-art" aria-hidden="true"><div class="db-layer db-layer-back"></div><div class="db-layer db-layer-mid"></div><div class="db-layer db-layer-front"><span>▤</span><b>SQLite</b><small>LOCAL · PRIVATE</small></div><span class="db-spark db-spark-one">✦</span><span class="db-spark db-spark-two">✳</span></div></section>
<section class="panel table-panel database-table-panel"><div class="panel-heading"><div><span class="eyebrow">LIVE DATABASE CONTENTS</span><h2>Application tables</h2></div><span class="table-caption"><?= count($databaseTables) ?> tables</span></div><div class="database-table-grid"><?php foreach ($databaseTables as $table): ?><article class="database-table-card"><span class="db-table-icon">▤</span><div><b><?= e($table['name']) ?></b><p><?= e($table['description']) ?></p></div><span class="db-row-count"><?= number_format($table['count']) ?><small>rows</small></span></article><?php endforeach; ?></div></section>
<section class="database-tools-grid"><article class="panel database-tool-card"><span class="database-tool-icon">↓</span><div><span class="eyebrow">TAKE YOUR DATA WITH YOU</span><h2>Download a backup</h2><p>Download a consistent SQLite copy of the current application database. Keep the file somewhere safe before making major changes.</p><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="download_database"><button class="button button-primary" type="submit">Download database <span>↓</span></button></form></div></article><article class="panel database-tool-card"><span class="database-tool-icon database-tool-icon-soft">↗</span><div><span class="eyebrow">BROWSE TABLES DIRECTLY</span><h2>Open in a SQLite viewer</h2><p>Stop the local server, then open <code>storage/campushub.sqlite</code> with a SQLite viewer such as DB Browser for SQLite. Reopen CampusHub afterward to see your changes.</p><a class="text-link" href="https://sqlitebrowser.org/" target="_blank" rel="noopener noreferrer">DB Browser for SQLite <span>↗</span></a></div></article></section>
<div class="report-footnote"><span>✦</span> Avoid editing the database directly while the app is running. Always make a backup first; changing table structure manually can prevent CampusHub from starting.</div>
