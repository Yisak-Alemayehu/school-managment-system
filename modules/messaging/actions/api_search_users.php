<?php
/**
 * Messaging — API: Search Users (AJAX)
 */
$userId = auth_user_id();
$query  = trim(input('q'));

if (mb_strlen($query) < 2) {
    json_response([]);
}

// Sanitize search term
$search = '%' . $query . '%';

// Super admins and admins can message anyone
// Teachers can message admins, other teachers, parents
// Students can message admins, teachers, other students
// Parents can message admins, teachers

$sql = "
    SELECT u.id, u.full_name, u.email, r.name AS role_name
      FROM users u
      JOIN user_roles ur ON u.id = ur.user_id
      JOIN roles r ON ur.role_id = r.id
     WHERE u.id != ?
       AND u.status = 'active'
       AND u.deleted_at IS NULL
       AND (u.full_name LIKE ? OR u.email LIKE ?)
     ORDER BY u.full_name
     LIMIT 15
";

$users = db_fetch_all($sql, [$userId, $search, $search]);

$results = [];
foreach ($users as $u) {
    $results[] = [
        'id'        => (int) $u['id'],
        'full_name' => $u['full_name'],
        'role'      => $u['role_name'],
    ];
}

json_response($results);
