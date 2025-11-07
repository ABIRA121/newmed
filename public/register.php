<?php
// [file name]: register.php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/autoload.php';

if (!defined('DATA_ENCRYPTION_KEY')) {
    define('DATA_ENCRYPTION_KEY', getenv('DATA_ENCRYPTION_KEY') ?: 'change_this_key_in_production');
}

if (!function_exists('validate_password_strength')) {
    function validate_password_strength($password) {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must include at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must include at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must include at least one number';
        }
        if (!preg_match('/[!@#$%^&*()_+\-={}\[\]:;"\'\\|<>,.?\/]/', $password)) {
            $errors[] = 'Password must include at least one special character';
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'message' => implode('. ', $errors)
            ];
        }

        return [
            'valid' => true,
            'message' => 'Strong password'
        ];
    }
}

if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('encrypt_data')) {
    function encrypt_data($plaintext) {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        $key = hash('sha256', DATA_ENCRYPTION_KEY, true);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = random_bytes($ivLength);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new RuntimeException('Failed to encrypt data');
        }

        return base64_encode($iv . $ciphertext);
    }
}

if (!function_exists('log_activity')) {
    function log_activity($userId, $action, $ipAddress = null, $userAgent = null) {
        try {
            $auditLog = new AuditLog();
            $auditLog->log($userId, $action, $ipAddress, $userAgent);
        } catch (Exception $e) {
            error_log('Activity logging failed: ' . $e->getMessage());
        }
    }
}

