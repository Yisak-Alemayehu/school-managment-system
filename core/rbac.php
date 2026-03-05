<?php
/**
 * RBAC Helper Functions
 * Role-Based Access Control utilities for teacher class restrictions,
 * student/parent data scoping, and permission-aware navigation.
 *
 * Urji Beri School Management System
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// ═══════════════════════════════════════════════════════════════
// TEACHER — Class & Section Restrictions
// ═══════════════════════════════════════════════════════════════

/**
 * Get class IDs the current teacher is assigned to (via class_teachers).
 * Returns all classes if the user is admin/super_admin.
 * Cached in session for the request.
 */
function rbac_teacher_class_ids(): array {
    if (auth_is_super_admin() || auth_has_role('admin')) {
        // Admin/super admin: no restriction
        return [];
    }

    if (isset($_SESSION['_rbac_teacher_class_ids'])) {
        return $_SESSION['_rbac_teacher_class_ids'];
    }

    $userId = auth_user_id();
    if (!$userId) return [];

    $session = get_active_session();
    $sessionId = $session['id'] ?? 0;

    $rows = db_fetch_all(
        "SELECT DISTINCT class_id FROM class_teachers WHERE teacher_id = ? AND session_id = ?",
        [$userId, $sessionId]
    );

    $_SESSION['_rbac_teacher_class_ids'] = array_map('intval', array_column($rows, 'class_id'));
    return $_SESSION['_rbac_teacher_class_ids'];
}

/**
 * Get section IDs the current teacher is assigned to.
 * Returns empty array (no restriction) for admin/super_admin.
 */
function rbac_teacher_section_ids(): array {
    if (auth_is_super_admin() || auth_has_role('admin')) {
        return [];
    }

    if (isset($_SESSION['_rbac_teacher_section_ids'])) {
        return $_SESSION['_rbac_teacher_section_ids'];
    }

    $userId = auth_user_id();
    if (!$userId) return [];

    $session = get_active_session();
    $sessionId = $session['id'] ?? 0;

    $rows = db_fetch_all(
        "SELECT DISTINCT section_id FROM class_teachers WHERE teacher_id = ? AND session_id = ? AND section_id IS NOT NULL",
        [$userId, $sessionId]
    );

    $_SESSION['_rbac_teacher_section_ids'] = array_map('intval', array_column($rows, 'section_id'));
    return $_SESSION['_rbac_teacher_section_ids'];
}

/**
 * Get subject IDs the current teacher is assigned to for a given class.
 */
function rbac_teacher_subject_ids(?int $classId = null): array {
    if (auth_is_super_admin() || auth_has_role('admin')) {
        return [];
    }

    $userId = auth_user_id();
    if (!$userId) return [];

    $session = get_active_session();
    $sessionId = $session['id'] ?? 0;

    $sql = "SELECT DISTINCT subject_id FROM class_teachers WHERE teacher_id = ? AND session_id = ?";
    $params = [$userId, $sessionId];

    if ($classId) {
        $sql .= " AND class_id = ?";
        $params[] = $classId;
    }

    $rows = db_fetch_all($sql, $params);
    return array_map('intval', array_column($rows, 'subject_id'));
}

/**
 * Check if the current teacher is assigned to a specific class.
 * Always true for admin/super_admin.
 */
function rbac_teacher_has_class(int $classId): bool {
    if (auth_is_super_admin() || auth_has_role('admin')) {
        return true;
    }
    $classIds = rbac_teacher_class_ids();
    return empty($classIds) || in_array($classId, $classIds);
}

/**
 * Require that the current teacher is assigned to the given class.
 * Returns 403 if not.
 */
function rbac_require_teacher_class(int $classId): void {
    if (!rbac_teacher_has_class($classId)) {
        http_response_code(403);
        include TEMPLATES_PATH . '/errors/403.php';
        exit;
    }
}

// ═══════════════════════════════════════════════════════════════
// STUDENT — Own Data Scoping
// ═══════════════════════════════════════════════════════════════

/**
 * Get the student record linked to the current user.
 * Returns null if the user is not a student or not linked.
 */
function rbac_get_student(): ?array {
    if (!auth_has_role('student')) {
        return null;
    }

    if (isset($_SESSION['_rbac_student'])) {
        return $_SESSION['_rbac_student'] ?: null;
    }

    $userId = auth_user_id();
    $student = db_fetch_one(
        "SELECT s.*, e.class_id, e.section_id, e.roll_no,
                c.name as class_name, sec.name as section_name
         FROM students s
         LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
         LEFT JOIN classes c ON e.class_id = c.id
         LEFT JOIN sections sec ON e.section_id = sec.id
         WHERE s.user_id = ? AND s.status = 'active' AND s.deleted_at IS NULL
         LIMIT 1",
        [$userId]
    );

    $_SESSION['_rbac_student'] = $student ?: [];
    return $student ?: null;
}

/**
 * Get the student ID linked to the current user.
 */
