<?php
/**
 * Sidebar Navigation Partial
 * Role-Based Access Control — shows only permitted navigation items
 *
 * Roles: super_admin, admin, teacher, student, parent, accountant
 */
$user = auth_user();
$roles = $user['roles'];
$isSuperAdmin = auth_is_super_admin();
$isAdmin      = $isSuperAdmin || auth_has_role('admin');
$isTeacher    = auth_has_role('teacher');
$isStudent    = auth_has_role('student');
$isParent     = auth_has_role('parent');

// Auto-expand flags
$inAcademics  = route_is('academics');
$inStudents   = route_is('students');
$inFinance    = route_is('finance');
$inHr         = route_is('hr');
$inMessaging  = route_is('messaging');

$navItems = [];

// Messaging unread count (used for badge)
$msgUnread = get_unread_message_count();

// ── Dashboard — everyone ──
$navItems[] = ['icon' => 'home', 'label' => 'Dashboard', 'url' => '/dashboard', 'module' => 'dashboard'];

// ══════════════════════════════════════════════════════════════
// ACADEMICS — Role-specific visibility per permission matrix
// ══════════════════════════════════════════════════════════════
if ($isAdmin) {
    // 🔴🔵 Super Admin / School Admin: Full access to all academics
    $navItems[] = [
        'icon'   => 'academic-cap',
        'label'  => 'Academics',
        'module' => 'academics',
        'tree'   => true,
        'groups' => [
            'Setup'       => [
                ['action' => 'sessions',          'label' => 'Sessions'],
                ['action' => 'terms',             'label' => 'Terms'],
                ['action' => 'mediums',           'label' => 'Mediums'],
                ['action' => 'streams',           'label' => 'Streams'],
                ['action' => 'shifts',            'label' => 'Shifts'],
            ],
            'Structure'   => [
                ['action' => 'classes',           'label' => 'Classes'],
                ['action' => 'sections',          'label' => 'Sections'],
                ['action' => 'subjects',          'label' => 'Subjects'],
            ],
            'Assignments' => [
                ['action' => 'class-subjects',    'label' => 'Class Subjects'],
                ['action' => 'elective-subjects', 'label' => 'Elective Subjects'],
                ['action' => 'class-teachers',    'label' => 'Class Teachers'],
                ['action' => 'subject-teachers',  'label' => 'Subject Teachers'],
            ],
            'Actions'     => [
                ['action' => 'promote',           'label' => 'Promote Students'],
            ],
            'Schedule'    => [
                ['action' => 'timetable',         'label' => 'Timetable'],
            ],
        ],
    ];
} elseif ($isTeacher) {
    // 🟢 Teacher: NO access to Setup, Structure, Assignments, Promote, Schedule
    //    Only sees Timetable (view) and Subjects (view) for their assigned classes
    $navItems[] = [
        'icon'   => 'academic-cap',
        'label'  => 'Academics',
        'module' => 'academics',
        'tree'   => true,
        'groups' => [
            'View' => [
                ['action' => 'subjects',          'label' => 'Subjects'],
                ['action' => 'timetable',         'label' => 'Time Table'],
            ],
        ],
    ];
} elseif ($isStudent) {
    // 🟡 Student: View assigned subjects & daily timetable
    $navItems[] = [
        'icon'   => 'academic-cap',
        'label'  => 'Academics',
        'module' => 'academics',
        'tree'   => true,
        'groups' => [
            'View' => [
                ['action' => 'subjects',          'label' => 'Assigned Subjects'],
                ['action' => 'timetable',         'label' => 'Daily Time Table'],
            ],
        ],
    ];
} elseif ($isParent) {
    // 🟠 Parent: View child's subjects & daily timetable
    $navItems[] = [
        'icon'   => 'academic-cap',
        'label'  => 'Academics',
        'module' => 'academics',
        'tree'   => true,
        'groups' => [
            'View' => [
                ['action' => 'subjects',          'label' => 'Assigned Subjects'],
                ['action' => 'timetable',         'label' => 'Daily Time Table'],
            ],
        ],
    ];
}
// 🟣 Accountant: NO access to academics (not shown)

