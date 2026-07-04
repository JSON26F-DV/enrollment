<?php
/**
 * Cron job to clean up expired applications and documents
 * 
 * Run this daily via cron:
 * 0 0 * * * php /path/to/cron/cleanup_expired.php
 * 
 * Or add to system crontab:
 * sudo crontab -e
 */

// Set the base path
define('BASE_PATH', __DIR__ . '/..');

// Include database configuration
require_once BASE_PATH . '/src/config/config.php';

echo "Starting cleanup process...\n";

try {
    // 1. Delete expired pending applications (older than 14 days)
    $stmt = $pdo->prepare("
        SELECT id FROM applicants 
        WHERE status = 'pending' 
        AND expires_at < NOW()
    ");
    $stmt->execute();
    $expired_pending = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($expired_pending) . " expired pending applications.\n";
    
    foreach ($expired_pending as $applicant_id) {
        echo "Deleting pending applicant ID: $applicant_id\n";
        $stmt = $pdo->prepare("DELETE FROM applicants WHERE id = ?");
        $stmt->execute([$applicant_id]);
    }
    
    // 2. Delete old approved/rejected/revision applications (older than 3 months = 90 days)
    $stmt = $pdo->prepare("
        SELECT id, user_id FROM applicants 
        WHERE status IN ('approved', 'rejected', 'revision') 
        AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    $stmt->execute();
    $old_applications = $stmt->fetchAll();
    
    echo "Found " . count($old_applications) . " old applications (older than 3 months).\n";
    
    foreach ($old_applications as $app) {
        echo "Deleting application ID: {$app['id']}\n";
        
        // Delete associated student record if exists
        if ($app['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM students WHERE user_id = ?");
            $stmt->execute([$app['user_id']]);
            
            // Delete user account
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$app['user_id']]);
        }
        
        // Delete the applicant record
        $stmt = $pdo->prepare("DELETE FROM applicants WHERE id = ?");
        $stmt->execute([$app['id']]);
    }
    
    // 3. Clean up expired document records
    $stmt = $pdo->prepare("
        DELETE FROM applicant_documents 
        WHERE expires_at < NOW() 
        AND applicant_id NOT IN (SELECT id FROM applicants)
    ");
    $stmt->execute();
    $orphan_deleted = $stmt->rowCount();
    
    if ($orphan_deleted > 0) {
        echo "Deleted $orphan_deleted orphaned document records.\n";
    }
    
    // 4. Clean up upload directories without applicants
    $upload_base = BASE_PATH . '/uploads/applicants';
    if (is_dir($upload_base)) {
        $dirs = glob($upload_base . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $id = basename($dir);
            if (!is_numeric($id)) continue;
            
            // Check if applicant still exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM applicants WHERE id = ?");
            $stmt->execute([$id]);
            $exists = $stmt->fetchColumn() > 0;
            
            if (!$exists) {
                recursiveDelete($dir);
                echo "Deleted orphaned upload directory: $dir\n";
            }
        }
    }
    
    echo "\nCleanup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}

function recursiveDelete($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? recursiveDelete($path) : unlink($path);
    }
    rmdir($dir);
}