<?php
// =============================================
// ✏️ EDIT SUBJECT - Advanced Version
// =============================================
require_once '../config/config.php';
require_once '../config/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$id = (int)$_GET['id'];

// =============================================
// FETCH SUBJECT
// =============================================
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
$stmt->execute([$id, $user_id]);
$subject = $stmt->fetch();

if (!$subject) {
    setFlash('subject', 'Subject not found!', 'error');
    redirect('pages/subjects.php');
}

// =============================================
// PRE-DEFINED COLORS AND ICONS
// =============================================
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

// =============================================
// HANDLE UPDATE
// =============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['subject_name']);
    $desc = sanitize($_POST['description']);
    $color = sanitize($_POST['color']);
    $icon = sanitize($_POST['icon']);
    $semester = sanitize($_POST['semester']);
    $credits = (int)$_POST['credits'];
    
    // Validation
    $errors = [];
    if (empty($name)) {
        $errors[] = 'Subject name is required!';
    }
    if (strlen($name) > 100) {
        $errors[] = 'Subject name cannot exceed 100 characters!';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE subjects 
            SET subject_name = ?, description = ?, color = ?, icon = ?, semester = ?, credits = ? 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$name, $desc, $color, $icon, $semester, $credits, $id, $user_id]);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details) 
            VALUES (?, 'subject_updated', ?)
        ");
        $stmt->execute([
            $user_id,
            json_encode(['subject_id' => $id, 'subject_name' => $name])
        ]);
        
        setFlash('subject', '✅ Subject updated successfully!', 'success');
        redirect('pages/subjects.php');
    } else {
        $error_message = implode('<br>', $errors);
        setFlash('subject', $error_message, 'error');
    }
}

// =============================================
// GET SUBJECT STATS (Tasks count)
// =============================================
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks
    FROM tasks 
    WHERE subject_id = ? AND user_id = ? AND deleted_at IS NULL
");
$stmt->execute([$id, $user_id]);
$stats = $stmt->fetch();
$total_tasks = $stats['total_tasks'] ?? 0;
$completed_tasks = $stats['completed_tasks'] ?? 0;
$completion = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
?>
<?php include '../includes/header.php'; ?>

<!-- =============================================
PAGE HEADER
============================================= -->
<div class="page-header fade-in">
    <div class="page-header-left">
        <h1>
            <i class="fas fa-edit"></i> 
            Edit Subject
            <span class="page-badge"><?php echo sanitize($subject['subject_name']); ?></span>
        </h1>
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            <a href="<?php echo BASE_URL; ?>pages/subjects.php">Subjects</a>
            <span class="breadcrumb-sep">›</span>
            <span>Edit Subject</span>
        </div>
    </div>
    <div class="page-header-right">
        <a href="<?php echo BASE_URL; ?>pages/subjects.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Subjects
        </a>
    </div>
</div>

