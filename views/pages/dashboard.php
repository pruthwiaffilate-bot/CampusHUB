<?php
$stats = [
    ['label' => 'Students', 'value' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(), 'note' => 'In your campus directory', 'icon' => '◉', 'tone' => 'violet'],
    ['label' => 'Faculty members', 'value' => (int) $pdo->query('SELECT COUNT(*) FROM faculty')->fetchColumn(), 'note' => 'Across all departments', 'icon' => '✳', 'tone' => 'blue'],
    ['label' => 'Active room spaces', 'value' => (int) $pdo->query("SELECT COALESCE(SUM(capacity), 0) FROM rooms WHERE status = 'Active'")->fetchColumn(), 'note' => 'Across active rooms', 'icon' => '⌂', 'tone' => 'green'],
    ['label' => 'Open requests', 'value' => (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Submitted', 'In Progress')")->fetchColumn(), 'note' => 'Need a staff response', 'icon' => '↗', 'tone' => 'amber'],
];
$audience = match ($role) { 'student' => 'Students', 'faculty' => 'Faculty', 'warden' => 'Hostel', default => 'Everyone' };
if ($role === 'admin') {
    $recentNotices = $pdo->query(
        'SELECT notices.*, users.full_name AS author FROM notices JOIN users ON users.id = notices.created_by
         ORDER BY notices.published_at DESC LIMIT 3'
    )->fetchAll();
} else {
    $noticeQuery = $pdo->prepare(
        "SELECT notices.*, users.full_name AS author FROM notices JOIN users ON users.id = notices.created_by
         WHERE notices.audience IN ('Everyone', ?) ORDER BY notices.published_at DESC LIMIT 3"
    );
    $noticeQuery->execute([$audience]);
    $recentNotices = $noticeQuery->fetchAll();
}
$recentComplaints = [];
if (in_array($role, ['admin', 'warden'], true)) {
    $recentComplaints = $pdo->query(
        'SELECT complaints.*, users.full_name AS student_name FROM complaints
         JOIN students ON students.id = complaints.student_id
         JOIN users ON users.id = students.user_id ORDER BY complaints.updated_at DESC LIMIT 4'
    )->fetchAll();
} elseif ($role === 'student') {
    $statement = $pdo->prepare(
        'SELECT complaints.* FROM complaints JOIN students ON students.id = complaints.student_id
         WHERE students.user_id = ? ORDER BY complaints.updated_at DESC LIMIT 4'
    );
    $statement->execute([$user['id']]);
    $recentComplaints = $statement->fetchAll();
}
$greeting = match (true) {
    (int) date('G') < 12 => 'Good morning',
    (int) date('G') < 17 => 'Good afternoon',
    default => 'Good evening',
};
?>
<section class="welcome-row">
    <div><span class="eyebrow">HERE'S YOUR CAMPUS AT A GLANCE</span><h1><?= e($greeting) ?>, <?= e($firstName) ?><span class="heading-dot">.</span></h1><p>Your campus, all in one place. Here's what’s happening today.</p></div>
    <div class="date-pill"><span>◷</span><?= e(date('l, F j')) ?></div>
</section>
<section class="stats-grid">
    <?php foreach ($stats as $stat): ?>
        <article class="stat-card"><div class="stat-top"><span><?= e($stat['label']) ?></span><span class="stat-icon tone-<?= e($stat['tone']) ?>"><?= e($stat['icon']) ?></span></div><div class="stat-value"><?= number_format($stat['value']) ?></div><div class="stat-foot"><span class="stat-foot-dot tone-<?= e($stat['tone']) ?>"></span><?= e($stat['note']) ?></div><div class="stat-decoration"></div></article>
    <?php endforeach; ?>
</section>
<div class="dashboard-grid">
    <section class="panel activity-panel">
        <div class="panel-heading"><div><span class="eyebrow">STAY IN THE LOOP</span><h2>Recent requests</h2></div><a class="text-link" href="index.php?view=complaints">View all <span>→</span></a></div>
        <?php if ($recentComplaints): ?>
            <div class="request-list">
                <?php foreach ($recentComplaints as $complaint): ?>
                    <a class="request-item" href="index.php?view=complaints"><span class="request-graphic"><?= $complaint['status'] === 'Resolved' ? '✓' : '✳' ?></span><span class="request-copy"><b><?= e($complaint['title']) ?></b><small><?= isset($complaint['student_name']) ? e($complaint['student_name']) . ' · ' : '' ?><?= e(date('M j', strtotime($complaint['created_at']))) ?></small></span><span class="status-badge status-<?= e(strtolower(str_replace(' ', '-', $complaint['status']))) ?>"><?= e($complaint['status']) ?></span><span class="request-arrow">→</span></a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state compact"><span class="empty-art">✳</span><b>It’s wonderfully quiet</b><p>No requests here just yet.</p></div>
        <?php endif; ?>
        <?php if ($role === 'student'): ?><a class="button button-light button-wide" href="index.php?view=complaints">Create a request <span>＋</span></a><?php endif; ?>
    </section>
    <section class="panel notice-panel">
        <div class="panel-heading"><div><span class="eyebrow">THE LATEST</span><h2>Campus notices</h2></div><a class="icon-link" href="index.php?view=notices" aria-label="View all notices">↗</a></div>
        <?php if ($recentNotices): ?>
            <div class="notice-list">
                <?php foreach ($recentNotices as $index => $notice): ?>
                    <article class="notice-item"><span class="notice-dot notice-dot-<?= $index + 1 ?>"></span><div><div class="notice-meta"><?= e($notice['audience']) ?> <i>·</i> <?= e(date('M j', strtotime($notice['published_at']))) ?></div><h3><?= e($notice['title']) ?></h3><p><?= e($notice['content']) ?></p></div></article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state compact"><span class="empty-art">✦</span><b>No notices yet</b><p>New updates will show up here.</p></div>
        <?php endif; ?>
    </section>
</div>
<?php if (in_array($role, ['admin', 'warden'], true)): ?>
<section class="quick-actions">
    <div><span class="eyebrow">MAKE IT HAPPEN</span><h2>Quick actions</h2></div>
    <div class="quick-action-list">
        <?php if ($role === 'admin'): ?><a href="index.php?view=students"><span class="quick-icon quick-purple">＋</span><span><b>Add a student</b><small>Grow your campus directory</small></span><i>→</i></a><?php endif; ?>
        <?php if (in_array($role, ['admin', 'warden'], true)): ?><a href="index.php?view=rooms"><span class="quick-icon quick-blue">⌂</span><span><b>Manage rooms</b><small>See availability & capacity</small></span><i>→</i></a><a href="index.php?view=allocations"><span class="quick-icon quick-green">↗</span><span><b>Room allocations</b><small>Assign a student to a room</small></span><i>→</i></a><?php endif; ?>
        <?php if ($role === 'admin'): ?><a href="index.php?view=notices"><span class="quick-icon quick-amber">✳</span><span><b>Publish a notice</b><small>Share an update with campus</small></span><i>→</i></a><?php endif; ?>
    </div>
</section>
<?php endif; ?>
