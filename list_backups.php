<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get client name from request
$client = isset($_GET['client']) ? $_GET['client'] : '';

// Validate client name to prevent directory traversal
if (!preg_match('/^[a-zA-Z0-9_]+$/', $client)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid client name']);
    exit;
}

// Set backup directory
$backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $client . DIRECTORY_SEPARATOR;

// Check if directory exists
if (!is_dir($backupDir)) {
    echo json_encode(['status' => 'success', 'backups' => [], 'total_size' => 0, 'last_backup_time' => null, 'hours_ago' => null]);
    exit;
}

// Normalize directory path for comparison
$backupDir = realpath($backupDir) . DIRECTORY_SEPARATOR;
$backups = [];

// Check if directory exists and is readable
if (is_dir($backupDir) && is_readable($backupDir)) {
    // Scan directory for backup files
    $files = [];
    $dir = opendir($backupDir);
    if ($dir) {
        while (($file = readdir($dir)) !== false) {
            if ($file !== '.' && $file !== '..' && 
                (strpos($file, '.sql') !== false || strpos($file, '.zip') !== false)) {
                $files[] = $backupDir . $file;
            }
        }
        closedir($dir);
    }
    
    $lastBackupTime = null;
    foreach ($files as $file) {
        $fileInfo = [
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'timestamp' => filemtime($file)
        ];
        $backups[] = $fileInfo;
        
        // Track the latest backup time
        if ($lastBackupTime === null || $fileInfo['timestamp'] > $lastBackupTime) {
            $lastBackupTime = $fileInfo['timestamp'];
        }
    }
    
    // Sort by timestamp (newest first)
    usort($backups, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    // Debug log
    file_put_contents('list_debug.log', 
        "Backup Dir: " . $backupDir . "\n" .
        "Files found: " . count($backups) . "\n" .
        "Sample file: " . ($backups[0]['path'] ?? 'none') . "\n",
        FILE_APPEND
    );
    
    // Prepare last backup info
    $lastBackupInfo = [
        'count' => count($backups),
        'last_backup_time' => $lastBackupTime,
        'hours_ago' => $lastBackupTime ? floor((time() - $lastBackupTime) / 3600) : null
    ];
    
    // Return backups with count and last backup info
    $response = [
        'status' => 'success',
        'files' => $backups,
        'count' => count($backups)
    ];
    
    // Debug log
    file_put_contents('list_debug.log', 
        "Response: " . print_r($response, true) . "\n" .
        "Backup Dir: " . $backupDir . "\n" .
        "Files found: " . count($backups) . "\n",
        FILE_APPEND
    );
    
    echo json_encode($response);
} else {
    echo json_encode([
        'status' => 'success',
        'files' => []
    ]);
}
