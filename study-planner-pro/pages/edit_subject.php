<?php
// =============================================
// ✏️ EDIT SUBJECT - Advanced Version
// =============================================
require_once '../config/config.php';
require_once '../config/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$id = (int)$_GET['id'];

// Fetch subject
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$subject = $stmt->fetch();

if (!$subject) {
    setFlash('subject', 'Subject not found!', 'error');
    redirect('pages/subjects.php');
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['subject_name']);
    $desc = sanitize($_POST['description']);
    $color = sanitize($_POST['color']);
    $icon = sanitize($_POST['icon']);
    $credits = (int)$_POST['credits'];
    $semester = sanitize($_POST['semester']);
    
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Subject name is required!';
    }
    
    if (strlen($name) > 100) {
        $errors[] = 'Subject name must be less than 100 characters!';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE subjects 
                SET subject_name = ?, 
                    description = ?, 
                    color = ?, 
                    icon = ?, 
                    credits = ?, 
                    semester = ? 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$name, $desc, $color, $icon, $credits, $semester, $id, $user_id]);
            
            setFlash('subject', 'Subject updated successfully! 🎉', 'success');
            redirect('pages/subjects.php');
        } catch (PDOException $e) {
            setFlash('subject', 'Error updating subject: ' . $e->getMessage(), 'error');
        }
    } else {
        setFlash('subject', implode('<br>', $errors), 'error');
    }
}

// =============================================
// PRE-DEFINED ICONS
// =============================================
$subject_icons = [
    '📚' => 'Books',
    '📖' => 'Book',
    '✏️' => 'Pencil',
    '🔬' => 'Science',
    '🧮' => 'Math',
    '💻' => 'Computer',
    '🎨' => 'Art',
    '🎵' => 'Music',
    '🏛️' => 'History',
    '🌍' => 'Geography',
    '🧪' => 'Chemistry',
    '⚛️' => 'Physics',
    '📊' => 'Statistics',
    '📝' => 'Writing',
    '🗣️' => 'Language',
    '🧠' => 'Psychology',
    '💡' => 'Philosophy',
    '🏥' => 'Medicine',
    '⚖️' => 'Law',
    '🎭' => 'Drama',
    '🏋️' => 'PE',
    '🍳' => 'Cooking',
    '🎮' => 'Gaming',
    '📱' => 'Mobile',
    '🖥️' => 'Desktop'
];

// =============================================
// PRE-DEFINED COLORS
// =============================================
$subject_colors = [
    '#667eea' => 'Purple Blue',
    '#764ba2' => 'Purple',
    '#3498db' => 'Blue',
    '#2ecc71' => 'Green',
    '#f39c12' => 'Orange',
    '#e74c3c' => 'Red',
    '#1abc9c' => 'Teal',
    '#9b59b6' => 'Violet',
    '#e67e22' => 'Dark Orange',
    '#2c3e50' => 'Dark Blue',
    '#16a085' => 'Dark Teal',
    '#27ae60' => 'Dark Green',
    '#2980b9' => 'Dark Blue',
    '#8e44ad' => 'Dark Violet',
    '#d35400' => 'Burnt Orange',
    '#c0392b' => 'Dark Red',
    '#f1c40f' => 'Yellow',
    '#ecf0f1' => 'White',
    '#bdc3c7' => 'Light Gray',
    '#95a5a6' => 'Gray'
];

// =============================================
// PAGE TITLE
// =============================================
$page_title = 'Edit Subject: ' . sanitize($subject['subject_name']);
?>
<?php 
// Set page title for header
$page_title = $page_title;
include '../includes/header.php'; 
?>

<!-- =============================================
PAGE HEADER
============================================= -->
<div class="page-header fade-in">
    <div class="page-header-left">
        <a href="subjects.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Subjects
        </a>
        <h2>
            <span class="page-icon">✏️</span>
            Edit Subject
        </h2>
        <p class="page-subtitle">Update subject details and settings</p>
    </div>
    <div class="page-header-right">
        <div class="subject-preview-card" style="background: <?php echo $subject['color'] ?? '#667eea'; ?>;">
            <span class="subject-preview-icon"><?php echo $subject['icon'] ?? '📚'; ?></span>
            <span class="subject-preview-name" id="previewName">
                <?php echo sanitize($subject['subject_name']); ?>
            </span>
        </div>
    </div>
</div>

<!-- =============================================
FLASH MESSAGES
============================================= -->
<?php displayFlash('subject'); ?>

