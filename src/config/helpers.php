<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/enrollment');
}
function url($path = '') {
    return BASE_URL . $path;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get document path by key
 * @param string $key Path key (e.g., 'applicant_photo', 'student_document')
 * @param bool $full_url Return full URL (default: false, returns path only)
 * @return string|null Path value or null if not found/inactive
 */
function get_document_path($key, $full_url = false) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT path_value FROM document_paths WHERE path_key = ? AND is_active = 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if ($row) {
            return $full_url ? url($row['path_value']) : $row['path_value'];
        }
    } catch (Exception $e) {
        // Fallback to default paths if table doesn't exist or error
    }
    return null;
}

/**
 * Get all active document paths
 * @return array Array of document path configurations
 */
function get_all_document_paths() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM document_paths WHERE is_active = 1 ORDER BY path_key ASC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Set document path (create or update)
 * @param string $key Path key
 * @param string $value Path value
 * @param string|null $description Optional description
 * @return bool Success status
 */
function set_document_path($key, $value, $description = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO document_paths (path_key, path_value, description, created_by)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE path_value = VALUES(path_value), description = VALUES(description), updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$key, $value, $description, $_SESSION['user_id'] ?? null]);
    } catch (Exception $e) {
        return false;
    }
}
