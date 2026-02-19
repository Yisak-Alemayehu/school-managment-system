<?php
/**
 * Exams Module Routes
 */

$action = current_action();

switch ($action) {
    // ===== Assignments =====
    case 'assignments':
    case '':
        auth_require_permission('assignment.view');
        $pageTitle = 'Assignments';
        require __DIR__ . '/views/assignments.php';
        break;
    case 'assignment-create':
        auth_require_permission('assignment.manage');
        $pageTitle = 'Create Assignment';
        require __DIR__ . '/views/assignment_form.php';
        break;
    case 'assignment-save':
        auth_require_permission('assignment.manage');
        require __DIR__ . '/actions/assignment_save.php';
        break;
    case 'assignment-view':
        auth_require_permission('assignment.view');
        $pageTitle = 'Assignment Details';
        require __DIR__ . '/views/assignment_view.php';
        break;
    case 'assignment-delete':
        auth_require_permission('assignment.manage');
        require __DIR__ . '/actions/assignment_delete.php';
        break;

    // ===== Exams =====
    case 'exams':
        auth_require_permission('exam.view');
        $pageTitle = 'Exams';
        require __DIR__ . '/views/exams.php';
        break;
    case 'exam-save':
        auth_require_permission('exam.manage');
        require __DIR__ . '/actions/exam_save.php';
        break;
    case 'exam-schedule':
        auth_require_permission('exam.manage');
        $pageTitle = 'Exam Schedule';
        require __DIR__ . '/views/exam_schedule.php';
        break;
    case 'exam-schedule-save':
        auth_require_permission('exam.manage');
        require __DIR__ . '/actions/exam_schedule_save.php';
        break;

    // ===== Marks =====
    case 'marks':
        auth_require_permission('marks.view');
        $pageTitle = 'Enter Marks';
        require __DIR__ . '/views/marks.php';
        break;
    case 'marks-save':
        auth_require_permission('marks.manage');
        require __DIR__ . '/actions/marks_save.php';
        break;

    // ===== Grade Scale =====
    case 'grade-scale':
        auth_require_permission('exam.manage');
        $pageTitle = 'Grade Scale';
        require __DIR__ . '/views/grade_scale.php';
        break;
    case 'grade-scale-save':
        auth_require_permission('exam.manage');
        require __DIR__ . '/actions/grade_scale_save.php';
        break;

    // ===== Report Cards =====
    case 'report-cards':
        auth_require_permission('report_card.view');
        $pageTitle = 'Report Cards';
        require __DIR__ . '/views/report_cards.php';
        break;
    case 'report-card-generate':
        auth_require_permission('report_card.manage');
        require __DIR__ . '/actions/report_card_generate.php';
        break;
    case 'report-card-print':
        auth_require_permission('report_card.view');
        $pageTitle = 'Print Report Card';
        require __DIR__ . '/views/report_card_print.php';
        break;

    default:
        http_response_code(404);
        require APP_ROOT . '/templates/errors/404.php';
}