<!-- =============================================
EDIT FORM
============================================= -->
<div class="edit-form-container fade-in">
    <div class="card card-glass">
        <div class="card-body">
            <form method="POST" id="editSubjectForm" class="advanced-form">
                
                <!-- =============================================
                BASIC INFO SECTION
                ============================================= -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-info-circle"></i>
                        Basic Information
                    </h3>
                    
                    <div class="form-group">
                        <label for="subject_name" class="form-label required">
                            Subject Name
                        </label>
                        <input 
                            type="text" 
                            id="subject_name" 
                            name="subject_name" 
                            value="<?php echo sanitize($subject['subject_name']); ?>" 
                            class="form-control" 
                            required
                            placeholder="Enter subject name..."
                            maxlength="100"
                        >
                        <div class="form-helper">
                            <span id="charCount">0</span>/100 characters
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">
                            Description
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            class="form-control" 
                            rows="3"
                            placeholder="Enter subject description (optional)..."
                        ><?php echo sanitize($subject['description']); ?></textarea>
                    </div>
                </div>
                
                <!-- =============================================
                APPEARANCE SECTION
                ============================================= -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-palette"></i>
                        Appearance
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="icon" class="form-label">
                                Icon
                            </label>
                            <div class="icon-selector">
                                <button type="button" class="icon-selector-toggle" id="iconToggle">
                                    <span id="selectedIcon"><?php echo $subject['icon'] ?? '📚'; ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="icon-selector-dropdown" id="iconDropdown">
                                    <div class="icon-selector-search">
                                        <input type="text" placeholder="Search icons..." id="iconSearch">
                                    </div>
                                    <div class="icon-selector-grid" id="iconGrid">
                                        <?php foreach ($subject_icons as $icon => $label): ?>
                                        <div class="icon-option <?php echo ($icon == $subject['icon']) ? 'selected' : ''; ?>" 
                                             data-icon="<?php echo $icon; ?>"
                                             title="<?php echo $label; ?>">
                                            <span class="icon-emoji"><?php echo $icon; ?></span>
                                            <span class="icon-label"><?php echo $label; ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <input type="hidden" name="icon" id="iconInput" value="<?php echo $subject['icon'] ?? '📚'; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="color" class="form-label">
                                Color
                            </label>
                            <div class="color-selector">
                                <div class="color-picker-wrapper">
                                    <input type="color" id="colorPicker" name="color" 
                                           value="<?php echo $subject['color'] ?? '#667eea'; ?>">
                                </div>
                                <div class="color-presets" id="colorPresets">
                                    <?php foreach ($subject_colors as $color => $name): ?>
                                    <button type="button" 
                                            class="color-preset <?php echo ($color == $subject['color']) ? 'active' : ''; ?>"
                                            data-color="<?php echo $color; ?>"
                                            style="background: <?php echo $color; ?>;"
                                            title="<?php echo $name; ?>">
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- =============================================
                ACADEMIC DETAILS SECTION
                ============================================= -->
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-graduation-cap"></i>
                        Academic Details
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="credits" class="form-label">
                                Credits
                            </label>
                            <input 
                                type="number" 
                                id="credits" 
                                name="credits" 
                                value="<?php echo $subject['credits'] ?? 0; ?>" 
                                class="form-control"
                                min="0"
                                max="10"
                                placeholder="e.g., 3"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="semester" class="form-label">
                                Semester
                            </label>
                            <select id="semester" name="semester" class="form-control">
                                <option value="">Select Semester</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="Semester <?php echo $i; ?>" 
                                    <?php echo ($subject['semester'] == 'Semester ' . $i) ? 'selected' : ''; ?>>
                                    Semester <?php echo $i; ?>
                                </option>
                                <?php endfor; ?>
                                <option value="Year 1" <?php echo ($subject['semester'] == 'Year 1') ? 'selected' : ''; ?>>Year 1</option>
                                <option value="Year 2" <?php echo ($subject['semester'] == 'Year 2') ? 'selected' : ''; ?>>Year 2</option>
                                <option value="Year 3" <?php echo ($subject['semester'] == 'Year 3') ? 'selected' : ''; ?>>Year 3</option>
                                <option value="Year 4" <?php echo ($subject['semester'] == 'Year 4') ? 'selected' : ''; ?>>Year 4</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- =============================================
                FORM ACTIONS
                ============================================= -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Update Subject
                    </button>
                    <a href="subjects.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="button" class="btn btn-danger" id="deleteSubjectBtn">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                
            </form>
        </div>
    </div>
</div>

<!-- =============================================
DELETE CONFIRMATION MODAL
============================================= -->
<div class="modal" id="deleteModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>⚠️ Delete Subject</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete <strong>"<?php echo sanitize($subject['subject_name']); ?>"</strong>?</p>
            <p class="modal-warning">This will also delete all tasks associated with this subject. This action cannot be undone!</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <a href="subjects.php?delete=<?php echo $id; ?>" class="btn btn-danger">Yes, Delete</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- =============================================
