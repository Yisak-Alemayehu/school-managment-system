-- Eduelevate Seed Data
USE eduelevate;

-- ─── Admin User (password: admin123) ─────────────────────────
INSERT INTO users (email, password, name, role) VALUES
('admin@eduelevate.com', '$2y$12$LJ3m4rOKBBCGP0GyK9L2/.5N5R4wTGvN5X9X0uj.o1Nq7LQVC7YXu', 'Eduelevate Admin', 'admin');

-- ─── Default SEO Settings ────────────────────────────────────
INSERT INTO seo_settings (page_slug, meta_title, meta_description, keywords, og_title, og_description) VALUES
('home', 'Eduelevate – Smart School Management Platform', 'All-in-one platform to manage students, finances, communication, and performance. Trusted by forward-thinking schools across Ethiopia.', 'school management, school software, student management, attendance tracking, fee management, Ethiopia', 'Eduelevate – Smart School Management', 'Elevate your school with smarter management. All-in-one platform for students, finances, and communication.'),
('pricing', 'Pricing – Eduelevate', 'Affordable school management packages starting from 60,000 ETB. Flexible payment options for schools of all sizes.', 'school software pricing, eduelevate pricing, school management cost', 'Eduelevate Pricing', 'Affordable packages for schools of all sizes.'),
('login', 'Login – Eduelevate', 'Access your Eduelevate dashboard. Manage your school from anywhere.', 'eduelevate login, school dashboard', 'Login – Eduelevate', 'Access your Eduelevate dashboard.');

-- ─── Default Content Sections ────────────────────────────────
INSERT INTO content (section_key, title, subtitle, body, extra_data, sort_order) VALUES
('hero', 'Elevate Your School with Smarter Management', 'All-in-one platform to manage students, finances, communication, and performance — effortlessly.', NULL, '{"cta_primary": "Request Demo", "cta_secondary": "Get Started", "stats": [{"value": "500+", "label": "Schools Trust Us"}, {"value": "20+", "label": "Hours Saved/Week"}, {"value": "99.9%", "label": "Uptime"}]}', 1),
('social_proof', 'Trusted by Forward-Thinking Schools', 'Helping schools save 20+ hours per week with smarter tools', NULL, '{"logos": ["school1.png", "school2.png", "school3.png", "school4.png", "school5.png"]}', 2),
('features_intro', 'Everything Your School Needs in One Platform', 'Powerful tools designed to simplify every aspect of school management', NULL, NULL, 3),
('showcase', 'See Eduelevate in Action', 'Real dashboards. Real results. Explore the tools that make school management effortless.', NULL, NULL, 4),
('how_it_works', 'Your Journey to Smarter School Management', 'A simple, guided process from discovery to activation', NULL, NULL, 5),
('pricing_intro', 'Simple, Transparent Pricing', 'We have packages starting from 60,000 ETB — designed for schools of all sizes', NULL, '{"installment_text": "Start now, pay in parts — 50% upfront, 50% after 1-2 months"}', 6),
('testimonials_intro', 'What Schools Are Saying', 'Real feedback from schools transforming their operations with Eduelevate', NULL, NULL, 7),
('faq_intro', 'Frequently Asked Questions', 'Everything you need to know about getting started with Eduelevate', NULL, NULL, 8),
('final_cta', 'Start Your School''s Digital Transformation', 'Join hundreds of schools already using Eduelevate to streamline operations and improve outcomes.', NULL, '{"cta_text": "Request Access", "cta_secondary": "Talk to Sales"}', 9),
('footer', 'Eduelevate', 'Smart school management for the modern age.', NULL, '{"email": "hello@eduelevate.com", "phone": "+251 91 234 5678", "address": "Addis Ababa, Ethiopia"}', 10);

-- ─── Default Features ────────────────────────────────────────
INSERT INTO features (title, description, icon, sort_order) VALUES
('Student Portal', 'Give students and parents a dedicated portal to track grades, attendance, assignments, and communicate with teachers in real-time.', 'users', 1),
('Attendance Tracking', 'Automated daily attendance with smart reports, absence alerts, and trend analysis. Never lose track of a student again.', 'clipboard-check', 2),
('Results & Assessments', 'Comprehensive grade management with customizable report cards, GPA calculations, and performance analytics across all subjects.', 'chart-bar', 3),
('Fee Management', 'Streamline fee collection with automated invoicing, payment tracking, installment plans, and detailed financial reports.', 'currency-dollar', 4),
('Communication System', 'Built-in messaging, announcements, SMS notifications, and parent communication tools — all in one place.', 'chat-alt-2', 5),
('Admin Dashboard', 'A powerful command center with real-time analytics, enrollment stats, financial summaries, and system-wide controls.', 'view-grid', 6),
('Reports & Analytics', 'Generate detailed reports on academics, finances, attendance, and operations. Export to PDF or Excel with one click.', 'document-report', 7),
('Timetable & Scheduling', 'Create and manage class timetables, exam schedules, and events with an intuitive drag-and-drop calendar.', 'calendar', 8);

