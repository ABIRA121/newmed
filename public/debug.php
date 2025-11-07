<?php
// Debug script to check class loading
require_once '../config/config.php';

echo "<h1>MedPortal Debug Information</h1>";

// Check if files exist
$files = [
    '../app/autoload.php' => 'Autoloader',
    '../app/models/Database.php' => 'Database Class',
    '../app/models/User.php' => 'User Class', 
    '../app/models/AuditLog.php' => 'AuditLog Class',
    '../app/core/Session.php' => 'Session Class',
    '../app/core/Auth.php' => 'Auth Class'
];

echo "<h2>File Check:</h2>";
foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $description exists: $file</p>";
    } else {
        echo "<p style='color: red;'>✗ $description missing: $file</p>";
    }
}

// Test class loading
echo "<h2>Class Loading Test:</h2>";
try {
    require_once '../app/autoload.php';
    
    $classes = ['Database', 'User', 'AuditLog', 'Session', 'Auth'];
    
    foreach ($classes as $className) {
        if (class_exists($className)) {
            echo "<p style='color: green;'>✓ $className class loaded</p>";
        } else {
            echo "<p style='color: red;'>✗ $className class not found</p>";
            
            // Try manual include
            $possiblePaths = [
                "../app/models/$className.php",
                "../app/core/$className.php",
                "../app/$className.php"
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    if (class_exists($className)) {
                        echo "<p style='color: orange;'>→ $className loaded manually from: $path</p>";
                        break;
                    }
                }
            }
        }
    }
    
    // Test instantiation
    echo "<h2>Instantiation Test:</h2>";
    try {
        $db = Database::getInstance();
        echo "<p style='color: green;'>✓ Database instantiated successfully</p>";
        
        $user = new User();
        echo "<p style='color: green;'>✓ User instantiated successfully</p>";
        
        $auditLog = new AuditLog();
        echo "<p style='color: green;'>✓ AuditLog instantiated successfully</p>";
        
        $session = new Session();
        echo "<p style='color: green;'>✓ Session instantiated successfully</p>";
        
        $auth = new Auth();
        echo "<p style='color: green;'>✓ Auth instantiated successfully</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Instantiation failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Show PHP info
echo "<h2>PHP Information:</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Include Path: " . get_include_path() . "</p>";
?>