// Initialize variables
$errors = [];
$success = false;
$form_data = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'dob' => '',
    'gender' => '',
    'address' => '',
    'medical_notes' => '',
    'emergency_contact' => '',
    'insurance_info' => ''
];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $form_data = array_map('sanitize_input', $_POST);
    
    // Validate required fields
    if (empty($form_data['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($form_data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($form_data['phone'])) {
        $errors['phone'] = 'Phone number is required';
    }
    
    if (empty($form_data['password'])) {
        $errors['password'] = 'Password is required';
    } else {
        $password_strength = validate_password_strength($form_data['password']);
        if (!$password_strength['valid']) {
            $errors['password'] = $password_strength['message'];
        }
    }
    
    if (empty($form_data['confirm_password'])) {
        $errors['confirm_password'] = 'Please confirm your password';
    } elseif ($form_data['password'] !== $form_data['confirm_password']) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($form_data['dob'])) {
        $errors['dob'] = 'Date of birth is required';
    }
    
    if (empty($form_data['gender'])) {
        $errors['gender'] = 'Gender is required';
    }
    
    // Check if email already exists
    if (empty($errors['email'])) {
        if (email_exists($form_data['email'])) {
            $errors['email'] = 'This email is already registered';
        }
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $db = getDatabaseConnection();
            
            // Begin transaction
            $db->beginTransaction();
            
            // Hash password
            $password_hash = hash_password($form_data['password']);
            
            // Insert into users table
            $user_sql = "INSERT INTO users (role, email, password_hash, full_name, phone, created_at, is_active, password_change_required) 
                        VALUES ('patient', ?, ?, ?, ?, NOW(), TRUE, FALSE)";
            
            $user_stmt = $db->prepare($user_sql);
            $user_stmt->execute([
                $form_data['email'],
                $password_hash,
                $form_data['full_name'],
                $form_data['phone']
            ]);

            $user_id = (int)$db->lastInsertId();
            
            // Encrypt sensitive data
            $encrypted_address = !empty($form_data['address']) ? encrypt_data($form_data['address']) : null;
            $encrypted_medical_notes = !empty($form_data['medical_notes']) ? encrypt_data($form_data['medical_notes']) : null;
            $encrypted_emergency_contact = !empty($form_data['emergency_contact']) ? encrypt_data($form_data['emergency_contact']) : null;
            $encrypted_insurance_info = !empty($form_data['insurance_info']) ? encrypt_data($form_data['insurance_info']) : null;
            
            // Insert into patients table
            $patient_sql = "INSERT INTO patients (user_id, dob, gender, address, medical_notes, emergency_contact, insurance_info) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $patient_stmt = $db->prepare($patient_sql);
            $patient_stmt->execute([
                $user_id,
                $form_data['dob'],
                $form_data['gender'],
                $encrypted_address,
                $encrypted_medical_notes,
                $encrypted_emergency_contact,
                $encrypted_insurance_info
            ]);
            
            // Commit transaction
            $db->commit();
            
            $success = true;
            
            // Log the registration
            log_activity($user_id, 'patient_registered', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            // Clear form data
            $form_data = array_fill_keys(array_keys($form_data), '');
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors['database'] = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

// Function to check if email exists
function email_exists($email) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Email check error: " . $e->getMessage());
        return false;
    }
}

// Function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - MedPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .registration-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .section-title {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .password-strength {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #fd7e14; }
        .strength-strong { color: #198754; }
    </style>
</head>
<body data-disable-utilities="true">
    <header class="page-header">
        <div class="container header-inner">
            <a href="index.php" class="logo">MedPortal</a>
            <div class="header-actions">
                <a href="index.php" class="header-action" data-nav-home>
                    <span class="icon">🏠</span>
                    <span>Home</span>
                </a>
                <button type="button" class="header-action theme-toggle" data-theme-toggle>
                    Toggle Theme
                </button>
            </div>
        </div>
    </header>
    <div class="container">
        <div class="registration-container">
            <div class="text-center mb-4">
                <h1 class="h3">Patient Registration</h1>
                <p class="text-muted">Create your MedPortal account</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Registration successful!</strong> You can now <a href="login.php" class="alert-link">login to your account</a>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['database'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $errors['database']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h3 class="section-title">Personal Information</h3>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>" 
                                   id="full_name" name="full_name" value="<?php echo $form_data['full_name']; ?>" required>
                            <?php if (isset($errors['full_name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['full_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" value="<?php echo $form_data['email']; ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" 
                                   id="phone" name="phone" value="<?php echo $form_data['phone']; ?>" required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="dob" class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control <?php echo isset($errors['dob']) ? 'is-invalid' : ''; ?>" 
                                   id="dob" name="dob" value="<?php echo $form_data['dob']; ?>" required>
                            <?php if (isset($errors['dob'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['dob']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender *</label>
                            <select class="form-select <?php echo isset($errors['gender']) ? 'is-invalid' : ''; ?>" 
                                    id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo $form_data['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo $form_data['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo $form_data['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                <option value="prefer_not_to_say" <?php echo $form_data['gender'] === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                            <?php if (isset($errors['gender'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['gender']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Medical Information Section -->
                <div class="form-section">
                    <h3 class="section-title">Medical Information</h3>
                    
                    <div class="mb-3">
                        <label for="medical_notes" class="form-label">Medical Notes</label>
                        <textarea class="form-control" id="medical_notes" name="medical_notes" rows="3" 
                                  placeholder="Any known allergies, current medications, or medical conditions..."><?php echo $form_data['medical_notes']; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="emergency_contact" class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" 
                               value="<?php echo $form_data['emergency_contact']; ?>" placeholder="Name and phone number">
                    </div>
                    
                    <div class="mb-3">
                        <label for="insurance_info" class="form-label">Insurance Information</label>
                        <input type="text" class="form-control" id="insurance_info" name="insurance_info" 
                               value="<?php echo $form_data['insurance_info']; ?>" placeholder="Insurance provider and policy number">
                    </div>
                </div>

                <!-- Address Information -->
                <div class="form-section">
                    <h3 class="section-title">Address Information</h3>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Full Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" 
                                  placeholder="Street address, city, state, and zip code"><?php echo $form_data['address']; ?></textarea>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="form-section">
                    <h3 class="section-title">Account Security</h3>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                            <?php else: ?>
                                <div class="form-text">Password must be at least 8 characters with uppercase, lowercase, number, and special character.</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Terms and Submit -->
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a> *
                        </label>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                </div>
            </form>

            <div class="text-center mt-4">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('password-strength');
            
            if (!strengthIndicator) {
                const indicator = document.createElement('div');
                indicator.id = 'password-strength';
                indicator.className = 'password-strength';
                this.parentNode.appendChild(indicator);
            }
            
            const indicator = document.getElementById('password-strength');
            let strength = 'Weak';
            let strengthClass = 'strength-weak';
            
            if (password.length >= 8) {
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*()_+\-={}\[\]:;"'\\|<>,.?\/]/.test(password);
                
                const requirementsMet = [hasUpper, hasLower, hasNumber, hasSpecial].filter(Boolean).length;
                
                if (requirementsMet >= 3) {
                    strength = 'Strong';
                    strengthClass = 'strength-strong';
                } else if (requirementsMet >= 2) {
                    strength = 'Medium';
                    strengthClass = 'strength-medium';
                }
            }
            
            indicator.textContent = `Strength: ${strength}`;
            indicator.className = `password-strength ${strengthClass}`;
        });
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>