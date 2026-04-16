<?php
/**
 * Admin Dashboard Controller
 */

class AdminController
{
    public static function dashboard(): void
    {
        Auth::requireAdmin();

        $stats = [
            'total_schools'   => (int) Database::fetchColumn("SELECT COUNT(*) FROM schools"),
            'active_schools'  => (int) Database::fetchColumn("SELECT COUNT(*) FROM schools WHERE pipeline_stage = 'active'"),
            'pending_demos'   => (int) Database::fetchColumn("SELECT COUNT(*) FROM demos WHERE status IN ('pending','scheduled')"),
            'pending_payments'=> (int) Database::fetchColumn("SELECT COUNT(*) FROM payments WHERE status IN ('pending','paid')"),
            'total_revenue'   => (float) Database::fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'verified'"),
            'new_submissions' => (int) Database::fetchColumn("SELECT COUNT(*) FROM contact_submissions WHERE status = 'new'"),
        ];

        $pipelineData = Database::fetchAll("SELECT pipeline_stage, COUNT(*) as count FROM schools GROUP BY pipeline_stage");
        $recentSchools = Database::fetchAll("SELECT s.*, u.email as user_email FROM schools s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 10");
        $recentActivity = Database::fetchAll("SELECT al.*, u.name as user_name FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10");

        include __DIR__ . '/../views/admin/dashboard.php';
    }

