<?php
/**
 * Academics Module — Routes
 * Sessions, Terms, Mediums, Streams, Shifts, Classes, Sections, Subjects,
 * Class Subjects, Elective Subjects, Class Teachers, Subject Teachers, Promote, Timetable
 */

auth_require();

$action = current_action();

switch ($action) {
    // ── Academic Sessions ────────────────────────────────────
    case 'sessions':
        auth_require_permission('academics.manage');
        $pageTitle = 'Academic Sessions';
        require __DIR__ . '/views/sessions.php';
        break;

    case 'session-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/session_save.php'; }
        break;

    case 'session-toggle':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/session_toggle.php'; }
        break;

    // ── Terms ────────────────────────────────────────────────
    case 'terms':
        auth_require_permission('academics.manage');
        $pageTitle = 'Terms / Semesters';
        require __DIR__ . '/views/terms.php';
        break;

    case 'term-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/term_save.php'; }
        break;

    // ── Mediums ──────────────────────────────────────────────
    case 'mediums':
        auth_require_permission('academics.manage');
        $pageTitle = 'Mediums';
        require __DIR__ . '/views/mediums.php';
        break;

    case 'medium-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/medium_save.php'; }
        break;

    // ── Streams ──────────────────────────────────────────────
    case 'streams':
        auth_require_permission('academics.manage');
        $pageTitle = 'Streams';
        require __DIR__ . '/views/streams.php';
        break;

    case 'stream-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/stream_save.php'; }
        break;

    // ── Shifts ───────────────────────────────────────────────
    case 'shifts':
        auth_require_permission('academics.manage');
        $pageTitle = 'Shifts';
        require __DIR__ . '/views/shifts.php';
        break;

    case 'shift-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/shift_save.php'; }
        break;

    // ── Classes ──────────────────────────────────────────────
    case 'classes':
        auth_require_permission('academics.manage');
        $pageTitle = 'Classes';
        require __DIR__ . '/views/classes.php';
        break;

    case 'class-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/class_save.php'; }
        break;

    // ── Sections ─────────────────────────────────────────────
    case 'sections':
        auth_require_permission('academics.manage');
        $pageTitle = 'Sections';
        require __DIR__ . '/views/sections.php';
        break;

    case 'section-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/section_save.php'; }
        break;

    // ── Subjects ─────────────────────────────────────────────
    case 'subjects':
        auth_require_permission('academics.manage');
        $pageTitle = 'Subjects';
        require __DIR__ . '/views/subjects.php';
        break;

    case 'subject-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/subject_save.php'; }
        break;

    // ── Class-Subject Mapping ────────────────────────────────
    case 'class-subjects':
        auth_require_permission('academics.manage');
        $pageTitle = 'Class-Subject Assignment';
        require __DIR__ . '/views/class_subjects.php';
        break;

    case 'class-subjects-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/class_subjects_save.php'; }
        break;

    // ── Elective Subjects Assignment ─────────────────────────
    case 'elective-subjects':
        auth_require_permission('academics.manage');
        $pageTitle = 'Assign Elective Subjects';
        require __DIR__ . '/views/elective_subjects.php';
        break;

    case 'elective-subjects-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/elective_subjects_save.php'; }
        break;

    // ── Class Teachers ───────────────────────────────────────
    case 'class-teachers':
        auth_require_permission('academics.manage');
        $pageTitle = 'Class Teachers';
        require __DIR__ . '/views/class_teachers.php';
        break;

    case 'class-teacher-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/class_teacher_save.php'; }
        break;

    // ── Subject Teachers ─────────────────────────────────────
    case 'subject-teachers':
        auth_require_permission('academics.manage');
        $pageTitle = 'Subject Teachers';
        require __DIR__ . '/views/subject_teachers.php';
        break;

    case 'subject-teacher-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/subject_teacher_save.php'; }
        break;

    case 'subject-teacher-delete':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/subject_teacher_delete.php'; }
        break;

    // ── Promote Students ─────────────────────────────────────
    case 'promote':
        auth_require_permission('academics.manage');
        $pageTitle = 'Promote Students';
        require __DIR__ . '/views/promote.php';
        break;

    case 'promote-save':
        auth_require_permission('academics.manage');
        if (is_post()) { require __DIR__ . '/actions/promote_save.php'; }
        break;

    // ── Timetable ────────────────────────────────────────────
    case 'timetable':
        auth_require_permission('timetable.view');
        $pageTitle = 'Timetable';
        require __DIR__ . '/views/timetable.php';
        break;

    case 'timetable-save':
        auth_require_permission('timetable.manage');
        if (is_post()) { require __DIR__ . '/actions/timetable_save.php'; }
        break;

    default:
        redirect(url('academics', 'sessions'));
        break;
}
