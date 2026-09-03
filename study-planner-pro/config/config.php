<?php
// =============================================
// 🔐 STUDYHUB PRO - Advanced Configuration
// =============================================

// =============================================
// 1. ERROR REPORTING (Development Mode)
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// =============================================
// 2. DATABASE CONFIGURATION
// =============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'study_planner_pro');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =============================================
// 3. DATABASE CONNECTION (With Advanced Options)
// =============================================
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
    PDO::ATTR_PERSISTENT => false
];

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $options
    );
} catch (PDOException $e) {
    // Log error and show friendly message
    error_log("Database Connection Failed: " . $e->getMessage());
    die("🔴 Database connection failed. Please check your configuration.");
}

// =============================================
// 4. SESSION CONFIGURATION
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    
    session_name('studyhub_session');
    session_start();
}

// =============================================
// 5. APPLICATION CONFIGURATION
// =============================================
define('APP_NAME', 'StudyHub Pro');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost/study-planner-pro/');
define('BASE_URL', APP_URL);
define('TIMEZONE', 'Asia/Colombo');
date_default_timezone_set(TIMEZONE);

// =============================================
// 6. SECURITY
// =============================================
define('SALT', 'studyhub_secure_salt_2024');
define('HASH_ALGO', PASSWORD_DEFAULT);
define('HASH_OPTIONS', ['cost' => 12]);

// =============================================
// 7. FILE PATHS
// =============================================
define('ROOT_PATH', dirname(__DIR__) . '/');
define('LOG_PATH', ROOT_PATH . 'logs/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');

// =============================================
// 8. CREATE REQUIRED DIRECTORIES
// =============================================
$directories = [LOG_PATH, UPLOAD_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// =============================================
// 9. DEVELOPMENT MODE
// =============================================
define('DEVELOPMENT_MODE', true);

// =============================================
// 10. CSRF TOKEN
// =============================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =============================================
// 11. DATABASE CONNECTION TEST
// =============================================
function testDatabaseConnection($pdo) {
    try {
        $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// =============================================
// 12. CONFIGURATION SUMMARY (Admin Only)
// =============================================
$config_summary = [
    'App Name' => APP_NAME,
    'Version' => APP_VERSION,
    'Timezone' => TIMEZONE,
    'Database' => DB_NAME,
    'Connection' => testDatabaseConnection($pdo) ? '✅ Connected' : '❌ Failed'
];

// Only show in development mode
if (DEVELOPMENT_MODE && isset($_SESSION['user_id'])) {
    // Uncomment to see config details
    // error_log(json_encode($config_summary));
}

// =============================================
// 13. ERROR HANDLER (Custom)
// =============================================
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $log_message = sprintf(
        "[%s] Error: %s in %s on line %d",
        date('Y-m-d H:i:s'),
        $errstr,
        $errfile,
        $errline
    );
    
    error_log($log_message);
    
    if (DEVELOPMENT_MODE) {
        echo "<div style='background:#f8d7da;padding:10px;border:1px solid #f5c6cb;margin:10px;border-radius:5px;'>";
        echo "<strong>⚠️ PHP Error:</strong> " . htmlspecialchars($errstr);
        echo "<br><small>File: " . htmlspecialchars($errfile) . " (Line " . $errline . ")</small>";
        echo "</div>";
    }
    
    return true;
});

// =============================================
// 14. EXCEPTION HANDLER (Custom)
// =============================================
set_exception_handler(function($exception) {
    $log_message = sprintf(
        "[%s] Exception: %s in %s on line %d\n%s",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    error_log($log_message);
    
    if (DEVELOPMENT_MODE) {
        echo "<div style='background:#f8d7da;padding:15px;border:1px solid #f5c6cb;margin:10px;border-radius:5px;'>";
        echo "<strong>🚨 Exception:</strong> " . htmlspecialchars($exception->getMessage());
        echo "<br><small>File: " . htmlspecialchars($exception->getFile()) . " (Line " . $exception->getLine() . ")</small>";
        echo "</div>";
    } else {
        echo "An unexpected error occurred. Please try again later.";
    }
});

// =============================================
// 15. SHUTDOWN FUNCTION
// =============================================
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log(sprintf(
            "[%s] Fatal Error: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        ));
    }
});

// =============================================
// 16. CORS (If needed)
// =============================================
// header("Access-Control-Allow-Origin: *");
// header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
// header("Access-Control-Allow-Headers: Content-Type, Authorization");

// =============================================
// 17. SECURITY HEADERS
// =============================================
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// =============================================
// 18. CONFIGURATION COMPLETE
// =============================================
// Uncomment to test config
// echo "✅ Configuration loaded successfully!";
// echo "<pre>" . print_r($config_summary, true) . "</pre>";
?>