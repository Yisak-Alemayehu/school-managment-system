<?php
/**
 * Authentication Controller
 */

class AuthController
{
    public static function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::isAdmin() ? 'admin' : 'customer');
        }
        $seo = get_seo('login');
        include __DIR__ . '/../views/auth/login.php';
    }

    public static function login(): void
    {
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request. Please try again.');
            redirect('login');
        }

        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            flash('error', 'Please enter a valid email and password.');
            redirect('login');
        }

        // Rate limiting check (simple session-based)
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_login_attempt'] ?? 0;
        if ($attempts >= 5 && (time() - $lastAttempt) < 300) {
            flash('error', 'Too many login attempts. Please wait 5 minutes.');
            redirect('login');
        }

        $user = Auth::attempt($email, $password);
        if ($user) {
            unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']);

            Database::insert('activity_log', [
                'user_id'     => $user['id'],
                'action'      => 'login',
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);

            redirect($user['role'] === 'admin' ? 'admin' : 'customer');
        }

        $_SESSION['login_attempts'] = $attempts + 1;
        $_SESSION['last_login_attempt'] = time();
        flash('error', 'Invalid email or password.');
        redirect('login');
    }

    public static function showRegister(): void
    {
        if (Auth::check()) {
            redirect(Auth::isAdmin() ? 'admin' : 'customer');
        }
        $seo = get_seo('register');
        include __DIR__ . '/../views/auth/register.php';
    }

    public static function register(): void
    {
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('register');
        }

        $name     = sanitize($_POST['name'] ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';
        $schoolName    = sanitize($_POST['school_name'] ?? '');
        $studentCount  = (int) ($_POST['student_count'] ?? 0);
        $phone         = sanitize($_POST['phone'] ?? '');

        $errors = [];
        if (!$name)     $errors[] = 'Full name is required.';
        if (!$email)    $errors[] = 'Valid email is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        if (!$schoolName) $errors[] = 'School name is required.';

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('register');
        }

        // Check duplicate email
        $existing = Database::fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            flash('error', 'An account with this email already exists.');
            redirect('register');
        }

        // Create user
        $userId = Auth::register($email, $password, $name, 'school');

        // Determine package based on student count
        $package = 'basic';
        if ($studentCount > 1000) $package = 'premium';
        elseif ($studentCount > 500) $package = 'standard';

        // Create school record
        Database::insert('schools', [
            'user_id'       => $userId,
            'name'          => $schoolName,
            'phone'         => $phone,
            'email'         => $email,
            'student_count' => $studentCount,
            'package'       => $package,
            'pipeline_stage'=> 'requested',
        ]);

        // Notify admins
        $admins = Database::fetchAll("SELECT id FROM users WHERE role = 'admin'");
        foreach ($admins as $admin) {
            Database::insert('notifications', [
                'user_id' => $admin['id'],
                'title'   => 'New School Registration',
                'message' => "{$schoolName} has registered and is requesting access.",
                'type'    => 'info',
                'link'    => '/admin/schools',
            ]);
        }

        // Auto-login
        Auth::attempt($email, $password);

        flash('success', 'Welcome to Eduelevate! Your access request has been submitted.');
        redirect('customer');
    }

    public static function logout(): void
    {
        if (Auth::check()) {
            Database::insert('activity_log', [
                'user_id'    => Auth::id(),
                'action'     => 'logout',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        }
        Auth::logout();
        redirect('login');
    }
}
