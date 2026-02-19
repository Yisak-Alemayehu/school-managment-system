<?php
/**
 * Database Connection (PDO)
 * Urjiberi School Management ERP
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

/**
 * Get PDO database connection (singleton pattern via static var)
 */
function db_connect(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            if (APP_DEBUG) {
                die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
            }
            die('A database error occurred. Please try again later.');
        }
    }

    return $pdo;
}

/**
 * Execute a query with bound parameters. Returns PDOStatement.
 */
function db_query(string $sql, array $params = []): PDOStatement {
    $pdo = db_connect();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch all rows
 */
function db_fetch_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

/**
 * Fetch single row
 */
function db_fetch_one(string $sql, array $params = []): ?array {
    $row = db_query($sql, $params)->fetch();
    return $row ?: null;
}

/**
 * Fetch single column value
 */
function db_fetch_value(string $sql, array $params = []) {
    return db_query($sql, $params)->fetchColumn();
}

/**
 * Insert and return last insert ID
 */
function db_insert(string $table, array $data): int {
    $columns = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
    db_query($sql, array_values($data));
    return (int) db_connect()->lastInsertId();
}

/**
 * Update rows. Returns number of affected rows.
 */
function db_update(string $table, array $data, string $where, array $whereParams = []): int {
    $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
    $sql = "UPDATE `$table` SET $set WHERE $where";
    $params = array_merge(array_values($data), $whereParams);
    return db_query($sql, $params)->rowCount();
}

/**
 * Delete rows. Returns number of affected rows.
 */
function db_delete(string $table, string $where, array $params = []): int {
    $sql = "DELETE FROM `$table` WHERE $where";
    return db_query($sql, $params)->rowCount();
}

/**
 * Soft delete (set deleted_at)
 */
function db_soft_delete(string $table, string $where, array $params = []): int {
    $sql = "UPDATE `$table` SET `deleted_at` = NOW() WHERE $where AND `deleted_at` IS NULL";
    return db_query($sql, $params)->rowCount();
}

/**
 * Count rows
 */
function db_count(string $table, string $where = '1=1', array $params = []): int {
    $sql = "SELECT COUNT(*) FROM `$table` WHERE $where";
    return (int) db_fetch_value($sql, $params);
}

/**
 * Check if row exists
 */
function db_exists(string $table, string $where, array $params = []): bool {
    return db_count($table, $where, $params) > 0;
}

/**
 * Begin transaction
 */
function db_begin(): void {
    db_connect()->beginTransaction();
}

/**
 * Commit transaction
 */
function db_commit(): void {
    db_connect()->commit();
}

/**
 * Rollback transaction
 */
function db_rollback(): void {
    if (db_connect()->inTransaction()) {
        db_connect()->rollBack();
    }
}

/**
 * Run callback inside a transaction
 */
function db_transaction(callable $callback) {
    db_begin();
    try {
        $result = $callback();
        db_commit();
        return $result;
    } catch (Throwable $e) {
        db_rollback();
        throw $e;
    }
}

/**
 * Build paginated query
 */
function db_paginate(string $sql, array $params = [], int $page = 1, int $perPage = DEFAULT_PER_PAGE): array {
    $perPage = min($perPage, MAX_PER_PAGE);
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    // Get total count
    $countSql = "SELECT COUNT(*) FROM ($sql) AS count_query";
    $total = (int) db_fetch_value($countSql, $params);

    // Get paginated results
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $rows = db_fetch_all($sql, $params);

    return [
        'data'         => $rows,
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $page,
        'last_page'    => (int) ceil($total / $perPage),
        'from'         => $total > 0 ? $offset + 1 : 0,
        'to'           => min($offset + $perPage, $total),
    ];
}

/**
 * Alias for db_connect()
 */
function db_connection(): PDO {
    return db_connect();
}
