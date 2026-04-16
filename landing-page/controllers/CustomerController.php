<?php
/**
 * Customer (School) Dashboard Controller
 */

class CustomerController
{
    /**
     * Get the current school record.
     */
    private static function getSchool(): array
    {
        $school = Database::fetch("SELECT * FROM schools WHERE user_id = ?", [Auth::id()]);
        if (!$school) {
            flash('error', 'School profile not found.');
            redirect('login');
        }
        return $school;
    }

    public static function dashboard(): void
    {
        Auth::requireSchool();
        $school = self::getSchool();
        $notifications = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
            [Auth::id()]
        );
        $demo = Database::fetch(
            "SELECT d.*, ds.slot_date, ds.time_start, ds.time_end FROM demos d LEFT JOIN demo_slots ds ON d.demo_slot_id = ds.id WHERE d.school_id = ? ORDER BY d.created_at DESC LIMIT 1",
            [$school['id']]
        );
        $agreement = Database::fetch(
            "SELECT * FROM agreements WHERE school_id = ? ORDER BY created_at DESC LIMIT 1",
            [$school['id']]
        );
        $payments = Database::fetchAll(
            "SELECT * FROM payments WHERE school_id = ? ORDER BY created_at DESC",
            [$school['id']]
        );

        include __DIR__ . '/../views/customer/dashboard.php';
    }

    public static function demo(): void
    {
        Auth::requireSchool();
        $school = self::getSchool();
        $demo = Database::fetch(
            "SELECT d.*, ds.slot_date, ds.time_start, ds.time_end FROM demos d LEFT JOIN demo_slots ds ON d.demo_slot_id = ds.id WHERE d.school_id = ? ORDER BY d.created_at DESC LIMIT 1",
            [$school['id']]
        );
        $availableSlots = Database::fetchAll(
            "SELECT * FROM demo_slots WHERE is_available = 1 AND slot_date >= CURDATE() ORDER BY slot_date, time_start"
        );

        include __DIR__ . '/../views/customer/demo.php';
    }

    public static function bookDemo(): void
    {
        Auth::requireSchool();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('dashboard/demo');
        }

        $school = self::getSchool();
        $slotId = (int) ($_POST['slot_id'] ?? 0);
        $notes  = sanitize($_POST['notes'] ?? '');

        $slot = Database::fetch("SELECT * FROM demo_slots WHERE id = ? AND is_available = 1", [$slotId]);
        if (!$slot) {
            flash('error', 'Selected slot is no longer available.');
            redirect('dashboard/demo');
        }

        // Create demo booking
        Database::insert('demos', [
            'school_id'    => $school['id'],
            'demo_slot_id' => $slotId,
            'scheduled_at' => $slot['slot_date'] . ' ' . $slot['time_start'],
            'status'       => 'scheduled',
            'notes'        => $notes,
        ]);

        // Mark slot as unavailable
        Database::update('demo_slots', ['is_available' => 0], 'id = ?', [$slotId]);

        // Update pipeline
        if ($school['pipeline_stage'] === 'requested') {
            Database::update('schools', ['pipeline_stage' => 'demo_scheduled'], 'id = ?', [$school['id']]);
        }

        // Notify admins
        $admins = Database::fetchAll("SELECT id FROM users WHERE role = 'admin'");
        foreach ($admins as $admin) {
            Database::insert('notifications', [
                'user_id' => $admin['id'],
                'title'   => 'Demo Booked',
                'message' => "{$school['name']} has booked a demo for {$slot['slot_date']}.",
                'type'    => 'info',
                'link'    => '/admin/demos',
            ]);
        }

        flash('success', 'Demo booked successfully!');
        redirect('dashboard/demo');
    }

    public static function agreement(): void
    {
        Auth::requireSchool();
        $school = self::getSchool();
        $agreement = Database::fetch(
            "SELECT * FROM agreements WHERE school_id = ? ORDER BY created_at DESC LIMIT 1",
            [$school['id']]
        );
        if ($agreement && $agreement['status'] === 'sent') {
            Database::update('agreements', ['status' => 'viewed', 'viewed_at' => date('Y-m-d H:i:s')], 'id = ?', [$agreement['id']]);
            $agreement['status'] = 'viewed';
        }
        include __DIR__ . '/../views/customer/agreement.php';
    }

    public static function respondAgreement(): void
    {
        Auth::requireSchool();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('dashboard/agreement');
        }

        $school = self::getSchool();
        $agreementId = (int) ($_POST['agreement_id'] ?? 0);
        $response    = ($_POST['response'] ?? '') === 'accept' ? 'accepted' : 'rejected';

        $agreement = Database::fetch("SELECT * FROM agreements WHERE id = ? AND school_id = ?", [$agreementId, $school['id']]);
        if (!$agreement || !in_array($agreement['status'], ['sent', 'viewed'])) {
            flash('error', 'Agreement not found or already responded.');
            redirect('dashboard/agreement');
        }

        Database::update('agreements', [
            'status'       => $response,
            'responded_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$agreementId]);

        if ($response === 'accepted') {
            Database::update('schools', ['pipeline_stage' => 'payment_pending'], 'id = ?', [$school['id']]);

            // Notify admin
            $admins = Database::fetchAll("SELECT id FROM users WHERE role = 'admin'");
            foreach ($admins as $admin) {
                Database::insert('notifications', [
                    'user_id' => $admin['id'],
                    'title'   => 'Agreement Accepted',
                    'message' => "{$school['name']} has accepted the service agreement.",
                    'type'    => 'success',
                    'link'    => '/admin/agreements',
                ]);
            }
        }

        flash('success', 'Agreement ' . $response . ' successfully.');
        redirect('dashboard/agreement');
    }

    public static function payments(): void
    {
        Auth::requireSchool();
        $school = self::getSchool();
        $payments = Database::fetchAll(
            "SELECT * FROM payments WHERE school_id = ? ORDER BY due_date ASC",
            [$school['id']]
        );
        include __DIR__ . '/../views/customer/payments.php';
    }

    public static function submitPayment(): void
    {
        Auth::requireSchool();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('dashboard/payments');
        }

        $school    = self::getSchool();
        $paymentId = (int) ($_POST['payment_id'] ?? 0);
        $transRef  = sanitize($_POST['transaction_ref'] ?? '');
        $method    = sanitize($_POST['payment_method'] ?? '');

        if (!$transRef || !$method) {
            flash('error', 'Transaction reference and payment method are required.');
            redirect('dashboard/payments');
        }

        $payment = Database::fetch("SELECT * FROM payments WHERE id = ? AND school_id = ? AND status = 'pending'", [$paymentId, $school['id']]);
        if (!$payment) {
            flash('error', 'Payment not found or already processed.');
            redirect('dashboard/payments');
        }

        Database::update('payments', [
            'status'          => 'paid',
            'paid_at'         => date('Y-m-d H:i:s'),
            'transaction_ref' => $transRef,
            'payment_method'  => $method,
        ], 'id = ?', [$paymentId]);

        // Notify admin
        $admins = Database::fetchAll("SELECT id FROM users WHERE role = 'admin'");
        foreach ($admins as $admin) {
            Database::insert('notifications', [
                'user_id' => $admin['id'],
                'title'   => 'Payment Submitted',
                'message' => "{$school['name']} has submitted payment of " . format_etb($payment['amount']) . " for verification.",
                'type'    => 'info',
                'link'    => '/admin/payments',
            ]);
        }

        flash('success', 'Payment submitted! It will be verified shortly.');
        redirect('dashboard/payments');
    }

    public static function notifications(): void
    {
        Auth::requireSchool();
        $notifications = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
            [Auth::id()]
        );
        // Mark all as read
        Database::update('notifications', ['is_read' => 1], 'user_id = ? AND is_read = 0', [Auth::id()]);
        include __DIR__ . '/../views/customer/notifications.php';
    }

    public static function profile(): void
    {
        Auth::requireSchool();
        $school = self::getSchool();
        $user = Auth::user();
        include __DIR__ . '/../views/customer/profile.php';
    }

    public static function updateProfile(): void
    {
        Auth::requireSchool();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('dashboard/profile');
        }

        $school = self::getSchool();

        $schoolData = [
            'name'          => sanitize($_POST['school_name'] ?? ''),
            'address'       => sanitize($_POST['address'] ?? ''),
            'city'          => sanitize($_POST['city'] ?? ''),
            'phone'         => sanitize($_POST['phone'] ?? ''),
            'student_count' => (int) ($_POST['student_count'] ?? 0),
            'school_type'   => in_array($_POST['school_type'] ?? '', ['kindergarten','primary','secondary','preparatory','mixed'])
                               ? $_POST['school_type'] : 'mixed',
        ];

        if (!$schoolData['name']) {
            flash('error', 'School name is required.');
            redirect('dashboard/profile');
        }

        Database::update('schools', $schoolData, 'id = ?', [$school['id']]);

        // Update user name
        $userName = sanitize($_POST['contact_name'] ?? '');
        if ($userName) {
            Database::update('users', ['name' => $userName], 'id = ?', [Auth::id()]);
            $_SESSION['user_name'] = $userName;
        }

        flash('success', 'Profile updated successfully.');
        redirect('dashboard/profile');
    }
}
