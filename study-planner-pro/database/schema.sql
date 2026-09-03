-- =============================================
-- 🚀 STUDYHUB PRO - Advanced Database Schema
-- =============================================

-- =============================================
-- 1. CREATE DATABASE
-- =============================================
CREATE DATABASE IF NOT EXISTS study_planner_pro;
USE study_planner_pro;

-- =============================================
-- 2. USERS TABLE (Enhanced)
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    
    -- Profile Fields
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    
    -- Settings
    theme ENUM('light', 'dark', 'auto') DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'en',
    notification_preferences JSON DEFAULT NULL,
    
    -- Account Status
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255) DEFAULT NULL,
    verification_token_expires DATETIME DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL, -- Soft Delete
    
    INDEX idx_email (email),
    INDEX idx_is_active (is_active),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 3. SUBJECTS TABLE (Enhanced)
-- =============================================
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    
    -- Additional Fields
    color VARCHAR(7) DEFAULT '#667eea', -- Subject Color
    icon VARCHAR(50) DEFAULT '📚',
    credits INT DEFAULT 0,
    semester VARCHAR(20) DEFAULT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL, -- Soft Delete
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_subject (user_id),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_semester (semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 4. TASKS TABLE (Enhanced)
-- =============================================
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_id INT NOT NULL,
    
    -- Task Details
    task_name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    
    -- Scheduling
    due_date DATE DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    estimated_hours DECIMAL(5,2) DEFAULT 0,
    actual_hours DECIMAL(5,2) DEFAULT 0,
    
    -- Status & Priority
    status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    
    -- Additional
    tags VARCHAR(255) DEFAULT NULL, -- Comma separated tags
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_pattern VARCHAR(50) DEFAULT NULL, -- daily, weekly, monthly
    reminder_date DATETIME DEFAULT NULL,
    reminder_sent BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL, -- Soft Delete
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    
    INDEX idx_user_tasks (user_id),
    INDEX idx_subject_tasks (subject_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_due_date (due_date),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_reminder_date (reminder_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 5. TASK COMMENTS TABLE (NEW)
-- =============================================
CREATE TABLE task_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_task_comments (task_id),
    INDEX idx_user_comments (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 6. ACTIVITY LOG TABLE (NEW)
-- =============================================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_activity (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 7. NOTIFICATIONS TABLE (NEW)
-- =============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL, -- task_reminder, task_assigned, etc.
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user_notifications (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 8. PASSWORD RESET TOKENS (NEW)
-- =============================================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    
    INDEX idx_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 9. SUBJECT PROGRESS (NEW)
-- =============================================
CREATE TABLE subject_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    user_id INT NOT NULL,
    completion_percentage DECIMAL(5,2) DEFAULT 0,
    total_tasks INT DEFAULT 0,
    completed_tasks INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_subject_progress (subject_id),
    UNIQUE KEY unique_subject_progress (subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 10. TRIGGERS (Automatic Updates)
-- =============================================

-- Trigger: Update subject progress when task status changes
DELIMITER //
CREATE TRIGGER update_subject_progress
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    DECLARE total_tasks INT;
    DECLARE completed_tasks INT;
    DECLARE progress DECIMAL(5,2);
    
    -- Count total tasks for this subject
    SELECT COUNT(*) INTO total_tasks 
    FROM tasks 
    WHERE subject_id = NEW.subject_id 
      AND deleted_at IS NULL;
    
    -- Count completed tasks
    SELECT COUNT(*) INTO completed_tasks 
    FROM tasks 
    WHERE subject_id = NEW.subject_id 
      AND status = 'Completed' 
      AND deleted_at IS NULL;
    
    -- Calculate progress
    IF total_tasks > 0 THEN
        SET progress = (completed_tasks / total_tasks) * 100;
    ELSE
        SET progress = 0;
    END IF;
    
    -- Insert or update progress
    INSERT INTO subject_progress (subject_id, user_id, total_tasks, completed_tasks, completion_percentage)
    VALUES (NEW.subject_id, NEW.user_id, total_tasks, completed_tasks, progress)
    ON DUPLICATE KEY UPDATE
        total_tasks = VALUES(total_tasks),
        completed_tasks = VALUES(completed_tasks),
        completion_percentage = VALUES(completion_percentage),
        last_updated = CURRENT_TIMESTAMP;
END//
DELIMITER ;

-- Trigger: Log activity when task is completed
DELIMITER //
CREATE TRIGGER log_task_completion
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    IF NEW.status = 'Completed' AND OLD.status != 'Completed' THEN
        INSERT INTO activity_logs (user_id, action, details)
        VALUES (
            NEW.user_id,
            'task_completed',
            JSON_OBJECT('task_id', NEW.id, 'task_name', NEW.task_name, 'subject_id', NEW.subject_id)
        );
    END IF;
END//
DELIMITER ;

-- Trigger: Log activity when new task is created
DELIMITER //
CREATE TRIGGER log_task_creation
AFTER INSERT ON tasks
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, details)
    VALUES (
        NEW.user_id,
        'task_created',
        JSON_OBJECT('task_id', NEW.id, 'task_name', NEW.task_name, 'subject_id', NEW.subject_id)
    );
END//
DELIMITER ;

-- =============================================
-- 11. VIEWS (For easier reporting)
-- =============================================

-- View: Task Summary per Subject
CREATE VIEW v_subject_task_summary AS
SELECT 
    s.id AS subject_id,
    s.subject_name,
    s.user_id,
    COUNT(t.id) AS total_tasks,
    SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks,
    SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) AS pending_tasks,
    SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_tasks,
    SUM(CASE WHEN t.status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_tasks,
    ROUND(SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) / NULLIF(COUNT(t.id), 0) * 100, 2) AS completion_rate
FROM subjects s
LEFT JOIN tasks t ON s.id = t.subject_id AND t.deleted_at IS NULL
WHERE s.deleted_at IS NULL
GROUP BY s.id, s.subject_name, s.user_id;

-- View: Upcoming Tasks (Next 7 days)
CREATE VIEW v_upcoming_tasks AS
SELECT 
    t.id,
    t.task_name,
    t.description,
    t.due_date,
    t.status,
    t.priority,
    s.subject_name,
    s.color AS subject_color,
    DATEDIFF(t.due_date, CURDATE()) AS days_remaining
FROM tasks t
JOIN subjects s ON t.subject_id = s.id
WHERE t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
  AND t.status != 'Completed'
  AND t.deleted_at IS NULL
ORDER BY t.due_date ASC;

-- =============================================
-- 12. STORED PROCEDURES (Advanced Operations)
-- =============================================

-- Procedure: Get Dashboard Statistics
DELIMITER //
CREATE PROCEDURE get_dashboard_stats(IN p_user_id INT)
BEGIN
    -- Total Subjects
    SELECT COUNT(*) AS total_subjects 
    FROM subjects 
    WHERE user_id = p_user_id AND deleted_at IS NULL;
    
    -- Task Statistics
    SELECT 
        COUNT(*) AS total_tasks,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM tasks 
    WHERE user_id = p_user_id AND deleted_at IS NULL;
    
    -- Overdue Tasks
    SELECT COUNT(*) AS overdue_tasks
    FROM tasks 
    WHERE user_id = p_user_id 
      AND due_date < CURDATE() 
      AND status != 'Completed' 
      AND deleted_at IS NULL;
    
    -- Recent Activity
    SELECT 
        action,
        details,
        created_at
    FROM activity_logs
    WHERE user_id = p_user_id
    ORDER BY created_at DESC
    LIMIT 10;
    
    -- Subject Progress
    SELECT 
        s.subject_name,
        s.color,
        sp.completion_percentage
    FROM subject_progress sp
    JOIN subjects s ON sp.subject_id = s.id
    WHERE sp.user_id = p_user_id
    ORDER BY sp.completion_percentage DESC;
END//
DELIMITER ;

-- =============================================
-- 13. INDEXES FOR PERFORMANCE
-- =============================================
CREATE INDEX idx_tasks_due_status ON tasks(due_date, status);
CREATE INDEX idx_tasks_priority_status ON tasks(priority, status);
CREATE INDEX idx_activity_user_date ON activity_logs(user_id, created_at);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);

-- =============================================
-- 14. INSERT TEST DATA (Optional)
-- =============================================
-- INSERT INTO users (name, email, password) VALUES 
-- ('John Doe', 'john@example.com', '$2y$10$YourHashedPasswordHere');

-- =============================================
-- 15. DATABASE MAINTENANCE
-- =============================================
-- Optimize tables
OPTIMIZE TABLE users;
OPTIMIZE TABLE subjects;
OPTIMIZE TABLE tasks;
OPTIMIZE TABLE activity_logs;
OPTIMIZE TABLE notifications;

-- =============================================
-- ✅ DATABASE SCHEMA COMPLETE
-- =============================================
-- Version: 2.0.0
-- Date: 2026-09-03
-- Author: StudyHub Pro Team
-- =============================================