<?php
/**
 * Simple autoloader for MedPortal classes
 */

spl_autoload_register(function ($className) {
    // Base directory for classes
    $baseDir = __DIR__ . '/';
    
    // List of directories to check
    $directories = [
        '',
        'models/',
        'core/',
        'controllers/'
    ];
    
    // Try each directory
    foreach ($directories as $directory) {
        $file = $baseDir . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    // Log autoload failure for debugging
    error_log("Autoloader: Class $className not found in any directory");
    return false;
});
?>