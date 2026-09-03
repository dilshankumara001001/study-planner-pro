<?php
// =============================================
// 📝 TASKS - Advanced Version
// =============================================
require_once '../config/config.php';
require_once '../config/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// =============================================
// GET FILTER PARAMETERS
// =============================================
$filter_status = $_GET['status'] ?? '';
$filter_subject = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$search_query = $_GET['search'] ?? '';

// =============================================
// HANDLE ADD TASK
// =============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_task'])) {
    $subject_id = (int)$_POST['subject_id'];
    $task_name = sanitize($_POST['task_name']);
    $desc = sanitize($_POST['description']);
    $due_date = sanitize($_POST['due_date']);
    $priority = sanitize($_POST['priority'] ?? 'Medium');
    $start_date = sanitize($_POST['start_date'] ?? null);
    $estimated_hours = (float)($_POST['estimated_hours'] ?? 0);
    $tags = sanitize($_POST['tags'] ?? '');
    
    if (!empty($task_name) && $subject_id > 0 && !empty($due_date)) {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (user_id, subject_id, task_name, description, due_date, priority, start_date, estimated_hours, tags) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $subject_id, $task_name, $desc, $due_date, $priority, $start_date, $estimated_hours, $tags]);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details) 
            VALUES (?, 'task_created', ?)
        ");
        $stmt->execute([
            $user_id,
            json_encode(['task_name' => $task_name, 'subject_id' => $subject_id])
        ]);
        
        setFlash('task', '✅ Task added successfully!', 'success');
    } else {
        setFlash('task', '❌ Please fill all required fields!', 'error');
    }
    redirect('pages/tasks.php');
}

// =============================================
// HANDLE COMPLETE TASK
// =============================================
if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET status = 'Completed', completed_at = NOW() 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$id, $user_id]);
    setFlash('task', '🎉 Task completed! Great job!', 'success');
    redirect('pages/tasks.php');
}

