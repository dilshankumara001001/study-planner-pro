<?php
// =============================================
// 📝 REGISTER - Advanced Version
// =============================================
require_once '../config/config.php';
require_once '../config/functions.php';

// =============================================
// IF ALREADY LOGGED IN, REDIRECT TO DASHBOARD
// =============================================
if (isLoggedIn()) {
    redirect('pages/dashboard.php');
}

// =============================================
// GENERATE CSRF TOKEN
// =============================================
$csrf_token = generateCSRFToken();

// =============================================
// HANDLE REGISTRATION
// =============================================
$error = '';
$success = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed. Please try again.';
    } else {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $terms = isset($_POST['terms']);
        
        // Validation
        $errors = [];
        
        // Name validation
        if (empty($name)) {
            $errors[] = 'Full name is required!';
        } elseif (strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters!';
        } elseif (strlen($name) > 100) {
            $errors[] = 'Name cannot exceed 100 characters!';
        } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $name)) {
            $errors[] = 'Name contains invalid characters!';
        }
        
        // Email validation
        if (empty($email)) {
            $errors[] = 'Email address is required!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address!';
        }
        
        // Password validation
        if (empty($password)) {
            $errors[] = 'Password is required!';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters!';
        } elseif (strlen($password) > 255) {
            $errors[] = 'Password cannot exceed 255 characters!';
        } elseif (!preg_match("/[A-Z]/", $password)) {
            $errors[] = 'Password must contain at least one uppercase letter!';
        } elseif (!preg_match("/[a-z]/", $password)) {
            $errors[] = 'Password must contain at least one lowercase letter!';
        } elseif (!preg_match("/[0-9]/", $password)) {
            $errors[] = 'Password must contain at least one number!';
        }
        
        // Confirm password validation
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match!';
        }
        
        // Terms validation
        if (!$terms) {
            $errors[] = 'You must agree to the Terms & Conditions!';
        }
        
        // Check if email already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR deleted_at IS NOT NULL");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Email already registered! Please <a href="login.php">login</a>.';
            } else {
                // Hash password and insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, is_verified, created_at) 
                    VALUES (?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$name, $email, $hashed_password]);
                
                $user_id = $pdo->lastInsertId();
                
                // Log activity
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, details, ip_address) 
                    VALUES (?, 'register', ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    json_encode(['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                $success = "✅ Registration successful! Please <a href='login.php'>login</a> to continue.";
                
                // Clear form
                $name = '';
                $email = '';
            }
        }
        
        if (!empty($errors)) {
            $error = implode('<br>', $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- =============================================
    META TAGS
    ============================================= -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#667eea">
    <title>Register - <?php echo APP_NAME; ?></title>
    
    <!-- =============================================
    FONTS
    ============================================= -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- =============================================
    CSS
    ============================================= -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    
    <style>
        /* =============================================
        AUTH PAGE SPECIFIC STYLES
        ============================================= */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            padding: 20px;
        }
        
        .auth-container {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 0;
            background: var(--card-bg);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.15);
            min-height: 600px;
            animation: fadeInUp 0.8s ease-out;
        }
        
        .auth-left {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .auth-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 20s ease-in-out infinite;
        }
        
        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -30%;
            width: 80%;
            height: 80%;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            animation: float 25s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(20px, -30px) scale(1.1); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .auth-left-content {
            position: relative;
            z-index: 1;
        }
        
        .auth-logo {
            font-size: 48px;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .auth-left h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }
        
        .auth-left h1 span {
            font-weight: 300;
        }
        
        .auth-left p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.8;
            margin-bottom: 30px;
            max-width: 400px;
        }
        
        .auth-features {
            list-style: none;
            padding: 0;
        }
        
        .auth-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 15px;
            opacity: 0.95;
        }
        
        .auth-features li i {
            width: 20px;
            color: #fff;
            font-size: 18px;
        }
        
        .auth-right {
            padding: 40px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .auth-right::-webkit-scrollbar {
            width: 6px;
        }
        
        .auth-right::-webkit-scrollbar-track {
            background: var(--bg);
            border-radius: 10px;
        }
        
        .auth-right::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }
        
        .auth-right h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }
        
        .auth-right .subtitle {
            color: var(--text-light);
            font-size: 15px;
            margin-bottom: 25px;
        }
        
        .auth-form-group {
            margin-bottom: 16px;
        }
        
        .auth-form-group label {
            display: block;
            font-weight: 500;
            font-size: 14px;
            color: var(--text);
            margin-bottom: 5px;
        }
        
        .auth-form-group label .required {
            color: #e74c3c;
        }
        
        .auth-input-wrapper {
            position: relative;
        }
        
        .auth-input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
            z-index: 1;
        }
        
        .auth-input-wrapper input {
            width: 100%;
            padding: 12px 14px 12px 44px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: var(--bg);
            color: var(--text);
        }
        
        .auth-input-wrapper input:focus {
            border-color: #2ecc71;
            outline: none;
            background: var(--card-bg);
            box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.1);
        }
        
        .auth-input-wrapper input.error {
            border-color: #e74c3c;
        }
        
        .auth-input-wrapper input.success {
            border-color: #2ecc71;
        }
        
        .auth-input-wrapper .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
            z-index: 1;
        }
        
        .auth-input-wrapper .toggle-password:hover {
            color: var(--text);
        }
        
        .field-error {
            color: #dc2626;
            font-size: 13px;
            margin-top: 4px;
            display: block;
        }
        
        .field-success {
            color: #2ecc71;
            font-size: 13px;
            margin-top: 4px;
            display: block;
        }
        
        .field-hint {
            color: var(--text-light);
            font-size: 13px;
            margin-top: 4px;
            display: block;
        }
        
        /* =============================================
        PASSWORD STRENGTH METER
        ============================================= */
        .password-strength {
            margin-top: 8px;
        }
        
        .password-strength-bar {
            display: flex;
            gap: 6px;
            margin-bottom: 4px;
        }
        
        .password-strength-bar .segment {
            flex: 1;
            height: 4px;
            background: var(--border);
            border-radius: 4px;
            transition: all 0.4s ease;
        }
        
        .password-strength-bar .segment.active.weak {
            background: #e74c3c;
        }
        
        .password-strength-bar .segment.active.medium {
            background: #f39c12;
        }
        
        .password-strength-bar .segment.active.strong {
            background: #2ecc71;
        }
        
        .password-strength-bar .segment.active.very-strong {
            background: #27ae60;
        }
        
        .password-strength-text {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-light);
        }
        
        .password-strength-text.weak { color: #e74c3c; }
        .password-strength-text.medium { color: #f39c12; }
        .password-strength-text.strong { color: #2ecc71; }
        .password-strength-text.very-strong { color: #27ae60; }
        
        /* =============================================
        PASSWORD REQUIREMENTS
        ============================================= */
        .password-requirements {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 20px;
            margin-top: 6px;
            font-size: 13px;
        }
        
        .password-requirements .req {
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }
        
        .password-requirements .req i {
            font-size: 12px;
            width: 16px;
        }
        
        .password-requirements .req.met {
            color: #2ecc71;
        }
        
        .password-requirements .req.met i {
            color: #2ecc71;
        }
        
        .password-requirements .req.unmet {
            color: var(--text-light);
        }
        
        .password-requirements .req.unmet i {
            color: var(--text-light);
        }
        
        /* =============================================
        TERMS CHECKBOX
        ============================================= */
        .terms-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 16px 0 20px;
        }
        
        .terms-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            min-width: 20px;
            accent-color: #2ecc71;
            cursor: pointer;
            margin-top: 2px;
        }
        
        .terms-group label {
            font-size: 14px;
            color: var(--text-light);
            cursor: pointer;
            line-height: 1.5;
        }
        
        .terms-group label a {
            color: #2ecc71;
            text-decoration: none;
            font-weight: 500;
        }
        
        .terms-group label a:hover {
            text-decoration: underline;
        }
        
        .btn-auth {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(46, 204, 113, 0.4);
        }
        
        .btn-auth:active {
            transform: translateY(0);
        }
        
        .btn-auth:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 22px 0;
        }
        
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        
        .auth-divider span {
            font-size: 13px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        
        .social-btn {
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--bg);
            color: var(--text);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            text-decoration: none;
        }
        
        .social-btn:hover {
            border-color: #2ecc71;
            background: var(--card-bg);
            transform: translateY(-2px);
        }
        
        .social-btn.google { border-color: #ea4335; }
        .social-btn.google:hover { background: #fee8e6; }
        .social-btn.github { border-color: #333; }
        .social-btn.github:hover { background: #f0f0f0; }
        .social-btn.facebook { border-color: #1877f2; }
        .social-btn.facebook:hover { background: #e8f0fe; }
        
        .auth-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 15px;
            color: var(--text-light);
        }
        
        .auth-footer a {
            color: #2ecc71;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .auth-footer a:hover {
            color: #27ae60;
            text-decoration: underline;
        }
        
        /* =============================================
        ALERT STYLES
        ============================================= */
        .auth-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }
        
        .auth-error i {
            font-size: 18px;
            margin-top: 2px;
        }
        
        .auth-error a {
            color: #dc2626;
            font-weight: 600;
        }
        
        .auth-success {
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInUp 0.5s ease-out;
        }
        
        .auth-success i {
            font-size: 18px;
        }
        
        .auth-success a {
            color: #065f46;
            font-weight: 600;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        /* =============================================
        RESPONSIVE
        ============================================= */
        @media (max-width: 992px) {
            .auth-container {
                grid-template-columns: 1fr;
                max-width: 550px;
                min-height: auto;
            }
            
            .auth-left {
                padding: 35px 30px;
                min-height: 220px;
            }
            
            .auth-left h1 {
                font-size: 28px;
            }
            
            .auth-left p {
                max-width: 100%;
            }
            
            .auth-features li {
                font-size: 14px;
            }
            
            .auth-right {
                padding: 30px 25px;
                max-height: none;
                overflow-y: visible;
            }
            
            .password-requirements {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .auth-left {
                padding: 25px 20px;
            }
            
            .auth-left h1 {
                font-size: 24px;
            }
            
            .auth-right {
                padding: 20px 15px;
            }
            
            .auth-right h2 {
                font-size: 22px;
            }
            
            .social-login {
                grid-template-columns: 1fr;
            }
            
            .auth-form-group label {
                font-size: 13px;
            }
            
            .auth-input-wrapper input {
                padding: 10px 12px 10px 38px;
                font-size: 14px;
            }
            
            .auth-right::-webkit-scrollbar {
                width: 4px;
            }
        }
        
        /* =============================================
        DARK MODE SUPPORT
        ============================================= */
        body.dark-mode .auth-container {
            background: #1a1a2e;
        }
        
        body.dark-mode .auth-input-wrapper input {
            background: #2d2d44;
            border-color: #3d3d5c;
            color: #e0e0e0;
        }
        
        body.dark-mode .auth-input-wrapper input:focus {
            background: #1a1a2e;
        }
        
        body.dark-mode .auth-right .subtitle {
            color: #999;
        }
        
        body.dark-mode .social-btn {
            background: #2d2d44;
            border-color: #3d3d5c;
            color: #e0e0e0;
        }
        
        body.dark-mode .social-btn.github:hover {
            background: #3d3d5c;
        }
        
        body.dark-mode .auth-error {
            background: #3d1a1a;
            border-color: #6d2a2a;
            color: #f87171;
        }
        
        body.dark-mode .auth-success {
            background: #1a3d2a;
            border-color: #2d6d4a;
            color: #6ee7b7;
        }
        
        body.dark-mode .auth-success a {
            color: #6ee7b7;
        }
        
        body.dark-mode .auth-error a {
            color: #f87171;
        }
        
        body.dark-mode .terms-group label {
            color: #999;
        }
        
        body.dark-mode .auth-divider span {
            color: #666;
        }
        
        body.dark-mode .auth-divider::before,
        body.dark-mode .auth-divider::after {
            background: #3d3d5c;
        }
        
        body.dark-mode .password-requirements .req {
            color: #888;
        }
        
        body.dark-mode .password-requirements .req.met {
            color: #6ee7b7;
        }
        
        body.dark-mode .password-requirements .req.met i {
            color: #6ee7b7;
        }
        
        body.dark-mode .password-strength-text {
            color: #888;
        }
        
        body.dark-mode .field-hint {
            color: #888;
        }
    </style>
</head>
<body class="auth-page">
    
    <div class="auth-container">
        <!-- =============================================
        LEFT PANEL - Branding
        ============================================= -->
        <div class="auth-left">
            <div class="auth-left-content">
                <div class="auth-logo">📚</div>
                <h1>Join <span>StudyHub</span></h1>
                <p>Create your account and start organizing your studies effectively. Track subjects, manage tasks, and achieve more.</p>
                
                <ul class="auth-features">
                    <li><i class="fas fa-check-circle"></i> Free forever</li>
                    <li><i class="fas fa-check-circle"></i> Track unlimited subjects</li>
                    <li><i class="fas fa-check-circle"></i> Smart task management</li>
                    <li><i class="fas fa-check-circle"></i> Progress analytics</li>
                </ul>
            </div>
        </div>
        
        <!-- =============================================
        RIGHT PANEL - Register Form
        ============================================= -->
        <div class="auth-right">
            <h2>📝 Create Account</h2>
            <p class="subtitle">Start your journey to organized studying.</p>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="auth-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="auth-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form method="POST" id="registerForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <!-- Full Name -->
                <div class="auth-form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?php echo htmlspecialchars($name); ?>" 
                               placeholder="John Doe" 
                               required 
                               autofocus
                               minlength="2"
                               maxlength="100"
                               pattern="[a-zA-Z\s'-]+">
                    </div>
                    <small class="field-hint">Use your real name for a better experience</small>
                </div>
                
                <!-- Email -->
                <div class="auth-form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               placeholder="you@example.com" 
                               required>
                    </div>
                </div>
                
                <!-- Password -->
                <div class="auth-form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Create a strong password" 
                               required
                               minlength="6">
                        <button type="button" 
                                class="toggle-password" 
                                onclick="togglePasswordVisibility('password', 'passwordToggleIcon')"
                                aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                    
                    <!-- Password Strength Meter -->
                    <div class="password-strength" id="passwordStrength" style="display:none;">
                        <div class="password-strength-bar">
                            <div class="segment" id="ps1"></div>
                            <div class="segment" id="ps2"></div>
                            <div class="segment" id="ps3"></div>
                            <div class="segment" id="ps4"></div>
                        </div>
                        <span class="password-strength-text" id="psText">Weak</span>
                    </div>
                    
                    <!-- Password Requirements -->
                    <div class="password-requirements" id="passwordRequirements">
                        <div class="req unmet" id="req-length">
                            <i class="fas fa-circle"></i> At least 6 characters
                        </div>
                        <div class="req unmet" id="req-uppercase">
                            <i class="fas fa-circle"></i> One uppercase letter
                        </div>
                        <div class="req unmet" id="req-lowercase">
                            <i class="fas fa-circle"></i> One lowercase letter
                        </div>
                        <div class="req unmet" id="req-number">
                            <i class="fas fa-circle"></i> One number
                        </div>
                    </div>
                </div>
                
                <!-- Confirm Password -->
                <div class="auth-form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-check-circle"></i>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Confirm your password" 
                               required>
                        <button type="button" 
                                class="toggle-password" 
                                onclick="togglePasswordVisibility('confirm_password', 'confirmToggleIcon')"
                                aria-label="Toggle confirm password visibility">
                            <i class="fas fa-eye" id="confirmToggleIcon"></i>
                        </button>
                    </div>
                    <div id="confirmMatch" class="field-hint"></div>
                </div>
                
                <!-- Terms & Conditions -->
                <div class="terms-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the 
                        <a href="#" onclick="alert('Terms & Conditions page coming soon!'); return false;">Terms & Conditions</a> 
                        and <a href="#" onclick="alert('Privacy Policy page coming soon!'); return false;">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-auth" id="registerBtn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <!-- Divider -->
            <div class="auth-divider">
                <span>or continue with</span>
            </div>
            
            <!-- Social Login -->
            <div class="social-login">
                <a href="#" class="social-btn google" onclick="alert('Google sign up coming soon!'); return false;">
                    <i class="fab fa-google"></i> Google
                </a>
                <a href="#" class="social-btn github" onclick="alert('GitHub sign up coming soon!'); return false;">
                    <i class="fab fa-github"></i> GitHub
                </a>
                <a href="#" class="social-btn facebook" onclick="alert('Facebook sign up coming soon!'); return false;">
                    <i class="fab fa-facebook"></i> Facebook
                </a>
            </div>
            
            <!-- Footer -->
            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>
    
    <!-- =============================================
    JAVASCRIPT
    ============================================= -->
    <script>
        // =============================================
        // TOGGLE PASSWORD VISIBILITY
        // =============================================
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // =============================================
        // PASSWORD STRENGTH METER
        // =============================================
        const passwordInput = document.getElementById('password');
        const strengthDiv = document.getElementById('passwordStrength');
        const ps1 = document.getElementById('ps1');
        const ps2 = document.getElementById('ps2');
        const ps3 = document.getElementById('ps3');
        const ps4 = document.getElementById('ps4');
        const psText = document.getElementById('psText');
        
        const reqLength = document.getElementById('req-length');
        const reqUppercase = document.getElementById('req-uppercase');
        const reqLowercase = document.getElementById('req-lowercase');
        const reqNumber = document.getElementById('req-number');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const val = this.value;
                const segments = [ps1, ps2, ps3, ps4];
                
                // Check requirements
                const hasLength = val.length >= 6;
                const hasUppercase = /[A-Z]/.test(val);
                const hasLowercase = /[a-z]/.test(val);
                const hasNumber = /[0-9]/.test(val);
                
                // Update requirements
                updateRequirement(reqLength, hasLength);
                updateRequirement(reqUppercase, hasUppercase);
                updateRequirement(reqLowercase, hasLowercase);
                updateRequirement(reqNumber, hasNumber);
                
                // Calculate strength
                let score = 0;
                if (hasLength) score++;
                if (hasUppercase) score++;
                if (hasLowercase) score++;
                if (hasNumber) score++;
                
                // Show/hide strength meter
                if (val.length > 0) {
                    strengthDiv.style.display = 'block';
                } else {
                    strengthDiv.style.display = 'none';
                    return;
                }
                
                // Update segments
                segments.forEach((seg, i) => {
                    seg.className = 'segment';
                    if (i < score) {
                        seg.classList.add('active');
                        if (score <= 2) seg.classList.add('weak');
                        else if (score === 3) seg.classList.add('medium');
                        else seg.classList.add('strong');
                    }
                });
                
                // Update text
                psText.className = 'password-strength-text';
                if (score <= 2) {
                    psText.textContent = 'Weak Password';
                    psText.classList.add('weak');
                } else if (score === 3) {
                    psText.textContent = 'Medium Password';
                    psText.classList.add('medium');
                } else {
                    psText.textContent = 'Strong Password';
                    psText.classList.add('strong');
                }
            });
        }
        
        function updateRequirement(element, met) {
            element.className = 'req';
            if (met) {
                element.classList.add('met');
                element.innerHTML = '<i class="fas fa-check-circle"></i> ' + element.textContent.trim();
            } else {
                element.classList.add('unmet');
                element.innerHTML = '<i class="fas fa-circle"></i> ' + element.textContent.trim();
            }
        }
        
        // =============================================
        // CONFIRM PASSWORD VALIDATION
        // =============================================
        const confirmInput = document.getElementById('confirm_password');
        const confirmMatch = document.getElementById('confirmMatch');
        
        if (confirmInput && passwordInput) {
            confirmInput.addEventListener('input', function() {
                if (this.value.length > 0) {
                    if (this.value === passwordInput.value) {
                        confirmMatch.textContent = '✅ Passwords match!';
                        confirmMatch.className = 'field-success';
                        this.classList.remove('error');
                        this.classList.add('success');
                    } else {
                        confirmMatch.textContent = '❌ Passwords do not match';
                        confirmMatch.className = 'field-error';
                        this.classList.remove('success');
                        this.classList.add('error');
                    }
                } else {
                    confirmMatch.textContent = '';
                    this.classList.remove('error', 'success');
                }
            });
            
            passwordInput.addEventListener('input', function() {
                if (confirmInput.value.length > 0) {
                    if (confirmInput.value === this.value) {
                        confirmMatch.textContent = '✅ Passwords match!';
                        confirmMatch.className = 'field-success';
                        confirmInput.classList.remove('error');
                        confirmInput.classList.add('success');
                    } else {
                        confirmMatch.textContent = '❌ Passwords do not match';
                        confirmMatch.className = 'field-error';
                        confirmInput.classList.remove('success');
                        confirmInput.classList.add('error');
                    }
                }
            });
        }
        
        // =============================================
        // EMAIL VALIDATION
        // =============================================
        const emailInput = document.getElementById('email');
        
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (this.value.trim() && !emailRegex.test(this.value.trim())) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
            
            emailInput.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (this.value.trim() && emailRegex.test(this.value.trim())) {
                        this.classList.remove('error');
                    }
                }
            });
        }
        
        // =============================================
        // NAME VALIDATION
        // =============================================
        const nameInput = document.getElementById('name');
        
        if (nameInput) {
            nameInput.addEventListener('input', function() {
                const nameRegex = /^[a-zA-Z\s'-]*$/;
                if (this.value.trim() && !nameRegex.test(this.value.trim())) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
        }
        
        // =============================================
        // FORM VALIDATION
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const registerBtn = document.getElementById('registerBtn');
            
            form.addEventListener('submit', function(e) {
                const name = document.getElementById('name');
                const email = document.getElementById('email');
                const password = document.getElementById('password');
                const confirm = document.getElementById('confirm_password');
                const terms = document.getElementById('terms');
                
                let hasError = false;
                
                // Validate name
                if (!name.value.trim() || name.value.trim().length < 2) {
                    showFieldError(name, 'Please enter your full name (min 2 characters)');
                    hasError = true;
                } else {
                    clearFieldError(name);
                }
                
                // Validate email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email.value.trim()) {
                    showFieldError(email, 'Email address is required');
                    hasError = true;
                } else if (!emailRegex.test(email.value.trim())) {
                    showFieldError(email, 'Please enter a valid email address');
                    hasError = true;
                } else {
                    clearFieldError(email);
                }
                
                // Validate password
                if (!password.value.trim() || password.value.trim().length < 6) {
                    showFieldError(password, 'Password must be at least 6 characters');
                    hasError = true;
                } else {
                    clearFieldError(password);
                }
                
                // Validate confirm password
                if (confirm.value.trim() !== password.value.trim()) {
                    showFieldError(confirm, 'Passwords do not match');
                    hasError = true;
                } else if (confirm.value.trim()) {
                    clearFieldError(confirm);
                }
                
                // Validate terms
                if (!terms.checked) {
                    alert('⚠️ Please agree to the Terms & Conditions to continue.');
                    hasError = true;
                }
                
                if (hasError) {
                    e.preventDefault();
                    registerBtn.disabled = false;
                } else {
                    registerBtn.disabled = true;
                    registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';
                }
            });
            
            function showFieldError(input, message) {
                const wrapper = input.closest('.auth-input-wrapper');
                input.classList.add('error');
                input.classList.remove('success');
                
                const existing = wrapper.parentElement.querySelector('.field-error');
                if (existing) existing.remove();
                
                const error = document.createElement('span');
                error.className = 'field-error';
                error.textContent = '⚠️ ' + message;
                wrapper.parentElement.appendChild(error);
            }
            
            function clearFieldError(input) {
                input.classList.remove('error');
                const wrapper = input.closest('.auth-input-wrapper');
                const existing = wrapper.parentElement.querySelector('.field-error');
                if (existing) existing.remove();
            }
        });
    </script>
</body>
</html>