<?php
// =============================================
// 📚 STUDYHUB PRO - Advanced Functions
// Version: 2.0.0
// =============================================

// =============================================
// 1. REDIRECT
// =============================================
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

// =============================================
// 2. AUTHENTICATION
// =============================================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Check if AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['status' => 'error', 'message' => 'Please login first']);
            exit();
        }
        redirect('pages/login.php');
    }
}

// =============================================
// 3. SANITIZE
// =============================================
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// =============================================
// 4. FLASH MESSAGES
// =============================================
function setFlash($key, $message, $type = 'success') {
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type,
        'time' => time()
    ];
}

function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function displayFlash($key) {
    $flash = getFlash($key);
    if ($flash) {
        $type = $flash['type'] === 'error' ? 'danger' : 'success';
        $icon = $flash['type'] === 'error' ? '❌' : '✅';
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade-in" role="alert">
                <span class="alert-icon">' . $icon . '</span>
                ' . sanitize($flash['message']) . '
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
              </div>';
    }
}

// =============================================
// 5. CSRF TOKEN
// =============================================
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// =============================================
// 6. STATUS BADGE
// =============================================
function getStatusBadge($status) {
    $badges = [
        'Pending' => '<span class="badge badge-pending">⏳ Pending</span>',
        'In Progress' => '<span class="badge badge-progress">🔄 In Progress</span>',
        'Completed' => '<span class="badge badge-completed">✅ Completed</span>',
        'Cancelled' => '<span class="badge badge-cancelled">❌ Cancelled</span>'
    ];
    return $badges[$status] ?? $badges['Pending'];
}

function getStatusColor($status) {
    $colors = [
        'Pending' => '#f39c12',
        'In Progress' => '#3498db',
        'Completed' => '#2ecc71',
        'Cancelled' => '#95a5a6'
    ];
    return $colors[$status] ?? '#888';
}

// =============================================
// 7. DATE HELPERS
// =============================================
function formatDate($date) {
    if (!$date) return 'No due date';
    return date('M d, Y', strtotime($date));
}

function timeAgo($datetime) {
    if (!$datetime) return 'Just now';
    
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return round($diff / 60) . ' min ago';
    if ($diff < 86400) return round($diff / 3600) . ' hours ago';
    if ($diff < 604800) return round($diff / 86400) . ' days ago';
    if ($diff < 2592000) return round($diff / 604800) . ' weeks ago';
    return date('M d, Y', $time);
}

function isOverdue($due_date) {
    if (!$due_date) return false;
    return strtotime($due_date) < time() && strtotime($due_date) > 0;
}

// =============================================
// 8. GET USER STATS (Dashboard)
// =============================================
function getUserStats($pdo, $user_id) {
    // Total Subjects
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM subjects WHERE user_id = ? AND deleted_at IS NULL");
    $stmt->execute([$user_id]);
    $total_subjects = $stmt->fetch()['total'] ?? 0;
    
    // Pending Tasks
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE user_id = ? AND status != 'Completed' AND deleted_at IS NULL");
    $stmt->execute([$user_id]);
    $pending_tasks = $stmt->fetch()['total'] ?? 0;
    
    // In Progress Tasks
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE user_id = ? AND status = 'In Progress' AND deleted_at IS NULL");
    $stmt->execute([$user_id]);
    $in_progress_tasks = $stmt->fetch()['total'] ?? 0;
    
    // Completed Tasks
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE user_id = ? AND status = 'Completed' AND deleted_at IS NULL");
    $stmt->execute([$user_id]);
    $completed_tasks = $stmt->fetch()['total'] ?? 0;
    
    // Total Tasks
    $total_tasks = $pending_tasks + $in_progress_tasks + $completed_tasks;
    
    // Completion Percentage
    $completion = ($total_tasks > 0) ? round(($completed_tasks / $total_tasks) * 100) : 0;
    
    // Overdue Tasks
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM tasks 
        WHERE user_id = ? 
            AND due_date < CURDATE() 
            AND status != 'Completed' 
            AND deleted_at IS NULL
    ");
    $stmt->execute([$user_id]);
    $overdue_tasks = $stmt->fetch()['total'] ?? 0;
    
    return [
        'total_subjects' => $total_subjects,
        'pending_tasks' => $pending_tasks,
        'in_progress_tasks' => $in_progress_tasks,
        'completed_tasks' => $completed_tasks,
        'total_tasks' => $total_tasks,
        'completion' => $completion,
        'overdue_tasks' => $overdue_tasks
    ];
}

