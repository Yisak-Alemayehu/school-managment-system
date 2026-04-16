<?php
/**
 * Landing Page Controller
 */

class LandingController
{
    public static function index(): void
    {
        $seo          = get_seo('home');
        $hero         = get_content('hero');
        $socialProof  = get_content('social_proof');
        $featuresIntro = get_content('features_intro');
        $features     = get_features();
        $showcase     = get_content('showcase');
        $howItWorks   = get_content('how_it_works');
        $pricingIntro = get_content('pricing_intro');
        $packages     = get_pricing();
        $testimonialsIntro = get_content('testimonials_intro');
        $testimonials = get_testimonials();
        $faqIntro     = get_content('faq_intro');
        $faqs         = get_faqs();
        $finalCta     = get_content('final_cta');
        $footer       = get_content('footer');

        include __DIR__ . '/../views/landing/index.php';
    }

    public static function submitContact(): void
    {
        if (!Auth::validateCsrf($_POST['csrf_token'] ?? '')) {
            json_response(['error' => 'Invalid request.'], 403);
        }

        $name   = sanitize($_POST['name'] ?? '');
        $email  = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone  = sanitize($_POST['phone'] ?? '');
        $school = sanitize($_POST['school_name'] ?? '');
        $msg    = sanitize($_POST['message'] ?? '');
        $type   = in_array($_POST['type'] ?? '', ['demo_request', 'contact', 'get_started'])
                  ? $_POST['type'] : 'contact';

        if (!$name || !$email) {
            json_response(['error' => 'Name and valid email are required.'], 422);
        }

        $id = Database::insert('contact_submissions', [
            'name'        => $name,
            'email'       => $email,
            'phone'       => $phone,
            'school_name' => $school,
            'message'     => $msg,
            'type'        => $type,
        ]);

        // Notify admins
        $admins = Database::fetchAll("SELECT id FROM users WHERE role = 'admin'");
        foreach ($admins as $admin) {
            Database::insert('notifications', [
                'user_id' => $admin['id'],
                'title'   => 'New ' . ucfirst(str_replace('_', ' ', $type)),
                'message' => "{$name} from {$school} submitted a {$type} request.",
                'type'    => 'info',
                'link'    => '/admin/submissions',
            ]);
        }

        json_response(['success' => true, 'message' => 'Thank you! We\'ll get back to you within 24 hours.']);
    }
}
