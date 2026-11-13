<?php
$students = $pdo->query(
    'SELECT students.*, users.full_name, users.username,
     (SELECT rooms.room_number FROM allocations JOIN rooms ON rooms.id = allocations.room_id
      WHERE allocations.student_id = students.id AND allocations.status = \'Active\' LIMIT 1) AS room_number
     FROM students JOIN users ON users.id = students.user_id ORDER BY users.full_name'
)->fetchAll();
?>
<section class="page-heading-row"><div><span class="eyebrow">YOUR CAMPUS DIRECTORY</span><h1>Students<span class="heading-dot">.</span></h1><p>Keep student profiles and campus details in one easy place.</p></div><span class="count-chip"><?= count($students) ?> profiles</span></section>
<?php if ($role === 'admin'): ?>
<section class="panel form-panel"><div class="panel-heading"><div><span class="eyebrow">DIRECTORY MANAGEMENT</span><h2>Add a student</h2></div><span class="form-step">01 <i>/</i> profile</span></div>
<form method="post" class="form-grid">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_student"><input type="hidden" name="return_to" value="students">
    <label class="form-field"><span>Full name</span><input name="full_name" required maxlength="120" placeholder="e.g. Maya Patel"></label>
    <label class="form-field"><span>Student ID / PRN</span><input name="prn" required maxlength="40" placeholder="e.g. CH2025002"></label>
    <label class="form-field"><span>Username</span><input name="username" required maxlength="80" placeholder="Choose a login name"></label>
    <label class="form-field"><span>Temporary password</span><input name="password" type="password" required minlength="8" placeholder="At least 8 characters"></label>
    <label class="form-field"><span>Course</span><input name="course" required maxlength="100" placeholder="e.g. Computer Science"></label>
    <label class="form-field"><span>Year</span><select name="year" required><option value="">Select academic year</option><?php foreach (['Year 1', 'Year 2', 'Year 3', 'Year 4', 'Postgraduate'] as $year): ?><option><?= e($year) ?></option><?php endforeach; ?></select></label>
    <div class="form-submit-row"><span>New student accounts receive the password you set here.</span><button class="button button-primary" type="submit">Add student <span>→</span></button></div>
</form></section>
<?php endif; ?>
<section class="panel table-panel"><div class="panel-heading"><div><span class="eyebrow">PEOPLE & PROFILES</span><h2>Student directory</h2></div><span class="table-caption"><?= count($students) ?> total</span></div>
<?php if ($students): ?><div class="table-scroll"><table><thead><tr><th>STUDENT</th><th>STUDENT ID</th><th>COURSE</th><th>YEAR</th><th>HOSTEL ROOM</th></tr></thead><tbody>
<?php foreach ($students as $index => $student): ?><tr><td><span class="person-cell"><span class="person-avatar person-color-<?= $index % 5 ?>"><?= e(strtoupper(substr($student['full_name'], 0, 1))) ?></span><span><b><?= e($student['full_name']) ?></b><small>@<?= e($student['username']) ?></small></span></span></td><td><span class="id-code"><?= e($student['prn']) ?></span></td><td><?= e($student['course']) ?></td><td><?= e($student['year']) ?></td><td><?= $student['room_number'] ? '<span class="room-tag">' . e($student['room_number']) . '</span>' : '<span class="muted">Not allocated</span>' ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php else: ?><div class="empty-state"><span class="empty-art">◉</span><b>Your directory is ready</b><p>Add the first student profile to get started.</p></div><?php endif; ?>
</section>
