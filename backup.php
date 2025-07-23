<?php
/**
 * Database Backup Script
 * 
 * This script handles the backup process for various database types including:
 * - MySQL/MariaDB
 * - PostgreSQL
 * - SQL Server
 * - SQLite
 * 
 * The script is called via AJAX and returns JSON responses.
 */

// Set JSON content type for all responses
header('Content-Type: application/json');

// Load configuration
$config = require 'config.php';

// Get client key from POST data
$clientKey = $_POST['client'] ?? '';

// Validate client key exists in config
if (!isset($config['clients'][$clientKey])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid client',
        'available_clients' => array_keys($config['clients'])
    ]);
    exit;
}

// Get client configuration
$client = $config['clients'][$clientKey];
$enableZip = $config['enable_zip'];
$purgeDays = $config['purge_old_days'];

/**
 * Set up backup file paths
 * Format: /backups/{client_key}/{database_name}_{timestamp}.sql
 */
$backupDir = __DIR__ . "/backups/";
$folderPath = rtrim($backupDir, '/') . '/' . $clientKey . '/';
$folderPath = str_replace('//', '/', $folderPath);
$folderPath = str_replace('\\', '/', $folderPath);

// Create the backup folder if it doesn't exist
if (!file_exists($folderPath)) {
    if (!mkdir($folderPath, 0777, true)) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create backup directory',
            'path' => $folderPath
        ]);
        exit;
    }
    // Add .htaccess to prevent directory listing in web browsers
    file_put_contents($folderPath . '.htaccess', "Options -Indexes");
}

/**
 * Generate timestamp and set up backup file path
 */
$timestamp = date('Ymd_His');
$filename = "{$client['database']}_{$timestamp}.sql";
$filepath = $folderPath . $filename;
$filepath = str_replace('//', '/', $filepath);

/**
 * Database-specific command generation
 * 
 * The script supports multiple database types through a switch statement.
 * Each database type has its own command syntax for creating backups.
 */
$driver = strtolower($client['driver'] ?? 'mysql');
$command = '';      // Will hold the backup command
$envVars = '';      // For environment variables like PGPASSWORD
$port = $client['port'] ?? '';  // Get port if specified

switch ($driver) {
    case 'mysql':
    case 'mariadb':
        /**
         * MySQL/MariaDB Backup Command
         * Uses mysqldump utility for creating database dumps
         */
        $portParam = !empty($port) ? "-P {$port} " : "";
        $command = "mysqldump -h {$client['hostname']} {$portParam}-u {$client['username']} " .
            (!empty($client['password']) ? "-p{$client['password']} " : "") .
            "{$client['database']} > \"$filepath\"";
        break;
        
    case 'pgsql':
    case 'postgres':
    case 'postgresql':
        /**
         * PostgreSQL Backup Command
         * Uses pg_dump utility for creating database dumps
         * Requires PGPASSWORD environment variable for password
         */
        $portParam = !empty($port) ? "-p {$port} " : "";
        $envVars = !empty($client['password']) ? "PGPASSWORD='{$client['password']}' " : "";
        $command = "pg_dump -h {$client['hostname']} {$portParam}-U {$client['username']} " .
            "-d {$client['database']} > \"$filepath\"";
        break;
        
    case 'sqlsrv':
    case 'mssql':
        /**
         * SQL Server Backup Command
         * Uses sqlcmd utility for creating database backups
         * Note: Requires SQL Server authentication
         */
        $portParam = !empty($port) ? ",{$port}" : "";
        $passwordParam = !empty($client['password']) ? " -P '{$client['password']}'" : "";
        $command = "sqlcmd -S {$client['hostname']}{$portParam} -U {$client['username']}{$passwordParam} " .
            "-d {$client['database']} -Q \"BACKUP DATABASE [{$client['database']}] TO DISK='$filepath'\"";
        break;
        
    case 'sqlite':
        /**
         * SQLite Backup
         * Simply copies the database file as it's self-contained
         */
        $command = "cp \"{$client['database']}\" \"$filepath\"";
        break;
        
    default:
        // Return error for unsupported database drivers
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Unsupported database driver',
            'supported_drivers' => ['mysql', 'mariadb', 'pgsql', 'postgres', 'postgresql', 'sqlsrv', 'mssql', 'sqlite']
        ]);
        exit;
}

/**
 * Execute the backup command
 * 
 * The command is executed with combined output (stdout + stderr) captured
 * The result code indicates success (0) or failure (non-zero)
 */
$output = [];
$result = 0;
$fullCommand = $envVars . $command . ' 2>&1';
exec($fullCommand, $output, $result);

// Check if the command executed successfully
if ($result !== 0) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Backup failed!',
        'driver' => $driver,
        'command' => $command,  // For debugging purposes
        'output' => $output,    // Command output (including errors)
        'result' => $result     // Exit code from the command
    ]);
    exit;
}

/**
 * Backup was successful
 * Prepare response with backup details
 */
$backupInfo = [
    'status' => 'success',
    'message' => 'Backup created successfully',
    'filename' => $filename,        // Original SQL filename
    'path' => $filepath,           // Full path to the backup file
    'size' => filesize($filepath), // File size in bytes
    'created_at' => date('Y-m-d H:i:s'),  // Timestamp of backup creation
    'database' => $client['database']
];

/**
 * ZIP Compression (if enabled in config)
 * 
 * If ZIP compression is enabled, the SQL file will be compressed
 * and the original SQL file will be deleted to save space.
 */
if ($enableZip) {
    $zip = new ZipArchive();
    $zipFile = str_replace('.sql', '.zip', $filepath);
    
    // Create a new ZIP archive
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        // Add the SQL file to the ZIP archive
        $zip->addFile($filepath, $filename);
        $zip->close();
        
        // Remove the original SQL file after successful ZIP creation
        unlink($filepath);
        
        // Update backup info with ZIP file details
        $backupInfo['filename'] = basename($zipFile);
        $backupInfo['path'] = $zipFile;
        $backupInfo['size'] = filesize($zipFile);
        $backupInfo['compressed'] = true;
        $backupInfo['compression_ratio'] = round((1 - filesize($zipFile) / $backupInfo['size']) * 100, 2) . '%';
    } else {
        // If ZIP creation fails, keep the original SQL file
        $backupInfo['compressed'] = false;
        $backupInfo['warning'] = 'Failed to create zip file';
        $backupInfo['zip_error'] = $zip->getStatusString();
    }
    if (file_exists($zipFile)) {
        $fileSize = @filesize($zipFile);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Backup completed successfully!',
            'file' => basename($zipFile),
            'size' => $fileSize !== false ? $fileSize : 0,
            'timestamp' => time()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Backup file was not created',
            'path' => $zipFile
        ]);
    }
} else {
    if ($purgeDays > 0) {
        $files = glob($folderPath . '*');
        foreach ($files as $file) {
            if (is_file($file) && time() - filemtime($file) >= 60 * 60 * 24 * $purgeDays) {
                unlink($file);
            }
        }
    }

    if (file_exists($filepath)) {
        $fileSize = @filesize($filepath);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Backup completed successfully!',
            'file' => basename($filepath),
            'size' => $fileSize !== false ? $fileSize : 0,
            'timestamp' => time()
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Backup file was not created',
            'path' => $filepath
        ]);
    }
}
?>