// ══════════════════════════════════════════════════════════════
// STUDENTS — Role-specific visibility
// ══════════════════════════════════════════════════════════════
if ($isAdmin) {
    // 🔴🔵 Super Admin / School Admin: Full student management
    $navItems[] = [
        'icon'   => 'users',
        'label'  => 'Students',
        'module' => 'students',
        'tree'   => true,
        'groups' => [
            'Admission' => [
                ['action' => 'admission',      'label' => 'Student Admission'],
                ['action' => 'roll-numbers',   'label' => 'Assign Roll Number'],
                ['action' => 'bulk-import',    'label' => 'Add Bulk Data'],
            ],
            'Records' => [
                ['action' => 'details',        'label' => 'Student Details'],
                ['action' => 'id-cards',       'label' => 'Generate ID Card'],
            ],
            'Student Credentials' => [
                ['action' => 'credentials',              'label' => 'Generate Username & Password'],
                ['action' => 'reset-password',           'label' => 'Reset Password'],
            ],
            'Parent Credentials' => [
                ['action' => 'parent-credentials',       'label' => 'Generate Parent Username & Password'],
                ['action' => 'parent-reset-password',    'label' => 'Reset Parent Password'],
            ],
        ],
    ];
} elseif ($isTeacher) {
    // 🟢 Teacher: View student details & ID cards only (assigned classes)
    //    NO access to Admission, Roll Numbers, Bulk Data, Credentials
    $navItems[] = [
        'icon'   => 'users',
        'label'  => 'Students',
        'module' => 'students',
        'tree'   => true,
        'groups' => [
            'Records' => [
                ['action' => 'details',        'label' => 'Student Details'],
                ['action' => 'id-cards',       'label' => 'Generate ID Card'],
            ],
        ],
    ];
} elseif ($isParent) {
    // 🟠 Parent: View children & manage their credentials
    $navItems[] = [
        'icon'   => 'users',
        'label'  => 'Students',
        'module' => 'students',
        'tree'   => true,
        'groups' => [
            'My Children' => [
                ['action' => 'details',        'label' => 'Student Details'],
            ],
            'Credentials' => [
                ['action' => 'credentials',    'label' => 'Generate Username & Password'],
                ['action' => 'reset-password', 'label' => 'Reset Password'],
            ],
        ],
    ];
}
// 🟡 Student: Views own profile via Dashboard (no Students menu)

// ══════════════════════════════════════════════════════════════
// ATTENDANCE — Role-specific visibility
// ══════════════════════════════════════════════════════════════
if ($isAdmin) {
    // 🔴🔵 Admin: Full attendance management
    $navItems[] = [
        'icon'   => 'clipboard-check',
        'label'  => 'Attendance',
        'module' => 'attendance',
        'tree'   => true,
        'groups' => [
            'Manage' => [
                ['action' => 'index',  'label' => 'Add Attendance'],
                ['action' => 'view',   'label' => 'View Attendance'],
                ['action' => 'report', 'label' => 'Attendance Report'],
            ],
        ],
    ];
} elseif ($isTeacher) {
    // 🟢 Teacher: Can submit attendance (but cannot modify after submission — only Admin can edit)
    $navItems[] = [
        'icon'   => 'clipboard-check',
        'label'  => 'Attendance',
        'module' => 'attendance',
        'tree'   => true,
        'groups' => [
            'Manage' => [
                ['action' => 'index',  'label' => 'Take Attendance'],
                ['action' => 'view',   'label' => 'View Attendance'],
                ['action' => 'report', 'label' => 'Attendance Report'],
            ],
        ],
    ];
} elseif ($isStudent || $isParent) {
    // 🟡🟠 Student/Parent: View attendance performance only
    $navItems[] = [
        'icon'   => 'clipboard-check',
        'label'  => 'Attendance',
        'module' => 'attendance',
        'tree'   => true,
        'groups' => [
            'View' => [
                ['action' => 'view',   'label' => 'Attendance Performance'],
                ['action' => 'report', 'label' => 'Attendance Report'],
            ],
        ],
    ];
}
// ══════════════════════════════════════════════════════════════
// RESULTS / EXAMS — Role-specific visibility
// ══════════════════════════════════════════════════════════════
if ($isAdmin) {
    // 🔴🔵 Admin: Full results management
    $navItems[] = [
        'icon'   => 'academic-cap',
        'label'  => 'Results',
        'module' => 'exams',
        'tree'   => true,
        'groups' => [
            'Setup' => [
                ['action' => 'add-assessment', 'label' => 'Add Assessment'],
            ],
            'Results' => [
                ['action' => 'enter-results',  'label' => 'Enter Students\' Results'],
                ['action' => 'enter-conduct',  'label' => 'Enter Conduct Grades'],
            ],
            'Reports' => [
                ['action' => 'roster',          'label' => 'Generate Roster'],
                ['action' => 'report-cards',    'label' => 'Report Card'],
                ['action' => 'result-analysis', 'label' => 'Result Analysis'],
            ],
        ],
    ];
} elseif ($isTeacher) {
    // 🟢 Teacher: Marks Entry, Homework, Assignments for assigned classes
    $navItems[] = [
        'icon'   => 'academic-cap',
        'label'  => 'Results',
        'module' => 'exams',
        'tree'   => true,
        'groups' => [
            'Results' => [
                ['action' => 'enter-results',  'label' => 'Enter Students\' Results'],
                ['action' => 'enter-conduct',  'label' => 'Enter Conduct Grades'],
            ],
            'Reports' => [
                ['action' => 'roster',          'label' => 'Generate Roster'],
                ['action' => 'report-cards',    'label' => 'Report Card'],
                ['action' => 'result-analysis', 'label' => 'Result Analysis'],
            ],
        ],
    ];
} elseif ($isStudent || $isParent) {
    // 🟡🟠 Student/Parent: View all assessment results & report cards
    $navItems[] = [
        'icon'   => 'academic-cap',
        'label'  => 'Results',
        'module' => 'exams',
        'tree'   => true,
        'groups' => [
            'Reports' => [
                ['action' => 'report-cards',    'label' => 'Report Card'],
                ['action' => 'result-analysis', 'label' => 'Result Analysis'],
            ],
        ],
    ];
}
// ══════════════════════════════════════════════════════════════
// FINANCE — Admin & Accountant
// ══════════════════════════════════════════════════════════════
if ($isAdmin || auth_has_role('accountant')) {
    $navItems[] = [
        'icon'   => 'currency-dollar',
        'label'  => 'Finance',
        'module' => 'finance',
        'tree'   => true,
        'groups' => [
            'Manage Students' => [
                ['action' => 'students',        'label' => 'Student List'],
            ],
            'Grouping' => [
                ['action' => 'groups',          'label' => 'Groups'],
            ],
            'Payments' => [
                ['action' => 'collect-payment', 'label' => 'Collect Payment'],
                ['action' => 'payments',        'label' => 'School Payment History'],
                ['action' => 'supplementary-payments', 'label' => 'Supplementary Payments'],
            ],
            'Fee / Tuition' => [
                ['action' => 'fee-due',         'label' => 'Fee Due'],
                ['action' => 'supplementary-fees', 'label' => 'Supplementary Fees'],
            ],
            'Report Center' => [
                ['action' => 'report-students',  'label' => 'Students Report'],
                ['action' => 'report-penalty',   'label' => 'Penalty Report'],
                ['action' => 'report-supplementary', 'label' => 'Supplementary Transactions'],
            ],
        ],
    ];
}

