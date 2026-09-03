<?php
// =============================================
// 📚 SUBJECTS - Advanced Version
// =============================================
require_once '../config/config.php';
require_once '../config/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// =============================================
// HANDLE ADD SUBJECT
// =============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $name = sanitize($_POST['subject_name']);
    $desc = sanitize($_POST['description']);
    $color = sanitize($_POST['color'] ?? '#667eea');
    $icon = sanitize($_POST['icon'] ?? '📚');
    $semester = sanitize($_POST['semester'] ?? '');
    $credits = (int)($_POST['credits'] ?? 0);
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("
            INSERT INTO subjects (user_id, subject_name, description, color, icon, semester, credits) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $name, $desc, $color, $icon, $semester, $credits]);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details) 
            VALUES (?, 'subject_created', ?)
        ");
        $stmt->execute([
            $user_id,
            json_encode(['subject_name' => $name])
        ]);
        
        setFlash('subject', '✅ Subject added successfully!', 'success');
    } else {
        setFlash('subject', '❌ Subject name is required!', 'error');
    }
    redirect('pages/subjects.php');
}

// =============================================
// HANDLE DELETE (Soft Delete)
// =============================================
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("UPDATE subjects SET deleted_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    setFlash('subject', '🗑️ Subject moved to trash!', 'success');
    redirect('pages/subjects.php');
}

// =============================================
// FETCH SUBJECTS WITH STATS
// =============================================
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        COUNT(t.id) as total_tasks,
        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
    FROM subjects s
    LEFT JOIN tasks t ON s.id = t.subject_id AND t.deleted_at IS NULL
    WHERE s.user_id = ? AND s.deleted_at IS NULL
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll();

// Predefined colors and icons
$colors = [
    '#667eea' => 'Blue',
    '#764ba2' => 'Purple',
    '#2ecc71' => 'Green',
    '#f39c12' => 'Orange',
    '#e74c3c' => 'Red',
    '#3498db' => 'Sky Blue',
    '#1abc9c' => 'Teal',
    '#9b59b6' => 'Violet',
    '#e67e22' => 'Dark Orange',
    '#2c3e50' => 'Dark Blue'
];

$icons = [
    '📚' => 'Book',
    '📖' => 'Open Book',
    '📐' => 'Geometry',
    '🔬' => 'Science',
    '🧮' => 'Math',
    '💻' => 'Computer',
    '🎨' => 'Art',
    '🎵' => 'Music',
    '🏛️' => 'History',
    '🌍' => 'Geography',
    '🧪' => 'Chemistry',
    '⚛️' => 'Physics',
    '📝' => 'Writing',
    '🎭' => 'Drama',
    '🏃' => 'Sports'
];

$semesters = ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Year 1', 'Year 2', 'Year 3'];
?>
<?php include '../includes/header.php'; ?>

<!-- =============================================
PAGE HEADER
============================================= -->
<div class="page-header fade-in">
    <div class="page-header-left">
        <h1>
            <i class="fas fa-book"></i> 
            My Subjects
            <span class="page-badge"><?php echo count($subjects); ?></span>
        </h1>
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            <span>Subjects</span>
        </div>
    </div>
    <div class="page-header-right">
        <button class="btn btn-primary" onclick="document.getElementById('addSubjectForm').scrollIntoView({behavior:'smooth'});">
            <i class="fas fa-plus"></i> Add Subject
        </button>
    </div>
</div>

<!-- =============================================
FLASH MESSAGES
============================================= -->
<?php displayFlash('subject'); ?>

