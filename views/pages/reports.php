<?php
$reportStats = [
    ['label' => 'Student profiles', 'value' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(), 'icon' => '◉', 'tone' => 'violet'],
    ['label' => 'Faculty members', 'value' => (int) $pdo->query('SELECT COUNT(*) FROM faculty')->fetchColumn(), 'icon' => '✳', 'tone' => 'blue'],
    ['label' => 'Active allocations', 'value' => (int) $pdo->query("SELECT COUNT(*) FROM allocations WHERE status = 'Active'")->fetchColumn(), 'icon' => '⌂', 'tone' => 'green'],
    ['label' => 'Open complaints', 'value' => (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Submitted', 'In Progress')")->fetchColumn(), 'icon' => '↗', 'tone' => 'amber'],
];
$roomReport = $pdo->query(
    "SELECT rooms.*, (SELECT COUNT(*) FROM allocations WHERE allocations.room_id = rooms.id AND allocations.status = 'Active') AS occupied
     FROM rooms ORDER BY rooms.building, rooms.room_number"
)->fetchAll();
$complaintSummary = $pdo->query('SELECT status, COUNT(*) AS total FROM complaints GROUP BY status')->fetchAll();
$totalComplaints = max(1, array_sum(array_column($complaintSummary, 'total')));
$complaintColors = ['Submitted' => 'bar-coral', 'In Progress' => 'bar-blue', 'Resolved' => 'bar-green', 'Closed' => 'bar-lilac'];
?>
<section class="page-heading-row"><div><span class="eyebrow">THE BIGGER PICTURE</span><h1>Reports & insights<span class="heading-dot">.</span></h1><p>A live snapshot of the records currently in CampusHub.</p></div><span class="report-live"><i></i> Live data</span></section>
<section class="report-stats"><?php foreach ($reportStats as $stat): ?><article class="report-stat-card"><span class="report-stat-icon tone-<?= e($stat['tone']) ?>"><?= e($stat['icon']) ?></span><div><span><?= e($stat['label']) ?></span><b><?= number_format($stat['value']) ?></b></div><span class="report-sparkle">✦</span></article><?php endforeach; ?></section>
<div class="reports-grid"><section class="panel"><div class="panel-heading"><div><span class="eyebrow">HOSTEL OVERVIEW</span><h2>Room occupancy</h2></div><span class="table-caption"><?= count($roomReport) ?> rooms</span></div><?php if ($roomReport): ?><div class="occupancy-report"><?php foreach ($roomReport as $room): $ratio = (int) round(((int) $room['occupied'] / max(1, (int) $room['capacity'])) * 100); ?><div class="occupancy-report-row"><div class="occupancy-report-head"><span><b><?= e($room['room_number']) ?></b><small><?= e($room['building']) ?></small></span><span><?= (int) $room['occupied'] ?> / <?= (int) $room['capacity'] ?><small>beds</small></span></div><div class="occupancy-track"><span style="width: <?= min(100, $ratio) ?>%"></span></div></div><?php endforeach; ?></div><?php else: ?><div class="empty-state compact"><b>No room data yet</b></div><?php endif; ?></section>
<section class="panel"><div class="panel-heading"><div><span class="eyebrow">STUDENT SUPPORT</span><h2>Request status</h2></div><span class="report-donut">✳</span></div><?php if ($complaintSummary): ?><div class="complaint-report"><?php foreach (['Submitted', 'In Progress', 'Resolved', 'Closed'] as $status): $count = 0; foreach ($complaintSummary as $entry) { if ($entry['status'] === $status) { $count = (int) $entry['total']; break; } } ?><div class="complaint-report-row"><span><i class="report-legend <?= e($complaintColors[$status]) ?>"></i><?= e($status) ?></span><b><?= $count ?></b><div class="report-bar"><i class="<?= e($complaintColors[$status]) ?>" style="width: <?= (int) round(($count / $totalComplaints) * 100) ?>%"></i></div></div><?php endforeach; ?></div><div class="report-note">Status summaries reflect complaint records currently in the system.</div><?php else: ?><div class="empty-state compact"><b>No requests to report</b><p>Complaint insights will appear here.</p></div><?php endif; ?></section></div>
<div class="report-footnote"><span>✦</span> These summaries are generated from live records and are visible only to authorized staff.</div>
