<?php
/**
 * Portal — Change Password View
 */

portal_head('Change Password', portal_url('profile'));
?>

<div class="max-w-sm mx-auto">
  <div class="card">
    <h2 class="font-bold text-gray-900 text-lg mb-4">🔑 Change Password</h2>

    <form method="POST" action="<?= portal_url('change-password') ?>">
      <?= csrf_field() ?>

      <div class="mb-4">
        <label class="form-label" for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password"
               class="form-input" required autocomplete="current-password">
      </div>

      <div class="mb-4">
        <label class="form-label" for="password">New Password</label>
        <input type="password" id="password" name="password"
               class="form-input" required minlength="8" autocomplete="new-password">
        <p class="text-xs text-gray-400 mt-1">At least 8 characters.</p>
      </div>

      <div class="mb-5">
        <label class="form-label" for="password_confirmation">Confirm New Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               class="form-input" required minlength="8" autocomplete="new-password">
      </div>

      <button type="submit" class="btn-primary w-full py-3">
        Update Password
      </button>
    </form>
  </div>
</div>

<?php portal_foot('profile'); ?>
