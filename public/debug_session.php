<?php
require_once '../config/config.php';
require_once '../app/models/Database.php';
require_once '../app/models/User.php';
require_once '../app/models/AuditLog.php';
require_once '../app/core/Session.php';
require_once '../app/core/Auth.php';
require_once '../app/core/CSRF.php';

echo "<h1>Session Debug Information</h1>";

$session = new Session();
$auth = new Auth();
$csrf = new CSRF();

echo "<h2>Session Information:</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";

echo "<h2>CSRF Token Information:</h2>";
$storedToken = $session->get('csrf_token');
$tokenTime = $session->get('csrf_token_time');
echo "<p>Stored CSRF Token: " . ($storedToken ? substr($storedToken, 0, 20) . "..." : "NOT SET") . "</p>";
echo "<p>Token Time: " . ($tokenTime ? date('Y-m-d H:i:s', $tokenTime) : "NOT SET") . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Token Age: " . ($tokenTime ? (time() - $tokenTime) . " seconds" : "N/A") . "</p>";

echo "<h2>New CSRF Token:</h2>";
$newToken = $csrf->getToken();
echo "<p>New Token: " . substr($newToken, 0, 20) . "...</p>";

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Test Form:</h2>";
?>
<form method="POST" action="debug_session.php">
    <?= $csrf->tokenField() ?>
    <input type="text" name="test_field" value="test">
    <button type="submit">Test CSRF</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $isValid = $csrf->validateToken($_POST['csrf_token'] ?? '');
    echo "<p>CSRF Validation: " . ($isValid ? "VALID" : "INVALID") . "</p>";
}
?>