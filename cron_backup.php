<?php
/**
 * Optimized Database Backup Cron Job
 * 
 * Features:
 * - Better error handling with try-catch blocks
 * - Memory optimization with unset() calls
 * - Improved logging with log levels
 * - Configuration validation
 * - Backup statistics tracking
 * - Automatic cleanup of old backups
 */

// Start time for performance measurement
$startTime = microtime(true);
define('LOG_FILE', __DIR__ . '/backup_cron.log');
define('MAX_LOG_FILES', 5); // Keep last 5 log files
define('MAX_BACKUP_AGE', '30 days'); // Auto-delete backups older than 30 days

/**
 * Log a message with different severity levels
 * 
 * @param string $message The message to log
 * @param string $level The severity level (INFO, WARNING, ERROR, SUCCESS)
 */
function logMessage(string $message, string $level = 'INFO'): void {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Output to console when run manually
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
    
    // Append to log file
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}

/**
 * Rotate log files to prevent them from growing too large
 */
function rotateLogs(): void {
    if (file_exists(LOG_FILE) {
        $currentSize = filesize(LOG_FILE);
        if ($currentSize > 5 * 1024 * 1024) { // Rotate if >5MB
            for ($i = MAX_LOG_FILES; $i > 0; $i--) {
                $oldLog = LOG_FILE . '.' . $i;
                $newLog = LOG_FILE . '.' . ($i + 1);
                if (file_exists($oldLog)) {
                    rename($oldLog, $newLog);
                }
            }
            rename(LOG_FILE, LOG_FILE . '.1');
        }
    }
}

/**
 * Format bytes to human readable format
 */
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Validate client configuration
 */
function validateClientConfig(array $client): bool {
    $required = ['client_name', 'db_host', 'db_user', 'db_pass', 'db_name'];
    foreach ($required as $field) {
        if (!isset($client[$field]) || empty($client[$field])) {
            return false;
        }
    }
    return true;
}

/**
 * Clean up old backup files
 */
function cleanupOldBackups(string $backupDir): void {
    $now = time();
    $cutoff = strtotime('-' . MAX_BACKUP_AGE);
    
    if (!is_dir($backupDir)) {
        return;
    }
    
    $files = glob($backupDir . '/*.sql*');
    foreach ($files as $file) {
        if (filemtime($file) < $cutoff) {
            if (unlink($file)) {
                logMessage("Deleted old backup: " . basename($file), 'INFO');
            } else {
                logMessage("Failed to delete old backup: " . basename($file), 'WARNING');
            }
        }
    }
}

// Main execution
try {
    // Rotate logs if needed
    rotateLogs();
    
    logMessage("Starting backup process", 'INFO');
    
    // Load and validate configuration
    $configFile = __DIR__ . '/config.php';
    if (!file_exists($configFile)) {
        throw new RuntimeException("Configuration file not found: $configFile");
    }
    
    $config = require $configFile;
    
    if (!isset($config['clients']) || !is_array($config['clients']) || empty($config['clients'])) {
        throw new RuntimeException("No valid clients configured in config.php");
    }
    
    $stats = [
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'total_size' => 0,
    ];
    
    // Process each client
    foreach ($config['clients'] as $key => $client) {
        $clientStart = microtime(true);
        
        try {
            // Skip invalid client configurations
            if (!validateClientConfig($client)) {
                $stats['skipped']++;
                logMessage("Skipping invalid client configuration: $key", 'WARNING');
                continue;
            }
            
            logMessage("Processing client: {$client['client_name']} ($key)", 'INFO');
            
            // Set client in POST data (for compatibility with backup.php)
            $_POST['client'] = $key;
            
            // Start output buffering to capture backup.php output
            ob_start();
            
            // Include backup script
            include __DIR__ . '/backup.php';
            
            // Get the output and clean buffer
            $output = json_decode(ob_get_clean(), true);
            
            if (!isset($output['status'])) {
                throw new RuntimeException("Invalid response from backup script");
            }
            
            if ($output['status'] === 'success') {
                $stats['success']++;
                $stats['total_size'] += $output['size'] ?? 0;
                
                $msg = sprintf(
                    "Backup successful: %s (Size: %s)",
                    $output['filename'],
                    formatBytes($output['size'])
                );
                logMessage($msg, 'SUCCESS');
                
                // Cleanup old backups for this client
                if (!empty($client['backup_dir'])) {
                    cleanupOldBackups($client['backup_dir']);
                }
            } else {
                throw new RuntimeException($output['message'] ?? 'Unknown error during backup');
            }
        } catch (Exception $e) {
            $stats['failed']++;
            logMessage("Backup failed for {$client['client_name']}: " . $e->getMessage(), 'ERROR');
        } finally {
            $timeTaken = round(microtime(true) - $clientStart, 2);
            logMessage("Client processing time: {$timeTaken} seconds", 'INFO');
            
            // Free memory
            unset($output, $client);
        }
    }
    
    // Final summary
    $totalTime = round(microtime(true) - $startTime, 2);
    $summary = sprintf(
        "Backup process completed. Success: %d, Failed: %d, Skipped: %d, Total size: %s, Time: %s seconds",
        $stats['success'],
        $stats['failed'],
        $stats['skipped'],
        formatBytes($stats['total_size']),
        $totalTime
    );
    
    logMessage($summary, 'SUMMARY');
    
    // Exit with appropriate status code
    exit($stats['failed'] > 0 ? 1 : 0);
    
} catch (Throwable $e) {
    logMessage("FATAL ERROR: " . $e->getMessage(), 'ERROR');
    exit(1);
}