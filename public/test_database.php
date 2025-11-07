<?php
require_once '../config/config.php';
require_once '../app/models/Database.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Test 1: Basic Database Connection
    echo "<h2>Test 1: Database Connection</h2>";
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    echo "<p>Database Host: " . DB_HOST . "</p>";
    echo "<p>Database Name: " . DB_NAME . "</p>";
    echo "<p>PDO Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "</p>";
    echo "<p>Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
    
    // Test 2: Check if tables exist
    echo "<h2>Test 2: Database Tables</h2>";
    $tables = ['users', 'patients', 'appointments', 'audit_logs', 'login_attempts'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' is missing</p>";
        }
    }
    
    // Test 3: Check table structures
    echo "<h2>Test 3: Table Structures</h2>";
    foreach ($tables as $table) {
        echo "<h3>Table: $table</h3>";
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($columns) {
                echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                foreach ($columns as $column) {
                    echo "<tr>";
                    echo "<td>{$column['Field']}</td>";
                    echo "<td>{$column['Type']}</td>";
                    echo "<td>{$column['Null']}</td>";
                    echo "<td>{$column['Key']}</td>";
                    echo "<td>{$column['Default']}</td>";
                    echo "<td>{$column['Extra']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error describing table $table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test 4: Check if seed data exists
    echo "<h2>Test 4: Seed Data</h2>";
    
    // Check users table
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total Users: $userCount</p>";
    
    // Check users by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roles as $role) {
        echo "<p>{$role['role']}: {$role['count']} users</p>";
    }
    
    // Check specific test users
    $testUsers = [
        'admin@medportal.com',
        'dr.smith@medportal.com', 
        'patient1@example.com'
    ];
    
    foreach ($testUsers as $email) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p style='color: green;'>✓ Test user '$email' exists</p>";
            echo "<p> - Name: {$user['full_name']}, Role: {$user['role']}, Active: {$user['is_active']}</p>";
        } else {
            echo "<p style='color: red;'>✗ Test user '$email' is missing</p>";
        }
    }
    
    // Test 5: Test password verification
    echo "<h2>Test 5: Password Verification</h2>";
    $testPasswords = [
        'admin@medportal.com' => 'Admin123!',
        'dr.smith@medportal.com' => 'Staff123!',
        'patient1@example.com' => 'Patient123!'
    ];
    
    foreach ($testPasswords as $email => $password) {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            echo "<p style='color: green;'>✓ Password verification successful for $email</p>";
        } else {
            echo "<p style='color: red;'>✗ Password verification failed for $email</p>";
        }
    }
    
    // Test 6: Test basic queries
    echo "<h2>Test 6: Basic Query Tests</h2>";
    
    // Test appointments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments");
    $appointmentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total Appointments: $appointmentCount</p>";
    
    // Test patients
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM patients");
    $patientCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total Patient Profiles: $patientCount</p>";
    
    // Test audit logs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM audit_logs");
    $logCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total Audit Logs: $logCount</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Database Connection Failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<h3>Configuration Check:</h3>";
    echo "<p>DB_HOST: " . DB_HOST . "</p>";
    echo "<p>DB_NAME: " . DB_NAME . "</p>";
    echo "<p>DB_USER: " . DB_USER . "</p>";
    echo "<p>DB_PASS: " . (DB_PASS ? "*** (set)" : "empty") . "</p>";
    
    // Check if MySQL is running
    echo "<h3>MySQL Service Check:</h3>";
    $mysqlRunning = false;
    try {
        $testPdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $mysqlRunning = true;
        echo "<p style='color: green;'>✓ MySQL service is running</p>";
        
        // Check if database exists
        $stmt = $testPdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Database '" . DB_NAME . "' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Database '" . DB_NAME . "' does not exist</p>";
            echo "<p>You need to create the database and import the schema.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Cannot connect to MySQL: " . $e->getMessage() . "</p>";
        echo "<p>Make sure MySQL is running and credentials are correct.</p>";
    }
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ul>";
echo "<li>If all tests pass, your database is properly connected</li>";
echo "<li>If tables are missing, run the schema.sql file</li>";
echo "<li>If seed data is missing, run the seeds.sql file</li>";
echo "<li>If connection fails, check your config.php file</li>";
echo "</ul>";
?>