function rbac_student_id(): ?int {
    $student = rbac_get_student();
    return $student ? (int)$student['id'] : null;
}

// ═══════════════════════════════════════════════════════════════
// PARENT — Children Scoping
// ═══════════════════════════════════════════════════════════════

/**
 * Get all children (student records) linked to the current parent user.
 */
function rbac_get_children(): array {
    if (!auth_has_role('parent')) {
        return [];
    }

    if (isset($_SESSION['_rbac_children'])) {
        return $_SESSION['_rbac_children'];
    }

    $userId = auth_user_id();
    $children = db_fetch_all(
        "SELECT s.id, s.admission_no, s.first_name, s.last_name, s.full_name, s.gender, s.photo,
                e.class_id, e.section_id, e.roll_no,
                c.name as class_name, sec.name as section_name
         FROM students s
         JOIN student_guardians sg ON s.id = sg.student_id
         JOIN guardians g ON sg.guardian_id = g.id
         LEFT JOIN enrollments e ON s.id = e.student_id AND e.status = 'active'
         LEFT JOIN classes c ON e.class_id = c.id
         LEFT JOIN sections sec ON e.section_id = sec.id
         WHERE g.user_id = ? AND s.status = 'active' AND s.deleted_at IS NULL
         ORDER BY c.sort_order, s.first_name",
        [$userId]
    );

    $_SESSION['_rbac_children'] = $children;
    return $children;
}

/**
 * Get IDs of all children linked to the current parent.
 */
function rbac_children_ids(): array {
    $children = rbac_get_children();
    return array_map(function($c) { return (int)$c['id']; }, $children);
}

/**
 * Check if the current parent has access to a specific student.
 */
function rbac_parent_has_child(int $studentId): bool {
    if (auth_is_super_admin() || auth_has_role('admin') || auth_has_role('teacher')) {
        return true;
    }
    if (auth_has_role('student')) {
        return rbac_student_id() === $studentId;
    }
    return in_array($studentId, rbac_children_ids());
}

// ═══════════════════════════════════════════════════════════════
// PERMISSION-AWARE QUERY HELPERS
// ═══════════════════════════════════════════════════════════════

/**
 * Build a WHERE clause to restrict queries to the teacher's assigned classes.
 * Returns ['sql' => 'AND class_id IN (...)', 'params' => [...]] or empty clause.
 *
 * @param string $column The column name (e.g., 'class_id', 'a.class_id')
 */
function rbac_class_filter(string $column = 'class_id'): array {
    if (auth_is_super_admin() || auth_has_role('admin')) {
        return ['sql' => '', 'params' => []];
    }

    if (auth_has_role('teacher')) {
        $classIds = rbac_teacher_class_ids();
        if (!empty($classIds)) {
            $placeholders = implode(',', array_fill(0, count($classIds), '?'));
            return ['sql' => "AND {$column} IN ({$placeholders})", 'params' => $classIds];
        }
    }

    if (auth_has_role('student')) {
        $student = rbac_get_student();
        if ($student && !empty($student['class_id'])) {
            return ['sql' => "AND {$column} = ?", 'params' => [(int)$student['class_id']]];
        }
    }

    if (auth_has_role('parent')) {
        $children = rbac_get_children();
        $classIds = array_unique(array_filter(array_column($children, 'class_id')));
        if (!empty($classIds)) {
            $placeholders = implode(',', array_fill(0, count($classIds), '?'));
            return ['sql' => "AND {$column} IN ({$placeholders})", 'params' => array_values($classIds)];
        }
    }

    return ['sql' => '', 'params' => []];
}

/**
 * Build a WHERE clause to restrict queries to the student's own records.
 * Returns ['sql' => 'AND student_id = ?', 'params' => [...]] or empty clause.
 *
 * @param string $column The column name (e.g., 'student_id', 's.id')
 */
function rbac_student_filter(string $column = 'student_id'): array {
    if (auth_is_super_admin() || auth_has_role('admin') || auth_has_role('teacher')) {
        return ['sql' => '', 'params' => []];
    }

    if (auth_has_role('student')) {
        $studentId = rbac_student_id();
        if ($studentId) {
            return ['sql' => "AND {$column} = ?", 'params' => [$studentId]];
        }
    }

    if (auth_has_role('parent')) {
        $childIds = rbac_children_ids();
        if (!empty($childIds)) {
            $placeholders = implode(',', array_fill(0, count($childIds), '?'));
            return ['sql' => "AND {$column} IN ({$placeholders})", 'params' => $childIds];
        }
    }

    // No access
    return ['sql' => 'AND 1=0', 'params' => []];
}

/**
 * Clear RBAC session caches (call on role/assignment changes).
 */
function rbac_clear_cache(): void {
    unset(
        $_SESSION['_rbac_teacher_class_ids'],
        $_SESSION['_rbac_teacher_section_ids'],
        $_SESSION['_rbac_student'],
        $_SESSION['_rbac_children']
    );
}