// =============================================
// HANDLE DELETE (Soft Delete)
// =============================================
if (isset($_GET['delete_task'])) {
    $id = (int)$_GET['delete_task'];
    $stmt = $pdo->prepare("UPDATE tasks SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    setFlash('task', '🗑️ Task deleted!', 'success');
    redirect('pages/tasks.php');
}

// =============================================
// FETCH SUBJECTS FOR DROPDOWN
// =============================================
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE user_id = ? AND deleted_at IS NULL ORDER BY subject_name ASC");
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll();

// =============================================
// BUILD FILTER CONDITIONS
// =============================================
$where_conditions = ["t.user_id = ?", "t.deleted_at IS NULL"];
$params = [$user_id];

if ($filter_status) {
    $where_conditions[] = "t.status = ?";
    $params[] = $filter_status;
}

if ($filter_subject > 0) {
    $where_conditions[] = "t.subject_id = ?";
    $params[] = $filter_subject;
}

if ($search_query) {
    $where_conditions[] = "(t.task_name LIKE ? OR t.description LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(" AND ", $where_conditions);

// =============================================
// FETCH TASKS
// =============================================
$sql = "
    SELECT 
        t.*,
        s.subject_name,
        s.color as subject_color
    FROM tasks t
    JOIN subjects s ON t.subject_id = s.id
    WHERE $where_clause
    ORDER BY 
        CASE 
            WHEN t.due_date < CURDATE() AND t.status != 'Completed' THEN 0
            WHEN t.status = 'Pending' THEN 1
            WHEN t.status = 'In Progress' THEN 2
            WHEN t.status = 'Completed' THEN 3
            ELSE 4
        END,
        t.due_date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// =============================================
// GET STATS
// =============================================
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
    FROM tasks 
    WHERE user_id = ? AND deleted_at IS NULL
");
$stmt->execute([$user_id]);
$task_stats = $stmt->fetch();

// Priority labels and colors
$priorities = [
    'Low' => ['label' => 'Low', 'color' => '#2ecc71', 'bg' => '#d4edda', 'icon' => '🟢'],
    'Medium' => ['label' => 'Medium', 'color' => '#f39c12', 'bg' => '#fff3cd', 'icon' => '🟡'],
    'High' => ['label' => 'High', 'color' => '#e74c3c', 'bg' => '#f8d7da', 'icon' => '🔴'],
    'Urgent' => ['label' => 'Urgent', 'color' => '#dc3545', 'bg' => '#f5c6cb', 'icon' => '🔥']
];
?>
<?php include '../includes/header.php'; ?>

<!-- =============================================
PAGE HEADER
============================================= -->
<div class="page-header fade-in">
    <div class="page-header-left">
        <h1>
            <i class="fas fa-tasks"></i> 
            My Tasks
            <span class="page-badge"><?php echo $task_stats['total'] ?? 0; ?></span>
        </h1>
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            <span>Tasks</span>
        </div>
    </div>
    <div class="page-header-right">
        <button class="btn btn-primary" onclick="document.getElementById('addTaskForm').scrollIntoView({behavior:'smooth'});">
            <i class="fas fa-plus"></i> Add Task
        </button>
    </div>
</div>

<!-- =============================================
STATS ROW
============================================= -->
<div class="stats-mini fade-in">
    <div class="stat-mini-item">
        <span class="stat-mini-label">Total</span>
        <span class="stat-mini-value"><?php echo $task_stats['total'] ?? 0; ?></span>
    </div>
    <div class="stat-mini-item pending">
        <span class="stat-mini-label">Pending</span>
        <span class="stat-mini-value"><?php echo $task_stats['pending'] ?? 0; ?></span>
    </div>
    <div class="stat-mini-item progress">
        <span class="stat-mini-label">In Progress</span>
        <span class="stat-mini-value"><?php echo $task_stats['in_progress'] ?? 0; ?></span>
    </div>
    <div class="stat-mini-item completed">
        <span class="stat-mini-label">Completed</span>
        <span class="stat-mini-value"><?php echo $task_stats['completed'] ?? 0; ?></span>
    </div>
</div>

<!-- =============================================
FLASH MESSAGES
============================================= -->
<?php displayFlash('task'); ?>

<!-- =============================================
SEARCH & FILTER
============================================= -->
<div class="filter-section fade-in">
    <form method="GET" class="filter-form" id="filterForm">
        <div class="filter-row">
            <div class="filter-search">
                <i class="fas fa-search"></i>
                <input type="text" 
                       name="search" 
                       placeholder="Search tasks..." 
                       value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            
            <div class="filter-group">
                <select name="status" id="filterStatus">
                    <option value="">All Status</option>
                    <option value="Pending" <?php echo ($filter_status == 'Pending') ? 'selected' : ''; ?>>⏳ Pending</option>
                    <option value="In Progress" <?php echo ($filter_status == 'In Progress') ? 'selected' : ''; ?>>🔄 In Progress</option>
                    <option value="Completed" <?php echo ($filter_status == 'Completed') ? 'selected' : ''; ?>>✅ Completed</option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="subject" id="filterSubject">
                    <option value="0">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" 
                                <?php echo ($filter_subject == $subject['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($subject['subject_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="<?php echo BASE_URL; ?>pages/tasks.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Clear
            </a>
        </div>
    </form>
</div>

<!-- =============================================
ADD TASK FORM (Collapsible)
============================================= -->
<div class="card card-glass fade-in" id="addTaskForm">
    <div class="card-header">
        <h3><i class="fas fa-plus-circle"></i> Add New Task</h3>
        <span class="card-badge">Required fields *</span>
    </div>
    <div class="card-body">
        <form method="POST" class="task-form" id="taskForm">
            <div class="form-grid">
                <div class="form-group">
                    <label for="task_name">Task Name <span class="required">*</span></label>
                    <input type="text" id="task_name" name="task_name" placeholder="e.g., Complete assignment" required>
                </div>
                <div class="form-group">
                    <label for="subject_id">Subject <span class="required">*</span></label>
                    <select id="subject_id" name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>">
                                <?php echo sanitize($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (count($subjects) == 0): ?>
                        <small class="form-hint error">
                            ⚠️ No subjects found. <a href="<?php echo BASE_URL; ?>pages/subjects.php">Create one first</a>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="due_date">Due Date <span class="required">*</span></label>
                    <input type="date" id="due_date" name="due_date" required>
                </div>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="Low">🟢 Low</option>
                        <option value="Medium" selected>🟡 Medium</option>
                        <option value="High">🔴 High</option>
                        <option value="Urgent">🔥 Urgent</option>
                    </select>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date">
                </div>
                <div class="form-group">
                    <label for="estimated_hours">Estimated Hours</label>
                    <input type="number" id="estimated_hours" name="estimated_hours" 
                           step="0.5" min="0" placeholder="e.g., 2.5">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <input type="text" id="description" name="description" placeholder="Task description (optional)">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="tags">Tags</label>
                    <input type="text" id="tags" name="tags" placeholder="e.g., assignment, exam, project (comma separated)">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="add_task" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Task
                </button>
                <button type="reset" class="btn btn-secondary">Clear</button>
            </div>
        </form>
    </div>
</div>

<!-- =============================================
TASKS LIST
============================================= -->
<?php if (count($tasks) > 0): ?>
    <div class="tasks-list fade-in">
        <?php foreach ($tasks as $task): 
            $is_overdue = $task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] != 'Completed';
            $priority_info = $priorities[$task['priority'] ?? 'Medium'] ?? $priorities['Medium'];
            $status_color = $task['status'] == 'Completed' ? '#2ecc71' : ($is_overdue ? '#e74c3c' : '#f39c12');
        ?>
        <div class="task-card slide-up" 
             style="border-left-color: <?php echo $status_color; ?>;">
            <div class="task-card-header">
                <div class="task-card-left">
                    <div class="task-card-check">
                        <?php if ($task['status'] == 'Completed'): ?>
                            <span class="task-checked">✅</span>
                        <?php else: ?>
                            <a href="?complete=<?php echo $task['id']; ?>" class="task-check-btn" title="Mark as complete">
                                <i class="far fa-circle"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="task-card-info">
                        <div class="task-card-title">
                            <?php echo sanitize($task['task_name']); ?>
                            <?php if ($is_overdue): ?>
                                <span class="task-badge overdue">🔴 Overdue</span>
                            <?php endif; ?>
                            <span class="task-priority" style="color: <?php echo $priority_info['color']; ?>;">
                                <?php echo $priority_info['icon'] . ' ' . $priority_info['label']; ?>
                            </span>
                        </div>
                        <div class="task-card-meta">
                            <span class="task-subject">
                                <span class="subject-dot" style="background: <?php echo $task['subject_color'] ?? '#667eea'; ?>;"></span>
                                <?php echo sanitize($task['subject_name']); ?>
                            </span>
                            <?php if ($task['due_date']): ?>
                                <span class="task-due <?php echo $is_overdue ? 'overdue' : ''; ?>">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                    <?php if (!$is_overdue && $task['status'] != 'Completed'): ?>
                                        <?php 
                                        $days = ceil((strtotime($task['due_date']) - time()) / 86400);
                                        if ($days <= 3 && $days > 0):
                                        ?>
                                            <span class="task-days-urgent">(<?php echo $days; ?> days left)</span>
                                        <?php elseif ($days > 0): ?>
                                            <span class="task-days">(<?php echo $days; ?> days left)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($task['estimated_hours'] > 0): ?>
                                <span class="task-hours">
                                    <i class="fas fa-clock"></i> <?php echo $task['estimated_hours']; ?>h
                                </span>
                            <?php endif; ?>
                            <?php if ($task['tags']): ?>
                                <span class="task-tags">
                                    <?php 
                                    $tag_list = explode(',', $task['tags']);
                                    foreach ($tag_list as $tag):
                                        $tag = trim($tag);
                                        if ($tag):
                                    ?>
                                        <span class="tag">#<?php echo sanitize($tag); ?></span>
                                    <?php endif; endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($task['description']): ?>
                            <p class="task-card-description"><?php echo sanitize($task['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="task-card-right">
                    <span class="task-status-badge" style="background: <?php echo $status_color; ?>;">
                        <?php echo $task['status']; ?>
                    </span>
                    <div class="task-actions">
                        <a href="<?php echo BASE_URL; ?>pages/edit_task.php?id=<?php echo $task['id']; ?>" 
                           class="btn btn-sm btn-edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($task['status'] != 'Completed'): ?>
                            <a href="?complete=<?php echo $task['id']; ?>" 
                               class="btn btn-sm btn-complete" title="Complete">
                                <i class="fas fa-check"></i>
                            </a>
                        <?php endif; ?>
                        <a href="?delete_task=<?php echo $task['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           title="Delete"
                           onclick="return confirm('⚠️ Delete this task? This cannot be undone!');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- Empty State -->
    <div class="empty-state fade-in">
        <div class="empty-state-icon">📝</div>
        <h3>No Tasks Found</h3>
        <?php if ($filter_status || $filter_subject || $search_query): ?>
            <p>No tasks match your filters. Try clearing the filters.</p>
            <a href="<?php echo BASE_URL; ?>pages/tasks.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear Filters
            </a>
        <?php else: ?>
            <p>Start by adding your first task. Stay organized and productive!</p>
            <button class="btn btn-primary" onclick="document.getElementById('addTaskForm').scrollIntoView({behavior:'smooth'});">
                <i class="fas fa-plus"></i> Add Your First Task
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- =============================================
JAVASCRIPT
============================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit filter on change
    const filterStatus = document.getElementById('filterStatus');
    const filterSubject = document.getElementById('filterSubject');
    const filterForm = document.getElementById('filterForm');
    
    if (filterStatus) {
        filterStatus.addEventListener('change', function() {
            filterForm.submit();
        });
    }
    
    if (filterSubject) {
        filterSubject.addEventListener('change', function() {
            filterForm.submit();
        });
    }
    
    // Search with debounce
    const searchInput = document.querySelector('.filter-search input');
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                filterForm.submit();
            }, 500);
        });
    }
    
    // Set default date to today
    const dueDate = document.getElementById('due_date');
    if (dueDate) {
        const today = new Date().toISOString().split('T')[0];
        dueDate.setAttribute('min', today);
    }
    
    const startDate = document.getElementById('start_date');
    if (startDate) {
        const today = new Date().toISOString().split('T')[0];
        startDate.setAttribute('max', today);
    }
    
    // Form validation
    const form = document.getElementById('taskForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('task_name').value.trim();
            const subject = document.getElementById('subject_id').value;
            const dueDate = document.getElementById('due_date').value;
            
            if (!name) {
                e.preventDefault();
                alert('⚠️ Task name is required!');
                document.getElementById('task_name').focus();
                return false;
            }
            if (!subject) {
                e.preventDefault();
                alert('⚠️ Please select a subject!');
                document.getElementById('subject_id').focus();
                return false;
            }
            if (!dueDate) {
                e.preventDefault();
                alert('⚠️ Due date is required!');
                document.getElementById('due_date').focus();
                return false;
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>