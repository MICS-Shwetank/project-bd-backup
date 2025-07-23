<?php
/**
 * Script to clean up empty backup folders
 * This will recursively scan the backups directory and delete empty folders
 * Runs silently in the background
 */

// Function to check if directory contains any .zip files
function containsZipFiles($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = glob($dir . '/*.zip');
    return !empty($files);
}

// Function to recursively delete empty directories
function deleteEmptyDirs($dir) {
    if (!is_dir($dir)) {
        return false;
    }

    $isEmpty = true;
    $entries = @scandir($dir);
    
    if ($entries === false) {
        return false;
    }
    
    $entries = array_diff($entries, array('.', '..', '.htaccess'));
    
    foreach ($entries as $entry) {
        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        
        if (is_dir($path)) {
            if (!deleteEmptyDirs($path)) {
                $isEmpty = false;
            }
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ($ext !== 'zip' && $entry !== '.htaccess') {
                $isEmpty = false;
            }
        }
    }

    // Only delete if it's not the main backups folder and contains no zip files
    if ($dir !== __DIR__ . '/backups' && !containsZipFiles($dir)) {
        // Delete all files in the directory first
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                deleteEmptyDirs($path);
            }
        }
        // Then delete the directory itself
        @rmdir($dir);
        return true;
    }

    return $isEmpty;
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Main execution (silent mode)
$backupDir = __DIR__ . '/backups';

// Check if backups directory exists
if (is_dir($backupDir)) {
    deleteEmptyDirs($backupDir);
}

// No output needed - running silently
?>
