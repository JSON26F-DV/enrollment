<?php
require_once __DIR__ . '/../../../../src/config/bootstrap.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE users SET is_deleted = TRUE, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: " . url('/src/view/admin/landing/dashboard.php'));
exit;
