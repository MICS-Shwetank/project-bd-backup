<?php
/**
 * Backup Download Handler
 * 
 * This script handles secure file downloads from the backup directory.
 * It includes security checks to prevent directory traversal attacks
 * and ensures files are only downloaded from the designated backups directory.
 * 
 * Security Features:
 * - Validates file path is within backup directory
 * - Checks file existence and accessibility
 * - Prevents directory traversal attacks
 * - Sets proper download headers
 * - Logs download attempts for auditing
 */

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering at the very beginning
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Load configuration
$config = require __DIR__ . '/config.php';
$backupDir = rtrim($config['backup_path'], '/\\');

// Get and validate file path from query string
// The file parameter should be URL-encoded and relative to the backups directory
$relativePath = isset($_GET['file']) ? trim($_GET['file']) : '';

// Decode the URL-encoded file path (e.g., convert %20 to spaces)
$relativePath = urldecode($relativePath);

// Remove any path traversal attempts
$relativePath = str_replace(['../', '..\\'], '', $relativePath);
$relativePath = ltrim($relativePath, '/\\');

// Build the full path
$fullPath = realpath($backupDir . DIRECTORY_SEPARATOR . $relativePath);

// Security check: Ensure the resolved path is within our backup directory
if ($fullPath === false || strpos($fullPath, $backupDir) !== 0) {
    http_response_code(403); // Forbidden
    die('Access denied: Invalid file path');
}

// Check if the file exists and is readable
if (!is_file($fullPath) || !is_readable($fullPath)) {
    http_response_code(404);
    die('File not found or not accessible');
}

// Get file info
$fileSize = filesize($fullPath);
$fileName = basename($fullPath);
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Set content type based on file extension
$contentTypes = [
    'sql' => 'application/sql',
    'zip' => 'application/zip',
    'gz'  => 'application/gzip',
    'bz2' => 'application/x-bzip2',
    'txt' => 'text/plain'
];

$contentType = $contentTypes[$fileExt] ?? 'application/octet-stream';

// Clear all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Disable output buffering completely
if (ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'Off');
}

// Set headers for file download
header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $fileName . '"'); // यहां फाइल का असली नाम सेट होगा
header('Content-Length: ' . $fileSize);
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Transfer-Encoding: binary');

// Log the download attempt for auditing
$logMessage = sprintf(
    "[%s] DOWNLOAD %s - IP: %s - Size: %s\n",
    date('Y-m-d H:i:s'),
    $fullPath,
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $fileSize
);

// Ensure logs directory exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Append to access log
file_put_contents(
    $logDir . '/download_access.log',
    $logMessage,
    FILE_APPEND
);

// Open the file for reading
$handle = @fopen($fullPath, 'rb');
if ($handle === false) {
    http_response_code(500);
    die('Error opening file');
}

// Output the file in chunks
while (!feof($handle) && !connection_aborted()) {
    echo fread($handle, 8192);
    flush();
}

fclose($handle);
exit;
