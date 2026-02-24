<?php
/**
 * API Module — JSON helper endpoints
 * Consumed by AJAX calls (class → section / class → subject lookups, etc.)
 */

auth_require();

// All responses from this module are JSON
header('Content-Type: application/json; charset=utf-8');
// Prevent caching of dynamic data
header('Cache-Control: no-store');

$action = current_action();

switch ($action) {

    // ── GET /api/sections?class_id=X ───────────────────────────────
    case 'sections':
        $classId = input_int('class_id');
        if (!$classId) {
            echo json_encode([]);
            exit;
        }
        $rows = db_fetch_all(
            "SELECT id, name FROM sections WHERE class_id = ? AND is_active = 1 ORDER BY name",
            [$classId]
        );
        echo json_encode($rows ?: []);
        exit;

    // ── GET /api/subjects?class_id=X&session_id=Y ──────────────────
    case 'subjects':
        $classId   = input_int('class_id');
        $sessionId = input_int('session_id');
        if (!$classId || !$sessionId) {
            echo json_encode([]);
            exit;
        }
        $rows = db_fetch_all(
            "SELECT s.id, s.name, s.code
             FROM subjects s
             JOIN class_subjects cs ON cs.subject_id = s.id
             WHERE cs.class_id = ? AND cs.session_id = ?
             ORDER BY s.name",
            [$classId, $sessionId]
        );
        echo json_encode($rows ?: []);
        exit;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        exit;
}
