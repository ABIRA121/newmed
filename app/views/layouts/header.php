<?php
// Check if auth is initialized
if (!isset($auth)) {
    require_once '../../config/config.php';
    require_once '../../app/models/Database.php';
    require_once '../../app/models/User.php';
    require_once '../../app/models/AuditLog.php';
    require_once '../../app/core/Session.php';
    require_once '../../app/core/Auth.php';
    $auth = new Auth();
}
?>
<header class="header">
    <div class="container">
        <nav class="nav">
            <a href="index.php" class="logo">MedPortal</a>
            
            <ul class="nav-menu">
                <?php if ($auth->isAuthenticated()): ?>
                    <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    
                    <?php if ($auth->hasRole('admin')): ?>
                        <li><a href="admin/dashboard.php" class="nav-link">Admin</a></li>
                        <li><a href="admin/users.php" class="nav-link">Manage Users</a></li>
                        <li><a href="admin/logs.php" class="nav-link">Audit Logs</a></li>
                    <?php elseif ($auth->hasRole('doctor')): ?>
                        <li><a href="staff/doctor/dashboard.php" class="nav-link">Doctor Portal</a></li>
                    <?php elseif ($auth->hasRole('accountant')): ?>
                        <li><a href="staff/accountant/dashboard.php" class="nav-link">Accountant Portal</a></li>
                    <?php elseif ($auth->hasRole('pharmacy')): ?>
                        <li><a href="staff/pharmacy/dashboard.php" class="nav-link">Pharmacy Portal</a></li>
                    <?php elseif ($auth->hasRole('nurse')): ?>
                        <li><a href="staff/nurse/dashboard.php" class="nav-link">Nurse Portal</a></li>
                    <?php elseif ($auth->hasRole('patient')): ?>
                        <li><a href="patient/appointments.php" class="nav-link">My Appointments</a></li>
                        <li><a href="patient/profile.php" class="nav-link">Profile</a></li>
                    <?php endif; ?>
                    
                    <?php 
                    $currentUser = $auth->getCurrentUser();
                    if ($currentUser): ?>
                        <li><a href="logout.php" class="nav-link">Logout (<?= htmlspecialchars($currentUser->full_name) ?>)</a></li>
                    <?php else: ?>
                        <li><a href="logout.php" class="nav-link">Logout</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="index.php" class="nav-link">Home</a></li>
                    <li><a href="login.php" class="nav-link">Login</a></li>
                    <li><a href="register.php" class="nav-link">Register</a></li>
                <?php endif; ?>
                
                <li>
                    <button id="themeToggle" class="theme-toggle" type="button" data-theme-toggle aria-label="Toggle dark mode">
                        Toggle Theme
                    </button>
                </li>
            </ul>
        </nav>
    </div>
</header>

<?php if (isset($session) && ($session->hasFlash('success') || $session->hasFlash('error'))): ?>
    <div class="container">
        <?php if ($session->hasFlash('success')): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($session->getFlash('success')) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($session->hasFlash('error')): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($session->getFlash('error')) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>