    public static function schools(): void
    {
        Auth::requireAdmin();
        $filter = $_GET['stage'] ?? '';
        $search = sanitize($_GET['q'] ?? '');

        $sql = "SELECT s.*, u.email as user_email, u.name as user_name FROM schools s JOIN users u ON s.user_id = u.id WHERE 1=1";
        $params = [];

        if ($filter && $filter !== 'all') {
            $sql .= " AND s.pipeline_stage = ?";
            $params[] = $filter;
        }
        if ($search) {
            $sql .= " AND (s.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        $sql .= " ORDER BY s.created_at DESC";

        $schools = Database::fetchAll($sql, $params);
        include __DIR__ . '/../views/admin/schools.php';
    }

    public static function schoolDetail(string $id): void
    {
        Auth::requireAdmin();
        $school = Database::fetch("SELECT s.*, u.email as user_email, u.name as user_name FROM schools s JOIN users u ON s.user_id = u.id WHERE s.id = ?", [(int) $id]);
        if (!$school) {
            flash('error', 'School not found.');
            redirect('admin/schools');
        }

        $demos = Database::fetchAll("SELECT d.*, ds.slot_date, ds.time_start, ds.time_end FROM demos d LEFT JOIN demo_slots ds ON d.demo_slot_id = ds.id WHERE d.school_id = ? ORDER BY d.created_at DESC", [$school['id']]);
        $agreements = Database::fetchAll("SELECT * FROM agreements WHERE school_id = ? ORDER BY created_at DESC", [$school['id']]);
        $payments = Database::fetchAll("SELECT * FROM payments WHERE school_id = ? ORDER BY created_at DESC", [$school['id']]);

        include __DIR__ . '/../views/admin/school_detail.php';
    }

    public static function updateSchoolStage(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            json_response(['error' => 'Invalid request.'], 403);
        }

        $schoolId = (int) ($_POST['school_id'] ?? 0);
        $stage    = $_POST['stage'] ?? '';
        $validStages = ['requested','demo_scheduled','demo_completed','interested','agreement_sent','payment_pending','active','churned'];

        if (!in_array($stage, $validStages)) {
            json_response(['error' => 'Invalid stage.'], 422);
        }

        $school = Database::fetch("SELECT * FROM schools WHERE id = ?", [$schoolId]);
        if (!$school) {
            json_response(['error' => 'School not found.'], 404);
        }

        Database::update('schools', ['pipeline_stage' => $stage], 'id = ?', [$schoolId]);

        // Notify the school user
        Database::insert('notifications', [
            'user_id' => $school['user_id'],
            'title'   => 'Status Updated',
            'message' => 'Your account status has been updated to: ' . pipeline_stage_info($stage)['label'],
            'type'    => 'info',
        ]);

        Database::insert('activity_log', [
            'user_id'     => Auth::id(),
            'action'      => 'update_pipeline',
            'entity_type' => 'school',
            'entity_id'   => $schoolId,
            'details'     => json_encode(['from' => $school['pipeline_stage'], 'to' => $stage]),
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        json_response(['success' => true]);
    }

    public static function demos(): void
    {
        Auth::requireAdmin();
        $demos = Database::fetchAll(
            "SELECT d.*, ds.slot_date, ds.time_start, ds.time_end, s.name as school_name FROM demos d LEFT JOIN demo_slots ds ON d.demo_slot_id = ds.id JOIN schools s ON d.school_id = s.id ORDER BY d.scheduled_at DESC"
        );
        $slots = Database::fetchAll("SELECT * FROM demo_slots ORDER BY slot_date, time_start");
        include __DIR__ . '/../views/admin/demos.php';
    }

    public static function createDemoSlots(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('admin/demos');
        }

        $date      = $_POST['slot_date'] ?? '';
        $timeStart = $_POST['time_start'] ?? '';
        $timeEnd   = $_POST['time_end'] ?? '';

        if (!$date || !$timeStart || !$timeEnd) {
            flash('error', 'All fields are required.');
            redirect('admin/demos');
        }

        Database::insert('demo_slots', [
            'slot_date'  => $date,
            'time_start' => $timeStart,
            'time_end'   => $timeEnd,
        ]);

        flash('success', 'Demo slot created.');
        redirect('admin/demos');
    }

    public static function updateDemoStatus(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            json_response(['error' => 'Invalid request.'], 403);
        }

        $demoId = (int) ($_POST['demo_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $notes  = sanitize($_POST['admin_notes'] ?? '');

        if (!in_array($status, ['pending','scheduled','completed','cancelled','no_show'])) {
            json_response(['error' => 'Invalid status.'], 422);
        }

        $demo = Database::fetch("SELECT d.*, s.user_id, s.name as school_name, s.id as school_id FROM demos d JOIN schools s ON d.school_id = s.id WHERE d.id = ?", [$demoId]);
        if (!$demo) {
            json_response(['error' => 'Demo not found.'], 404);
        }

        Database::update('demos', ['status' => $status, 'admin_notes' => $notes], 'id = ?', [$demoId]);

        if ($status === 'completed') {
            Database::update('schools', ['pipeline_stage' => 'demo_completed'], 'id = ?', [$demo['school_id']]);
        }

        Database::insert('notifications', [
            'user_id' => $demo['user_id'],
            'title'   => 'Demo Update',
            'message' => "Your demo status has been updated to: {$status}",
            'type'    => 'info',
        ]);

        json_response(['success' => true]);
    }

    public static function agreements(): void
    {
        Auth::requireAdmin();
        $agreements = Database::fetchAll(
            "SELECT a.*, s.name as school_name FROM agreements a JOIN schools s ON a.school_id = s.id ORDER BY a.created_at DESC"
        );
        $schools = Database::fetchAll("SELECT id, name FROM schools WHERE pipeline_stage IN ('demo_completed','interested') ORDER BY name");
        include __DIR__ . '/../views/admin/agreements.php';
    }

    public static function sendAgreement(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('admin/agreements');
        }

        $schoolId = (int) ($_POST['school_id'] ?? 0);
        $title    = sanitize($_POST['title'] ?? 'Service Agreement');
        $content  = $_POST['content'] ?? ''; // Allow HTML for agreement content

        if (!$schoolId || !$content) {
            flash('error', 'School and content are required.');
            redirect('admin/agreements');
        }

        $school = Database::fetch("SELECT * FROM schools WHERE id = ?", [$schoolId]);
        if (!$school) {
            flash('error', 'School not found.');
            redirect('admin/agreements');
        }

        Database::insert('agreements', [
            'school_id' => $schoolId,
            'title'     => $title,
            'content'   => $content,
            'status'    => 'sent',
            'sent_at'   => date('Y-m-d H:i:s'),
        ]);

        Database::update('schools', ['pipeline_stage' => 'agreement_sent'], 'id = ?', [$schoolId]);

        Database::insert('notifications', [
            'user_id' => $school['user_id'],
            'title'   => 'Agreement Sent',
            'message' => 'A new service agreement has been sent for your review.',
            'type'    => 'info',
            'link'    => '/dashboard/agreement',
        ]);

        flash('success', 'Agreement sent to ' . $school['name']);
        redirect('admin/agreements');
    }

    public static function payments(): void
    {
        Auth::requireAdmin();
        $filter = $_GET['status'] ?? '';
        $sql = "SELECT p.*, s.name as school_name FROM payments p JOIN schools s ON p.school_id = s.id WHERE 1=1";
        $params = [];
        if ($filter && $filter !== 'all') {
            $sql .= " AND p.status = ?";
            $params[] = $filter;
        }
        $sql .= " ORDER BY p.created_at DESC";
        $payments = Database::fetchAll($sql, $params);
        $schools = Database::fetchAll("SELECT id, name FROM schools WHERE pipeline_stage IN ('agreement_sent','payment_pending') ORDER BY name");
        include __DIR__ . '/../views/admin/payments.php';
    }

    public static function createPayment(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('admin/payments');
        }

        $schoolId     = (int) ($_POST['school_id'] ?? 0);
        $amount       = (float) ($_POST['amount'] ?? 0);
        $type         = in_array($_POST['payment_type'] ?? '', ['setup','monthly','installment']) ? $_POST['payment_type'] : 'setup';
        $dueDate      = $_POST['due_date'] ?? null;
        $installNum   = (int) ($_POST['installment_number'] ?? 0);
        $totalInstall = (int) ($_POST['total_installments'] ?? 0);

        if (!$schoolId || $amount <= 0) {
            flash('error', 'School and valid amount are required.');
            redirect('admin/payments');
        }

        Database::insert('payments', [
            'school_id'          => $schoolId,
            'amount'             => $amount,
            'payment_type'       => $type,
            'installment_number' => $installNum ?: null,
            'total_installments' => $totalInstall ?: null,
            'due_date'           => $dueDate ?: null,
            'status'             => 'pending',
        ]);

        $school = Database::fetch("SELECT * FROM schools WHERE id = ?", [$schoolId]);
        if ($school) {
            Database::insert('notifications', [
                'user_id' => $school['user_id'],
                'title'   => 'New Payment Required',
                'message' => 'A payment of ' . format_etb($amount) . ' has been created for your account.',
                'type'    => 'warning',
                'link'    => '/dashboard/payments',
            ]);
        }

        flash('success', 'Payment created.');
        redirect('admin/payments');
    }