<!-- =============================================
ADD SUBJECT FORM (Collapsible)
============================================= -->
<div class="card card-glass fade-in" id="addSubjectForm">
    <div class="card-header">
        <h3><i class="fas fa-plus-circle"></i> Add New Subject</h3>
        <span class="card-badge">Required fields *</span>
    </div>
    <div class="card-body">
        <form method="POST" class="subject-form" id="subjectForm">
            <div class="form-grid">
                <!-- Subject Name -->
                <div class="form-group">
                    <label for="subject_name">Subject Name <span class="required">*</span></label>
                    <input type="text" 
                           id="subject_name" 
                           name="subject_name" 
                           placeholder="e.g., Mathematics" 
                           required>
                </div>
                
                <!-- Semester -->
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester">
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem; ?>"><?php echo $sem; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Color Picker -->
                <div class="form-group">
                    <label>Color</label>
                    <div class="color-picker-mini">
                        <?php foreach ($colors as $hex => $name): ?>
                        <label class="color-option-mini">
                            <input type="radio" name="color" value="<?php echo $hex; ?>" 
                                   <?php echo ($hex == '#667eea') ? 'checked' : ''; ?>>
                            <span class="color-swatch-mini" style="background: <?php echo $hex; ?>;" 
                                  title="<?php echo $name; ?>"></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Icon Picker -->
                <div class="form-group">
                    <label>Icon</label>
                    <div class="icon-picker-mini">
                        <?php 
                        $icon_keys = array_keys($icons);
                        $first_icons = array_slice($icon_keys, 0, 8);
                        foreach ($first_icons as $emoji): ?>
                        <label class="icon-option-mini">
                            <input type="radio" name="icon" value="<?php echo $emoji; ?>" 
                                   <?php echo ($emoji == '📚') ? 'checked' : ''; ?>>
                            <span class="icon-emoji-mini"><?php echo $emoji; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Description -->
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <input type="text" 
                           id="description" 
                           name="description" 
                           placeholder="Brief description (optional)">
                </div>
                
                <!-- Credits -->
                <div class="form-group" style="max-width:200px;">
                    <label for="credits">Credits</label>
                    <select id="credits" name="credits">
                        <?php for ($i = 0; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="add_subject" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Subject
                </button>
                <button type="reset" class="btn btn-secondary">Clear</button>
            </div>
        </form>
    </div>
</div>

<!-- =============================================
SUBJECTS GRID
============================================= -->
<?php if (count($subjects) > 0): ?>
    <div class="subjects-grid fade-in">
        <?php foreach ($subjects as $subject): 
            $total_tasks = $subject['total_tasks'] ?? 0;
            $completed_tasks = $subject['completed_tasks'] ?? 0;
            $completion = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
            $subject_color = $subject['color'] ?? '#667eea';
        ?>
        <div class="subject-card slide-up" style="border-left-color: <?php echo $subject_color; ?>;">
            <div class="subject-card-header">
                <div class="subject-card-icon" style="background: <?php echo $subject_color . '20'; ?>;">
                    <span style="font-size: 28px;"><?php echo $subject['icon'] ?? '📚'; ?></span>
                </div>
                <div class="subject-card-info">
                    <h3 class="subject-card-title"><?php echo sanitize($subject['subject_name']); ?></h3>
                    <?php if ($subject['semester']): ?>
                        <span class="subject-card-semester">📅 <?php echo sanitize($subject['semester']); ?></span>
                    <?php endif; ?>
                    <?php if ($subject['credits'] > 0): ?>
                        <span class="subject-card-credits">⭐ <?php echo $subject['credits']; ?> Credits</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($subject['description']): ?>
                <p class="subject-card-description"><?php echo sanitize($subject['description']); ?></p>
            <?php endif; ?>
            
            <div class="subject-card-stats">
                <div class="stat-item">
                    <span class="stat-label">Total Tasks</span>
                    <span class="stat-value"><?php echo $total_tasks; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Completed</span>
                    <span class="stat-value"><?php echo $completed_tasks; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Progress</span>
                    <span class="stat-value"><?php echo $completion; ?>%</span>
                </div>
            </div>
            
            <div class="subject-card-progress">
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" 
                         style="width: <?php echo $completion; ?>%; background: <?php echo $subject_color; ?>;">
                    </div>
                </div>
            </div>
            
            <div class="subject-card-actions">
                <a href="<?php echo BASE_URL; ?>pages/edit_subject.php?id=<?php echo $subject['id']; ?>" 
                   class="btn btn-sm btn-edit">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="<?php echo BASE_URL; ?>pages/tasks.php?subject=<?php echo $subject['id']; ?>" 
                   class="btn btn-sm btn-primary">
                    <i class="fas fa-tasks"></i> Tasks
                </a>
                <a href="?delete=<?php echo $subject['id']; ?>" 
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('⚠️ Delete this subject and all its tasks? This cannot be undone!');">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- Empty State -->
    <div class="empty-state fade-in">
        <div class="empty-state-icon">📚</div>
        <h3>No Subjects Yet</h3>
        <p>Start by adding your first subject. Organize your studies effectively!</p>
        <button class="btn btn-primary" onclick="document.getElementById('addSubjectForm').scrollIntoView({behavior:'smooth'});">
            <i class="fas fa-plus"></i> Add Your First Subject
        </button>
    </div>
<?php endif; ?>

<!-- =============================================
JAVASCRIPT
============================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Color picker toggle
    document.querySelectorAll('.color-option-mini input[type="radio"]').forEach(function(input) {
        input.addEventListener('change', function() {
            document.querySelectorAll('.color-option-mini').forEach(function(opt) {
                opt.classList.remove('active');
            });
            this.closest('.color-option-mini').classList.add('active');
        });
    });
    
    // Icon picker toggle
    document.querySelectorAll('.icon-option-mini input[type="radio"]').forEach(function(input) {
        input.addEventListener('change', function() {
            document.querySelectorAll('.icon-option-mini').forEach(function(opt) {
                opt.classList.remove('active');
            });
            this.closest('.icon-option-mini').classList.add('active');
        });
    });
    
    // Set default active states
    document.querySelector('.color-option-mini input:checked')?.closest('.color-option-mini')?.classList.add('active');
    document.querySelector('.icon-option-mini input:checked')?.closest('.icon-option-mini')?.classList.add('active');
    
    // Form validation
    const form = document.getElementById('subjectForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('subject_name').value.trim();
            if (!name) {
                e.preventDefault();
                alert('⚠️ Subject name is required!');
                document.getElementById('subject_name').focus();
                return false;
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>