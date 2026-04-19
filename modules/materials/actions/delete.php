<?php
/**
 * Academic Materials — Delete Action (POST)
 */

csrf_protect();
$id = route_id();

$material = db_fetch_one(
    "SELECT * FROM academic_materials WHERE id = ? AND deleted_at IS NULL",
    [$id]
);
if (!$material) {
    set_flash('error', 'Material not found.');
    redirect(url('materials'));
}

db_soft_delete('academic_materials', 'id = ?', [$id]);

set_flash('success', 'Material deleted successfully.');
audit_log('materials.delete', "Material ID $id deleted: {$material['title']}");
redirect(url('materials'));