// ══════════════════════════════════════════════════════════════
// HR MANAGEMENT — Admin, School Admin, Accountant (limited)
// ══════════════════════════════════════════════════════════════
if ($isAdmin) {
    // 🔴🔵 Super Admin / School Admin: Full HR access
    $navItems[] = [
        'icon'   => 'briefcase',
        'label'  => 'HR Management',
        'module' => 'hr',
        'tree'   => true,
        'groups' => [
            'Staff Management' => [
                ['action' => 'employees',       'label' => 'Employees'],
                ['action' => 'employee-form',   'label' => 'Add Employee'],
            ],
            'Organization' => [
                ['action' => 'departments',     'label' => 'Departments'],
            ],
            'Attendance' => [
                ['action' => 'attendance',      'label' => 'Mark Attendance'],
                ['action' => 'attendance-report','label' => 'Attendance Report'],
            ],
            'Leave' => [
                ['action' => 'leave-types',     'label' => 'Leave Types'],
                ['action' => 'holidays',        'label' => 'Holidays'],
                ['action' => 'leave-requests',  'label' => 'Leave Requests'],
                ['action' => 'leave-request-form', 'label' => 'Submit Leave'],
                ['action' => 'leave-balances',  'label' => 'Leave Balances'],
            ],
            'Payroll' => [
                ['action' => 'payroll',         'label' => 'Payroll Periods'],
                ['action' => 'payroll-printing','label' => 'Print Forms'],
            ],
            'Reports' => [
                ['action' => 'reports-dashboard', 'label' => 'Reports Dashboard'],
                ['action' => 'reports',         'label' => 'Quick Links'],
            ],
            'Devices' => [
                ['action' => 'devices',         'label' => 'Biometric Devices'],
            ],
        ],
    ];
} elseif (auth_has_role('accountant')) {
    // 🟣 Accountant: View employees, payroll, reports, print
    $navItems[] = [
        'icon'   => 'briefcase',
        'label'  => 'HR Management',
        'module' => 'hr',
        'tree'   => true,
        'groups' => [
            'Staff' => [
                ['action' => 'employees',       'label' => 'Employee List'],
            ],
            'Payroll' => [
                ['action' => 'payroll',         'label' => 'Payroll Periods'],
                ['action' => 'payroll-printing','label' => 'Print Forms'],
            ],
            'Reports' => [
                ['action' => 'reports-dashboard', 'label' => 'Reports Dashboard'],
            ],
        ],
    ];
}