    public static function verifyPayment(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            json_response(['error' => 'Invalid request.'], 403);
        }

        $paymentId = (int) ($_POST['payment_id'] ?? 0);
        $action    = $_POST['action'] ?? '';

        $payment = Database::fetch("SELECT p.*, s.user_id, s.name as school_name, s.id as school_id FROM payments p JOIN schools s ON p.school_id = s.id WHERE p.id = ?", [$paymentId]);
        if (!$payment) {
            json_response(['error' => 'Payment not found.'], 404);
        }

        if ($action === 'verify') {
            Database::update('payments', ['status' => 'verified', 'verified_at' => date('Y-m-d H:i:s')], 'id = ?', [$paymentId]);

            // Check if all payments are verified to activate school
            $pendingCount = (int) Database::fetchColumn(
                "SELECT COUNT(*) FROM payments WHERE school_id = ? AND status NOT IN ('verified','refunded')",
                [$payment['school_id']]
            );
            if ($pendingCount <= 1) { // This one is being verified
                Database::update('schools', ['pipeline_stage' => 'active'], 'id = ?', [$payment['school_id']]);
            }

            Database::insert('notifications', [
                'user_id' => $payment['user_id'],
                'title'   => 'Payment Verified',
                'message' => 'Your payment of ' . format_etb($payment['amount']) . ' has been verified.',
                'type'    => 'success',
            ]);
        } elseif ($action === 'reject') {
            Database::update('payments', ['status' => 'pending', 'paid_at' => null, 'transaction_ref' => null], 'id = ?', [$paymentId]);
            Database::insert('notifications', [
                'user_id' => $payment['user_id'],
                'title'   => 'Payment Issue',
                'message' => 'There was an issue with your payment. Please resubmit.',
                'type'    => 'error',
            ]);
        }