JAVASCRIPT FOR THIS PAGE
============================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =============================================
    // CHARACTER COUNTER
    // =============================================
    const nameInput = document.getElementById('subject_name');
    const charCount = document.getElementById('charCount');
    
    function updateCharCount() {
        charCount.textContent = nameInput.value.length;
    }
    updateCharCount();
    nameInput.addEventListener('input', updateCharCount);
    
    // =============================================
    // LIVE PREVIEW
    // =============================================
    const previewName = document.getElementById('previewName');
    const previewCard = document.querySelector('.subject-preview-card');
    const colorInput = document.getElementById('colorPicker');
    const iconInput = document.getElementById('iconInput');
    
    nameInput.addEventListener('input', function() {
        previewName.textContent = this.value || 'Subject Name';
    });
    
    colorInput.addEventListener('input', function() {
        previewCard.style.background = this.value;
    });
    
    // =============================================
    // ICON SELECTOR
    // =============================================
    const iconToggle = document.getElementById('iconToggle');
    const iconDropdown = document.getElementById('iconDropdown');
    const iconGrid = document.getElementById('iconGrid');
    const iconSearch = document.getElementById('iconSearch');
    const selectedIcon = document.getElementById('selectedIcon');
    
    iconToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        iconDropdown.classList.toggle('show');
    });
    
    document.addEventListener('click', function() {
        iconDropdown.classList.remove('show');
    });
    
    iconGrid.addEventListener('click', function(e) {
        const option = e.target.closest('.icon-option');
        if (option) {
            const icon = option.dataset.icon;
            selectedIcon.textContent = icon;
            document.getElementById('iconInput').value = icon;
            
            // Update preview
            document.querySelector('.subject-preview-icon').textContent = icon;
            
            // Update selected state
            document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
            option.classList.add('selected');
            iconDropdown.classList.remove('show');
        }
    });
    
    iconSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.icon-option').forEach(el => {
            const label = el.querySelector('.icon-label').textContent.toLowerCase();
            const emoji = el.dataset.icon;
            el.style.display = label.includes(query) || emoji.includes(query) ? '' : 'none';
        });
    });
    
    // =============================================
    // COLOR PRESETS
    // =============================================
    document.querySelectorAll('.color-preset').forEach(preset => {
        preset.addEventListener('click', function() {
            const color = this.dataset.color;
            document.getElementById('colorPicker').value = color;
            document.querySelectorAll('.color-preset').forEach(el => el.classList.remove('active'));
            this.classList.add('active');
            
            // Update preview
            document.querySelector('.subject-preview-card').style.background = color;
        });
    });
    
    // =============================================
    // FORM VALIDATION (Real-time)
    // =============================================
    const form = document.getElementById('editSubjectForm');
    const nameField = document.getElementById('subject_name');
    
    nameField.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            this.classList.add('error');
            showFieldError(this, 'Subject name is required!');
        } else {
            this.classList.remove('error');
            clearFieldError(this);
        }
    });
    
    nameField.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            this.classList.remove('error');
            clearFieldError(this);
        }
    });
    
    function showFieldError(field, message) {
        let error = field.parentElement.querySelector('.field-error');
        if (!error) {
            error = document.createElement('div');
            error.className = 'field-error';
            field.parentElement.appendChild(error);
        }
        error.textContent = message;
    }
    
    function clearFieldError(field) {
        const error = field.parentElement.querySelector('.field-error');
        if (error) error.remove();
    }
    
    // =============================================
    // DELETE MODAL
    // =============================================
    window.openModal = function() {
        document.getElementById('deleteModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    };
    
    window.closeModal = function() {
        document.getElementById('deleteModal').classList.remove('show');
        document.body.style.overflow = '';
    };
    
    document.getElementById('deleteSubjectBtn').addEventListener('click', openModal);
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    // Close modal clicking overlay
    document.querySelector('.modal-overlay').addEventListener('click', closeModal);
    
    // =============================================
    // ANIMATION - Fade In Elements
    // =============================================
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.fade-in, .slide-up').forEach(el => {
        observer.observe(el);
    });
});
</script>

<style>
/* =============================================
PAGE SPECIFIC STYLES
============================================= */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-light);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition);
}

.back-link:hover {
    color: var(--primary);
    transform: translateX(-3px);
}

.page-header h2 {
    font-size: 28px;
    font-weight: 700;
    margin: 10px 0 5px;
}