// ══════════════════════════════════════════════════════════════
// MESSAGING — Role-specific visibility
// ══════════════════════════════════════════════════════════════
if ($isAdmin) {
    // 🔴🔵 Super Admin / School Admin: Bulk + Solo messaging
    $navItems[] = [
        'icon'   => 'chat-alt',
        'label'  => 'Messaging',
        'module' => 'messaging',
        'tree'   => true,
        'groups' => [
            'Messages' => [
                ['action' => 'inbox',          'label' => 'Inbox'],
                ['action' => 'compose',        'label' => 'New Message'],
                ['action' => 'sent',           'label' => 'Sent Messages'],
            ],
            'Broadcast' => [
                ['action' => 'bulk',           'label' => 'Bulk Message'],
                ['action' => 'bulk-history',   'label' => 'Bulk History'],
            ],
        ],
    ];
} elseif ($isTeacher) {
    // 🟢 Teacher: Solo messaging only
    $navItems[] = [
        'icon'   => 'chat-alt',
        'label'  => 'Messaging',
        'module' => 'messaging',
        'tree'   => true,
        'groups' => [
            'Messages' => [
                ['action' => 'inbox',          'label' => 'Inbox'],
                ['action' => 'compose',        'label' => 'New Message'],
                ['action' => 'sent',           'label' => 'Sent Messages'],
            ],
        ],
    ];
} elseif ($isStudent) {
    // 🟡 Student: Solo + Group messaging
    $navItems[] = [
        'icon'   => 'chat-alt',
        'label'  => 'Messaging',
        'module' => 'messaging',
        'tree'   => true,
        'groups' => [
            'Messages' => [
                ['action' => 'inbox',          'label' => 'Inbox'],
                ['action' => 'compose',        'label' => 'New Message'],
                ['action' => 'sent',           'label' => 'Sent Messages'],
            ],
            'Groups' => [
                ['action' => 'groups',         'label' => 'My Groups'],
                ['action' => 'group-create',   'label' => 'Create Group'],
            ],
        ],
    ];
} elseif ($isParent) {
    // 🟠 Parent: Solo messaging only
    $navItems[] = [
        'icon'   => 'chat-alt',
        'label'  => 'Messaging',
        'module' => 'messaging',
        'tree'   => true,
        'groups' => [
            'Messages' => [
                ['action' => 'inbox',          'label' => 'Inbox'],
                ['action' => 'compose',        'label' => 'New Message'],
                ['action' => 'sent',           'label' => 'Sent Messages'],
            ],
        ],
    ];
}

// ── Users — admin only ──
if ($isAdmin) {
    $navItems[] = ['icon' => 'user-group', 'label' => 'Users', 'url' => '/users', 'module' => 'users'];
}

// ── Reports — admin only ──
if ($isAdmin) {
    $navItems[] = ['icon' => 'chart-bar', 'label' => 'Reports', 'url' => '/reports', 'module' => 'reports'];
}

