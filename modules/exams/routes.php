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

    // ===== Assessments (Results module) =====
    case 'add-assessment':
        auth_require_permission('exam.manage');
        $pageTitle = 'Add Assessment';
        require __DIR__ . '/views/assessment_add.php';
        break;
    case 'assessment-save':
        auth_require_permission('exam.manage');
        require __DIR__ . '/actions/assessment_save.php';
        break;
    case 'assessment-delete':
        auth_require_permission('exam.manage');
        require __DIR__ . '/actions/assessment_delete.php';
        break;

    // ===== Enter Conduct (behavioral grades) =====
    case 'enter-conduct':
        auth_require_permission('marks.view');
        $pageTitle = 'Enter Student Conduct';
        require __DIR__ . '/views/enter_conduct.php';
        break;
    case 'conduct-save':
        auth_require_permission('marks.manage');
        require __DIR__ . '/actions/conduct_save.php';
        break;

    // ===== Enter Results =====
    case 'enter-results':
        auth_require_permission('marks.view');
        $pageTitle = 'Enter Students\' Results';
        require __DIR__ . '/views/enter_results.php';
        break;
    case 'results-save':
        auth_require_permission('marks.manage');
        require __DIR__ . '/actions/results_save.php';
        break;

    // ===== Roster =====
    case 'roster':
        auth_require_permission('report_card.view');
        $pageTitle = 'Generate Roster';
        require __DIR__ . '/views/roster.php';
        break;

    // ===== Report Cards (Results) =====
    case 'result-cards':
        auth_require_permission('report_card.view');
        $pageTitle = 'Report Cards';
        require __DIR__ . '/views/result_cards.php';
        break;

    // ===== Result Analysis =====
    case 'result-analysis':
        auth_require_permission('exam.view');
        $pageTitle = 'Result Analysis';
        require __DIR__ . '/views/result_analysis.php';
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

    // ===== AJAX: current total marks committed for class+subject+term =====
    case 'ajax-subject-total':
        auth_require_permission('exam.view');
        $ajClassId   = input_int('class_id');
        $ajSubjectId = input_int('subject_id');
        $ajTermId    = input_int('term_id') ?: null;
        $ajSession   = get_active_session();
        $ajSessionId = $ajSession['id'] ?? 0;
        $ajTotal     = 0;
        if ($ajClassId && $ajSubjectId && $ajSessionId) {
            $tw = $ajTermId ? ' AND term_id = ?' : ' AND term_id IS NULL';
            $tp = $ajTermId ? [$ajSessionId, $ajClassId, $ajSubjectId, $ajTermId]
                           : [$ajSessionId, $ajClassId, $ajSubjectId];
            $ajTotal = (int) db_fetch_value(
                "SELECT COALESCE(SUM(total_marks),0) FROM assessments
                 WHERE session_id=? AND class_id=? AND subject_id=?{$tw}",
                $tp
            );
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['total' => $ajTotal, 'remaining' => 100 - $ajTotal]);
        exit;

    // ===== AJAX: subjects for a given class (used by result-analysis dropdown) =====
    case 'ajax-subjects':
        auth_require_permission('exam.view');
        $classId   = input_int('class_id');
        $sessionId = (get_active_session() ?? [])['id'] ?? 0;
        $subjects  = $classId && $sessionId
            ? db_fetch_all(
                "SELECT s.id, s.name FROM subjects s
                 JOIN class_subjects cs ON cs.subject_id = s.id
                 WHERE cs.class_id = ? AND cs.session_id = ? ORDER BY s.name",
                [$classId, $sessionId])
            : [];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_values($subjects));
        exit;

    default:
        http_response_code(404);
        require APP_ROOT . '/templates/errors/404.php';
}