.page-header .page-icon {
    font-size: 32px;
}

.page-subtitle {
    color: var(--text-light);
    font-size: 14px;
}

.subject-preview-card {
    padding: 12px 24px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
    min-width: 150px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: var(--transition);
}

.subject-preview-icon {
    font-size: 28px;
}

.subject-preview-name {
    font-weight: 600;
    font-size: 16px;
}

/* =============================================
FORM STYLES
============================================= */
.edit-form-container {
    max-width: 800px;
    margin: 0 auto;
}

.advanced-form {
    padding: 10px 0;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid var(--border);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 20px;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section-title i {
    color: var(--primary);
}

.form-group {
    margin-bottom: 20px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
    font-size: 14px;
}

.form-label.required::after {
    content: '*';
    color: #e74c3c;
    margin-left: 4px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--border);
    border-radius: 10px;
    font-size: 15px;
    font-family: inherit;
    transition: var(--transition);
    background: var(--bg);
}

.form-control:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-control.error {
    border-color: #e74c3c;
}

.form-control.error:focus {
    box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
}

.form-control:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.form-helper {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 4px;
}

.field-error {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 4px;
}

/* =============================================
ICON SELECTOR
============================================= */
.icon-selector {
    position: relative;
}

.icon-selector-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    border: 2px solid var(--border);
    border-radius: 10px;
    background: var(--bg);
    cursor: pointer;
    font-size: 24px;
    transition: var(--transition);
    width: 100%;
}

.icon-selector-toggle:hover {
    border-color: var(--primary);
}

.icon-selector-toggle i {
    font-size: 14px;
    color: var(--text-light);
}

.icon-selector-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: var(--shadow-hover);
    display: none;
    z-index: 1000;
    max-height: 300px;
    overflow: hidden;
}

.icon-selector-dropdown.show {
    display: block;
    animation: slideDown 0.2s ease;
}

.icon-selector-search {
    padding: 12px;
    border-bottom: 1px solid var(--border);
}

.icon-selector-search input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
}

.icon-selector-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 6px;
    padding: 12px;
    max-height: 220px;
    overflow-y: auto;
}

.icon-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 4px;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    gap: 2px;
}

.icon-option:hover {
    background: var(--bg);
    transform: scale(1.05);
}

.icon-option.selected {
    background: var(--primary);
    color: white;
}

.icon-option .icon-emoji {
    font-size: 24px;
}

.icon-option .icon-label {
    font-size: 8px;
    text-align: center;
    line-height: 1.2;
}

/* =============================================
COLOR SELECTOR
============================================= */
.color-selector {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.color-picker-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.color-picker-wrapper input[type="color"] {
    width: 50px;
    height: 50px;
    border: 2px solid var(--border);
    border-radius: 10px;
    padding: 2px;
    cursor: pointer;
}

.color-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.color-preset {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 3px solid transparent;
    cursor: pointer;
    transition: var(--transition);
    padding: 0;
}

.color-preset:hover {
    transform: scale(1.15);
}

.color-preset.active {
    border-color: var(--text);
    box-shadow: 0 0 0 2px var(--bg), 0 0 0 4px var(--text);
}

/* =============================================
FORM ACTIONS
============================================= */
.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
    flex-wrap: wrap;
}

/* =============================================
MODAL
============================================= */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal.show {
    display: flex;
    animation: fadeIn 0.2s ease;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    position: relative;
    background: var(--card-bg);
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
    padding: 30px;
    z-index: 1;
    box-shadow: var(--shadow-hover);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h3 {
    font-size: 20px;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: var(--text-light);
}

.modal-body p {
    margin-bottom: 12px;
}

.modal-warning {
    color: #e74c3c;
    font-size: 14px;
    background: #fde8e8;
    padding: 12px;
    border-radius: 8px;
}

.modal-footer {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    justify-content: flex-end;
}

/* =============================================
BUTTONS
============================================= */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    font-size: 14px;
    font-family: inherit;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: var(--bg);
    color: var(--text);
}

.btn-secondary:hover {
    background: var(--border);
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-lg {
    padding: 14px 32px;
    font-size: 16px;
}

.btn-sm {
    padding: 6px 14px;
    font-size: 12px;
}

/* =============================================
ANIMATIONS
============================================= */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fade-in {
    opacity: 0;
    animation: fadeIn 0.5s ease forwards;
}

.slide-up {
    opacity: 0;
    transform: translateY(20px);
    animation: slideUp 0.5s ease forwards;
}

@keyframes slideUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* =============================================
RESPONSIVE
============================================= */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .icon-selector-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 480px) {
    .icon-selector-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>