// ── Settings — admin only (super admin gets backup too) ──
if ($isAdmin) {
    $navItems[] = ['icon' => 'cog', 'label' => 'Settings', 'url' => '/settings', 'module' => 'settings'];
}
?>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-sidebar-bg dark:bg-gray-950 sidebar-transition transform -translate-x-full lg:translate-x-0 overflow-y-auto no-print">
    <!-- Logo / School Name -->
    <div class="flex items-center gap-3 px-4 h-16 border-b border-white/10">
        <img src="<?= url('/img/Logo.ico') ?>" alt="Urji Beri School" class="w-9 h-9 rounded-lg flex-shrink-0 object-contain">
        <div class="min-w-0">
            <h1 class="text-white font-semibold text-sm truncate"><?= e(get_school_name()) ?></h1>
            <p class="text-sidebar-text text-xs truncate">School SMS</p>
        </div>
        <button onclick="toggleSidebar()" class="lg:hidden ml-auto text-sidebar-text hover:text-white p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="mt-2 px-3 pb-4 space-y-1">
        <?php foreach ($navItems as $item):
            // ── Tree item ──────────────────────────────────────────────────
            if (!empty($item['tree'])):
                $mod = $item['module'];
                $parentActive = route_is($mod);
                $parentCls = $parentActive
                    ? 'bg-sidebar-active text-white'
                    : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white';
                $curAction = current_action();
                $submenuId = $mod . '-submenu';
                $arrowId   = $mod . '-arrow';
        ?>
        <div>
            <button type="button"
                    onclick="sidebarTreeToggle('<?= $submenuId ?>','<?= $arrowId ?>')"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $parentCls ?>">
                <?= sidebar_icon($item['icon']) ?>
                <span><?= e($item['label']) ?></span>
                <?php if ($mod === 'messaging' && $msgUnread > 0): ?>
                <span id="msg-tree-badge" class="ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= $msgUnread > 99 ? '99+' : $msgUnread ?></span>
                <?php endif; ?>
                <svg id="<?= $arrowId ?>"
                     class="<?= ($mod === 'messaging' && $msgUnread > 0) ? '' : 'ml-auto ' ?>w-4 h-4 flex-shrink-0 transition-transform duration-200 <?= $parentActive ? 'rotate-180' : '' ?>"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Sub-menu -->
            <div id="<?= $submenuId ?>"
                 class="overflow-hidden transition-all duration-200 <?= $parentActive ? '' : 'hidden' ?>">
                <div class="mt-1 ml-3 pl-3 border-l border-white/10 space-y-0.5 pb-1">
                    <?php foreach ($item['groups'] as $groupLabel => $children): ?>
                    <p class="px-2 pt-2 pb-0.5 text-xs font-semibold uppercase tracking-wider"
                       style="color:rgba(255,255,255,0.35)"><?= e($groupLabel) ?></p>
                    <?php foreach ($children as $child):
                        $childActive = ($inAcademics || $parentActive) && ($curAction === $child['action']);
                        $childCls = $childActive
                            ? 'bg-sidebar-active/80 text-white font-semibold'
                            : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white';
                    ?>
                    <a href="<?= url($mod, $child['action']) ?>"
                       class="flex items-center gap-2 px-2 py-1.5 rounded-md text-xs font-medium transition-colors <?= $childCls ?>">
                        <span class="w-1 h-1 rounded-full bg-current flex-shrink-0 opacity-60"></span>
                        <?= e($child['label']) ?>
                        <?php if ($mod === 'messaging' && $child['action'] === 'inbox' && $msgUnread > 0): ?>
                        <span id="msg-inbox-badge" class="ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= $msgUnread > 99 ? '99+' : $msgUnread ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php
            // ── Regular link item ───────────────────────────────────────────
            else:
                $active = route_is($item['module']);
                $activeClass = $active ? 'bg-sidebar-active text-white' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white';
        ?>
        <a href="<?= url($item['url']) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $activeClass ?>">
            <?= sidebar_icon($item['icon']) ?>
            <span><?= e($item['label']) ?></span>
            <?php if ($item['module'] === 'communication'): ?>
                <?php $unread = get_unread_notification_count(); if ($unread > 0): ?>
                <span class="ml-auto bg-red-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full"><?= $unread ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Session info -->
    <?php $activeSession = get_active_session(); ?>
    <?php if ($activeSession): ?>
    <div class="mx-3 mb-4 p-3 rounded-lg bg-white/5 border border-white/10">
        <p class="text-xs text-sidebar-text">Active Session</p>
        <p class="text-sm text-white font-medium"><?= e($activeSession['name']) ?></p>
        <?php $activeTerm = get_active_term(); if ($activeTerm): ?>
        <p class="text-xs text-primary-400 mt-0.5"><?= e($activeTerm['name']) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</aside>

<script>
/**
 * Toggle an academics-style sidebar tree.
 * submenuId  — id of the <div> to show/hide
 * arrowId    — id of the <svg> to rotate
 */
function sidebarTreeToggle(submenuId, arrowId) {
    var menu  = document.getElementById(submenuId);
    var arrow = document.getElementById(arrowId);
    if (!menu) return;
    var isHidden = menu.classList.contains('hidden');
    if (isHidden) {
        menu.classList.remove('hidden');
        if (arrow) arrow.classList.add('rotate-180');
    } else {
        menu.classList.add('hidden');
        if (arrow) arrow.classList.remove('rotate-180');
    }
}
</script>

<?php
function sidebar_icon(string $name): string {
    $icons = [
        'home'            => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
        'academic-cap'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
        'users'           => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'clipboard-check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        'document-text'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'currency-dollar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'chat-alt'        => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
        'user-group'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'chart-bar'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'cog'             => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'briefcase'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
    ];

    $path = $icons[$name] ?? $icons['home'];
    return '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">' . $path . '</svg>';
}
?>
