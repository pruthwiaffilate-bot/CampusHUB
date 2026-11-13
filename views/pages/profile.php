<?php
$profileDetails = null;
if ($role === 'student') {
    $statement = $pdo->prepare('SELECT prn, course, year FROM students WHERE user_id = ?');
    $statement->execute([$user['id']]);
    $profileDetails = $statement->fetch() ?: null;
} elseif ($role === 'faculty') {
    $statement = $pdo->prepare('SELECT department, designation FROM faculty WHERE user_id = ?');
    $statement->execute([$user['id']]);
    $profileDetails = $statement->fetch() ?: null;
}
?>
<section class="page-heading-row"><div><span class="eyebrow">YOUR ACCOUNT, YOUR DETAILS</span><h1>My profile<span class="heading-dot">.</span></h1><p>Review your account information and keep your sign-in secure.</p></div><span class="count-chip"><?= e(user_role_label($role)) ?></span></section>
<div class="profile-layout"><section class="panel profile-card"><span class="profile-avatar"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></span><span class="eyebrow">CAMPUSHUB ACCOUNT</span><h2><?= e($user['full_name']) ?></h2><p>@<?= e($user['username']) ?></p><span class="profile-role"><?= e(user_role_label($role)) ?></span><?php if ($profileDetails): ?><div class="profile-details"><?php foreach ($profileDetails as $label => $value): ?><div><span><?= e(ucwords(str_replace('_', ' ', $label))) ?></span><b><?= e($value) ?></b></div><?php endforeach; ?></div><?php endif; ?></section>
<section class="panel form-panel profile-edit-panel"><div class="panel-heading"><div><span class="eyebrow">ACCOUNT SETTINGS</span><h2>Update your profile</h2></div><span class="profile-lock">⌑</span></div><form method="post" class="form-grid profile-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_profile"><input type="hidden" name="return_to" value="profile"><label class="form-field field-full"><span>Display name</span><input name="full_name" required maxlength="120" value="<?= e($user['full_name']) ?>"></label><label class="form-field field-full"><span>Current password <i class="required-mark">*</i></span><input name="current_password" type="password" required autocomplete="current-password" placeholder="Confirm your current password"></label><label class="form-field field-full"><span>New password <small>(optional)</small></span><input name="new_password" type="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep your password"></label><div class="form-submit-row"><span>Your current password is required to save account changes.</span><button class="button button-primary" type="submit">Save profile <span>→</span></button></div></form></section></div>
