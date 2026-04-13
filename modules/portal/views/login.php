<?php
/**
 * Portal — Login View
 */
portal_head('Sign In');
$schoolName = function_exists('get_school_name') ? get_school_name() : 'School Portal';
$oldRole = $_SESSION['_old_input']['role'] ?? 'student';
$oldUser = $_SESSION['_old_input']['username'] ?? '';
unset($_SESSION['_old_input']);
?>

<div class="min-h-[70vh] flex flex-col items-center justify-center -mt-4 py-8">

  <!-- Logo / school name -->
  <div class="text-center mb-8">
    <div class="w-20 h-20 rounded-2xl bg-primary-600 flex items-center justify-center mx-auto mb-4"
         style="box-shadow:0 8px 24px rgba(7,77,217,.3)">
      <span class="text-3xl">🏫</span>
    </div>
    <h2 class="text-xl font-bold text-gray-900"><?= e($schoolName) ?></h2>
    <p class="text-sm text-gray-500 mt-1">Student &amp; Parent Portal</p>
  </div>

  <!-- Login card -->
  <div class="card w-full max-w-sm">
    <form method="POST" action="<?= portal_url('login') ?>" novalidate>
      <?= csrf_field() ?>

      <!-- Role selector -->
      <div class="mb-5">
        <label class="form-label">I am a</label>
        <div class="grid grid-cols-2 gap-2">
          <label class="relative cursor-pointer">
            <input type="radio" name="role" value="student"
                   class="sr-only peer" <?= $oldRole === 'student' ? 'checked' : '' ?>>
            <span class="block text-center py-2.5 px-3 rounded-xl border-2 text-sm font-semibold
                         border-gray-200 text-gray-500
                         peer-checked:border-primary-600 peer-checked:text-primary-700
                         peer-checked:bg-primary-50 transition-all">
              🎓 Student
            </span>
          </label>
          <label class="relative cursor-pointer">
            <input type="radio" name="role" value="parent"
                   class="sr-only peer" <?= $oldRole === 'parent' ? 'checked' : '' ?>>
            <span class="block text-center py-2.5 px-3 rounded-xl border-2 text-sm font-semibold
                         border-gray-200 text-gray-500
                         peer-checked:border-primary-600 peer-checked:text-primary-700
                         peer-checked:bg-primary-50 transition-all">
              👨‍👩‍👧 Parent
            </span>
          </label>
        </div>
      </div>

      <!-- Username -->
      <div class="mb-4">
        <label for="username" class="form-label">Username or Email</label>
        <input id="username" type="text" name="username"
               value="<?= e($oldUser) ?>"
               placeholder="Enter your username"
               autocomplete="username"
               class="form-input" required>
      </div>

      <!-- Password -->
      <div class="mb-6">
        <label for="password" class="form-label">Password</label>
        <div class="relative">
          <input id="password" type="password" name="password"
                 placeholder="Enter your password"
                 autocomplete="current-password"
                 class="form-input pr-10" required>
          <button type="button"
                  onclick="togglePwd()"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                       -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-primary w-full text-base py-3">
        Sign In
      </button>
    </form>
  </div>

  <p class="text-xs text-gray-400 mt-6 text-center">
    Having trouble? Contact the school administration.
  </p>
</div>

<script>
function togglePwd() {
  var inp = document.getElementById('password');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>

<?php portal_foot(); ?>
