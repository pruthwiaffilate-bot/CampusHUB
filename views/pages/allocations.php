<?php
$allocationSql = 'SELECT allocations.*, users.full_name AS student_name, students.prn, rooms.room_number, rooms.building
    FROM allocations JOIN students ON students.id = allocations.student_id
    JOIN users ON users.id = students.user_id JOIN rooms ON rooms.id = allocations.room_id';
if ($role === 'student') {
    $statement = $pdo->prepare($allocationSql . ' WHERE students.user_id = ? ORDER BY allocations.created_at DESC');
    $statement->execute([$user['id']]);
    $allocations = $statement->fetchAll();
} else {
    $allocations = $pdo->query($allocationSql . ' ORDER BY allocations.created_at DESC')->fetchAll();
}
$eligibleStudents = $role === 'student' ? [] : $pdo->query(
    "SELECT students.id, users.full_name, students.prn FROM students JOIN users ON users.id = students.user_id
     WHERE NOT EXISTS (SELECT 1 FROM allocations WHERE allocations.student_id = students.id AND allocations.status = 'Active')
     ORDER BY users.full_name"
)->fetchAll();
$availableRooms = $role === 'student' ? [] : $pdo->query(
    "SELECT rooms.* FROM rooms WHERE rooms.status = 'Active'
     AND (SELECT COUNT(*) FROM allocations WHERE allocations.room_id = rooms.id AND allocations.status = 'Active') < rooms.capacity
     ORDER BY rooms.building, rooms.room_number"
)->fetchAll();
?>
<section class="page-heading-row"><div><span class="eyebrow">EVERY STUDENT, SETTLED IN</span><h1>Room allocations<span class="heading-dot">.</span></h1><p><?= $role === 'student' ? 'Your current and previous room assignments.' : 'Manage room assignments and keep occupancy in check.' ?></p></div><span class="count-chip"><?= count($allocations) ?> records</span></section>
<?php if (in_array($role, ['admin', 'warden'], true)): ?><section class="panel form-panel"><div class="panel-heading"><div><span class="eyebrow">A GOOD PLACE TO START</span><h2>Assign a room</h2></div><span class="form-step">01 <i>/</i> allocation</span></div>
<?php if ($eligibleStudents && $availableRooms): ?><form method="post" class="form-grid allocation-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_allocation"><input type="hidden" name="return_to" value="allocations"><label class="form-field"><span>Student</span><select name="student_id" required><option value="">Choose a student</option><?php foreach ($eligibleStudents as $student): ?><option value="<?= (int) $student['id'] ?>"><?= e($student['full_name']) ?> · <?= e($student['prn']) ?></option><?php endforeach; ?></select></label><label class="form-field"><span>Available room</span><select name="room_id" required><option value="">Choose a room</option><?php foreach ($availableRooms as $room): ?><option value="<?= (int) $room['id'] ?>"><?= e($room['room_number']) ?> · <?= e($room['building']) ?> · <?= (int) $room['capacity'] ?> beds</option><?php endforeach; ?></select></label><div class="form-submit-row"><span>Active room capacity is checked before saving.</span><button class="button button-primary" type="submit">Confirm allocation <span>→</span></button></div></form><?php else: ?><div class="inline-notice"><span>✦</span><?= !$eligibleStudents ? 'All students already have an active room allocation.' : 'There are no active rooms with available capacity.' ?></div><?php endif; ?></section><?php endif; ?>
<section class="panel table-panel"><div class="panel-heading"><div><span class="eyebrow">HOSTEL RECORDS</span><h2><?= $role === 'student' ? 'Your room history' : 'Allocation history' ?></h2></div></div>
<?php if ($allocations): ?><div class="table-scroll"><table><thead><tr><th>STUDENT</th><th>ROOM</th><th>BUILDING</th><th>START DATE</th><th>STATUS</th><?php if (in_array($role, ['admin', 'warden'], true)): ?><th></th><?php endif; ?></tr></thead><tbody><?php foreach ($allocations as $row): ?><tr><td><span class="person-cell"><span class="person-avatar person-color-1"><?= e(strtoupper(substr($row['student_name'], 0, 1))) ?></span><span><b><?= e($row['student_name']) ?></b><small><?= e($row['prn']) ?></small></span></span></td><td><span class="room-tag"><?= e($row['room_number']) ?></span></td><td><?= e($row['building']) ?></td><td><?= e(date('M j, Y', strtotime($row['start_date']))) ?></td><td><span class="status-badge <?= $row['status'] === 'Active' ? 'status-resolved' : 'status-closed' ?>"><?= e($row['status']) ?></span></td><?php if (in_array($role, ['admin', 'warden'], true)): ?><td><?php if ($row['status'] === 'Active'): ?><form method="post" class="inline-form" data-confirm="End this room allocation?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="end_allocation"><input type="hidden" name="allocation_id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="return_to" value="allocations"><button class="small-action" type="submit">End allocation</button></form><?php endif; ?></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><span class="empty-art">⌂</span><b>No room allocations yet</b><p><?= $role === 'student' ? 'Your room assignment will appear here.' : 'Once a student is assigned, the record will appear here.' ?></p></div><?php endif; ?></section>
