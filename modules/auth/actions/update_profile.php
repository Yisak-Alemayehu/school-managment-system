<?php
/**
 * Auth — Update Profile Action (POST)
 */

auth_require();
csrf_protect();

$user = auth_user();

$rules = [
    'full_name' => 'required|min:2|max:100',
    'email'     => 'required|email|max:100',
    'phone'     => 'nullable|phone',
];

$errors = validate($_POST, $rules);

// Check email uniqueness (excluding current user)
if (empty($errors['email'])) {
    $emailExists = db_fetch_one(
        "SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL",
        [input('email'), $user['id']]
    );
    if ($emailExists) {
        $errors['email'] = 'This email is already in use.';
    }
}

if (!empty($errors)) {
    set_validation_errors($errors);
    set_old_input($_POST);
    redirect(url('auth', 'profile'));
}

$updateData = [
    'full_name' => input('full_name'),
    'email'     => input('email'),
    'phone'     => input('phone'),
];

// Handle avatar upload
if (!empty($_FILES['avatar']['name'])) {
    $avatarResult = handle_upload('avatar', 'avatars', ['image/jpeg', 'image/png', 'image/webp'], 2 * 1024 * 1024);
    if (is_string($avatarResult)) {
        // Error
        set_flash('error', $avatarResult);
        redirect(url('auth', 'profile'));
    }
    $updateData['avatar'] = $avatarResult;

    // Delete old avatar
    $oldUser = db_fetch_one("SELECT avatar FROM users WHERE id = ?", [$user['id']]);
    if ($oldUser && $oldUser['avatar']) {
        delete_upload($oldUser['avatar']);
    }
}

db_update('users', $updateData, 'id = ?', [$user['id']]);

// Update session data
$_SESSION['user']['full_name'] = $updateData['full_name'];
$_SESSION['user']['email']     = $updateData['email'];
if (isset($updateData['avatar'])) {
    $_SESSION['user']['avatar'] = $updateData['avatar'];
}

audit_log('profile_updated', 'users', $user['id']);

set_flash('success', 'Profile updated successfully.');
redirect(url('auth', 'profile'));
