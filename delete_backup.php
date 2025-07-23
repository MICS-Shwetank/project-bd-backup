<?php
/**
 * Backup Deletion Script
 * 
 * This script handles secure deletion of backup files. It includes security
 * checks to prevent directory traversal attacks and ensures files are only
 * deleted from the designated backups directory.
 * 
 * Security Features:
 * - Validates file path is within backup directory
 * - Checks file existence before deletion
 * - Uses realpath() to resolve any path traversal attempts
 * - Logs all deletion attempts for auditing
 */

// Set JSON response header
header('Content-Type: application/json');

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Get and validate file path from POST request
 * 
 * The file path should be URL-encoded when sent from client-side
 */
$filePath = isset($_POST['file']) ? trim($_POST['file']) : '';

// Decode the URL-encoded file path (e.g., convert %20 to spaces)
$filePath = urldecode($filePath);

// Get the absolute canonicalized path to the backups directory
$backupDir = realpath(__DIR__ . '/backups/');

// Verify backups directory exists and is accessible
if ($backupDir === false) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Backup directory not found or inaccessible',
        'path' => __DIR__ . '/backups/'
    ]);
    exit;
}

// Resolve the full path and ensure it's inside the backup directory
$fullPath = realpath($filePath);

/**
 * Debug logging
 * 
 * Logs detailed information about the deletion attempt for troubleshooting.
 * In production, consider using a proper logging library.
 */
$debugLog = sprintf(
    "[%s] Delete Request:\n" .
    "- Client IP: %s\n" .
    "- Requested Path: %s\n" .
    "- Resolved Path: %s\n" .
    "- Backup Directory: %s\n" .
    "- Directory Exists: %s\n" .
    "- File Exists: %s\n\n",
    date('Y-m-d H:i:s'),
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $filePath,
    $fullPath ?: 'INVALID PATH',
    $backupDir,
    is_dir($backupDir) ? 'Yes' : 'No',
    file_exists($filePath) ? 'Yes' : 'No'
);

// Append to debug log file
file_put_contents(
    __DIR__ . '/logs/delete_debug.log', 
    $debugLog,
    FILE_APPEND
);

// Check if file exists and is within the backups directory
if ($filePath === '' || $fullPath === false || strpos(str_replace('\\', '/', $fullPath), str_replace('\\', '/', $backupDir)) !== 0) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid file path',
        'debug' => [
            'filePath' => $filePath,
            'fullPath' => $fullPath,
            'backupDir' => $backupDir,
            'fileExists' => file_exists($filePath) ? 'Yes' : 'No',
            'dirExists' => is_dir($backupDir) ? 'Yes' : 'No'
        ]
    ]);
    exit;
}

// Check if file exists
if (!file_exists($fullPath)) {
    echo json_encode(['status' => 'error', 'message' => 'File not found']);
    exit;
}

/**
 * Security Check: Verify the resolved path is within our backup directory
 * 
 * Prevents directory traversal attacks by ensuring the file path is actually
 * inside our designated backup directory.
 */
if ($fullPath === false || strpos($fullPath, $backupDir) !== 0) {
    http_response_code(403); // Forbidden
    echo json_encode([
        'status' => 'error', 
        'message' => 'Access denied: Invalid file path',
        'details' => 'The requested file is outside the backup directory'
    ]);
    exit;
}

/**
 * File Validation
 * 
 * Ensures the path points to a valid file (not a directory or symlink)
 * and that we have proper permissions to access it.
 */
if (!is_file($fullPath)) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error', 
        'message' => 'File not found or not accessible',
        'path' => $fullPath
    ]);
    exit;
}

/**
 * File Deletion
 * 
 * Attempts to delete the file and handles the result.
 * Logs the operation for auditing purposes.
 */
try {
    // Log the deletion attempt
    file_put_contents(
        __DIR__ . '/logs/delete_audit.log',
        sprintf(
            "[%s] DELETE %s - IP: %s\n",
            date('Y-m-d H:i:s'),
            $fullPath,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ),
        FILE_APPEND
    );
    
    // Attempt to delete the file
    if (unlink($fullPath)) {
        // Success response
        echo json_encode([
            'status' => 'success', 
            'message' => 'Backup deleted successfully',
            'file' => basename($fullPath),
            'timestamp' => date('c')
        ]);
    } else {
        // Handle deletion failure
        $error = error_get_last();
        throw new Exception($error['message'] ?? 'Unknown error during deletion');
    }
} catch (Exception $e) {
    // Error handling and logging
    http_response_code(500);
    error_log("Failed to delete backup: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to delete backup',
        'details' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