        json_response(['success' => true]);
    }

    // ─── CMS Content Management ──────────────────────────────

    public static function content(): void
    {
        Auth::requireAdmin();
        $sections = Database::fetchAll("SELECT * FROM content ORDER BY sort_order ASC");
        include __DIR__ . '/../views/admin/content.php';
    }

    public static function updateContent(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            json_response(['error' => 'Invalid request.'], 403);
        }

        $id       = (int) ($_POST['id'] ?? 0);
        $title    = $_POST['title'] ?? '';
        $subtitle = $_POST['subtitle'] ?? '';
        $body     = $_POST['body'] ?? '';
        $extraData = $_POST['extra_data'] ?? '';

        $data = [
            'title'    => $title,
            'subtitle' => $subtitle,
            'body'     => $body,
        ];

        if ($extraData) {
            $decoded = json_decode($extraData, true);
            if ($decoded !== null) {
                $data['extra_data'] = $extraData;
            }
        }

        // Handle image upload
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $path = upload_file($_FILES['image'], 'content');
            if ($path) $data['image_path'] = $path;
        }

        Database::update('content', $data, 'id = ?', [$id]);
        json_response(['success' => true]);
    }

    public static function features(): void
    {
        Auth::requireAdmin();
        $features = Database::fetchAll("SELECT * FROM features ORDER BY sort_order ASC");
        include __DIR__ . '/../views/admin/features.php';
    }

    public static function saveFeature(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('admin/features');
        }

        $id    = (int) ($_POST['id'] ?? 0);
        $data  = [
            'title'       => sanitize($_POST['title'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'icon'        => sanitize($_POST['icon'] ?? 'star'),
            'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($id) {
            Database::update('features', $data, 'id = ?', [$id]);
        } else {
            Database::insert('features', $data);
        }

        flash('success', 'Feature saved.');
        redirect('admin/features');
    }

    public static function testimonialsList(): void
    {
        Auth::requireAdmin();
        $testimonials = Database::fetchAll("SELECT * FROM testimonials ORDER BY sort_order ASC");
        include __DIR__ . '/../views/admin/testimonials.php';
    }

    public static function saveTestimonial(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('admin/testimonials');
        }

        $id   = (int) ($_POST['id'] ?? 0);
        $data = [
            'school_name' => sanitize($_POST['school_name'] ?? ''),
            'person_name' => sanitize($_POST['person_name'] ?? ''),
            'person_role' => sanitize($_POST['person_role'] ?? ''),
            'content'     => sanitize($_POST['content'] ?? ''),
            'rating'      => min(5, max(1, (int) ($_POST['rating'] ?? 5))),
            'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($id) {
            Database::update('testimonials', $data, 'id = ?', [$id]);
        } else {
            Database::insert('testimonials', $data);
        }

        flash('success', 'Testimonial saved.');
        redirect('admin/testimonials');
    }

    public static function pricing(): void
    {
        Auth::requireAdmin();
        $packages = Database::fetchAll("SELECT * FROM pricing_packages ORDER BY sort_order ASC");
        foreach ($packages as &$p) {
            $p['features_list'] = json_decode($p['features_list'], true) ?: [];
        }
        include __DIR__ . '/../views/admin/pricing.php';
    }

    public static function savePricing(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('admin/pricing');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $featuresList = array_filter(array_map('trim', explode("\n", $_POST['features_text'] ?? '')));

        $data = [
            'name'            => sanitize($_POST['name'] ?? ''),
            'slug'            => sanitize($_POST['slug'] ?? ''),
            'school_size'     => sanitize($_POST['school_size'] ?? ''),
            'student_range'   => sanitize($_POST['student_range'] ?? ''),
            'setup_fee_min'   => (float) ($_POST['setup_fee_min'] ?? 0),
            'setup_fee_max'   => (float) ($_POST['setup_fee_max'] ?? 0),
            'monthly_fee_min' => (float) ($_POST['monthly_fee_min'] ?? 0),
            'monthly_fee_max' => (float) ($_POST['monthly_fee_max'] ?? 0),
            'features_list'   => json_encode($featuresList),
            'badge_text'      => sanitize($_POST['badge_text'] ?? ''),
            'is_popular'      => isset($_POST['is_popular']) ? 1 : 0,
            'sort_order'      => (int) ($_POST['sort_order'] ?? 0),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($id) {
            Database::update('pricing_packages', $data, 'id = ?', [$id]);
        } else {
            Database::insert('pricing_packages', $data);
        }

        flash('success', 'Package saved.');
        redirect('admin/pricing');
    }

    public static function faqsList(): void
    {
        Auth::requireAdmin();
        $faqs = Database::fetchAll("SELECT * FROM faqs ORDER BY sort_order ASC");
        include __DIR__ . '/../views/admin/faqs.php';
    }

    public static function saveFaq(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('admin/faqs');
        }

        $id   = (int) ($_POST['id'] ?? 0);
        $data = [
            'question'   => sanitize($_POST['question'] ?? ''),
            'answer'     => sanitize($_POST['answer'] ?? ''),
            'category'   => sanitize($_POST['category'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];

        if ($id) {
            Database::update('faqs', $data, 'id = ?', [$id]);
        } else {
            Database::insert('faqs', $data);
        }

        flash('success', 'FAQ saved.');
        redirect('admin/faqs');
    }

    public static function seo(): void
    {
        Auth::requireAdmin();
        $settings = Database::fetchAll("SELECT * FROM seo_settings ORDER BY page_slug ASC");
        include __DIR__ . '/../views/admin/seo.php';
    }

    public static function saveSeo(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('admin/seo');
        }

        $id   = (int) ($_POST['id'] ?? 0);
        $data = [
            'page_slug'        => sanitize($_POST['page_slug'] ?? ''),
            'meta_title'       => sanitize($_POST['meta_title'] ?? ''),
            'meta_description' => sanitize($_POST['meta_description'] ?? ''),
            'keywords'         => sanitize($_POST['keywords'] ?? ''),
            'og_title'         => sanitize($_POST['og_title'] ?? ''),
            'og_description'   => sanitize($_POST['og_description'] ?? ''),
            'og_image'         => sanitize($_POST['og_image'] ?? ''),
        ];

        if ($id) {
            Database::update('seo_settings', $data, 'id = ?', [$id]);
        } else {
            Database::insert('seo_settings', $data);
        }

        flash('success', 'SEO settings saved.');
        redirect('admin/seo');
    }

    public static function submissions(): void
    {
        Auth::requireAdmin();
        $submissions = Database::fetchAll("SELECT * FROM contact_submissions ORDER BY created_at DESC");
        include __DIR__ . '/../views/admin/submissions.php';
    }

    public static function updateSubmission(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            json_response(['error' => 'Invalid request.'], 403);
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['new', 'contacted', 'closed']) ? $_POST['status'] : 'new';

        Database::update('contact_submissions', ['status' => $status], 'id = ?', [$id]);
        json_response(['success' => true]);
    }

    public static function notifications(): void
    {
        Auth::requireAdmin();
        $notifications = Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
            [Auth::id()]
        );
        Database::update('notifications', ['is_read' => 1], 'user_id = ? AND is_read = 0', [Auth::id()]);
        include __DIR__ . '/../views/admin/notifications.php';
    }

    public static function sendNotification(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('admin/schools');
        }

        $userId  = (int) ($_POST['user_id'] ?? 0);
        $title   = sanitize($_POST['title'] ?? '');
        $message = sanitize($_POST['message'] ?? '');

        if (!$userId || !$title || !$message) {
            flash('error', 'All fields are required.');
            redirect('admin/schools');
        }

        Database::insert('notifications', [
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'type'    => 'info',
        ]);

        flash('success', 'Notification sent.');
        redirect($_SERVER['HTTP_REFERER'] ?? base_url('admin/schools'));
    }

    public static function deleteEntity(): void
    {
        Auth::requireAdmin();
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            json_response(['error' => 'Invalid request.'], 403);
        }

        $type = $_POST['entity_type'] ?? '';
        $id   = (int) ($_POST['entity_id'] ?? 0);

        $allowedTypes = ['features', 'testimonials', 'faqs', 'pricing_packages', 'demo_slots', 'seo_settings'];
        if (!in_array($type, $allowedTypes)) {
            json_response(['error' => 'Invalid entity type.'], 422);
        }

        Database::delete($type, 'id = ?', [$id]);
        json_response(['success' => true]);
    }
}
