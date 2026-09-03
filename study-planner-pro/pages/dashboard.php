<?php
// =============================================
// 🔐 DASHBOARD - Advanced Version
// =============================================
require_once '../config/config.php';
require_once '../config/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// =============================================
// 1. GET USER STATS (Using Advanced Function)
// =============================================
$stats = getUserStats($pdo, $user_id);

// =============================================
// 2. GET UPCOMING TASKS (Next 7 Days)
// =============================================
$upcoming_tasks = getUpcomingTasks($pdo, $user_id, 10);

// =============================================
// 3. GET RECENT ACTIVITY
// =============================================
$recent_activity = getRecentActivity($pdo, $user_id, 10);

// =============================================
// 4. GET SUBJECT PROGRESS (For Chart)
// =============================================
$stmt = $pdo->prepare("
    SELECT s.subject_name, s.color, sp.completion_percentage 
    FROM subject_progress sp
    JOIN subjects s ON sp.subject_id = s.id
    WHERE sp.user_id = ?
    ORDER BY sp.completion_percentage DESC
");
$stmt->execute([$user_id]);
$subject_progress = $stmt->fetchAll();

// =============================================
// 5. GET TASK STATUS DISTRIBUTION (For Pie Chart)
// =============================================
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM tasks 
    WHERE user_id = ? AND deleted_at IS NULL
    GROUP BY status
");
$stmt->execute([$user_id]);
$task_status_distribution = $stmt->fetchAll();

// =============================================
// 6. GET WEEKLY TASK COMPLETION
// =============================================
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
$weekly_completion = $stmt->fetchAll();

// Prepare data for weekly chart
$weekly_dates = [];
$weekly_counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weekly_dates[] = date('D', strtotime($date));
    $found = false;
    foreach ($weekly_completion as $row) {
        if ($row['date'] == $date) {
            $weekly_counts[] = $row['count'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $weekly_counts[] = 0;
    }
}

// =============================================
// 7. GREETING BASED ON TIME
// =============================================
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Good Morning 🌅';
} elseif ($hour < 17) {
    $greeting = 'Good Afternoon ☀️';
} elseif ($hour < 21) {
    $greeting = 'Good Evening 🌇';
} else {
    $greeting = 'Good Night 🌙';
}

// =============================================
// 8. GET TOTAL TASKS
// =============================================
$total_tasks = $stats['pending_tasks'] + $stats['completed_tasks'];

// =============================================
// 9. GET TASK STATUS ICONS
// =============================================
$status_icons = [
    'Pending' => '⏳',
    'In Progress' => '🔄',
    'Completed' => '✅',
    'Cancelled' => '❌'
];

// =============================================
// 10. CHECK FOR OVERDUE TASKS
// =============================================
$has_overdue = $stats['overdue_tasks'] > 0;
?>
<?php include '../includes/header.php'; ?>

<!-- =============================================
WELCOME SECTION
============================================= -->
<div class="dashboard-welcome fade-in">
    <div class="welcome-content">
        <div class="welcome-text">
            <h1><?php echo $greeting; ?> 👋</h1>
            <p class="welcome-name">
                <strong><?php echo sanitize($_SESSION['user_name']); ?></strong>
                <span class="welcome-badge">Pro</span>
            </p>
            <p class="welcome-subtitle">
                <?php
                if ($stats['pending_tasks'] == 0 && $stats['total_tasks'] > 0) {
                    echo '🎉 All tasks completed! Amazing work!';
                } elseif ($stats['pending_tasks'] == 0) {
                    echo '📚 Start by adding your first subject or task!';
                } elseif ($stats['overdue_tasks'] > 0) {
                    echo '⚠️ You have <strong>' . $stats['overdue_tasks'] . '</strong> overdue tasks! Check them below.';
                } else {
                    echo '📊 You have <strong>' . $stats['pending_tasks'] . '</strong> tasks pending. Keep going!';
                }
                ?>
            </p>
        </div>
        <div class="welcome-date">
            <div class="date-box">
                <span class="date-day"><?php echo date('d'); ?></span>
                <span class="date-month"><?php echo date('M'); ?></span>
                <span class="date-year"><?php echo date('Y'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
STATISTICS CARDS
============================================= -->
<div class="stats-grid fade-in">
    <div class="stat-card stat-card-primary slide-up">
        <div class="stat-card-icon">📚</div>
        <div class="stat-card-content">
            <h3>Subjects</h3>
            <div class="stat-number"><?php echo $stats['total_subjects']; ?></div>
            <div class="stat-trend">
                <span class="trend-label">Total subjects</span>
            </div>
        </div>
    </div>
    
    <div class="stat-card stat-card-warning slide-up" style="animation-delay:0.1s;">
        <div class="stat-card-icon">⏳</div>
        <div class="stat-card-content">
            <h3>Pending Tasks</h3>
            <div class="stat-number"><?php echo $stats['pending_tasks']; ?></div>
            <div class="stat-trend">
                <span class="trend-label"><?php echo ($stats['overdue_tasks'] > 0) ? '⚠️ ' . $stats['overdue_tasks'] . ' overdue' : 'All on track'; ?></span>
            </div>
        </div>
    </div>
    
    <div class="stat-card stat-card-success slide-up" style="animation-delay:0.2s;">
        <div class="stat-card-icon">✅</div>
        <div class="stat-card-content">
            <h3>Completed</h3>
            <div class="stat-number"><?php echo $stats['completed_tasks']; ?></div>
            <div class="stat-trend">
                <span class="trend-label"><?php echo $stats['completion']; ?>% completion</span>
            </div>
        </div>
    </div>
    
    <div class="stat-card stat-card-info slide-up" style="animation-delay:0.3s;">
        <div class="stat-card-icon">📊</div>
        <div class="stat-card-content">
            <h3>Progress</h3>
            <div class="stat-number"><?php echo $stats['completion']; ?>%</div>
            <div class="stat-trend">
                <span class="trend-label">
                    <?php
                    if ($stats['completion'] >= 80) {
                        echo '🌟 Excellent!';
                    } elseif ($stats['completion'] >= 50) {
                        echo '👍 Keep going!';
                    } elseif ($stats['total_tasks'] > 0) {
                        echo '💪 Start working!';
                    } else {
                        echo '📝 Add tasks';
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
MAIN DASHBOARD GRID (Charts & Activity)
============================================= -->
<div class="dashboard-grid fade-in">
    <!-- =============================================
    LEFT COLUMN - Charts
    ============================================= -->
    <div class="dashboard-left">
        <!-- Progress Card -->
        <div class="card card-glass slide-up">
            <div class="card-header">
                <h3>📈 Overall Progress</h3>
                <span class="card-badge">
                    <?php echo $stats['completed_tasks']; ?>/<?php echo $total_tasks; ?> tasks
                </span>
            </div>
            <div class="card-body">
                <div class="progress-wrapper">
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill animate-progress" 
                             style="width: <?php echo $stats['completion']; ?>%;">
                            <?php echo $stats['completion']; ?>%
                        </div>
                    </div>
                    <div class="progress-labels">
                        <span>0%</span>
                        <span>50%</span>
                        <span>100%</span>
                    </div>
                </div>
                
                <!-- Small stats -->
                <div class="progress-stats">
                    <div class="progress-stat-item">
                        <span class="stat-dot pending"></span>
                        <span>Pending: <?php echo $stats['pending_tasks']; ?></span>
                    </div>
                    <div class="progress-stat-item">
                        <span class="stat-dot completed"></span>
                        <span>Completed: <?php echo $stats['completed_tasks']; ?></span>
                    </div>
                    <?php if ($stats['overdue_tasks'] > 0): ?>
                    <div class="progress-stat-item">
                        <span class="stat-dot overdue"></span>
                        <span>Overdue: <?php echo $stats['overdue_tasks']; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Weekly Completion Chart -->
        <div class="card card-glass slide-up" style="animation-delay:0.1s;">
            <div class="card-header">
                <h3>📊 Weekly Task Completion</h3>
                <span class="card-badge">Last 7 days</span>
            </div>
            <div class="card-body">
                <div class="weekly-chart">
                    <?php foreach ($weekly_dates as $index => $day): ?>
                    <div class="weekly-bar-wrapper">
                        <div class="weekly-bar-label"><?php echo $day; ?></div>
                        <div class="weekly-bar-bg">
                            <div class="weekly-bar-fill" 
                                 style="height: <?php echo ($weekly_counts[$index] > 0) ? min($weekly_counts[$index] * 30, 100) : 0; ?>%;">
                                <?php if ($weekly_counts[$index] > 0): ?>
                                    <span class="weekly-bar-value"><?php echo $weekly_counts[$index]; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- =============================================
    RIGHT COLUMN - Activity & Subject Progress
    ============================================= -->
    <div class="dashboard-right">
        <!-- Recent Activity -->
        <div class="card card-glass slide-up" style="animation-delay:0.2s;">
            <div class="card-header">
                <h3>🔄 Recent Activity</h3>
                <span class="card-badge">Latest updates</span>
            </div>
            <div class="card-body activity-list">
                <?php if (count($recent_activity) > 0): ?>
                    <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php if ($activity['type'] == 'subject'): ?>
                                📚
                            <?php else: ?>
                                📝
                            <?php endif; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <?php if ($activity['type'] == 'subject'): ?>
                                    Added subject: <strong><?php echo sanitize($activity['name']); ?></strong>
                                <?php else: ?>
                                    Created task: <strong><?php echo sanitize($activity['name']); ?></strong>
                                <?php endif; ?>
                            </div>
                            <div class="activity-time">
                                <?php echo timeAgo($activity['date']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-empty">
                        <i class="fas fa-inbox"></i>
                        <p>No recent activity yet. Start adding subjects and tasks!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Subject Progress -->
        <div class="card card-glass slide-up" style="animation-delay:0.3s;">
            <div class="card-header">
                <h3>📚 Subject Progress</h3>
                <span class="card-badge">Completion rates</span>
            </div>
            <div class="card-body subject-progress-list">
                <?php if (count($subject_progress) > 0): ?>
                    <?php foreach ($subject_progress as $subject): ?>
                    <div class="subject-progress-item">
                        <div class="subject-progress-info">
                            <span class="subject-color-dot" style="background: <?php echo $subject['color']; ?>;"></span>
                            <span class="subject-name"><?php echo sanitize($subject['subject_name']); ?></span>
                        </div>
                        <div class="subject-progress-bar-wrapper">
                            <div class="subject-progress-bar-bg">
                                <div class="subject-progress-bar-fill" 
                                     style="width: <?php echo round($subject['completion_percentage']); ?>%; background: <?php echo $subject['color']; ?>;">
                                </div>
                            </div>
                            <span class="subject-progress-percentage">
                                <?php echo round($subject['completion_percentage']); ?>%
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-empty">
                        <i class="fas fa-book-open"></i>
                        <p>No subjects yet. Create your first subject to track progress!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
UPCOMING TASKS SECTION
============================================= -->
<div class="card card-glass fade-in" style="margin-top: 30px;">
    <div class="card-header">
        <h3>📅 Upcoming Deadlines</h3>
        <span class="card-badge">Next 7 days</span>
    </div>
    <div class="card-body">
        <?php if (count($upcoming_tasks) > 0): ?>
            <div class="upcoming-tasks-grid">
                <?php foreach ($upcoming_tasks as $task): 
                    $days_remaining = ceil((strtotime($task['due_date']) - time()) / 86400);
                    $is_overdue = $days_remaining < 0;
                    $priority_class = '';
                    if ($is_overdue) {
                        $priority_class = 'task-overdue';
                    } elseif ($days_remaining <= 2) {
                        $priority_class = 'task-urgent';
                    } elseif ($days_remaining <= 4) {
                        $priority_class = 'task-soon';
                    }
                ?>
                <div class="upcoming-task-item <?php echo $priority_class; ?>">
                    <div class="task-item-left">
                        <div class="task-item-icon">
                            <?php echo $is_overdue ? '🔴' : '📌'; ?>
                        </div>
                        <div class="task-item-info">
                            <div class="task-item-title"><?php echo sanitize($task['task_name']); ?></div>
                            <div class="task-item-meta">
                                <span class="task-item-subject">📖 <?php echo sanitize($task['subject_name']); ?></span>
                                <span class="task-item-due">
                                    <?php if ($is_overdue): ?>
                                        <span class="overdue-label">⚠️ Overdue</span>
                                    <?php else: ?>
                                        Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                        (<?php echo $days_remaining; ?> days)
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="task-item-right">
                        <?php echo getStatusBadge($task['status']); ?>
                        <a href="<?php echo BASE_URL; ?>pages/tasks.php" class="btn-sm btn-primary">View</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎉</div>
                <h3>No upcoming deadlines!</h3>
                <p>You're all caught up. Take a break or start planning.</p>
                <a href="<?php echo BASE_URL; ?>pages/tasks.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Task
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- =============================================
QUICK ACTIONS
============================================= -->
<div class="quick-actions fade-in">
    <h3>⚡ Quick Actions</h3>
    <div class="quick-actions-grid">
        <a href="<?php echo BASE_URL; ?>pages/subjects.php" class="quick-action-card">
            <div class="quick-action-icon">📚</div>
            <div class="quick-action-text">Add Subject</div>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/tasks.php" class="quick-action-card">
            <div class="quick-action-icon">📝</div>
            <div class="quick-action-text">Add Task</div>
        </a>
        <a href="<?php echo BASE_URL; ?>profile.php" class="quick-action-card">
            <div class="quick-action-icon">👤</div>
            <div class="quick-action-text">Update Profile</div>
        </a>
        <a href="<?php echo BASE_URL; ?>pages/tasks.php?status=pending" class="quick-action-card">
            <div class="quick-action-icon">⏳</div>
            <div class="quick-action-text">View Pending</div>
        </a>
    </div>
</div>

<!-- =============================================
MOTIVATIONAL QUOTE (Auto Rotating)
============================================= -->
<div class="motivational-quote fade-in">
    <div class="quote-container">
        <i class="fas fa-quote-left"></i>
        <p id="motivationalText">"Success is the sum of small efforts repeated day in and day out."</p>
        <i class="fas fa-quote-right"></i>
    </div>
</div>

<?php include '../includes/footer.php'; ?>