<!-- =============================================
EDIT FORM
============================================= -->
<div class="card card-glass fade-in">
    <div class="card-body">
        <?php displayFlash('subject'); ?>
        
        <!-- Subject Stats -->
        <?php if ($total_tasks > 0): ?>
        <div class="subject-stats">
            <div class="subject-stat-item">
                <span class="stat-label">Total Tasks</span>
                <span class="stat-value"><?php echo $total_tasks; ?></span>
            </div>
            <div class="subject-stat-item">
                <span class="stat-label">Completed</span>
                <span class="stat-value"><?php echo $completed_tasks; ?></span>
            </div>
            <div class="subject-stat-item">
                <span class="stat-label">Progress</span>
                <span class="stat-value"><?php echo $completion; ?>%</span>
            </div>
        </div>
        <div class="subject-stats-progress">
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $completion; ?>%; background: <?php echo $subject['color']; ?>;">
                    <?php echo $completion; ?>%
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="edit-form" id="editSubjectForm">
            <div class="form-grid">
                <!-- Subject Name -->
                <div class="form-group">
                    <label for="subject_name">
                        <i class="fas fa-tag"></i> Subject Name <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="subject_name" 
                           name="subject_name" 
                           value="<?php echo sanitize($subject['subject_name']); ?>" 
                           placeholder="Enter subject name (e.g., Mathematics)" 
                           required 
                           maxlength="100">
                    <small class="form-hint">Maximum 100 characters</small>
                </div>
                
                <!-- Semester -->
                <div class="form-group">
                    <label for="semester">
                        <i class="fas fa-calendar-alt"></i> Semester
                    </label>
                    <select id="semester" name="semester">
                        <option value="">Select Semester</option>
                        <option value="Semester 1" <?php echo ($subject['semester'] == 'Semester 1') ? 'selected' : ''; ?>>Semester 1</option>
                        <option value="Semester 2" <?php echo ($subject['semester'] == 'Semester 2') ? 'selected' : ''; ?>>Semester 2</option>
                        <option value="Semester 3" <?php echo ($subject['semester'] == 'Semester 3') ? 'selected' : ''; ?>>Semester 3</option>
                        <option value="Semester 4" <?php echo ($subject['semester'] == 'Semester 4') ? 'selected' : ''; ?>>Semester 4</option>
                        <option value="Year 1" <?php echo ($subject['semester'] == 'Year 1') ? 'selected' : ''; ?>>Year 1</option>
                        <option value="Year 2" <?php echo ($subject['semester'] == 'Year 2') ? 'selected' : ''; ?>>Year 2</option>
                        <option value="Year 3" <?php echo ($subject['semester'] == 'Year 3') ? 'selected' : ''; ?>>Year 3</option>
                    </select>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Color Picker -->
                <div class="form-group">
                    <label for="color">
                        <i class="fas fa-palette"></i> Subject Color
                    </label>
                    <div class="color-picker-wrapper">
                        <?php foreach ($colors as $hex => $name): ?>
                        <label class="color-option <?php echo ($subject['color'] == $hex) ? 'active' : ''; ?>">
                            <input type="radio" name="color" value="<?php echo $hex; ?>" 
                                   <?php echo ($subject['color'] == $hex) ? 'checked' : ''; ?>>
                            <span class="color-swatch" style="background: <?php echo $hex; ?>;"></span>
                            <span class="color-name"><?php echo $name; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Icon Picker -->
                <div class="form-group">
                    <label for="icon">
                        <i class="fas fa-icons"></i> Subject Icon
                    </label>
                    <div class="icon-picker-wrapper">
                        <div class="icon-picker-grid">
                            <?php foreach ($icons as $emoji => $name): ?>
                            <label class="icon-option <?php echo ($subject['icon'] == $emoji) ? 'active' : ''; ?>">
                                <input type="radio" name="icon" value="<?php echo $emoji; ?>" 
                                       <?php echo ($subject['icon'] == $emoji) ? 'checked' : ''; ?>>
                                <span class="icon-emoji"><?php echo $emoji; ?></span>
                                <span class="icon-name"><?php echo $name; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Description -->
                <div class="form-group full-width">
                    <label for="description">
                        <i class="fas fa-align-left"></i> Description
                    </label>
                    <textarea id="description" 
                              name="description" 
                              placeholder="Enter subject description (optional)"
                              rows="4"><?php echo sanitize($subject['description']); ?></textarea>
                    <small class="form-hint">Brief description about this subject</small>
                </div>
            </div>
            
            <div class="form-grid">
                <!-- Credits -->
                <div class="form-group">
                    <label for="credits">
                        <i class="fas fa-star"></i> Credits
                    </label>
                    <select id="credits" name="credits">
                        <?php for ($i = 0; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($subject['credits'] == $i) ? 'selected' : ''; ?>>
                            <?php echo $i . ' ' . ($i == 1 ? 'Credit' : 'Credits'); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <!-- Status / Delete -->
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Update Subject
                        </button>
                        <a href="subjects.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Danger Zone -->
        <div class="danger-zone">
            <div class="danger-zone-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Danger Zone</h3>
            </div>
            <div class="danger-zone-content">
                <p>Deleting this subject will also delete all associated tasks. This action cannot be undone.</p>
                <a href="<?php echo BASE_URL; ?>pages/subjects.php?delete=<?php echo $id; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('⚠️ Are you sure you want to delete this subject and all its tasks? This cannot be undone!');">
                    <i class="fas fa-trash"></i> Delete Subject
                </a>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
JAVASCRIPT - Form Validation & Live Preview
============================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live preview of subject name
    const nameInput = document.getElementById('subject_name');
    const previewBadge = document.querySelector('.page-badge');
    
    if (nameInput && previewBadge) {
        nameInput.addEventListener('input', function() {
            previewBadge.textContent = this.value || 'Untitled';
        });
    }
    
    // Color picker toggle
    document.querySelectorAll('.color-option input[type="radio"]').forEach(function(input) {
        input.addEventListener('change', function() {
            document.querySelectorAll('.color-option').forEach(function(opt) {
                opt.classList.remove('active');
            });
            this.closest('.color-option').classList.add('active');
        });
    });
    
    // Icon picker toggle
    document.querySelectorAll('.icon-option input[type="radio"]').forEach(function(input) {
        input.addEventListener('change', function() {
            document.querySelectorAll('.icon-option').forEach(function(opt) {
                opt.classList.remove('active');
            });
            this.closest('.icon-option').classList.add('active');
        });
    });
    
    // Form validation
    const form = document.getElementById('editSubjectForm');
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