-- ─── Pricing Packages ────────────────────────────────────────
INSERT INTO pricing_packages (name, slug, school_size, student_range, setup_fee_min, setup_fee_max, monthly_fee_min, monthly_fee_max, features_list, badge_text, is_popular, sort_order) VALUES
('Basic', 'basic', 'Small Schools', '<500 students', 60000.00, 120000.00, 5000.00, 8000.00, '["Full system installation","Staff training (up to 10 users)","Basic configuration","1 month free support","Email support","Core modules included"]', NULL, 0, 1),
('Standard', 'standard', 'Medium Schools', '500–1000 students', 120000.00, 250000.00, 10000.00, 15000.00, '["Full system installation","Staff training (up to 30 users)","Advanced configuration","2 months free support","Priority email & phone support","All modules included","Custom branding","Data migration assistance"]', 'Most Popular', 1, 2),
('Premium', 'premium', 'Large Schools', '1000+ students', 250000.00, 500000.00, 20000.00, 30000.00, '["Full system installation","Unlimited staff training","Enterprise configuration","3 months free support","Dedicated account manager","All modules + API access","Custom development hours","Priority data migration","On-site training available"]', 'Enterprise', 0, 3);

-- ─── Testimonials ────────────────────────────────────────────
INSERT INTO testimonials (school_name, person_name, person_role, content, rating, sort_order) VALUES
('Bright Future Academy', 'Ato Tadesse Bekele', 'Principal', 'Eduelevate transformed how we manage our school. Attendance tracking alone saves us 5 hours every week. The parent communication tools have dramatically improved engagement.', 5, 1),
('Sunrise International School', 'W/ro Hiwot Alemayehu', 'Vice Principal', 'The fee management module is incredible. We went from chasing payments to having 95% on-time collection. The dashboard gives me a complete picture of our school at a glance.', 5, 2),
('Green Valley Primary', 'Ato Yohannes Girma', 'Director', 'We evaluated 5 different systems before choosing Eduelevate. The onboarding was smooth, training was thorough, and the support team is always responsive. Best investment we made.', 5, 3),
('Alpha Preparatory School', 'W/ro Sara Tesfaye', 'Academic Dean', 'The results management system is exactly what we needed. Report card generation that used to take days now takes minutes. Parents love the real-time grade access.', 4, 4),
('Unity Academy', 'Ato Dawit Hailu', 'IT Coordinator', 'Easy to set up, intuitive to use, and the admin dashboard gives us complete control. The system handles our 1,200 students without breaking a sweat.', 5, 5);

-- ─── FAQs ────────────────────────────────────────────────────
INSERT INTO faqs (question, answer, category, sort_order) VALUES
('What is Eduelevate?', 'Eduelevate is a comprehensive school management platform that helps schools manage students, attendance, grades, fees, communication, and operations — all from one integrated system.', 'General', 1),
('How much does Eduelevate cost?', 'We offer three packages starting from 60,000 ETB for setup. Monthly subscriptions start at 5,000 ETB. We also offer flexible installment plans — start with 50% upfront and pay the rest over 1-2 months.', 'Pricing', 2),
('Is there a free trial?', 'We offer a comprehensive live demo where you can experience the full system. During the demo, our team will walk you through every feature tailored to your school''s needs.', 'Pricing', 3),
('How long does setup take?', 'Most schools are fully operational within 1-2 weeks. This includes installation, configuration, data migration, and staff training. Our team guides you through every step.', 'Onboarding', 4),
('Do you provide training?', 'Yes! Every package includes comprehensive staff training. Basic includes training for up to 10 users, Standard for 30 users, and Premium offers unlimited training sessions including on-site options.', 'Onboarding', 5),
('What kind of support do you offer?', 'All packages include free support (1-3 months depending on your plan). After that, our subscription includes email support for Basic, priority phone & email for Standard, and a dedicated account manager for Premium.', 'Support', 6),
('Can I customize the system for my school?', 'Absolutely. Eduelevate is highly configurable. You can customize report cards, grading systems, fee structures, timetables, and much more. Premium plans include custom development hours.', 'Features', 7),
('Is my data secure?', 'Security is our top priority. We use encrypted connections, secure authentication, regular backups, and role-based access control. Your data is stored securely and never shared with third parties.', 'Security', 8),
('Can I pay in installments?', 'Yes! We offer a flexible payment plan — pay 50% upfront to get started, and the remaining 50% within 1-2 months. This applies to the one-time setup fee.', 'Pricing', 9),
('What happens if I need to cancel?', 'You can cancel your monthly subscription at any time. The one-time setup fee is non-refundable after installation begins, but we offer a satisfaction guarantee during the demo phase.', 'Pricing', 10);