// =============================================
// 9. GET UPCOMING TASKS
// =============================================
function getUpcomingTasks($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT t.*, s.subject_name, s.color as subject_color 
        FROM tasks t
        JOIN subjects s ON t.subject_id = s.id 
        WHERE t.user_id = ? 
            AND t.due_date >= CURDATE()
            AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
            AND t.status != 'Completed'
            AND t.deleted_at IS NULL
        ORDER BY t.due_date ASC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

// =============================================
// 10. GET RECENT ACTIVITY
// =============================================
function getRecentActivity($pdo, $user_id, $limit = 5) {
    $stmt = $pdo->prepare("
        (SELECT 
            'subject' as type, 
            subject_name as name, 
            created_at as date 
        FROM subjects 
        WHERE user_id = ? AND deleted_at IS NULL)
        UNION ALL
        (SELECT 
            'task' as type, 
            task_name as name, 
            created_at as date 
        FROM tasks 
        WHERE user_id = ? AND deleted_at IS NULL)
        ORDER BY date DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $user_id, $limit]);
    return $stmt->fetchAll();
}

// =============================================
// 11. GET SUBJECT PROGRESS
// =============================================
function getSubjectProgress($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.subject_name,
            s.color,
            COUNT(t.id) as total_tasks,
            SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM subjects s
        LEFT JOIN tasks t ON s.id = t.subject_id AND t.deleted_at IS NULL
        WHERE s.user_id = ? AND s.deleted_at IS NULL
        GROUP BY s.id
        ORDER BY s.subject_name ASC
    ");
    $stmt->execute([$user_id]);
    $results = $stmt->fetchAll();
    
    foreach ($results as &$row) {
        $total = $row['total_tasks'] ?? 0;
        $completed = $row['completed_tasks'] ?? 0;
        $row['completion_percentage'] = ($total > 0) ? round(($completed / $total) * 100) : 0;
    }
    
    return $results;
}

// =============================================
// 12. GET TASK STATUS DISTRIBUTION
// =============================================
function getTaskStatusDistribution($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT 
            status, 
            COUNT(*) as count 
        FROM tasks 
        WHERE user_id = ? AND deleted_at IS NULL
        GROUP BY status
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// =============================================
// 13. GET WEEKLY COMPLETION
// =============================================
function getWeeklyCompletion($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(completed_at) as date,
            COUNT(*) as count
        FROM tasks
        WHERE user_id = ? 
            AND status = 'Completed' 
            AND completed_at IS NOT NULL
            AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(completed_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// =============================================
// 14. VALIDATE EMAIL
// =============================================
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// =============================================
// 15. GENERATE UNIQUE SLUG
// =============================================
function generateSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// =============================================
// 16. GET USER BY ID
// =============================================
function getUserById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// =============================================
// 17. LOG ACTIVITY
// =============================================
function logActivity($pdo, $user_id, $action, $details = null) {
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $action,
        $details ? json_encode($details) : null,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

// =============================================
// 18. GET MOTIVATIONAL QUOTE (Random)
// =============================================
function getMotivationalQuote() {
    $quotes = [
        "Success is the sum of small efforts repeated day in and day out.",
        "The secret of getting ahead is getting started.",
        "Don't watch the clock; do what it does. Keep going.",
        "The expert in anything was once a beginner.",
        "It does not matter how slowly you go as long as you do not stop.",
        "Believe you can and you're halfway there.",
        "The only way to do great work is to love what you do.",
        "Start where you are. Use what you have. Do what you can.",
        "Dream big. Work hard. Stay focused.",
        "Success is not final, failure is not fatal: it is the courage to continue that counts.",
        "The best time to start was yesterday. The next best time is now.",
        "Small daily improvements over time lead to stunning results.",
        "You don't have to be great to start, but you have to start to be great.",
        "The future belongs to those who believe in the beauty of their dreams.",
        "Hardships often prepare ordinary people for an extraordinary destiny."
    ];
    return $quotes[array_rand($quotes)];
}

// =============================================
// 19. GET TIME GREETING
// =============================================
function getGreeting() {
    $hour = date('H');
    if ($hour < 12) {
        return 'Good Morning 🌅';
    } elseif ($hour < 17) {
        return 'Good Afternoon ☀️';
    } elseif ($hour < 21) {
        return 'Good Evening 🌇';
    } else {
        return 'Good Night 🌙';
    }
}

// =============================================
// 20. GET TASK STATUS ICON
// =============================================
function getStatusIcon($status) {
    $icons = [
        'Pending' => '⏳',
        'In Progress' => '🔄',
        'Completed' => '✅',
        'Cancelled' => '❌'
    ];
    return $icons[$status] ?? '📌';
}

// =============================================
// END OF FUNCTIONS
// =============================================
?>