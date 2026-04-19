<?php
/**
 * Portal — Session Management
 * Handles student/parent portal authentication (separate from main CMS auth).
 * Stored in $_SESSION['portal'].
 */

if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

function portal_check(): bool
{
    return isset($_SESSION['portal']['user_id'], $_SESSION['portal']['role']);
}

function portal_role(): string
{
    return $_SESSION['portal']['role'] ?? '';
}

function portal_user_id(): int
{
    return (int) ($_SESSION['portal']['user_id'] ?? 0);
}

function portal_linked_id(): int
{
    return (int) ($_SESSION['portal']['linked_id'] ?? 0);
}

function portal_user(): array
{
    return $_SESSION['portal']['user'] ?? [];
}

function portal_student(): array
{
    return $_SESSION['portal']['student'] ?? [];
}

function portal_guardian(): array
{
    return $_SESSION['portal']['guardian'] ?? [];
}

function portal_children(): array
{
    return $_SESSION['portal']['children'] ?? [];
}

function portal_active_child_id(): ?int
{
    $id = $_SESSION['portal']['active_child_id'] ?? null;
    return $id ? (int) $id : null;
}

function portal_active_child(): ?array
{
    $id = portal_active_child_id();
    if (!$id) {
        return !empty($_SESSION['portal']['children']) ? $_SESSION['portal']['children'][0] : null;
    }
    foreach (portal_children() as $c) {
        if ((int) $c['id'] === $id) {
            return $c;
        }
    }
    return null;
}

/**
 * Require portal login. Optionally restrict to a role.
 */
function portal_require(string $role = ''): void
{
    if (!portal_check()) {
        redirect(url('portal', 'login'));
    }
    if ($role && portal_role() !== $role) {
        redirect(url('portal', 'dashboard'));
    }
}

function portal_login_session(array $data): void
{
    $_SESSION['portal'] = $data;
}

function portal_logout(): void
{
    unset($_SESSION['portal']);
}

function portal_switch_child(int $studentId): void
{
    foreach (portal_children() as $c) {
        if ((int) $c['id'] === $studentId) {
            $_SESSION['portal']['active_child_id'] = $studentId;
            return;
        }
    }
}

function portal_url(string $action, array $params = []): string
{
    $base = url('portal', $action);
    if ($params) {
        $sep = str_contains($base, '?') ? '&' : '?';
        return $base . $sep . http_build_query($params);
    }
    return $base;
}
