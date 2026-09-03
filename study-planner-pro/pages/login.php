<?php
// =============================================
// 🔐 LOGIN - Advanced Version
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
// HANDLE LOGIN
// =============================================
$error = '';
$email = '';
$remember = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security verification failed. Please try again.';
    } else {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);
        
        // Validation
        if (empty($email)) {
            $error = 'Email address is required!';
        } elseif (empty($password)) {
            $error = 'Password is required!';
        } else {
            // Check user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 AND deleted_at IS NULL");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['login_time'] = time();
                
                // Update last login
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Remember me (Cookie)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                    
                    // Store token in database (you would create a remember_tokens table)
                    // For now, we'll store it in a cookie
                }
                
                // Log activity
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, details, ip_address) 
                    VALUES (?, 'login', ?, ?)
                ");
                $stmt->execute([
                    $user['id'],
                    json_encode(['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                redirect('pages/dashboard.php');
            } else {
                $error = 'Invalid email or password!';
                // Log failed attempt
                error_log("Failed login attempt for email: " . $email);
            }
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
    <title>Login - <?php echo APP_NAME; ?></title>
    
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            padding: 50px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            margin-bottom: 30px;
        }
        
        .auth-form-group {
            margin-bottom: 20px;
        }
        
        .auth-form-group label {
            display: block;
            font-weight: 500;
            font-size: 14px;
            color: var(--text);
            margin-bottom: 6px;
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
        }
        
        .auth-input-wrapper input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: var(--bg);
            color: var(--text);
        }
        
        .auth-input-wrapper input:focus {
            border-color: #667eea;
            outline: none;
            background: var(--card-bg);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .auth-input-wrapper input.error {
            border-color: #e74c3c;
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
        }
        
        .auth-input-wrapper .toggle-password:hover {
            color: var(--text);
        }
        
        .auth-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0 25px;
        }
        
        .auth-options label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-light);
            cursor: pointer;
        }
        
        .auth-options label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
            cursor: pointer;
        }
        
        .auth-options a {
            font-size: 14px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .auth-options a:hover {
            color: #5a67d8;
        }
        
        .btn-auth {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 5px;
        }
        
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
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
            margin: 25px 0;
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
            border-color: #667eea;
            background: var(--card-bg);
            transform: translateY(-2px);
        }
        
        .social-btn.google {
            border-color: #ea4335;
        }
        .social-btn.google:hover {
            background: #fee8e6;
        }
        .social-btn.github {
            border-color: #333;
        }
        .social-btn.github:hover {
            background: #f0f0f0;
        }
        .social-btn.facebook {
            border-color: #1877f2;
        }
        .social-btn.facebook:hover {
            background: #e8f0fe;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 15px;
            color: var(--text-light);
        }
        
        .auth-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .auth-footer a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }
        
        .auth-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }
        
        .auth-error i {
            font-size: 18px;
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
                max-width: 500px;
                min-height: auto;
            }
            
            .auth-left {
                padding: 40px 35px;
                min-height: 250px;
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
                padding: 35px 30px;
            }
        }
        
        @media (max-width: 480px) {
            .auth-left {
                padding: 30px 25px;
            }
            
            .auth-left h1 {
                font-size: 24px;
            }
            
            .auth-right {
                padding: 25px 20px;
            }
            
            .auth-right h2 {
                font-size: 24px;
            }
            
            .social-login {
                grid-template-columns: 1fr;
            }
            
            .auth-options {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
        
        /* Dark mode support */
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
                <h1>Welcome to <span>StudyHub</span></h1>
                <p>Your all-in-one student study planner. Track subjects, manage tasks, and achieve your academic goals.</p>
                
                <ul class="auth-features">
                    <li><i class="fas fa-check-circle"></i> Track subjects and progress</li>
                    <li><i class="fas fa-check-circle"></i> Manage tasks and deadlines</li>
                    <li><i class="fas fa-check-circle"></i> Monitor your progress</li>
                    <li><i class="fas fa-check-circle"></i> Stay organized and focused</li>
                </ul>
            </div>
        </div>
        
        <!-- =============================================
        RIGHT PANEL - Login Form
        ============================================= -->
        <div class="auth-right">
            <h2>🔐 Sign In</h2>
            <p class="subtitle">Welcome back! Please enter your credentials.</p>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="auth-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="auth-form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               placeholder="you@example.com" 
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="auth-form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               placeholder="Enter your password" 
                               required>
                        <button type="button" 
                                class="toggle-password" 
                                onclick="togglePasswordVisibility()"
                                aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="auth-options">
                    <label>
                        <input type="checkbox" name="remember" <?php echo $remember ? 'checked' : ''; ?>>
                        Remember me
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn-auth" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <!-- Divider -->
            <div class="auth-divider">
                <span>or continue with</span>
            </div>
            
            <!-- Social Login -->
            <div class="social-login">
                <a href="#" class="social-btn google" onclick="alert('Google login coming soon!'); return false;">
                    <i class="fab fa-google"></i> Google
                </a>
                <a href="#" class="social-btn github" onclick="alert('GitHub login coming soon!'); return false;">
                    <i class="fab fa-github"></i> GitHub
                </a>
                <a href="#" class="social-btn facebook" onclick="alert('Facebook login coming soon!'); return false;">
                    <i class="fab fa-facebook"></i> Facebook
                </a>
            </div>
            
            <!-- Footer -->
            <div class="auth-footer">
                Don't have an account? <a href="register.php">Create Account</a>
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
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // =============================================
        // FORM VALIDATION
        // =============================================
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            
            // Real-time validation
            emailInput.addEventListener('blur', function() {
                validateEmail(this);
            });
            
            emailInput.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateEmail(this);
                }
            });
            
            function validateEmail(input) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (input.value.trim() && !emailRegex.test(input.value.trim())) {
                    input.classList.add('error');
                    return false;
                } else {
                    input.classList.remove('error');
                    return true;
                }
            }
            
            // Form submit
            form.addEventListener('submit', function(e) {
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();
                
                let hasError = false;
                
                // Validate email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email) {
                    showFieldError(emailInput, 'Email is required');
                    hasError = true;
                } else if (!emailRegex.test(email)) {
                    showFieldError(emailInput, 'Please enter a valid email');
                    hasError = true;
                } else {
                    clearFieldError(emailInput);
                }
                
                // Validate password
                if (!password) {
                    showFieldError(passwordInput, 'Password is required');
                    hasError = true;
                } else {
                    clearFieldError(passwordInput);
                }
                
                if (hasError) {
                    e.preventDefault();
                    loginBtn.disabled = false;
                } else {
                    loginBtn.disabled = true;
                    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
                }
            });
            
            function showFieldError(input, message) {
                const wrapper = input.closest('.auth-input-wrapper');
                input.classList.add('error');
                
                // Remove existing error
                const existing = wrapper.parentElement.querySelector('.field-error');
                if (existing) existing.remove();
                
                const error = document.createElement('small');
                error.className = 'field-error';
                error.style.cssText = 'color: #dc2626; font-size: 13px; margin-top: 4px; display: block;';
                error.textContent = '⚠️ ' + message;
                wrapper.parentElement.appendChild(error);
            }
            
            function clearFieldError(input) {
                input.classList.remove('error');
                const wrapper = input.closest('.auth-input-wrapper');
                const existing = wrapper.parentElement.querySelector('.field-error');
                if (existing) existing.remove();
            }
            
            // =============================================
            // ENTER KEY SUPPORT
            // =============================================
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    const active = document.activeElement;
                    if (active && (active.id === 'email' || active.id === 'password')) {
                        document.getElementById('loginBtn').click();
                    }
                }
            });
            
            // =============================================
            // DEMO ACCOUNT (For testing)
            // =============================================
            // Uncomment to auto-fill demo credentials
            // const demoBtn = document.createElement('button');
            // demoBtn.textContent = 'Use Demo Account';
            // demoBtn.className = 'btn-demo';
            // demoBtn.style.cssText = 'margin-top:10px; padding:8px; background:#eee; border:1px solid #ddd; border-radius:8px; cursor:pointer; width:100%;';
            // demoBtn.onclick = function(e) {
            //     e.preventDefault();
            //     document.getElementById('email').value = 'demo@example.com';
            //     document.getElementById('password').value = 'demo123';
            // };
            // document.querySelector('.auth-footer').before(demoBtn);
        });
    </script>
</body>
</html>