<?php
$audience = match ($role) { 'student' => 'Students', 'faculty' => 'Faculty', 'warden' => 'Hostel', default => 'Everyone' };
if ($role === 'admin') {
    $notices = $pdo->query(
        'SELECT notices.*, users.full_name AS author FROM notices JOIN users ON users.id = notices.created_by
         ORDER BY notices.published_at DESC'
    )->fetchAll();
} else {
    $statement = $pdo->prepare(
        "SELECT notices.*, users.full_name AS author FROM notices JOIN users ON users.id = notices.created_by
         WHERE notices.audience IN ('Everyone', ?) ORDER BY notices.published_at DESC"
    );
    $statement->execute([$audience]);
    $notices = $statement->fetchAll();
}
?>
<section class="page-heading-row"><div><span class="eyebrow">GOOD THINGS TO KNOW</span><h1>Campus notices<span class="heading-dot">.</span></h1><p>Updates and information from your campus community.</p></div><span class="count-chip"><?= count($notices) ?> notices</span></section>
<?php if ($role === 'admin'): ?><section class="panel form-panel notice-compose"><div class="panel-heading"><div><span class="eyebrow">SHARE WITH YOUR COMMUNITY</span><h2>Publish a notice</h2></div><span class="notice-compose-icon">✦</span></div><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_notice"><input type="hidden" name="return_to" value="notices"><label class="form-field"><span>Notice title</span><input name="title" maxlength="160" required placeholder="A clear, friendly headline"></label><label class="form-field"><span>Who should see this?</span><select name="audience"><option>Everyone</option><option>Students</option><option>Faculty</option><option>Hostel</option></select></label><label class="form-field field-full"><span>Your message</span><textarea name="content" rows="3" required maxlength="3000" placeholder="Share the details your community needs to know…"></textarea></label><div class="form-submit-row"><span>Keep it clear, useful, and kind.</span><button class="button button-primary" type="submit">Publish notice <span>→</span></button></div></form></section><?php endif; ?>
<section class="notice-board"><?php foreach ($notices as $index => $notice): ?><article class="notice-card"><div class="notice-card-top"><span class="notice-category"><i class="notice-dot notice-dot-<?= ($index % 3) + 1 ?>"></i><?= e($notice['audience']) ?></span><span><?= e(date('F j, Y', strtotime($notice['published_at']))) ?></span></div><h2><?= e($notice['title']) ?></h2><p><?= nl2br(e($notice['content'])) ?></p><div class="notice-card-footer"><span class="author-avatar"><?= e(strtoupper(substr($notice['author'], 0, 1))) ?></span><span>Shared by <b><?= e($notice['author']) ?></b></span><span class="notice-card-sparkle">✦</span></div></article><?php endforeach; ?><?php if (!$notices): ?><div class="panel empty-state"><span class="empty-art">✦</span><b>Clear skies for now</b><p>New campus notices will appear here.</p></div><?php endif; ?></section>
