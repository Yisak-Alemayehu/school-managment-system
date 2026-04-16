<?php
/**
 * Eduelevate - Main Entry Point
 * Routes all requests through the application Router.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Base path
define('BASE_PATH', __DIR__);

// Load core
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Auth.php';
require_once BASE_PATH . '/core/Router.php';
require_once BASE_PATH . '/core/Helpers.php';

// Start session
Auth::startSession();

// Load controllers
require_once BASE_PATH . '/controllers/LandingController.php';
require_once BASE_PATH . '/controllers/AuthController.php';
require_once BASE_PATH . '/controllers/CustomerController.php';
require_once BASE_PATH . '/controllers/AdminController.php';

$router = new Router();

// ============ PUBLIC ROUTES ============
$router->get('', [LandingController::class, 'index']);
$router->get('/', [LandingController::class, 'index']);
$router->post('contact', [LandingController::class, 'submitContact']);

// ============ AUTH ROUTES ============
$router->get('login', [AuthController::class, 'showLogin']);
$router->post('login', [AuthController::class, 'login']);
$router->get('register', [AuthController::class, 'showRegister']);
$router->post('register', [AuthController::class, 'register']);
$router->get('logout', [AuthController::class, 'logout']);

// ============ CUSTOMER ROUTES ============
$router->get('customer', [CustomerController::class, 'dashboard']);
$router->get('customer/demo', [CustomerController::class, 'demo']);
$router->post('customer/demo/book', [CustomerController::class, 'bookDemo']);
$router->get('customer/agreement', [CustomerController::class, 'agreement']);
$router->post('customer/agreement/respond', [CustomerController::class, 'respondAgreement']);
$router->get('customer/payments', [CustomerController::class, 'payments']);
$router->post('customer/payments/submit', [CustomerController::class, 'submitPayment']);
$router->get('customer/notifications', [CustomerController::class, 'notifications']);
$router->get('customer/profile', [CustomerController::class, 'profile']);
$router->post('customer/profile/update', [CustomerController::class, 'updateProfile']);

// ============ ADMIN ROUTES ============
$router->get('admin', [AdminController::class, 'dashboard']);
$router->get('admin/schools', [AdminController::class, 'schools']);
$router->get('admin/schools/{id}', [AdminController::class, 'schoolDetail']);
$router->post('admin/schools/update-stage', [AdminController::class, 'updateSchoolStage']);
$router->get('admin/demos', [AdminController::class, 'demos']);
$router->post('admin/demos/create-slot', [AdminController::class, 'createDemoSlots']);
$router->post('admin/demos/update-status', [AdminController::class, 'updateDemoStatus']);
$router->get('admin/agreements', [AdminController::class, 'agreements']);
$router->post('admin/agreements/send', [AdminController::class, 'sendAgreement']);
$router->get('admin/payments', [AdminController::class, 'payments']);
$router->post('admin/payments/create', [AdminController::class, 'createPayment']);
$router->post('admin/payments/verify', [AdminController::class, 'verifyPayment']);
$router->get('admin/submissions', [AdminController::class, 'submissions']);
$router->post('admin/submissions/update', [AdminController::class, 'updateSubmission']);
$router->get('admin/notifications', [AdminController::class, 'notifications']);
$router->post('admin/send-notification', [AdminController::class, 'sendNotification']);

// CMS Routes
$router->get('admin/content', [AdminController::class, 'content']);
$router->post('admin/content/update', [AdminController::class, 'updateContent']);
$router->get('admin/features', [AdminController::class, 'features']);
$router->post('admin/features/save', [AdminController::class, 'saveFeature']);
$router->get('admin/testimonials', [AdminController::class, 'testimonialsList']);
$router->post('admin/testimonials/save', [AdminController::class, 'saveTestimonial']);
$router->get('admin/pricing', [AdminController::class, 'pricing']);
$router->post('admin/pricing/save', [AdminController::class, 'savePricing']);
$router->get('admin/faqs', [AdminController::class, 'faqsList']);
$router->post('admin/faqs/save', [AdminController::class, 'saveFaq']);
$router->get('admin/seo', [AdminController::class, 'seo']);
$router->post('admin/seo/save', [AdminController::class, 'saveSeo']);
$router->post('admin/delete', [AdminController::class, 'deleteEntity']);

// Dispatch the request
$router->dispatch();
